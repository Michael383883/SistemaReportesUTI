<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * Prueba todas las rutas de la API registradas en Laravel y reporta
 * cuales responden bien y cuales fallan.
 *
 * INSTALACION:
 *   Copia este archivo a: app/Console/Commands/TestApiRoutes.php
 *
 * USO:
 *   php artisan app:test-routes
 *   php artisan app:test-routes --base=http://localhost:8000
 *   php artisan app:test-routes --email=admin@umss.edu --password=Admin1234!
 *   php artisan app:test-routes --only=resoluciones
 *   php artisan app:test-routes --include-mutating   (tambien prueba POST/PUT/DELETE, con cuidado: puede escribir datos)
 */
class TestApiRoutes extends Command
{
    protected $signature = 'app:test-routes
        {--base= : URL base de la API (ej: http://localhost:8000). Por defecto usa config(app.url)}
        {--email= : Email para hacer login y probar rutas protegidas con auth:sanctum}
        {--password= : Password para el login}
        {--include-mutating : Ademas de GET, probar tambien POST/PUT/DELETE (puede modificar datos reales)}
        {--only= : Filtrar solo rutas cuya URI contenga este texto}';

    protected $description = 'Prueba todas las rutas de la API y reporta donde estan fallando';

    // Valores de prueba para los parametros dinamicos que aparecen en las rutas
    protected array $sampleValues = [
        'id' => 1,
        'docente' => 1,
        'codigo_docente' => 1,
        'cod_docente' => 1,
        'codDocente' => 1,
        'idClasificacionDocente' => 1,
        'tabla' => 'test',
        'codigo' => 1,
        'materia' => 1,
    ];

    protected ?string $token = null;

    public function handle(): int
    {
        $base = rtrim($this->option('base') ?: config('app.url'), '/');
        $includeMutating = (bool) $this->option('include-mutating');
        $filter = $this->option('only');

        $this->info("Base URL: {$base}");
        if ($includeMutating) {
            $this->warn('Modo --include-mutating activo: se enviaran POST/PUT/DELETE reales, revisa que sea un entorno de pruebas.');
        }

        $email = $this->option('email');
        $password = $this->option('password');
        if ($email && $password) {
            $this->tryLogin($base, $email, $password);
        } else {
            $this->comment('No se pasaron --email/--password: las rutas protegidas por auth:sanctum daran 401 (es esperado).');
        }

        $routes = collect(RouteFacade::getRoutes())
            ->filter(fn($r) => Str::startsWith($r->uri(), 'api/'))
            ->when($filter, fn($c) => $c->filter(fn($r) => Str::contains($r->uri(), $filter)))
            ->unique(fn($r) => implode(',', $r->methods()) . '|' . $r->uri());

        $results = [];
        $okCount = 0;
        $failCount = 0;
        $skipCount = 0;

        foreach ($routes as $route) {
            $methods = array_diff($route->methods(), ['HEAD']);

            foreach ($methods as $method) {
                $uri = $route->uri();

                if ($method !== 'GET' && !$includeMutating) {
                    $results[] = [$method, $uri, 'OMITIDA', '-', 'Usa --include-mutating para probarla'];
                    $skipCount++;
                    continue;
                }

                $resolvedUri = $this->resolveParams($uri);
                $url = $base . '/' . ltrim($resolvedUri, '/');
                $requiereAuth = in_array('auth:sanctum', $route->gatherMiddleware());

                try {
                    $http = Http::timeout(10)->acceptJson();
                    if ($requiereAuth && $this->token) {
                        $http = $http->withToken($this->token);
                    }

                    $response = match ($method) {
                        'GET' => $http->get($url),
                        'POST' => $http->post($url, []),
                        'PUT' => $http->put($url, []),
                        'DELETE' => $http->delete($url),
                        default => null,
                    };

                    if (!$response) {
                        $results[] = [$method, $uri, 'OMITIDA', '-', "Metodo {$method} no manejado por el script"];
                        $skipCount++;
                        continue;
                    }

                    $status = $response->status();
                    $ok = $status < 500 && $status !== 404;
                    $ok ? $okCount++ : $failCount++;

                    $detalle = match (true) {
                        $status === 404 => 'No encontrada (revisar controlador o choque de orden de rutas)',
                        $status === 401 => 'Requiere autenticacion valida (pasa --email/--password)',
                        $status === 403 => 'Sin permiso para el rol usado',
                        $status === 422 => 'Validacion fallida (esperable con datos de prueba vacios)',
                        $status >= 500 => Str::limit($response->json('message') ?? $response->body(), 90),
                        default => 'OK',
                    };

                    $results[] = [$method, $uri, $status, $ok ? 'OK' : 'FALLA', $detalle];
                } catch (\Throwable $e) {
                    $failCount++;
                    $results[] = [$method, $uri, 'ERROR', 'FALLA', Str::limit($e->getMessage(), 90)];
                }
            }
        }

        $this->table(['Metodo', 'Ruta', 'Status', 'Resultado', 'Detalle'], $results);

        $this->newLine();
        $this->info("Resumen: {$okCount} OK | {$failCount} con FALLA | {$skipCount} OMITIDAS");

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function tryLogin(string $base, string $email, string $password): void
    {
        try {
            $response = Http::timeout(10)->acceptJson()->post($base . '/api/auth/login', [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $this->token = $response->json('token')
                    ?? $response->json('access_token')
                    ?? $response->json('data.token');

                $this->token
                    ? $this->info('Login OK, token obtenido para probar rutas protegidas.')
                    : $this->warn('Login respondio 2xx pero no se encontro el campo del token (revisa el JSON de AuthController@login).');
            } else {
                $this->warn('No se pudo iniciar sesion (status ' . $response->status() . '). Las rutas protegidas daran 401.');
            }
        } catch (\Throwable $e) {
            $this->warn('Error al intentar login: ' . $e->getMessage());
        }
    }

    protected function resolveParams(string $uri): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function ($m) {
            $name = trim($m[1], '?');
            return $this->sampleValues[$name] ?? 1;
        }, $uri);
    }
}
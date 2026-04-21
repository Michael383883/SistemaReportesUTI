<?php

/**
 * Archivo de prueba de conexión a SQL Server
 * Coloca este archivo en la raíz de tu proyecto Laravel y ejecútalo con:
 *   php test_sqlserver.php
 * O accede desde el navegador si usas:
 *   php artisan serve  →  http://localhost:8000/test_sqlserver.php
 *
 * NOTA: Elimina este archivo después de probar (no dejarlo en producción).
 */

// ──────────────────────────────────────────────
// 1. Prueba con PDO directo (sin Laravel)
// ──────────────────────────────────────────────
echo "========================================\n";
echo " TEST 1: Conexión PDO directa a SQL Server\n";
echo "========================================\n\n";

$host = 'LAPTOP-9RP179SA\\SQLSERVE';   // doble barra en PHP
$port = 1433;
$database = 'prueba';
$username = 'laravel_user';
$password = 'Laravel123!';

$dsn = "sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Conexión PDO exitosa!\n";

    // Verificar versión del servidor
    $stmt = $pdo->query("SELECT @@VERSION AS version");
    $row = $stmt->fetch();
    echo "   Versión SQL Server: " . substr($row['version'], 0, 80) . "...\n";

    // Listar tablas en la base de datos 'prueba'
    $stmt = $pdo->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    ");
    $tables = $stmt->fetchAll();

    if (count($tables) === 0) {
        echo "   ℹ️  La base de datos 'prueba' no tiene tablas todavía.\n";
    } else {
        echo "   Tablas encontradas (" . count($tables) . "):\n";
        foreach ($tables as $t) {
            echo "     - " . $t['TABLE_NAME'] . "\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ Error PDO: " . $e->getMessage() . "\n";
    echo "\n   Posibles causas:\n";
    echo "   • El driver 'sqlsrv' no está instalado (php_sqlsrv.dll / pdo_sqlsrv.dll)\n";
    echo "   • El servicio SQL Server no está corriendo\n";
    echo "   • El usuario 'laravel_user' no existe o la contraseña es incorrecta\n";
    echo "   • El nombre de instancia '\\SQLSERVE' no está bien escrito\n";
}

echo "\n";

// ──────────────────────────────────────────────
// 2. Prueba con la conexión de Laravel (DB facade)
//    Solo funciona si ejecutas desde el proyecto
// ──────────────────────────────────────────────
echo "========================================\n";
echo " TEST 2: Conexión via Laravel DB facade\n";
echo "========================================\n\n";

// Detectar si estamos dentro de un proyecto Laravel
$laravelBootstrap = __DIR__ . '/vendor/autoload.php';

if (!file_exists($laravelBootstrap)) {
    echo "⚠️  No se encontró vendor/autoload.php.\n";
    echo "   Ejecuta este test desde la raíz del proyecto Laravel.\n\n";
} else {
    require $laravelBootstrap;

    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    try {
        // Usar la conexión secundaria 'sqlsrv' definida en database.php
        $result = \Illuminate\Support\Facades\DB::connection('sqlsrv')
            ->select('SELECT @@VERSION AS version');

        echo "✅ Conexión Laravel (sqlsrv) exitosa!\n";
        echo "   Versión: " . substr($result[0]->version, 0, 80) . "...\n";

    } catch (\Exception $e) {
        echo "❌ Error Laravel DB: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ──────────────────────────────────────────────
// 3. Verificar drivers instalados
// ──────────────────────────────────────────────
echo "========================================\n";
echo " TEST 3: Drivers PDO disponibles\n";
echo "========================================\n\n";

$drivers = PDO::getAvailableDrivers();
echo "Drivers instalados: " . implode(', ', $drivers) . "\n\n";

if (in_array('sqlsrv', $drivers)) {
    echo "✅ Driver 'sqlsrv' está disponible.\n";
} else {
    echo "❌ Driver 'sqlsrv' NO está instalado.\n";
    echo "   Instálalo con:\n";
    echo "   Windows: Habilita php_sqlsrv.dll y php_pdo_sqlsrv.dll en php.ini\n";
    echo "   Linux:   pecl install sqlsrv pdo_sqlsrv\n";
}

echo "\n========================================\n";
echo " FIN DE PRUEBAS\n";
echo "========================================\n";
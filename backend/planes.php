<?php

/**
 * migrar_planes_materia.php
 * Uso: php migrar_planes_materia.php
 *
 * Migra las tablas PLANES y MATERIA desde SQL Server 2008 (origen)
 * hacia SQL Server 2022 (destino) usando PDO sqlsrv.
 *
 * ORDEN: primero PLANES (padre), luego MATERIA (hija),
 * para respetar las FK si existen.
 */

// ─────────────────────────────────────────────────────────────
// CONFIGURACIÓN
// ─────────────────────────────────────────────────────────────

// Origen: SQL Server 2008
$origen = [
    'host' => '127.0.0.1',
    'port' => '1433',
    'database' => 'prueba',
    'username' => 'laravel_user',
    'password' => 'Laravel123!',
];

// Destino: SQL Server 2022
$destino = [
    'host' => '127.0.0.1',
    'port' => '1434',
    'database' => 'prueba',
    'username' => 'laravel_dest',
    'password' => '1234',
];

// ─────────────────────────────────────────────────────────────
// CONEXIONES
// ─────────────────────────────────────────────────────────────

function conectar(array $cfg, string $nombre): PDO
{
    $dsn = "sqlsrv:Server={$cfg['host']},{$cfg['port']};Database={$cfg['database']};TrustServerCertificate=1";
    try {
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ]);
        echo "[OK] Conectado a $nombre ({$cfg['host']}:{$cfg['port']} / {$cfg['database']})\n";
        return $pdo;
    } catch (PDOException $e) {
        echo "[ERROR] No se pudo conectar a $nombre: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// ─────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────

function limpiarFecha($fecha): ?string
{
    if (is_null($fecha))
        return null;

    if ($fecha instanceof DateTime) {
        $str = $fecha->format('Y-m-d H:i:s');
    } else {
        $str = trim((string) $fecha);
    }

    if ($str === '' || $str === '?')
        return null;

    // Descartar fecha basura 1900-01-01
    if (str_starts_with($str, '1900-01-01'))
        return null;

    // Validar rango DATETIME de SQL Server: 1753 - 9999
    $year = (int) substr($str, 0, 4);
    if ($year < 1753 || $year > 9999)
        return null;

    return substr($str, 0, 19); // "YYYY-MM-DD HH:MM:SS"
}

function limpiarString($valor, string $default = ''): string
{
    if (is_null($valor))
        return $default;
    $str = trim((string) $valor);
    if ($str === '' || $str === '?')
        return $default;
    return $str;
}

function limpiarInt($valor): ?int
{
    if (is_null($valor))
        return null;
    $str = trim((string) $valor);
    if ($str === '' || $str === '?')
        return null;
    return (int) $str;
}

function limpiarDecimal($valor): ?float
{
    if (is_null($valor))
        return null;
    $str = trim((string) $valor);
    if ($str === '' || $str === '?')
        return null;
    return (float) $str;
}

// ─────────────────────────────────────────────────────────────
// FUNCIÓN GENÉRICA DE MIGRACIÓN
// Detecta columnas automáticamente desde el origen
// ─────────────────────────────────────────────────────────────

function migrarTabla(
    PDO $pdoOrigen,
    PDO $pdoDestino,
    string $tabla,
    string $colClave,          // columna PK para chequear duplicados
    array $colsFecha = [],   // columnas que necesitan CONVERT(DATETIME,?,120)
    array $colsOmitir = []    // columnas a no insertar (ej. identidades auto)
): void {
    echo "\n" . str_repeat('─', 55) . "\n";
    echo "  MIGRANDO: dbo.$tabla\n";
    echo str_repeat('─', 55) . "\n";

    // ── Leer filas del origen ──────────────────────────────
    $filas = $pdoOrigen->query("SELECT * FROM dbo.$tabla")->fetchAll();
    $total = count($filas);
    echo "[INFO] Registros en origen: $total\n\n";

    if ($total === 0) {
        echo "[INFO] Tabla vacía, nada que migrar.\n";
        return;
    }

    // ── Detectar columnas disponibles en el primer registro ─
    $primeraFila = (array) $filas[0];
    $todasCols = array_keys($primeraFila);
    $colsUsar = array_diff($todasCols, $colsOmitir);

    // Construir lista de columnas para el INSERT
    $colsSql = implode(', ', array_map(fn($c) => "[$c]", $colsUsar));

    // Construir placeholders: columnas fecha usan CONVERT, el resto ?
    $placeholders = [];
    foreach ($colsUsar as $col) {
        $placeholders[] = in_array($col, $colsFecha)
            ? "CONVERT(DATETIME, ?, 120)"
            : "?";
    }
    $placeholdersSql = implode(', ', $placeholders);

    $sqlInsert = "INSERT INTO dbo.$tabla ($colsSql) VALUES ($placeholdersSql)";
    $sqlExiste = "SELECT TOP 1 1 FROM dbo.$tabla WHERE [$colClave] = ?";

    $stmtExiste = $pdoDestino->prepare($sqlExiste);
    $stmtInsert = $pdoDestino->prepare($sqlInsert);

    $insertados = 0;
    $omitidos = 0;
    $errores = [];

    foreach ($filas as $fila) {
        $filaArr = (array) $fila;
        $valClave = $filaArr[$colClave];

        // ── Verificar duplicado ────────────────────────────
        $stmtExiste->execute([$valClave]);
        if ($stmtExiste->fetchColumn()) {
            $omitidos++;
            continue;
        }

        // ── Armar parámetros en el mismo orden que $colsUsar
        $params = [];
        foreach ($colsUsar as $col) {
            $raw = $filaArr[$col] ?? null;

            if (in_array($col, $colsFecha)) {
                $params[] = limpiarFecha($raw);
            } else {
                // Dejar null como null; strings los limpiamos suavemente
                $params[] = is_null($raw) ? null : $raw;
            }
        }

        try {
            $stmtInsert->execute($params);
            $insertados++;

            if ($insertados % 100 === 0) {
                echo "[PROGRESO] $tabla — Insertados: $insertados\n";
            }
        } catch (PDOException $e) {
            $errores[] = [
                'clave' => $valClave,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ── Resumen por tabla ──────────────────────────────────
    echo "\n  RESULTADO $tabla:\n";
    echo "  Total origen : $total\n";
    echo "  Insertados   : $insertados\n";
    echo "  Omitidos     : $omitidos\n";
    echo "  Errores      : " . count($errores) . "\n";

    if (!empty($errores)) {
        echo "\n  --- DETALLE DE ERRORES ---\n";
        foreach ($errores as $err) {
            echo "  Clave {$err['clave']}: {$err['error']}\n";
        }
    }
}

// ─────────────────────────────────────────────────────────────
// MAIN
// ─────────────────────────────────────────────────────────────

echo "\n=== MIGRACIÓN: PLANES y MATERIA ===\n\n";

$pdoOrigen = conectar($origen, 'ORIGEN  (SQL Server 2008)');
$pdoDestino = conectar($destino, 'DESTINO (SQL Server 2022)');

// ══════════════════════════════════════════════════════════════
// 1. TABLA: PLANES
//    Ajusta 'CODIGO' si tu PK tiene otro nombre.
//    Agrega a $colsFecha cualquier columna de tipo fecha/datetime.
//    Agrega a $colsOmitir columnas IDENTITY que el destino genera solo.
// ══════════════════════════════════════════════════════════════
migrarTabla(
    pdoOrigen: $pdoOrigen,
    pdoDestino: $pdoDestino,
    tabla: 'PLANES',
    colClave: 'CODIGO',          // ← PK de PLANES
    colsFecha: [                  // ← columnas datetime en PLANES
        'FECHA_INICIO',
        'FECHA_FIN',
        'FECHA_CREACION',
    ],
    colsOmitir: []                 // ← ej. ['ID'] si es IDENTITY auto
);

// ══════════════════════════════════════════════════════════════
// 2. TABLA: MATERIA
//    Ajusta igual que arriba según tu esquema real.
// ══════════════════════════════════════════════════════════════
migrarTabla(
    pdoOrigen: $pdoOrigen,
    pdoDestino: $pdoDestino,
    tabla: 'MATERIA',
    colClave: 'CODIGO',          // ← PK de MATERIA
    colsFecha: [                  // ← columnas datetime en MATERIA
        'FECHA_CREACION',
    ],
    colsOmitir: []
);

echo "\n\n[DONE] Migración de PLANES y MATERIA completada.\n";
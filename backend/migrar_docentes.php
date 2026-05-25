<?php

/**
 * migrar_docentes.php
 * Uso: php migrar_docentes.php
 *
 * Migra la tabla DOCENTES desde SQL Server 2008 (origen)
 * hacia SQL Server 2022 (destino) usando PDO sqlsrv.
 */

// ─────────────────────────────────────────────────────────────
// CONFIGURACIÓN — igual que tu .env
// ─────────────────────────────────────────────────────────────

// Origen: SQL Server 2008
$origen = [
    'host' => '127.0.0.1',
    'port' => '1433',
    'database' => 'prueba',         // DB_DATABASE_SQLSRV
    'username' => 'laravel_user',   // DB_USERNAME_SQLSRV
    'password' => 'Laravel123!',    // DB_PASSWORD_SQLSRV
];

// Destino: SQL Server 2022
$destino = [
    'host' => '127.0.0.1',
    'port' => '1434',
    'database' => 'prueba',         // DB_DATABASE
    'username' => 'laravel_dest',   // DB_USERNAME
    'password' => '1234',           // DB_PASSWORD
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

    // Objeto DateTime que devuelve el driver sqlsrv
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

function limpiarString($valor, string $default): string
{
    if (is_null($valor))
        return $default;
    $str = trim((string) $valor);
    if ($str === '' || $str === '?')
        return $default;
    return $str;
}

// ─────────────────────────────────────────────────────────────
// MIGRACIÓN
// ─────────────────────────────────────────────────────────────

echo "\n=== MIGRACIÓN DOCENTES ===\n\n";

$pdoOrigen = conectar($origen, 'ORIGEN  (SQL Server 2008)');
$pdoDestino = conectar($destino, 'DESTINO (SQL Server 2022)');

echo "\n";

// Leer todos los docentes del origen
$filas = $pdoOrigen->query("SELECT * FROM dbo.DOCENTES")->fetchAll();
$total = count($filas);
echo "[INFO] Registros encontrados en origen: $total\n\n";

// Preparar insert en destino
$sql = "
    INSERT INTO dbo.DOCENTES
        (CODIGO, CI, NOMBRES, APELLIDOS, FECHA_NAC, SEXO, TITULO, FECHA_NOMBRAMIENTO)
    VALUES
        (?, ?, ?, ?, CONVERT(DATETIME, ?, 120), ?, ?, CONVERT(DATETIME, ?, 120))
";
// CONVERT(..., 120) = formato ISO 'YYYY-MM-DD HH:MM:SS' — evita error nvarchar→datetime

$stmtExiste = $pdoDestino->prepare(
    "SELECT TOP 1 1 FROM dbo.DOCENTES WHERE CODIGO = ?"
);
$stmtInsert = $pdoDestino->prepare($sql);

$insertados = 0;
$omitidos = 0;
$errores = [];

foreach ($filas as $row) {
    $codigo = (int) $row->CODIGO;

    // Verificar si ya existe
    $stmtExiste->execute([$codigo]);
    if ($stmtExiste->fetchColumn()) {
        $omitidos++;
        continue;
    }

    $fechaNac = limpiarFecha($row->FECHA_NAC);
    $fechaNomb = limpiarFecha($row->FECHA_NOMBRAMIENTO);
    $ci = limpiarString($row->CI, 'SIN_CI');
    $sexo = limpiarString($row->SEXO, 'X');

    try {
        $stmtInsert->execute([
            $codigo,
            $ci,
            $row->NOMBRES,
            $row->APELLIDOS,
            $fechaNac,      // puede ser null → CONVERT(DATETIME, NULL, 120) = NULL ✓
            $sexo,
            $row->TITULO,
            $fechaNomb,
        ]);
        $insertados++;

        // Mostrar progreso cada 100 registros
        if ($insertados % 100 === 0) {
            echo "[PROGRESO] Insertados: $insertados\n";
        }

    } catch (PDOException $e) {
        $errores[] = [
            'CODIGO' => $row->CODIGO,
            'error' => $e->getMessage(),
        ];
    }
}

// ─────────────────────────────────────────────────────────────
// RESUMEN
// ─────────────────────────────────────────────────────────────

echo "\n=== RESULTADO ===\n";
echo "Total origen : $total\n";
echo "Insertados   : $insertados\n";
echo "Omitidos     : $omitidos\n";
echo "Errores      : " . count($errores) . "\n";

if (!empty($errores)) {
    echo "\n--- DETALLE DE ERRORES ---\n";
    foreach ($errores as $err) {
        echo "  CODIGO {$err['CODIGO']}: {$err['error']}\n";
    }
}

echo "\n[DONE]\n";
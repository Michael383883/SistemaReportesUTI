<?php
// ╔══════════════════════════════════════════════════════════════╗
// ║  INSTRUCCIÓN: Este archivo REEMPLAZA config/database.php     ║
// ║  Solo añade la conexión 'sqlsrv' al array 'connections'.     ║
// ║  El resto del archivo queda igual que el original de Laravel ║
// ╚══════════════════════════════════════════════════════════════╝

// Agrega este bloque dentro de 'connections' en tu database.php:

/*
'sqlsrv_docentes' => [
    'driver'   => 'sqlsrv',
    'host'     => env('DB_SQLSRV_HOST', 'localhost'),
    'port'     => env('DB_SQLSRV_PORT', '1433'),
    'database' => env('DB_SQLSRV_DATABASE', ''),
    'username' => env('DB_SQLSRV_USERNAME', ''),
    'password' => env('DB_SQLSRV_PASSWORD', ''),
    'charset'  => 'utf8',
    'prefix'   => '',
],
*/

// NOTA: Para HU5, los modelos que lean de SQL Server usan:
//   protected $connection = 'sqlsrv_docentes';

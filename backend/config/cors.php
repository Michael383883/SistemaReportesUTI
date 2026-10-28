<?php

// ╔══════════════════════════════════════════════════════════════╗
// ║  INSTRUCCIÓN: Reemplaza config/cors.php con esto             ║
// ╚══════════════════════════════════════════════════════════════╝

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:5173',

        'https://sia-uti.fce.umss.edu.bo',
        'https://scia.fce.umss.edu.bo',
        'https://scia-test.fce.umss.edu.bo',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,   // ← obligatorio para Sanctum SPA
];

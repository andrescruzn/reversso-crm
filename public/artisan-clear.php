<?php
// SEGURIDAD: clave en query string
if (($_GET['key'] ?? '') !== 'reversso2026') {
    http_response_code(403);
    die('Acceso denegado.');
}

$root = dirname(__DIR__);
$commands = [
    'view:clear',
    'cache:clear',
    'config:clear',
    'route:clear',
    'event:clear',
];

echo '<pre style="font-family:monospace;font-size:13px;padding:20px;">';
echo "=== REVERSSO CRM — Clear Cache ===\n\n";

foreach ($commands as $cmd) {
    $output = shell_exec("cd {$root} && php artisan {$cmd} 2>&1");
    echo "▶ php artisan {$cmd}\n";
    echo trim($output) . "\n\n";
}

// Intentar limpiar OPcache si está disponible
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "▶ OPcache reseteado ✓\n\n";
}

echo "=== Listo. BORRA ESTE ARCHIVO AHORA. ===\n";
echo '</pre>';

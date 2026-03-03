<?php
if (($_GET['key'] ?? '') !== 'reversso2026') {
    http_response_code(403);
    die('Acceso denegado.');
}

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '<pre style="font-family:monospace;font-size:13px;padding:20px;background:#111;color:#0f0;">';
echo "=== REVERSSO CRM — Clear Cache ===\n\n";

$commands = ['view:clear', 'cache:clear', 'config:clear', 'route:clear', 'event:clear'];

foreach ($commands as $cmd) {
    Illuminate\Support\Facades\Artisan::call($cmd);
    $output = trim(Illuminate\Support\Facades\Artisan::output());
    echo "▶ php artisan {$cmd}\n";
    echo ($output ?: 'OK') . "\n\n";
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "▶ OPcache reseteado ✓\n\n";
} else {
    echo "▶ OPcache: no disponible\n\n";
}

echo "=== Listo. BORRA ESTE ARCHIVO. ===\n";
echo '</pre>';

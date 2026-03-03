<?php
if (($_GET['key'] ?? '') !== 'reversso2026') {
    http_response_code(403);
    die('Acceso denegado.');
}

echo '<pre style="font-family:monospace;font-size:13px;padding:20px;background:#111;color:#0f0;">';
echo "=== REVERSSO CRM — Clear Cache ===\n\n";

$base = dirname(__DIR__);

// 1. Vistas compiladas de Blade
$viewsPath = $base . '/storage/framework/views';
$deleted = 0;
foreach (glob($viewsPath . '/*.php') ?: [] as $file) {
    if (unlink($file)) $deleted++;
}
echo "▶ Vistas Blade borradas: {$deleted} archivos\n\n";

// 2. Cache de la aplicación (archivo o carpeta data/)
$cachePath = $base . '/storage/framework/cache/data';
$cacheDeleted = 0;
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($cachePath, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($it as $file) {
    if ($file->isFile() && $file->getFilename() !== '.gitignore') {
        if (unlink($file->getPathname())) $cacheDeleted++;
    }
}
echo "▶ Cache app borrado: {$cacheDeleted} archivos\n\n";

// 3. OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "▶ OPcache reseteado ✓\n\n";
} else {
    echo "▶ OPcache: no disponible en este servidor\n\n";
}

echo "=== Listo. BORRA ESTE ARCHIVO. ===\n";
echo '</pre>';

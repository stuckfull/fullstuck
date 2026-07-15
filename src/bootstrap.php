<?php
$fst_config = fst_app('config');

// Detect web server misconfiguration (Routing Leakage)
if (php_sapi_name() !== 'cli' && strpos($_SERVER['REQUEST_URI'], 'fullstuck.php') !== false) {
    http_response_code(500);
    die('
        <div style="font-family: system-ui, -apple-system, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #24324f; border-radius: 8px; background: #172033; color: #f8fafc;">
            <h2 style="color: #ef4444; margin-top: 0;">🚨 Routing Misconfigured!</h2>
            <p style="color: #94a3b8;">The framework detected <code>fullstuck.php</code> in the URL. This indicates that URL Rewriting is not active on your web server.</p>
            <p style="color: #94a3b8;"><strong>Solution:</strong> Ensure you are using a web server that supports single-entry routing (Apache with .htaccess, Nginx, or FrankenPHP). Please read the Deployment section of the FullStuck documentation.</p>
        </div>
    ');
}


// 2. FST Procedural Auto-Require (Models, Helpers, etc.)
$require_items = $fst_config['require'] ?? ['globals'];
foreach ($require_items as $item) {
    $raw_path = FST_ROOT_DIR . '/' . ltrim($item, '/');
    $real_path = realpath($raw_path);
    
    if ($real_path && is_dir($real_path)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($real_path));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require_once $file->getPathname();
            }
        }
    } else {
        if (is_dir($raw_path)) {
            $raw_path = rtrim($raw_path, '/\\') . DIRECTORY_SEPARATOR . '*.php';
        }
        $matched_files = glob($raw_path);
        if ($matched_files) {
            foreach ($matched_files as $file) {
                $rp = realpath($file);
                if ($rp && str_starts_with($rp, realpath(FST_ROOT_DIR)) && is_file($rp) && str_ends_with($rp, '.php')) {
                    require_once $rp;
                }
            }
        }
    }
}

// 4. Execute Framework (If not CLI)
if (php_sapi_name() !== 'cli') {
    fst_run();
}

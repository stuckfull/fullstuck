<?php
$fst_config = fst_app('config');

// Deteksi miskonfigurasi web server (Routing Leakage)
if (php_sapi_name() !== 'cli' && strpos($_SERVER['REQUEST_URI'], 'fullstuck.php') !== false) {
    http_response_code(500);
    die('
        <div style="font-family: system-ui, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ff4444; border-radius: 8px; background: #fff1f1; color: #333;">
            <h2 style="color: #d32f2f; margin-top: 0;">🚨 Routing Misconfigured!</h2>
            <p>Framework mendeteksi <code>fullstuck.php</code> di dalam URL. Ini menandakan URL Rewriting di web server Anda belum aktif.</p>
            <p><strong>Solusi:</strong> Pastikan Anda menggunakan web server yang mendukung single-entry routing (Apache dengan .htaccess, Nginx, atau FrankenPHP). Silakan baca dokumentasi FullStuck bagian Deployment.</p>
        </div>
    ');
}


// 2. FST Procedural Auto-Require (Models, Helpers, dsb)
$require_items = $fst_config['routing']['require'] ?? [];
foreach ($require_items as $item) {
    $raw_path = FST_ROOT_DIR . '/' . ltrim($item, '/');
    
    // Jika path berupa direktori, otomatis ubah menjadi pencarian *.php
    if (is_dir($raw_path)) {
        $raw_path = rtrim($raw_path, '/\\') . DIRECTORY_SEPARATOR . '*.php';
    }
    
    // Glob mengeksekusi path biasa atau wildcard
    $matched_files = glob($raw_path);
    if ($matched_files) {
        foreach ($matched_files as $file) {
            $real_path = realpath($file);
            // SATPAM: Pastikan file berada di dalam Root Project, sebuah file, dan berekstensi .php
            if ($real_path && str_starts_with($real_path, realpath(FST_ROOT_DIR)) && is_file($real_path) && str_ends_with($real_path, '.php')) {
                require_once $real_path;
            }
        }
    }
}

// 3. Load Route Files
$routes_files = (array) ($fst_config['routing']['routes_file'] ?? ['router.php']);
foreach ($routes_files as $file) {
    if (file_exists(FST_ROOT_DIR . '/' . $file)) {
        require FST_ROOT_DIR . '/' . $file;
    } elseif (!fst_is_dev()) {
        fst_abort(500, "Configuration Error: Routes file not found at '{$file}'");
    }
}

// 4. Eksekusi Framework (Jika bukan CLI)
if (php_sapi_name() !== 'cli') {
    fst_run();
}

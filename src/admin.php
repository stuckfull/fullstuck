<?php
if (fst_is_dev()) {
    $fst_config = fst_app('config');
    $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';

    fst_get($admin_base . '/login', 'fst_admin_show_login');
    fst_post($admin_base . '/login', 'fst_admin_do_login');
    fst_get($admin_base . '/logout', 'fst_admin_do_logout');

    fst_get($admin_base, 'fst_admin_show_monitor');

    fst_get($admin_base . '/config', 'fst_admin_show_config');
    fst_post($admin_base . '/config/save', 'fst_admin_save_config');
    fst_post($admin_base . '/config/hash', function() use ($admin_base) { fst_admin_check_auth(); fst_flash_set('success_message', 'Hash: ' . password_hash($_POST['new_pass'], PASSWORD_DEFAULT)); fst_redirect($admin_base.'/config'); });

    fst_get($admin_base . '/routes', 'fst_admin_show_routes');
    
    fst_get($admin_base . '/server-info', 'fst_admin_show_server_info');

    fst_get($admin_base . '/scan', 'fst_admin_show_scan_page');
    fst_post($admin_base . '/scan/run', 'fst_admin_run_scan');

    fst_get($admin_base . '/integrity', 'fst_admin_show_integrity');
    fst_get($admin_base . '/plugins', 'fst_admin_show_plugins');
    fst_post($admin_base . '/plugins/install', 'fst_admin_install_plugin');
    fst_post($admin_base . '/plugins/toggle', 'fst_admin_toggle_plugin');
    fst_post($admin_base . '/plugins/uninstall', 'fst_admin_uninstall_plugin');

    // --- FITUR UPDATE OTA ---
    fst_get($admin_base . '/update', function() use ($admin_base) {
        fst_admin_check_auth();
        $current_version = defined('FST_VERSION') ? FST_VERSION : 'Unknown';
        $json_url = 'https://raw.githubusercontent.com/milio48/fullstuck/refs/heads/main/version.json';
        
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $remote_data = @file_get_contents($json_url, false, $ctx);
        $remote_version = 'Unknown';
        $can_update = false;

        if ($remote_data) {
            $json = json_decode($remote_data, true);
            if (isset($json['version'])) {
                $remote_version = $json['version'];
                // Hanya tawarkan update jika versi remote LEBIH BESAR dari versi lokal
                if ($current_version !== 'Unknown') {
                    $can_update = version_compare($remote_version, $current_version, '>');
                }
            }
        }

        $html = "<h2>System Update</h2>";
        $html .= "<p>Current Version: <strong>{$current_version}</strong></p>";
        $html .= "<p>Latest Version: <strong>{$remote_version}</strong></p>";

        if ($can_update) {
            $html .= '<div style="background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; margin-bottom: 20px;">
                        <strong>🚀 Update Tersedia!</strong> Versi baru siap diunduh.
                      </div>';
            $html .= '<form method="POST" action="' . $admin_base . '/update/run">
                        ' . fst_csrf_field() . '
                        <button type="submit" onclick="return confirm(\'Proses ini akan mengunduh core terbaru dan membackup file lama Anda. Lanjutkan?\');" style="background:#007bff; color:white; padding:10px 15px; border:none; cursor:pointer;">Update Core Sekarang</button>
                      </form>';
        } else {
            $html .= "<p style='color: green;'>Sistem Anda sudah up-to-date.</p>";
        }

        fst_admin_render_page('Update System', $html);
    });

    fst_post($admin_base . '/update/run', function() use ($admin_base) {
        fst_admin_check_auth();
        fst_csrf_check();

        $core_url = 'https://raw.githubusercontent.com/milio48/fullstuck/refs/heads/main/fullstuck.php';
        $ctx = stream_context_create(['http' => ['timeout' => 15]]);
        $new_core = @file_get_contents($core_url, false, $ctx);

        // Validasi ketat: pastikan file yang diunduh benar-benar script PHP
        if (!$new_core || strpos($new_core, '<?php') !== 0) {
            fst_flash_set('error_message', 'Gagal mengunduh update atau file korup.');
            fst_redirect($admin_base . '/update');
        }

        $target_file = FST_ROOT_DIR . '/fullstuck.php';
        $backup_file = FST_ROOT_DIR . '/fullstuck.bak.php';

        // Buat backup
        if (file_exists($target_file)) {
            copy($target_file, $backup_file);
        }

        // Timpa file core
        if (file_put_contents($target_file, $new_core) !== false) {
            fst_flash_set('success_message', 'Sistem berhasil diupdate! File lama disimpan sebagai fullstuck.bak.php');
        } else {
            fst_flash_set('error_message', 'Gagal menulis file. Periksa permission server.');
        }

        fst_redirect($admin_base . '/update');
    });

    fst_any($admin_base . '/p/{id}', function($id) use ($admin_base) {
        fst_admin_check_auth();
        $plugins = fst_app('plugins') ?? [];
        
        if (!isset($plugins[$id]) || !is_callable($plugins[$id]['admin_route'] ?? null)) {
            fst_abort(404, "Plugin '{$id}' tidak ditemukan atau tidak memiliki antarmuka admin.");
        }

        // Tangkap output plugin agar bisa dimasukkan ke layout admin
        ob_start();
        call_user_func($plugins[$id]['admin_route']);
        $plugin_content = ob_get_clean();

        $page_title = $plugins[$id]['menu_label'] ?? $plugins[$id]['name'] ?? 'Plugin';
        fst_admin_render_page($page_title, $plugin_content);
    });
}


if (fst_is_dev()) {

    function fst_admin_check_auth() {
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';
        
        // 1. Blokir /stuck di production
        if (!fst_is_dev() && $admin_base === '/stuck') {
            fst_abort(403, "SECURITY ALERT: You are in 'production' environment. You MUST change the default admin URL in fullstuck.json.");
        }

        // 2. IP Whitelist Check
        $allowed_ips = $fst_config['admin']['allowed_ips'] ?? [];
        if (!empty($allowed_ips) && !in_array($_SERVER['REMOTE_ADDR'], $allowed_ips, true)) {
            fst_abort(403, "Forbidden: Your IP (" . htmlspecialchars($_SERVER['REMOTE_ADDR']) . ") is not allowed to access the admin area.");
        }

        // 3. Login Check
        if (empty($_SESSION['fst_admin_logged_in'])) {
            fst_flash_set('error_message', 'Please login to access the admin area.');
            fst_redirect($admin_base . '/login');
        }
    }

    function fst_admin_show_login() {
        header('Content-Type: text/html; charset=UTF-8');
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';
        $error = fst_flash_get('error_message');
        $error_html = $error ? "<p style='color:red;'>{$error}</p>" : '';
        $csrf = fst_csrf_field();

        $html = <<<HTML
<!DOCTYPE html><html lang="en"><head><title>Admin Login</title><style>/* CSS Sederhana */ body{font-family:sans-serif; max-width:400px; margin:50px auto; padding:20px; border:1px solid #ccc;} input{width:100%; padding:8px; margin-bottom:10px;} button{padding:10px 15px;}</style></head>
<body><h1>Admin Login</h1>{$error_html}
<form method="POST" action="{$admin_base}/login" data-fst-no-spa>{$csrf}
<label for="password">Password:</label><input type="password" name="password" id="password" required><button type="submit">Login</button></form></body></html>
HTML;
        echo $html;
    }

    function fst_admin_do_login() {
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';
        fst_csrf_check();

        $password = $_POST['password'] ?? '';
        $hashed_password = $fst_config['admin']['password'] ?? '';

        if (password_verify($password, $hashed_password)) {
            // Mencegah Session Fixation
            session_regenerate_id(true);
            $_SESSION['fst_admin_logged_in'] = true;
            fst_redirect($admin_base);
        } else {
            fst_flash_set('error_message', 'Invalid password.');
            fst_redirect($admin_base . '/login');
        }
    }

    function fst_admin_do_logout() {
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';
        unset($_SESSION['fst_admin_logged_in']);
        fst_flash_set('success_message', 'You have been logged out.');
        fst_redirect($admin_base . '/login');
    }
    
    function fst_admin_render_page($title, $content) {
         header('Content-Type: text/html; charset=UTF-8');
         $fst_config = fst_app('config');
         $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';
         $success_msg = fst_flash_get('success_message');
         $error_msg = fst_flash_get('error_message');
         $info_html = '';
         if ($success_msg) $info_html .= "<p style='color:green;'>" . htmlspecialchars($success_msg) . "</p>";
         if ($error_msg) $info_html .= "<p style='color:red;'>" . htmlspecialchars($error_msg) . "</p>";
         
         $plugins = fst_app('plugins') ?? [];
         $plugin_links = '';
         foreach ($plugins as $p_id => $p_conf) {
             $p_label = htmlspecialchars($p_conf['menu_label'] ?? $p_conf['name'] ?? $p_id);
             $plugin_links .= "<a href=\"{$admin_base}/p/{$p_id}\" data-fst-no-spa>🔌 {$p_label}</a>\n    ";
         }
         
         $html = <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>{$title} - Admin</title>
<style>
    body { font-family: sans-serif; margin: 0; }
    .container { max-width: 900px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
    nav { background: #333; padding: 10px; margin-bottom: 20px; }
    nav a { color: white; margin-right: 15px; text-decoration: none; }
    nav a:hover { text-decoration: underline; }
    h1, h2 { border-bottom: 1px solid #eee; padding-bottom: 5px; }
    pre { background: #f4f4f4; padding: 10px; border: 1px solid #ccc; overflow-x: auto; }
    textarea { width: 100%; min-height: 400px; box-sizing: border-box; font-family: monospace; }
    button { padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px;}
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left;}
    th { background-color: #f2f2f2;}

    /* === GAYA PERINGATAN BARU === */
    .alert-warning {
        background-color: #fffbe6;
        border: 1px solid #ffe58f;
        border-left-width: 5px;
        border-left-color: #ffa940;
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        color: #ad8601;
        font-family: monospace;
        font-size: 1.1em;
    }
    .alert-warning strong {
        color: #d46b08;
    }
</style>
</head><body>
<nav>
    <a href="{$admin_base}" data-fst-no-spa>Monitor</a>
    <a href="{$admin_base}/config" data-fst-no-spa>Config Editor</a>
    <a href="{$admin_base}/routes" data-fst-no-spa>Route List</a>
    <a href="{$admin_base}/server-info" data-fst-no-spa>Server Info</a>
    <a href="{$admin_base}/scan" data-fst-no-spa>Scan Project</a>
    <a href="{$admin_base}/integrity" data-fst-no-spa>Integrity</a>
    <a href="{$admin_base}/plugins" data-fst-no-spa>Plugins</a>
    <a href="{$admin_base}/update" data-fst-no-spa>Update</a>
    {$plugin_links}<a href="{$admin_base}/logout" style="float:right;" data-fst-no-spa>Logout</a>
</nav>
<div class="container">
    <h1>{$title}</h1>
    {$info_html}
    {$content}
</div>
</body></html>
HTML;
         echo $html;
    }

    function fst_admin_get_remote_info() {
        $cache_key = 'fst_remote_version_cache';
        $cache_time = 3600; // 1 jam

        if (isset($_SESSION[$cache_key]) && (time() - $_SESSION[$cache_key]['time'] < $cache_time)) {
            return $_SESSION[$cache_key]['data'];
        }

        $remote_url = "https://raw.githubusercontent.com/milio48/fullstuck/main/version.json";
        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $remote_json = @file_get_contents($remote_url, false, $ctx);
        
        if ($remote_json) {
            $remote_data = json_decode($remote_json, true);
            if ($remote_data) {
                $_SESSION[$cache_key] = [
                    'time' => time(),
                    'data' => $remote_data
                ];
                return $remote_data;
            }
        }
        return false;
    }

    function fst_admin_show_monitor() {
        fst_admin_check_auth();
        $fst_config = fst_app('config');

        $update_banner = '';
        $remote_data = fst_admin_get_remote_info();
        if ($remote_data && isset($remote_data['version'])) {
            if (version_compare(FST_VERSION, $remote_data['version'], '<')) {
                $update_banner = '<div style="background: #e6f7ff; border: 1px solid #91d5ff; padding: 15px; margin-bottom: 20px; border-radius: 4px; color: #0050b3;">';
                $update_banner .= '<strong>🚀 New Update Available!</strong> v' . htmlspecialchars($remote_data['version']) . ' is now available. ';
                $update_banner .= '<a href="https://github.com/milio48/fullstuck/releases" target="_blank" style="color: #1890ff; font-weight: bold;">View Releases</a>';
                if (isset($remote_data['hash']) && fst_check_integrity()['declared'] !== $remote_data['hash']) {
                    $update_banner .= '<br><small style="color: #666;">Note: Your core file hash does not match the latest release.</small>';
                }
                $update_banner .= '</div>';
            }
        }
        
        $dev_warning_html = ''; // Variabel baru untuk warning khusus
        $warnings = [];
        $errors = [];

        // Ambil environment saat ini, default ke 'production' jika tidak ada
        $current_env = $fst_config['environment'] ?? 'production';

        if ($current_env === 'development') {
            // Kasus 1: Ini 'development'. Tampilkan warning besar.
            $dev_warning_html = '<div class="alert-warning"><strong>WARNING:</strong> Environment is set to \'development\'. Make sure to change it to \'production\' before going live!</div>';
        } elseif ($current_env !== 'production') {
            // Kasus 2: Ini BUKAN 'development' dan BUKAN 'production'
            // (misal: 'staging', 'testing', dll). Tampilkan warning biasa.
            $warnings[] = "Environment is set to '{$current_env}'. This is not a 'production' build.";
        }
        // Kasus 3: Ini 'production', tidak ada warning yang ditambahkan.

        $route_files = (array)($fst_config['routing']['routes_file'] ?? ['router.php']);
        foreach ($route_files as $file) {
            if (!file_exists(FST_ROOT_DIR . '/' . $file)) {
                $errors[] = "Static route file not found: <code>{$file}</code>";
            }
        }
        
        $public_folders = $fst_config['routing']['public_folders'] ?? [];
        foreach ($public_folders as $folder) {
            if (!is_dir(FST_ROOT_DIR . '/' . $folder)) {
                $warnings[] = "Public folder not found (will be ignored): <code>{$folder}</code>";
            }
        }

        $error_handlers = $fst_config['routing']['error_handlers'] ?? [];
        foreach ($error_handlers as $code => $handler) {
            if (preg_match('/\.php$|\.html$/', $handler) && !file_exists(FST_ROOT_DIR . '/' . $handler)) {
                $warnings[] = "Error handler file for code {$code} not found: <code>{$handler}</code> (Fallback will be used)";
            }
        }

        // Cek Koneksi DB
        $db_status = '';
        $default_conn = $fst_config['database']['default'] ?? 'main';
        $db_driver = $fst_config['database']['connections'][$default_conn]['driver'] ?? 'none';
        
        if ($db_driver === 'none') {
            $db_status = '<span style="color:orange;">⚠ Not Configured</span>';
        } else {
            try {
                fst_db('ROW', 'SELECT 1', [], $default_conn);
                $db_status = '<span style="color:green;">✔ OK</span> (Driver: ' . $db_driver . ')';
            } catch (Exception $e) {
                $db_status = '<span style="color:red;">❌ FAILED</span>: ' . (fst_is_safe_to_debug() ? $e->getMessage() : 'Connection error.');
                $errors[] = "Database connection test failed: " . $e->getMessage();
            }
        }

        $content = "<h2>Configuration Status</h2>";
        $content .= $update_banner;
        
        // Tampilkan warning khusus di bagian paling atas
        $content .= $dev_warning_html; 
        
        // Tampilkan environment yang sedang berjalan
        $content .= "<p><strong>Environment:</strong> " . htmlspecialchars($current_env) . "</p>";
        $content .= "<p><strong>Database Status:</strong> {$db_status}</p>";

        // Extension Health Check
        $ext_checks = [
            ['name' => 'mbstring', 'level' => 'recommended', 'note' => 'Digunakan untuk penghitungan panjang string multibyte (validasi). Tanpa ini, framework fallback ke strlen().'],
            ['name' => 'fileinfo', 'level' => 'recommended', 'note' => 'Meningkatkan deteksi MIME type saat upload file.'],
            ['name' => 'json', 'level' => 'required', 'note' => 'Diperlukan untuk parsing fullstuck.json dan fst_json().'],
            ['name' => 'pdo', 'level' => 'required', 'note' => 'Diperlukan untuk koneksi database.'],
            ['name' => 'session', 'level' => 'required', 'note' => 'Diperlukan untuk session, flash message, dan CSRF.'],
        ];
        $ext_html = "<h2>PHP Extension Check</h2><table><thead><tr><th>Extension</th><th>Status</th><th>Level</th><th>Keterangan</th></tr></thead><tbody>";
        foreach ($ext_checks as $ext) {
            $loaded = extension_loaded($ext['name']);
            $status_icon = $loaded ? '<span style="color:green;">✔ Loaded</span>' : '<span style="color:orange;">✗ Not Loaded</span>';
            $level_label = $ext['level'] === 'required' ? '<b>Required</b>' : 'Recommended';
            if (!$loaded && $ext['level'] === 'recommended') {
                $warnings[] = "Extension <code>{$ext['name']}</code> tidak aktif. {$ext['note']}";
            } elseif (!$loaded && $ext['level'] === 'required') {
                $errors[] = "Extension <code>{$ext['name']}</code> (REQUIRED) tidak aktif! {$ext['note']}";
            }
            $ext_html .= "<tr><td><code>{$ext['name']}</code></td><td>{$status_icon}</td><td>{$level_label}</td><td>{$ext['note']}</td></tr>";
        }
        $ext_html .= "</tbody></table>";

        if (!empty($errors)) {
            $content .= "<h2><span style='color:red;'>Errors Found!</span></h2><ul>";
            foreach($errors as $err) { $content .= "<li>{$err}</li>"; }
            $content .= "</ul>";
        }
        if (!empty($warnings)) {
            $content .= "<h2><span style='color:orange;'>Warnings</span></h2><ul>";
            foreach($warnings as $warn) { $content .= "<li>{$warn}</li>"; }
            $content .= "</ul>";
        }

        $content .= $ext_html;

        fst_admin_render_page('System Monitor', $content);
    }

    function fst_admin_show_config() {
        fst_admin_check_auth();
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';
        $csrf = fst_csrf_field();
        
        $config_content = htmlspecialchars(file_get_contents(FST_CONFIG_FILE), ENT_QUOTES, 'UTF-8');
        
        $content = <<<HTML
<p>Edit the raw JSON configuration below. Be careful with syntax!</p>
<form action="{$admin_base}/config/hash" method="POST" data-fst-no-spa style="margin-bottom:15px; padding:10px; background:#f4f4f4; border-radius:5px;">{$csrf}<strong>Generate Password Hash:</strong> <input type="text" name="new_pass" placeholder="Type new password" required> <button type="submit">Generate</button></form>
<form action="{$admin_base}/config/save" method="POST" data-fst-no-spa>
    {$csrf}
    <textarea name="config_content" spellcheck="false">{$config_content}</textarea>
    <br><br>
    <button type="submit">Save Configuration</button>
</form>
HTML;
        fst_admin_render_page('Configuration Editor', $content);
    }

    function fst_admin_save_config() {
        fst_admin_check_auth();
        fst_csrf_check();
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';

        $new_content = $_POST['config_content'] ?? '';

        $decoded = json_decode($new_content);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            fst_flash_set('error_message', 'Invalid JSON syntax! Changes not saved. Error: ' . json_last_error_msg());
        } else {
            if (file_put_contents(FST_CONFIG_FILE, $new_content) !== false) {
                 fst_flash_set('success_message', 'Configuration saved successfully!');
            } else {
                 fst_flash_set('error_message', 'Failed to write configuration file! Check permissions.');
            }
        }
        fst_redirect($admin_base . '/config');
    }
    
     function fst_admin_show_routes() {
        fst_admin_check_auth();
        $fst_routes = fst_app('routes');
        $fst_config = fst_app('config');
        $fst_route_prefix = fst_app('route_prefix');
        
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $base_path = $fst_config['routing']['base_path'] ?? '/';
        $base_url = rtrim($scheme . "://" . $host . $base_path, '/');
        
        $content = "<p>List of registered routes (from static files or admin routes).</p>";
        $content .= "<table><thead><tr><th>Method</th><th>Original Path</th><th>Pattern (Regex)</th><th>Example URL (GET only)</th></tr></thead><tbody>";
        
        if (empty($fst_routes)) {
             $content .= "<tr><td colspan='4'>No routes registered yet.</td></tr>";
        } else {
            foreach ($fst_routes as $method_group => $routes) {
                foreach ($routes as $route) {
                     list($method, $pattern, $callback, $original_path) = array_pad($route, 4, null);
                     
                     if ($original_path === null) {
                          $original_path = preg_replace(['/#\^|\\\$#/', '/\(\[\^\/]\+\)/', '/\(\[0-9]\+\)/', '/\(\[a-zA-Z0-9\\-]+)/'], ['', '{param}', '{id}', '{slug}'], str_replace('\/', '/', $pattern));
                     }

                     $link = '-';
                     if ($method === 'GET' || $method === 'ANY') {
                          $test_url_path = preg_replace('/\{[^}]+\??\}/', 'test', $original_path);
                          $test_url = $base_url . $test_url_path;
                          $link = "<a href='{$test_url}' target='_blank' title='Click to test (opens in new tab)'>" . htmlspecialchars($original_path) . "</a>";
                     } else {
                          $link = htmlspecialchars($original_path);
                     }

                     $content .= "<tr><td>{$method}</td><td><code>" . htmlspecialchars($original_path) . "</code></td><td><code>" . htmlspecialchars($pattern) . "</code></td><td>{$link}</td></tr>";
                }
            }
        }
        $content .= "</tbody></table>";
        
        fst_admin_render_page('Registered Routes', $content);
    }
     
     function fst_get_server_info() { return [ 'PHP Version' => PHP_VERSION, 'System' => php_uname(), 'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A', 'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A', 'FullStuck Root' => FST_ROOT_DIR, 'SAPI' => php_sapi_name(), 'PDO Loaded' => extension_loaded('pdo') ? 'Yes' : 'No', 'PDO MySQL' => extension_loaded('pdo_mysql') ? 'Yes' : 'No', 'PDO SQLite' => extension_loaded('pdo_sqlite') ? 'Yes' : 'No', 'mbstring' => extension_loaded('mbstring') ? 'Yes' : 'No (fallback to strlen)', 'json' => extension_loaded('json') ? 'Yes' : 'No', 'session' => extension_loaded('session') ? 'Yes' : 'No', 'fileinfo' => extension_loaded('fileinfo') ? 'Yes' : 'No (upload mime detection limited)', ]; }
     
     function fst_admin_show_server_info() {
         fst_admin_check_auth();
         $server_info = fst_get_server_info();
         
         $content = "<table><thead><tr><th>Parameter</th><th>Value</th></tr></thead><tbody>";
         foreach ($server_info as $key => $value) {
             $content .= "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
         }
         $content .= "</tbody></table>";
         
         $content .= "<h2>PHP Info (Raw)</h2>";
         $content .= "<details><summary>Click to expand/collapse</summary><div style='width:100%; height: 400px; overflow:auto; border:1px solid #ccc;'>";
         ob_start();
         phpinfo();
         $phpinfo = ob_get_clean();
         if (preg_match('/<body.*?>(.*)<\/body>/is', $phpinfo, $matches)) {
             $content .= $matches[1];
         } else {
             $content .= "Could not parse phpinfo().";
         }
         $content .= "</div></details>";
         
         fst_admin_render_page('Server Information', $content);
     }

    function fst_admin_show_scan_page() {
        fst_admin_check_auth();
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';
        $csrf = fst_csrf_field();
        
        $scan_results_html = '';
        $scan_data = fst_flash_get('scan_results');

        if ($scan_data !== null) {
            $file_count = count($scan_data);
            $scan_results_html .= "<h2>Scan Results ({$file_count} PHP files analyzed):</h2>";
            if (empty($scan_data)) {
                 $scan_results_html .= "<p>No PHP files found or scanned.</p>";
            } else {
                 $scan_results_html .= "<table border='1' style='width:100%; border-collapse: collapse;'><thead><tr><th>File Path</th><th>Function Groups & Functions Found</th></tr></thead><tbody>";
                 ksort($scan_data);
                 
                 foreach ($scan_data as $file => $groups) {
                     $scan_results_html .= "<tr><td><code>" . htmlspecialchars($file) . "</code></td><td>";
                     if(empty($groups)){
                         $scan_results_html .= "<i>(No fst_ usage found)</i>";
                     } else {
                         $group_details = [];
                         foreach($groups as $group_name => $functions) {
                             $group_details[] = "<strong>" . htmlspecialchars($group_name) . ":</strong> " . implode(', ', array_map('htmlspecialchars', $functions));
                         }
                         $scan_results_html .= implode('<br>', $group_details);
                     }
                     $scan_results_html .= "</td></tr>";
                 }
                 $scan_results_html .= "</tbody></table>";
            }
        } else {
             $scan_results_html .= "<p>Click 'Start Scan' to analyze project files.</p>";
        }

        $content = <<<HTML
<p>Click the button below to scan your project directory (<code>{$_SERVER['DOCUMENT_ROOT']}</code>) for usage of <code>fst_</code> functions in <code>.php</code> files.</p>
<p><strong>Warning:</strong> This might take a while on large projects. Folders like <code>vendor</code> and <code>node_modules</code> are automatically skipped.</p>

<form action="{$admin_base}/scan/run" method="POST" data-fst-no-spa>
    {$csrf}
    <button type="submit">Start Scan</button>
</form>

{$scan_results_html}
HTML;
        fst_admin_render_page('Scan Project for fst_ Usage', $content);
    }

    function fst_admin_run_scan() {
        fst_admin_check_auth();
        fst_csrf_check();
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';

        $function_groups = [
            'Core' => ['fst_abort', 'fst_run', 'fst_is_dev', 'fst_config', 'fst_extract_html_fragment', 'fst_app', 'fst_spa_page'],

            'Database' => ['fst_db', 'fst_db_begin', 'fst_db_commit', 'fst_db_rollback', 'fst_db_select', 'fst_db_row', 'fst_db_exists', 'fst_db_insert', 'fst_db_update', 'fst_db_delete', 'fst_db_quote_ident', '_fst_sanitize_order_by'],
            'Views' => [
                'fst_view',
                'fst_partial',
                'fst_serve_static_file',
                'fst_template',
                'fst_view_share'
            ],
            'Request' => ['fst_uri', 'fst_method', 'fst_input', 'fst_request', 'fst_file', 'fst_is_spa', 'fst_spa_target'],
            'Routing' => ['fst_route', 'fst_get', 'fst_post', 'fst_put', 'fst_patch', 'fst_delete', 'fst_any', 'fst_group'],
            'Response' => ['fst_json', 'fst_text', 'fst_redirect', 'fst_status_code'],
            'Session' => ['fst_session_set', 'fst_session_get', 'fst_session_forget', 'fst_flash_set', 'fst_flash_has', 'fst_flash_get'],
            'Security' => ['fst_csrf_token', 'fst_csrf_field', 'fst_csrf_check', 'fst_escape', 'e', 'fst_is_safe_to_debug'],
            'Upload' => ['fst_upload'],
            'Validation' => ['fst_validate'],
            'Debug' => ['fst_dump', 'fst_dd'],
            'Installation' => ['fst_handle_installation', 'fst_render_status_row', 'fst_show_install_success', 'fst_show_install_form'],
            'Admin' => [
                'fst_admin_check_auth', 'fst_admin_show_login', 'fst_admin_do_login',
                'fst_admin_do_logout', 'fst_admin_render_page', 'fst_admin_show_monitor',
                'fst_admin_show_config', 'fst_admin_save_config', 'fst_admin_show_routes',
                'fst_get_server_info', 'fst_admin_show_server_info', 'fst_admin_show_scan_page',
                'fst_admin_run_scan', 'fst_check_integrity', 'fst_admin_show_integrity', 'fst_admin_show_plugins',
                'fst_admin_install_plugin', 'fst_admin_toggle_plugin', 'fst_admin_uninstall_plugin', 'fst_admin_get_remote_info'
            ]
        ];

        $results = [];
        $php_files = [];

        $scan_dir = function ($dir) use (&$scan_dir, &$php_files) {
            $items = @scandir($dir);
            if ($items === false) return;

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $dir . '/' . $item;
                if (is_dir($path)) {
                    if ($item === 'vendor' || $item === 'node_modules' || $item === '.git') continue;
                    $scan_dir($path);
                } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                    $php_files[] = $path;
                }
            }
        };

        $scan_dir(FST_ROOT_DIR);

        foreach ($php_files as $file_path) {
            $content = @file_get_contents($file_path);
            if ($content === false) continue;

            $found_functions = [];
            if (preg_match_all('/\b(fst_\w+)\s*\(/', $content, $matches)) {
                $found_functions = array_unique($matches[1]);
                sort($found_functions);
            }
            
            $relative_path = str_replace(FST_ROOT_DIR . '/', '', $file_path);
            $results[$relative_path] = [];

            foreach($found_functions as $func_name) {
                $group_found = false;
                foreach ($function_groups as $group_name => $group_funcs) {
                    if (in_array($func_name, $group_funcs)) {
                        $results[$relative_path][$group_name][] = $func_name;
                        $group_found = true;
                        break;
                    }
                }
                if (!$group_found) {
                    $results[$relative_path]['Unknown'][] = $func_name;
                }
            }
             if (isset($results[$relative_path])) {
                 ksort($results[$relative_path]);
             }
        }
        
        fst_flash_set('scan_results', $results);
        fst_redirect($admin_base . '/scan');
    }

    function fst_check_integrity() {
        // Perbaikan 2: Deteksi lokasi file core secara akurat (bahkan saat simulasi di folder test/)
        $file_path = FST_ROOT_DIR . '/fullstuck.php';
        if (!file_exists($file_path)) {
            // Fallback jika dijalankan via php -S dari subfolder
            $script_path = $_SERVER['SCRIPT_FILENAME'] ?? '';
            if (basename($script_path) === 'fullstuck.php' && file_exists($script_path)) {
                $file_path = $script_path;
            } else {
                return false;
            }
        }
        
        $content = file_get_contents($file_path);
        if (!preg_match('/FST_HASH:\s*([a-f0-9]{64})/', $content, $matches)) return false;
        
        $declared_hash = $matches[1];
        
        // Perbaikan 1: Gunakan preg_split untuk bypass masalah CRLF (Windows) vs LF (Unix)
        $parts = preg_split('/ \*\/\r?\n/', $content, 2);
        if (count($parts) !== 2) return false;
        
        $actual_hash = hash('sha256', str_replace("\r\n", "\n", $parts[1]));
        return [
            'valid' => hash_equals($declared_hash, $actual_hash),
            'declared' => $declared_hash,
            'actual' => $actual_hash
        ];
    }

    function fst_admin_show_integrity() {
        fst_admin_check_auth();
        $integrity = fst_check_integrity();
        
        $remote_data = fst_admin_get_remote_info();
        $remote_info = "<i>Not checked</i>";
        
        if ($remote_data && isset($remote_data['hash'])) {
            if ($integrity && $integrity['declared'] === $remote_data['hash']) {
                $remote_info = "<span style='color:green;'>✔ Match with official GitHub registry (v{$remote_data['version']})</span>";
            } else {
                $remote_info = "<span style='color:red;'>❌ Mismatch with official GitHub registry!</span> (Latest: v{$remote_data['version']})";
            }
        } elseif ($remote_data === false) {
            $remote_info = "<span style='color:orange;'>Failed to connect to GitHub</span>";
        }
        
        $html = "<h2>File Integrity Monitoring (FIM)</h2>";
        if (!$integrity) {
            $html .= "<div class='alert-warning'>Cannot perform integrity check. <code>fullstuck.php</code> not found or malformed header.</div>";
        } else {
            if ($integrity['valid']) {
                $html .= "<div style='color:green; font-size:1.2em; margin-bottom:10px;'>✔ Local Integrity OK: The core file has not been tampered with.</div>";
            } else {
                $html .= "<div style='color:red; font-size:1.2em; font-weight:bold; margin-bottom:10px;'>❌ Local Integrity FAILED: The core file has been modified!</div>";
            }
            $html .= "<ul>";
            $html .= "<li><strong>Declared Hash (Line 1):</strong> <code>{$integrity['declared']}</code></li>";
            $html .= "<li><strong>Actual Content Hash:</strong> <code>{$integrity['actual']}</code></li>";
            $html .= "<li><strong>Remote Verification:</strong> {$remote_info}</li>";
            $html .= "</ul>";
        }
        
        fst_admin_render_page('Integrity Check', $html);
    }

    function fst_admin_show_plugins() {
        fst_admin_check_auth();
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';
        $csrf = fst_csrf_field();
        
        // 1. Scan Local Plugins
        $plugin_dir = FST_ROOT_DIR . '/fst-plugins';
        $local_plugins = [];
        if (is_dir($plugin_dir)) {
            $files = scandir($plugin_dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $is_disabled = str_ends_with($file, '.disabled');
                if (str_ends_with($file, '.php') || $is_disabled) {
                    $local_plugins[] = [
                        'filename' => $file,
                        'name' => str_replace(['.php', '.disabled'], '', $file),
                        'active' => !$is_disabled
                    ];
                }
            }
        }

        $html = "<h2>Installed Plugins</h2>";
        if (empty($local_plugins)) {
            $html .= "<p>No plugins installed yet.</p>";
        } else {
            $html .= "<table><thead><tr><th>Plugin Name</th><th>Status</th><th>Actions</th></tr></thead><tbody>";
            foreach ($local_plugins as $p) {
                $status = $p['active'] ? '<span style="color:green;">✔ Active</span>' : '<span style="color:gray;">○ Inactive</span>';
                $toggle_text = $p['active'] ? 'Disable' : 'Enable';
                $toggle_style = $p['active'] ? 'background:#6c757d;' : 'background:#28a745;';
                
                $html .= "<tr>";
                $html .= "<td><strong>" . htmlspecialchars($p['name']) . "</strong><br><small style='color:#666;'>" . htmlspecialchars($p['filename']) . "</small></td>";
                $html .= "<td>{$status}</td>";
                $html .= "<td>
                    <form action='{$admin_base}/plugins/toggle' method='POST' style='display:inline;' data-fst-no-spa>
                        {$csrf}
                        <input type='hidden' name='filename' value='" . htmlspecialchars($p['filename']) . "'>
                        <button type='submit' style='{$toggle_style}'>{$toggle_text}</button>
                    </form>
                    <form action='{$admin_base}/plugins/uninstall' method='POST' style='display:inline;' data-fst-no-spa onsubmit='return confirm(\"Are you sure you want to uninstall this plugin?\")'>
                        {$csrf}
                        <input type='hidden' name='filename' value='" . htmlspecialchars($p['filename']) . "'>
                        <button type='submit' style='background:#dc3545;'>Uninstall</button>
                    </form>
                </td>";
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";
        }

        // 2. Marketplace
        $remote_store_url = "https://raw.githubusercontent.com/milio48/fullstuck/main/store.json";
        $local_store_file = FST_ROOT_DIR . '/store.json';
        $store_plugins = [];
        $is_remote = false;

        $ctx = stream_context_create(['http' => ['timeout' => 5]]);
        $remote_json = @file_get_contents($remote_store_url, false, $ctx);
        if ($remote_json) {
            $store_plugins = json_decode($remote_json, true) ?: [];
            $is_remote = true;
        } elseif (file_exists($local_store_file)) {
            $store_plugins = json_decode(file_get_contents($local_store_file), true) ?: [];
        }
        
        $source_label = $is_remote ? "<span style='color:green;'>GitHub Store</span>" : "<span style='color:orange;'>Local Registry</span>";

        $html .= "<br><hr><h2>Plugin Store <small style='font-size:14px; font-weight:normal;'>({$source_label})</small></h2>";
        
        if (empty($store_plugins)) {
            $html .= "<p>No plugins found in store.</p>";
        } else {
            $html .= "<table><thead><tr><th>Plugin Name</th><th>Description</th><th>Action</th></tr></thead><tbody>";
            foreach ($store_plugins as $plugin) {
                $id = $plugin['id'] ?? '';
                $is_installed = false;
                foreach ($local_plugins as $lp) {
                    if ($lp['name'] === $id) { $is_installed = true; break; }
                }
                
                $btn_text = $is_installed ? 'Re-install' : 'Install';
                $btn_style = $is_installed ? 'background:#6c757d;' : 'background:#28a745;';
                
                $html .= "<tr>";
                $html .= "<td><strong>" . htmlspecialchars($plugin['name'] ?? 'Unknown') . "</strong><br><small style='color:#666;'>ID: " . htmlspecialchars($id) . "</small></td>";
                $html .= "<td>" . htmlspecialchars($plugin['description'] ?? '') . "</td>";
                $html .= "<td>
                    <form action='{$admin_base}/plugins/install' method='POST' style='display:inline;' data-fst-no-spa>
                        {$csrf}
                        <input type='hidden' name='id' value='" . htmlspecialchars($id) . "'>
                        <button type='submit' style='{$btn_style}'>{$btn_text}</button>
                    </form>
                </td>";
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";
        }
        
        fst_admin_render_page('Plugin Manager', $html);
    }

    function fst_admin_toggle_plugin() {
        fst_admin_check_auth();
        fst_csrf_check();
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';

        $filename = basename($_POST['filename'] ?? '');
        $plugin_dir = FST_ROOT_DIR . '/fst-plugins';
        $path = $plugin_dir . '/' . $filename;

        if (!empty($filename) && file_exists($path)) {
            if (str_ends_with($filename, '.disabled')) {
                $new_path = str_replace('.disabled', '', $path);
                rename($path, $new_path);
                fst_flash_set('success_message', 'Plugin enabled.');
            } else {
                $new_path = $path . '.disabled';
                rename($path, $new_path);
                fst_flash_set('success_message', 'Plugin disabled.');
            }
        }
        fst_redirect($admin_base . '/plugins');
    }

    function fst_admin_uninstall_plugin() {
        fst_admin_check_auth();
        fst_csrf_check();
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';

        $filename = basename($_POST['filename'] ?? '');
        $plugin_dir = FST_ROOT_DIR . '/fst-plugins';
        $path = $plugin_dir . '/' . $filename;

        if (!empty($filename) && file_exists($path)) {
            unlink($path);
            fst_flash_set('success_message', 'Plugin uninstalled successfully.');
        }
        fst_redirect($admin_base . '/plugins');
    }

    function fst_admin_install_plugin() {
        fst_admin_check_auth();
        fst_csrf_check();
        $fst_config = fst_app('config');
        $admin_base = $fst_config['admin']['page_url'] ?? '/stuck';

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            fst_flash_set('error_message', 'Invalid plugin ID.');
            fst_redirect($admin_base . '/plugins');
        }
        
        // 1. Konstruksi URL resmi (Hardcoded Prefix & Convention: fst-{id}.php)
        $clean_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        $url = "https://raw.githubusercontent.com/milio48/fullstuck/refs/heads/main/store/fst-" . $clean_id . ".php";

        // Pastikan direktori plugin ada
        $plugin_dir = FST_ROOT_DIR . '/fst-plugins';
        if (!is_dir($plugin_dir)) {
            if (!mkdir($plugin_dir, 0755, true)) {
                fst_flash_set('error_message', 'Failed to create fst-plugins directory.');
                fst_redirect($admin_base . '/plugins');
            }
        }

        // Download file
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $content = @file_get_contents($url, false, $ctx);
        if ($content === false || strpos(trim($content), '<?php') !== 0) {
            fst_flash_set('error_message', 'Invalid plugin file or failed to download from GitHub.');
        } else {
            $filename = $plugin_dir . '/fst-' . $clean_id . '.php';
            if (file_put_contents($filename, $content) !== false) {
                fst_flash_set('success_message', 'Plugin ' . htmlspecialchars($id) . ' installed successfully!');
            } else {
                fst_flash_set('error_message', 'Failed to save plugin file. Check permissions.');
            }
        }

        fst_redirect($admin_base . '/plugins');
    }

}
?>

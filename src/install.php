<?php
function _fst_cli_output(string $status, string $message): void {
    $colors = [
        'success' => "\033[1;32m✓\033[0m \033[32m", // Hijau Mint
        'error'   => "\033[1;31m✗\033[0m \033[31m", // Merah Terpadu
        'info'    => "\033[1;34mℹ\033[0m \033[36m", // Cyan Info
    ];
    $reset = "\033[0m\n";
    echo ($colors[$status] ?? "") . $message . $reset;
}

function fst_handle_installation() {
    $is_cli = php_sapi_name() === 'cli';
    
    if (!$is_cli) {
        http_response_code(500);
        die("Error: FullStuck.php v" . FST_VERSION . " requires CLI initialization. Please open your terminal and run: php fullstuck.php init");
    }

    global $argv;
    if (!isset($argv[1]) || $argv[1] !== 'init') {
        echo "FullStuck.php is not initialized.\n";
        echo "Run: php fullstuck.php init [options]\n\n";
        echo "Options:\n";
        echo "  --db=sqlite|mysql|pgsql (default: sqlite)\n";
        echo "  --agent_js=yes|no (default: yes)\n";
        echo "  --scaffold=yes|minimal|no (default: yes)\n";
        echo "  --htaccess=yes|no (default: no)\n";
        exit(1);
    }

    try {
        $input_data = [];
        foreach ($argv as $arg) {
            if (preg_match('/^--([^=]+)=(.*)$/', $arg, $m)) {
                $input_data[str_replace('-', '_', $m[1])] = $m[2];
            }
        }
        $driver = $input_data['db'] ?? 'sqlite';
        $input_data['enable_agent'] = ($input_data['agent_js'] ?? 'yes') === 'yes' ? '1' : '0';
        $scaffold_opt = $input_data['scaffold'] ?? 'yes';
        $input_data['generate_starter'] = $scaffold_opt === 'minimal' ? 'minimal' : ($scaffold_opt === 'yes' ? '1' : '0');
        $server_type = ($input_data['htaccess'] ?? 'no') === 'yes' ? 'apache_litespeed' : 'other';

        if ($driver !== 'none') {
            $h = $input_data['db_host'] ?? 'localhost';
            $n = $input_data['db_name'] ?? '';
            $u = $input_data['db_user'] ?? ($driver === 'pgsql' ? 'postgres' : 'root');
            $p = $input_data['db_pass'] ?? '';
            $port = $input_data['db_port'] ?? ($driver === 'pgsql' ? '5432' : '3306');

            if ($driver === 'mysql') { 
                $dsn = "mysql:host={$h};port={$port};dbname={$n};charset=utf8mb4"; 
                new PDO($dsn, $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]); 
            } elseif ($driver === 'pgsql') { 
                $dsn = "pgsql:host={$h};port={$port};dbname={$n}"; 
                new PDO($dsn, $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]); 
            } else { 
                $path = FST_ROOT_DIR . '/' . ($input_data['db_path'] ?? 'database.sqlite'); 
                $dir = dirname($path); 
                if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new Exception("Failed to create folder '{$dir}'. Check permissions."); 
                new PDO("sqlite:" . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); 
            }
        }
        
        $db_conn = ["driver" => $driver];
        if ($driver === 'sqlite') {
            $db_conn["database_path"] = $input_data['db_path'] ?? 'database.sqlite';
        } else if ($driver !== 'none') {
            $db_conn["host"] = $input_data['db_host'] ?? 'localhost';
            $db_conn["port"] = $input_data['db_port'] ?? ($driver === 'pgsql' ? '5432' : '3306');
            $db_conn["dbname"] = $input_data['db_name'] ?? '';
            $db_conn["username"] = $input_data['db_user'] ?? ($driver === 'pgsql' ? 'postgres' : 'root');
            $db_conn["password"] = $input_data['db_pass'] ?? '';
        }

        $config_data = [
            "production" => false, 
            "database" => [
                "default" => "main",
                "connections" => [
                    "main" => $db_conn
                ]
            ],
            "_ai_rules" => "Run `php fullstuck.php docs` in your terminal to read the framework API documentation.",
            "routing" => [
                "base_path" => "/",
                "public_folders" => ["assets", "uploads"],
                "routes_file" => ["router.php"]
            ],
            "agent_js" => isset($input_data['enable_agent']) && $input_data['enable_agent'] === '1'
        ];
        
        if (file_put_contents(FST_CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            throw new Exception("Failed to write `fullstuck.json`. Check folder permissions.");
        }
        
        if ($server_type === 'apache_litespeed') {
            $htaccess_code = implode("\n", [
                '# 1. Nonaktifkan fitur "Index of" dan "MultiViews"',
                'Options -Indexes -MultiViews',
                '',
                '# Blokir akses ke file hidden (dotfiles)',
                '<FilesMatch "^\.">',
                '    Require all denied',
                '</FilesMatch>',
                '',
                '<IfModule mod_rewrite.c>',
                '    RewriteEngine On',
                '    RewriteBase /',
                '    ',
                '    # 2. Aturan "Rakus" (Kirim SEMUA ke fullstuck.php)',
                '    RewriteRule ^(.*)$ fullstuck.php [L]',
                '</IfModule>'
            ]);
            file_put_contents(FST_ROOT_DIR . '/.htaccess', $htaccess_code);
        }

        // Auto-Scaffolding Starter Project
        if (isset($input_data['generate_starter']) && $input_data['generate_starter'] !== '0') {
            @mkdir(FST_ROOT_DIR . '/views', 0755, true);
            if ($input_data['generate_starter'] === 'minimal') {
                $html_template = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <style>
        :root {
            --bg-main: #0b0f19;
            --bg-surface: #172033;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #6366f1;
            --font-sans: system-ui, -apple-system, sans-serif;
        }
        body { font-family: var(--font-sans); background: var(--bg-main); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { text-align: center; background: var(--bg-surface); padding: 3rem; border-radius: 20px; border: 1px solid #24324f; border-top: 4px solid var(--primary); box-shadow: 0 10px 30px rgba(0,0,0,0.2); transition: opacity 0.3s ease; }
        h1 { color: var(--primary); margin-bottom: 0.5rem; }
        p { color: var(--text-muted); }
        .fst-loading { opacity: 0.5; pointer-events: none; cursor: wait; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome</h1>
        <p>Your FullStuck.php application is ready.</p>
    </div>
</body>
</html>
HTML;
                @file_put_contents(FST_ROOT_DIR . '/views/index.html', $html_template);

                $router_code = <<<PHP
<?php
// 🤖 AI Assistant: Run `php fullstuck.php docs` in the terminal to read the API documentation.

// Tampilkan Halaman Utama
fst_get('/', function() {
    fst_template(FST_ROOT_DIR . '/views/index.html', ['title' => 'Welcome to FullStuck'], [
        "title" => '\$title',
        "h1" => '\$title'
    ], FST_ROOT_DIR . '/build-template', true);
});
PHP;
                @file_put_contents(FST_ROOT_DIR . '/router.php', $router_code);
            }
        }
        _fst_cli_output('success', 'FullStuck initialized successfully!');
        return;
    } catch (Exception $e) { 
        _fst_cli_output('error', 'Initialization failed: ' . $e->getMessage());
        exit(1);
    }
}

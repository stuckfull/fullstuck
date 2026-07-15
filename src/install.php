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
        echo "  --scaffold=yes|no (default: yes)\n";
        echo "  --htaccess=yes|no (default: yes)\n";
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
        $input_data['generate_starter'] = $scaffold_opt === 'yes' ? '1' : '0';
        $server_type = ($input_data['htaccess'] ?? 'yes') === 'yes' ? 'apache_litespeed' : 'other';
        if ($server_type !== 'apache_litespeed') {
            _fst_cli_output('info', 'WARNING: .htaccess was not created. Make sure your web server (Nginx/other) manually blocks direct access to *.sqlite, *.json, *.log, and the app/globals/components/cache folders — these files are NOT protected by PHP if rewrite rules are inactive.');
        }

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
                "public_folders" => ["assets", "uploads"]
            ],
            "require" => ["globals"],
            "agent_js" => isset($input_data['enable_agent']) && $input_data['enable_agent'] === '1'
        ];
        
        if (file_put_contents(FST_CONFIG_FILE, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            throw new Exception("Failed to write `fullstuck.json`. Check folder permissions.");
        }
        
        if ($server_type === 'apache_litespeed') {
            $htaccess_code = "Options -Indexes -MultiViews\n\n# 1. Block sensitive files, sqlite databases, json configs, and logs\n<FilesMatch \"(\.(sqlite|json|log|ini|env|md|lock)$|^(\.))\">\n    Require all denied\n</FilesMatch>\n\n# 2. Block direct URL access to internal architecture & system folders\n<IfModule mod_rewrite.c>\n    RewriteEngine On\n    RewriteRule ^(app|globals|components|cache|storage)/(.*)$ - [F,L]\n</IfModule>\n\n# 3. Redirect all web traffic to fullstuck.php\n<IfModule mod_rewrite.c>\n    RewriteEngine On\n    RewriteBase /\n    RewriteCond %{REQUEST_FILENAME} !-f\n    RewriteCond %{REQUEST_FILENAME} !-d\n    RewriteRule ^(.*)$ fullstuck.php [L]\n</IfModule>";
            file_put_contents(FST_ROOT_DIR . '/.htaccess', $htaccess_code);
        }

        // Auto-Scaffolding Starter Project
        if (isset($input_data['generate_starter']) && $input_data['generate_starter'] === '1') {
            _fst_cli_output('info', 'Generating local minimal scaffold for v0.4...');
            $files = [
                'globals/helper.php' => "<?php\n/**\n * Globals Example\n * Files in this folder cannot be accessed directly via URL.\n * Useful for helper functions or configurations.\n * All files in the globals/ folder are automatically loaded by the framework.\n */\nfunction my_custom_helper() {\n    return 'Globals Helper Active!';\n}\n\nfunction log_scaffold_visit() {\n    try {\n        fst_db('EXEC', \"CREATE TABLE IF NOT EXISTS scaffold_visits (visited_at VARCHAR(255))\");\n        fst_db('EXEC', \"INSERT INTO scaffold_visits (visited_at) VALUES (?)\", [date('Y-m-d H:i:s')]);\n        return fst_db('SCALAR', \"SELECT COUNT(*) FROM scaffold_visits\");\n    } catch (\\Exception \$e) {\n        return 0;\n    }\n}\n",
                'app/_layout.fst.php' => "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>FullStuck v0.4</title>\n    <style>\n        body { font-family: system-ui, -apple-system, sans-serif; background: #0b0f19; color: #f8fafc; margin: 0; padding: 20px; }\n        nav { background: #172033; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #24324f; }\n        nav a { color: #6366f1; text-decoration: none; margin-right: 15px; font-weight: bold; }\n        nav a:hover { color: #10b981; }\n        .container { max-width: 800px; margin: 0 auto; }\n        .card { background: #172033; padding: 20px; border-radius: 8px; border: 1px solid #24324f; }\n        code { background: #1e293b; padding: 2px 6px; border-radius: 4px; color: #38bdf8; }\n    </style>\n</head>\n<body>\n    <div class=\"container\">\n        <nav>\n            <a href=\"/\">Home</a>\n            <a href=\"/about\">About</a>\n            <a href=\"/api/hello\">API Endpoint</a>\n        </nav>\n        <main class=\"card\">\n            @yield('content')\n        </main>\n    </div>\n</body>\n</html>",
                'app/content.fst.php' => "<h1 style=\"color: #10b981; margin-top: 0;\">🚀 Welcome to FullStuck.php</h1>\n<p>Framework is successfully initialized with <b>Path-Based Colocation</b>.</p>\n<p>Message from helper: <code>{{ my_custom_helper() }}</code></p>\n<p>Database Test: This scaffold has been visited <b>{{ log_scaffold_visit() }}</b> times.</p>\n<hr style=\"border-color: #24324f; margin: 20px 0;\">\n<h3>🧹 How to Clean Up This Scaffold:</h3>\n<p>If you want to start from a blank canvas, please delete the following files/folders:</p>\n<ul>\n    <li>Delete the folder <code>app/about/</code></li>\n    <li>Delete the folder <code>app/api/</code></li>\n    <li>Delete the folder <code>globals/</code> (optional)</li>\n    <li>Empty the contents of <code>app/content.fst.php</code></li>\n</ul>\n<p><small>View documentation: <code>php fullstuck.php docs</code></small></p>",
                'app/about/content.fst.php' => "<h1 style=\"color: #6366f1; margin-top: 0;\">📖 About Page</h1>\n<p>This demonstrates path-based routing. You didn't need to configure any router!</p>\n<p>This page lives in <code>app/about/content.fst.php</code>.</p>\n<p>Notice how fast the navigation is? That's <b>FST-Agent (SPA)</b> at work.</p>",
                'app/api/hello/action.php' => "<?php\n/**\n * Headless API Example\n * Endpoint: GET|POST /api/hello\n */\n\nfst_json([\n    'status' => 'success',\n    'message' => 'Hello from FullStuck API!',\n    'time' => date('Y-m-d H:i:s')\n]);\n"
            ];

            $fst_root_real = realpath(FST_ROOT_DIR);
            foreach ($files as $path => $content) {
                if (!is_string($path) || !is_string($content) || str_contains($path, '..')) {
                    _fst_cli_output('error', "Skipped unsafe scaffold path: {$path}");
                    continue;
                }
                $full_path = FST_ROOT_DIR . '/' . ltrim($path, '/');
                $dir = dirname($full_path);
                if (!is_dir($dir) && !@mkdir($dir, 0755, true)) continue;

                $real_dir = realpath($dir);
                if (!$real_dir || !str_starts_with($real_dir, $fst_root_real)) {
                    _fst_cli_output('error', "Skipped path escaping project root: {$path}");
                    continue;
                }
                @file_put_contents($full_path, $content);
            }
            _fst_cli_output('success', 'Scaffold generated successfully.');
        }

        // Download AI brain file
        $brain_url = 'https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/v0.4/brain.md';
        $brain_ctx = stream_context_create(['http' => ['header' => "User-Agent: FullStuck CLI\r\n", 'timeout' => 10]]);
        $brain_content = @file_get_contents($brain_url, false, $brain_ctx);
        if ($brain_content) {
            @file_put_contents(FST_ROOT_DIR . '/brain_fullstuck.md', $brain_content);
            _fst_cli_output('success', 'AI brain file downloaded: brain_fullstuck.md');
        } else {
            _fst_cli_output('info', 'Warning: Could not download AI brain file. You can get it later via: php fullstuck.php docs:full');
        }


        _fst_cli_output('success', 'FullStuck initialized successfully!');
        return;
    } catch (Exception $e) { 
        _fst_cli_output('error', 'Initialization failed: ' . $e->getMessage());
        exit(1);
    }
}

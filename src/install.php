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
    ]);
});
PHP;
                @file_put_contents(FST_ROOT_DIR . '/router.php', $router_code);
            } 
            else if ($input_data['generate_starter'] === '1') {
                // Full Scaffold (Task/Auth SPA Hybrid)
                @mkdir(FST_ROOT_DIR . '/assets', 0755, true);
                
                // _layout.html
                $layout_html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App</title>
    <style>body{font-family:system-ui,sans-serif;max-width:800px;margin:2rem auto;padding:0 1rem}nav a{margin-right:1rem}.flash-msg{background:#d1fae5;border:1px solid #6ee7b7;padding:.5rem 1rem;border-radius:6px}.error-msg{background:#fee2e2;border:1px solid #fca5a5;padding:.5rem 1rem;border-radius:6px}main.fst-loading{opacity:.5;pointer-events:none;cursor:wait;transition:opacity .2s}</style>
</head>
<body>
    <nav>
        <a href="/" data-fst-fragment="main">Beranda</a>
        <a href="/tasks" data-fst-fragment="main">Tasks</a>
        <a href="/logout" data-fst-normal-load>Logout</a>
    </nav>
    <hr>
    <main></main>
    <script src="/assets/app.js"></script>
</body>
</html>
HTML;
                @file_put_contents(FST_ROOT_DIR . '/views/_layout.html', $layout_html);

                // index.html
                $index_html = <<<HTML
<section>
    <h1>Selamat Datang</h1>
    <p>Aplikasi FullStuck.php Anda siap digunakan.</p>
    <a href="/tasks">Lihat Tasks &rarr;</a>
</section>
HTML;
                @file_put_contents(FST_ROOT_DIR . '/views/index.html', $index_html);

                // login.html
                $login_html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    <p class="error-msg"></p>
    <form action="/login" method="POST">
        <div class="fst-csrf"></div>
        <div>
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div>
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Masuk</button>
    </form>
</body>
</html>
HTML;
                @file_put_contents(FST_ROOT_DIR . '/views/login.html', $login_html);

                // tasks.html
                $tasks_html = <<<HTML
<div>
    <h1>Daftar Tasks</h1>
    <p class="flash-msg"></p>
    <form action="/tasks" method="POST" data-fst-fragment="main" data-fst-indicator="fst-loading">
        <div class="fst-csrf"></div>
        <div>
            <input type="text" name="title" placeholder="Tugas baru..." required>
        </div>
        <div style="margin-top: 0.5rem;">
            <textarea name="description" placeholder="Deskripsi tugas (opsional)" rows="3" style="width: 100%;"></textarea>
        </div>
        <div style="margin-top: 0.5rem;">
            <button type="submit">Tambah</button>
        </div>
    </form>
    <ul class="task-list">
        <li>
            <span class="task-title">Nama Task</span>
            <a href="/tasks/1/detail" class="detail-link" data-fst-fragment="main">Detail</a>
            <form class="delete-form" method="POST" data-fst-no-history data-fst-fragment="main" style="display:inline;">
                <button type="submit">Hapus</button>
            </form>
        </li>
    </ul>
</div>
HTML;
                @file_put_contents(FST_ROOT_DIR . '/views/tasks.html', $tasks_html);

                // assets/app.js
                $app_js = <<<JS
fst.set('/preview', (params) => {
    document.querySelector('main').innerHTML = `  
        <h1>Preview Mode</h1>  
        <p>Ini dirender murni di browser, tanpa request ke PHP.</p>  
        <a href="/tasks" data-fst-fragment="main">Kembali ke Tasks</a>  
    `;
});

fst.set('/tasks/:id/detail', async (params) => {
    const main = document.querySelector('main');
    main.innerHTML = `<div style="text-align:center; padding:2rem;" class="fst-loading">Memuat detail task...</div>`;
    
    const res = await fetch(`/api/tasks/\${params.id}`);
    
    if (res.status === 404) {
        main.innerHTML = `
            <div style="text-align:center; padding: 2rem; color: red;">
                <h2>404 Not Found</h2>
                <p>Task dengan ID #\${params.id} tidak ditemukan di database.</p>
                <a href="/tasks" data-fst-fragment="main">Kembali</a>
            </div>
        `;
        return;
    }

    const task = await res.json();
    main.innerHTML = `  
        <h1>Detail Task #\${task.id}</h1>  
        <h3>\${task.title}</h3>
        <p>\${task.description ? task.description : '<i>Tidak ada deskripsi.</i>'}</p>  
        <small>Dibuat: \${task.created_at}</small>
        <br><br>
        <a href="/tasks" data-fst-fragment="main">&larr; Kembali ke Daftar Tasks</a>
    `;
});

// ─── Event Hooks ──────────────────────────────────────────────────────────────  
document.addEventListener('fst:loading', (e) => {
    console.log('FST: navigating to', e.detail.url);
});

document.addEventListener('fst:unload', () => {
    // Destroy plugins here
});

document.addEventListener('fst:load', () => {
    // Init plugins here
    console.log('FST: page loaded');
});
JS;
                @file_put_contents(FST_ROOT_DIR . '/assets/app.js', $app_js);

                // router.php
                $router_php = <<<PHP
<?php  
function cek_login(\$next) {  
    if (!fst_session_get('user_id')) {  
        fst_flash_set('error', 'Silakan login terlebih dahulu.');  
        return fst_redirect('/login');  
    }  
    return \$next();  
}  
  
fst_get('/', function() {  
    \$content = fst_template_render(FST_ROOT_DIR . '/views/index.html', [], []);  
    fst_template(FST_ROOT_DIR . '/views/_layout.html', [  
        'title' => 'Beranda',  
        'content' => \$content,  
    ], ['title' => '\$title', 'main' => ['@html' => '\$content']]);  
});  
  
fst_get('/login', function() {  
    \$error = fst_flash_get('error');
    fst_template(FST_ROOT_DIR . '/views/login.html', ['error' => \$error], [  
        'title' => '"Login"',  
        'p.error-msg' => ['@if' => '\$error !== null', '@text' => '\$error'],  
        '.fst-csrf' => ['@html' => 'fst_csrf_field()'],  
    ]);  
});  
  
fst_post('/login', function() {  
    fst_csrf_check();
    \$val = fst_validate(fst_request(), ['email' => 'required|email', 'password' => 'required|min:6']);  
    if (!\$val['valid']) {  
        fst_flash_set('error', implode(', ', array_merge(...array_values(\$val['errors']))));  
        fst_redirect('/login');  
    }  
    \$user = fst_db_row('users', ['email' => \$val['data']['email']]);  
    if (!\$user || !password_verify(\$val['data']['password'], \$user['password'])) {  
        fst_flash_set('error', 'Email atau password salah.');  
        fst_redirect('/login');  
    }  
    fst_session_set('user_id', \$user['id']);  
    fst_session_set('user_name', \$user['name']);  
    fst_redirect('/tasks');  
});  
  
fst_get('/logout', function() {  
    fst_session_forget('user_id');  
    fst_session_forget('user_name');  
    fst_redirect('/login');  
});  
  
fst_get('/tasks', function() {  
    \$tasks = fst_db_select('tasks', [], ['order_by' => 'id DESC']);  
    \$flash = fst_flash_get('msg');  
    \$content = fst_template_render(FST_ROOT_DIR . '/views/tasks.html', [  
        'tasks' => \$tasks,  
        'flashMsg' => \$flash,  
    ], [  
        'p.flash-msg' => ['@if' => '\$flashMsg !== null', '@text' => '\$flashMsg'],  
        '.fst-csrf' => ['@html' => 'fst_csrf_field()'],  
        'ul.task-list > li' => [  
            '@foreach' => '\$tasks as \$task',  
            'span.task-title' => '\$task["title"]',  
            'a.detail-link' => ['[href]' => '"/tasks/" . \$task["id"] . "/detail"'],
            'form.delete-form' => [  
                '[action]' => '"/tasks/delete/" . \$task["id"]',  
                '[method]' => '"POST"',  
                '@append' => 'fst_csrf_field()',  
            ],  
        ],  
    ]);  
    fst_template(FST_ROOT_DIR . '/views/_layout.html', [  
        'title' => 'Tasks',  
        'content' => \$content,  
    ], ['title' => '\$title', 'main' => ['@html' => '\$content']]);  
}, 'cek_login');  
  
fst_post('/tasks', function() {  
    fst_csrf_check();  
    \$val = fst_validate(fst_request(), [  
        'title' => 'required|min:3|max:100',
        'description' => 'optional|max:1000'
    ]);  
    if (!\$val['valid']) {  
        fst_flash_set('msg', 'Error: ' . \$val['errors']['title'][0] ?? 'Input tidak valid.');  
        fst_redirect('/tasks');  
    }  
    fst_db_insert('tasks', [
        'title' => \$val['data']['title'],
        'description' => \$val['data']['description'] ?? null
    ]);  
    fst_flash_set('msg', 'Task berhasil ditambahkan!');  
    fst_redirect('/tasks');
}, 'cek_login');  
  
fst_post('/tasks/delete/:id', function(\$params) {  
    fst_csrf_check();  
    fst_db_delete('tasks', ['id' => \$params['id']]);  
    fst_flash_set('msg', 'Task dihapus.');  
    fst_redirect('/tasks');  
}, 'cek_login');

function fst_spa_fallback() {
    fst_template(FST_ROOT_DIR . '/views/_layout.html', [
        'title' => 'Loading...',
        'content' => '<div style="text-align:center; padding: 2rem;" class="fst-loading">Loading...</div>'
    ], ['title' => '\$title', 'main' => ['@html' => '\$content']]);
}

fst_any('/preview', 'fst_spa_fallback');
fst_any('/tasks/{id}/detail', 'fst_spa_fallback');

fst_get('/api/tasks/{id:i}', function(\$id) {
    \$task = fst_db_row('tasks', ['id' => \$id]);
    if (!\$task) fst_abort(404, 'Task not found');
    fst_json(\$task);
});
PHP;
                @file_put_contents(FST_ROOT_DIR . '/router.php', $router_php);
                
                // seed.php
                $seed_php = <<<PHP
<?php
// Script untuk mengisi tabel users dan tasks perdana (jalankan manual jika perlu)
require __DIR__ . '/fullstuck.php';
\$db = new PDO('sqlite:' . FST_ROOT_DIR . '/' . fst_config('database.connections.main.database_path', 'database.sqlite'));
\$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, password TEXT)");
\$db->exec("CREATE TABLE IF NOT EXISTS tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
\$stmt = \$db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
\$stmt->execute(['Demo User', 'demo@example.com', password_hash('123456', PASSWORD_DEFAULT)]);
echo "Database seeded successfully.\\n";
PHP;
                @file_put_contents(FST_ROOT_DIR . '/seed.php', $seed_php);
            }
        }

        // Generate fullstuck_readme.md for AI assistance
        $ai_readme = <<<TXT
# FullStuck.php AI Assistant Guidelines

Welcome! You are working on a FullStuck.php project. 
FullStuck is a custom micro-framework. To understand its syntax, features, and strict rules, you MUST read the documentation before writing any code.

**Run the following command in your terminal to view the table of contents:**
`php fullstuck.php docs`

*(Hint: To read section 1, run `php fullstuck.php docs:1`)*
TXT;
        @file_put_contents(FST_ROOT_DIR . '/fullstuck_readme.md', $ai_readme);


        _fst_cli_output('success', 'FullStuck initialized successfully!');
        return;
    } catch (Exception $e) { 
        _fst_cli_output('error', 'Initialization failed: ' . $e->getMessage());
        exit(1);
    }
}

<?php
function fst_abort($code, $message = '') {
    $fst_config = fst_app('config');
    http_response_code($code);
    $handler_path = $fst_config['routing']['error_handlers'][$code] ?? null;
    if ($handler_path) {
        if (preg_match('/\.php$|\.html$/', $handler_path)) {
            if (file_exists(FST_ROOT_DIR . '/' . $handler_path)) {
                if (function_exists('fst_view')) fst_view($handler_path, ['error_code' => $code, 'error_message' => $message]);
                else require FST_ROOT_DIR . '/' . $handler_path;
                die();
            }
        } else {
            echo htmlspecialchars($handler_path); die();
        }
    }
    $default_titles = [404 => 'Not Found', 403 => 'Forbidden', 405 => 'Method Not Allowed', 500 => 'Internal Server Error'];
    $title = $default_titles[$code] ?? 'Error';
    $message_safe = htmlspecialchars($message);
    $html = <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error {$code}</title>
<style>body{font-family: sans-serif; text-align: center; padding-top: 50px;}</style>
</head><body><h1>Error {$code}: {$title}</h1><p>{$message_safe}</p></body></html>
HTML;
    echo $html; die();
}

function _fst_route($method, $path, $callback, $middleware = []) {
    $fst_config = fst_app('config');
    $fst_routes = fst_app('routes');
    $fst_route_prefix = fst_app('route_prefix');
    $fst_group_middleware = fst_app('group_middleware');
    
    $full_original_path = $fst_route_prefix . $path;
    if ($full_original_path !== '/' && str_ends_with($full_original_path, '/')) {
        $full_original_path = rtrim($full_original_path, '/');
    }
     if (!str_starts_with($full_original_path, '/')) {
        $full_original_path = '/' . $full_original_path;
    }

    $path_for_regex = $full_original_path;

    $shortcuts = $fst_config['routing']['regex_shortcuts'] ?? ['i'=>'([0-9]+)','a'=>'([a-zA-Z0-9]+)','s'=>'([a-zA-Z0-9\-]+)','any'=>'([^/]+)'];
    $default_regex = $shortcuts['any'] ?? '([^/]+)';

    // 1. Eksekusi pola parameter opsional DULU
    $final_pattern = preg_replace_callback(
        '/\{([a-zA-Z0-9_]+)(?::([a-z]))?\}\?/',
        function ($matches) use ($shortcuts, $default_regex) {
             $type = $matches[2] ?? 'any';
             $regex = $shortcuts[$type] ?? $default_regex;
             $regex = str_starts_with($regex, '(') ? $regex : '(' . $regex . ')';
             return "(?:/" . $regex . ")?";
        },
        $path_for_regex);
    
    // 2. Eksekusi pola parameter wajib
    $final_pattern = preg_replace_callback(
        '/\{([a-zA-Z0-9_]+)(?::([a-z]))?\}/',
        function ($matches) use ($shortcuts, $default_regex) {
             $type = $matches[2] ?? 'any';
             $regex = $shortcuts[$type] ?? $default_regex;
             return str_starts_with($regex, '(') ? $regex : '(' . $regex . ')';
        },
        $final_pattern);

    $final_pattern = '#^' . str_replace('/', '\/', $final_pattern) . '$#';

    // Strict Mode: Detect Duplicates
    foreach ($fst_routes[$method] ?? [] as $existing) {
        if ($existing[1] === $final_pattern) {
            fst_abort(500, "Duplicate route pattern detected: [{$method}] {$full_original_path} conflicts with an existing route pattern. Each route pattern must be unique.");
        }
    }

    if (!is_array($middleware)) $middleware = [$middleware];
    $combined_middleware = array_merge($fst_group_middleware ?? [], $middleware);

    if (!isset($fst_routes[$method])) $fst_routes[$method] = [];
    $fst_routes[$method][] = [$method, $final_pattern, $callback, $full_original_path, $combined_middleware];
    fst_app('routes', $fst_routes);
}

function fst_get($path, $callback, $middleware = []) { _fst_route('GET', $path, $callback, $middleware); }
function fst_post($path, $callback, $middleware = []) { _fst_route('POST', $path, $callback, $middleware); }
function fst_put($path, $callback, $middleware = []) { _fst_route('PUT', $path, $callback, $middleware); }
function fst_patch($path, $callback, $middleware = []) { _fst_route('PATCH', $path, $callback, $middleware); }
function fst_delete($path, $callback, $middleware = []) { _fst_route('DELETE', $path, $callback, $middleware); }
function fst_any($path, $callback, $middleware = []) { _fst_route('ANY', $path, $callback, $middleware); }

function fst_group($prefix, $callback, $middleware = []) {
    $parent_prefix = fst_app('route_prefix');
    $parent_middleware = fst_app('group_middleware') ?? [];
    
    $trimmed_prefix = trim($prefix, '/');
    if ($trimmed_prefix === '') {
        $fst_route_prefix = $parent_prefix;
    } else {
        $fst_route_prefix = rtrim($parent_prefix, '/') . '/' . $trimmed_prefix;
    }
    fst_app('route_prefix', $fst_route_prefix);
    
    if (!is_array($middleware)) $middleware = [$middleware];
    $fst_group_middleware = array_merge($parent_middleware, $middleware);
    fst_app('group_middleware', $fst_group_middleware);
    
    call_user_func($callback);
    
    fst_app('route_prefix', $parent_prefix);
    fst_app('group_middleware', $parent_middleware);
}

function _fst_get_request_paths() {
    $fst_config = fst_app('config');
    $request_uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $base_path_config = $fst_config['routing']['base_path'] ?? '/';
    if ($base_path_config !== '/' && str_starts_with($request_uri_path, $base_path_config)) {
        $request_uri_path = substr($request_uri_path, strlen($base_path_config));
    }
    if (!str_starts_with($request_uri_path, '/')) $request_uri_path = '/' . $request_uri_path;
    $absolute_path = FST_ROOT_DIR . $request_uri_path;
    
    return [
        'uri_path' => $request_uri_path,
        'absolute_path' => $absolute_path
    ];
}

function _fst_is_protected_file($absolute_path) {
    // Protect core framework file and configuration file
    $normalized_abs_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absolute_path);
    $normalized_file_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, FST_ROOT_DIR . '/fullstuck.php');
    $normalized_config_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, FST_CONFIG_FILE);

    return ($normalized_abs_path === $normalized_file_path || $normalized_abs_path === $normalized_config_path);
}

function _fst_serve_static_asset($request_uri_path, $absolute_path) {
    $fst_config = fst_app('config');
    $public_folders = $fst_config['routing']['public_folders'] ?? [];
    foreach ($public_folders as $folder) {
        $clean_folder = trim($folder, '/');
        if (str_starts_with(ltrim($request_uri_path, '/'), $clean_folder . '/')) {
            if (is_file($absolute_path)) {
                fst_serve_static_file($absolute_path); 
                die(); // Halt execution after serving static file
            }
            break; // Stop checking other public folders if prefix matches but file doesn't exist
        }
    }
    return false;
}

function _fst_match_static_routes() {
    $fst_routes = fst_app('routes');
    $uri = fst_uri();
    $method = fst_method();
    
    // AMBIL BUCKET YANG SESUAI SAJA:
    $routes_to_check = array_merge($fst_routes[$method] ?? [], $fst_routes['ANY'] ?? []);
    
    foreach ($routes_to_check as $route) {
        list($route_method, $pattern, $callback) = $route;
        if ($route_method !== 'ANY' && $route_method !== $method) continue;
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Remove the full match string
            
            $middleware_list = $route[4] ?? [];

            // 1. Definisikan INTI bawang (eksekusi callback rute utama)
            $next = function() use ($callback, $matches) {
                return call_user_func_array($callback, $matches);
            };

            // 2. Balik urutan middleware agar dibungkus rapi dari luar ke dalam
            $middleware_list = array_reverse($middleware_list);

            // 3. Bungkus inti dengan lapisan middleware
            foreach ($middleware_list as $mw) {
                if (is_callable($mw)) {
                    $current_next = $next;
                    
                    $next = function() use ($mw, $current_next) {
                        $called = false;
                        $next_wrapper = function() use ($current_next, &$called) {
                            $called = true;
                            return $current_next();
                        };

                        // Eksekusi middleware, kirim fungsi next ke dalamnya
                        $result = call_user_func($mw, $next_wrapper);
                        
                        // --- MAGIC FULLSTUCK (BACKWARD COMPATIBILITY & SECURITY) ---
                        // Jika middleware mengembalikan false secara eksplisit, hentikan dan tolak akses.
                        if ($result === false) {
                            fst_abort(403, 'Forbidden by Middleware');
                        }
                        
                        // STRICT MODE: Jika middleware tidak memanggil $next() dan hanya mengembalikan null (lupa return), lemparkan error 500!
                        if (!$called && $result === null) {
                            fst_abort(500, "Middleware Logic Error: Middleware did not explicitly return a value or call \$next(). Security check failed."); 
                        }
                        
                        return $result;
                    };
                }
            }

            // 4. Jalankan seluruh bungkusan dari lapisan terluar
            $next();
            
            fst_app('route_found', true); 
            return true; 
        }
    }
    return false;
}

function fst_run() {
    // TAMBAHKAN BARIS INI:
    fst_app('route_found', false); 

    // [PATCH] Security Headers Global
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    ob_start();
    $handled = false;
    
    $req = _fst_get_request_paths(); 
    
    // [PATCH] Serve FST Agent JS
    if ($req['uri_path'] === '/fst-agent.js') {
        $agent_mode = fst_config('agent_js', false);
        if ($agent_mode === true || $agent_mode === '1') {
            header('Content-Type: application/javascript');
            // Cache strongly since it's framework code
            header('Cache-Control: public, max-age=31536000, immutable');
            echo defined('FST_AGENT_JS_CODE') ? FST_AGENT_JS_CODE : '';
            exit(0);
        }
    }

    if (_fst_is_protected_file($req['absolute_path'])) {
        fst_abort(404);
        $handled = true;
    }
    if (!$handled) {
        if (_fst_serve_static_asset($req['uri_path'], $req['absolute_path'])) {
            $handled = true;
        }
    }
    if (!$handled) {
        if (_fst_match_static_routes()) {
            $handled = true;
        }
    }
    
    if (!$handled && !fst_app('route_found')) {
        fst_abort(404);
    }
    
    $output = ob_get_clean();
    if ($output === false) $output = ''; // Guard: buffer sudah di-flush oleh exception handler

    // Evaluasi Opsi Agent JS
    $agent_mode = fst_config('agent_js', false);
    $should_inject_agent = false;

    if ($agent_mode === true || $agent_mode === '1') {
        $should_inject_agent = true;
    }


    if (fst_is_fragment_request()) {
        $target = fst_fragment_target();
        
        // 1. Selamatkan tag <title> dari output asli sebelum dipotong
        $title_tag = '';
        if (preg_match('/<title[^>]*>.*?<\/title>/is', $output, $matches)) {
            $title_tag = $matches[0];
        }

        // [PATCH] Ambil atribut dari tag <body> asli dan kirim via Header
        if ($target === 'body' && preg_match('/<body([^>]*)>/is', $output, $matches)) {
            header('X-FST-Body-Attrs: ' . trim($matches[1]));
        }

        // 2. Potong HTML sesuai target (biasanya 'body')
        $output = fst_extract_html_fragment($output, $target); 

        // 3. Sisipkan kembali tag <title> ke puncak fragmen agar terbaca oleh SPA Agent
        if (!empty($title_tag)) {
            $output = $title_tag . "\n" . $output;
        }
    } 
    else if ($should_inject_agent) {
        $script_id = fst_config('fragment.script_id', 'fst-agent');
        $req_header = fst_config('fragment.header_request', 'X-FST-Request');
        $target_header = fst_config('fragment.header_target', 'X-FST-Target');
        $indicator_class = fst_config('fragment.indicator_class', 'fst-loading');
        $history_cache = fst_config('fragment.history_cache', false) ? ' data-history-cache="true"' : '';
        $inject_id = $script_id ? 'id="'.$script_id.'" data-req-header="'.$req_header.'" data-target-header="'.$target_header.'" data-indicator-class="'.$indicator_class.'"'.$history_cache : '';
        $script_tag = "<script src=\"/fst-agent.js\" {$inject_id}></script>";
        
        if (stripos($output, '</body>') !== false) {
            $output = str_ireplace('</body>', $script_tag . "\n</body>", $output);
        } else {
            $output .= "\n" . $script_tag;
        }
    }

    echo $output;
}

?>

<?php
function fst_abort($code, $message = '') {
    http_response_code($code);
    
    $uri = function_exists('fst_uri') ? fst_uri() : ($_SERVER['REQUEST_URI'] ?? '/');
    $isApi = str_starts_with($uri, '/api') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'code' => $code,
            'message' => $message ?: "HTTP Error {$code}"
        ]);
        die();
    }
    
    // Default fallback UI
    $default_titles = [404 => 'Not Found', 403 => 'Forbidden', 405 => 'Method Not Allowed', 500 => 'Internal Server Error'];
    $title = $default_titles[$code] ?? 'Error';
    
    $route = fst_app('current_route');
    $view_path = $route['view'] ?? '';
    $layouts = $route['layouts'] ?? [];
    
    // 1. Search for {$code}.fst.php traversing up from the error folder to the app/ folder
    if (!empty($view_path)) {
        $dir = dirname(FST_ROOT_DIR . '/' . $view_path);
        while ($dir && str_starts_with($dir, FST_ROOT_DIR . '/app')) {
            $error_file = $dir . "/{$code}.fst.php";
            if (file_exists($error_file)) {
                $error_route = [
                    'view' => str_replace(FST_ROOT_DIR . '/', '', $error_file),
                    'layouts' => $layouts
                ];
                fst_app('shared_view_data', ['error_code' => $code, 'error_message' => $message]);
                fst_render_view($error_route);
                die();
            }
            if ($dir === FST_ROOT_DIR . '/app') break;
            $dir = dirname($dir);
        }
    }
    
    // 2. Check root /app/ if no active route or not found
    $error_file = FST_ROOT_DIR . "/app/{$code}.fst.php";
    if (file_exists($error_file)) {
        $error_route = [
            'view' => "app/{$code}.fst.php",
            'layouts' => file_exists(FST_ROOT_DIR . '/app/_layout.fst.php') ? ['app/_layout.fst.php'] : []
        ];
        fst_app('shared_view_data', ['error_code' => $code, 'error_message' => $message]);
        fst_render_view($error_route);
        die();
    }
    
    // 3. Fallback to Hardcoded UI
    $message_safe = htmlspecialchars($message);
    $html = <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error {$code}</title>
<style>body{font-family: sans-serif; text-align: center; padding-top: 50px;}</style>
</head><body><h1>Error {$code}: {$title}</h1><p>{$message_safe}</p></body></html>
HTML;
    echo $html; die();
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
    $root = realpath(FST_ROOT_DIR);
    
    foreach ($public_folders as $folder) {
        $clean_folder = trim($folder, '/');
        if (str_starts_with(ltrim($request_uri_path, '/'), $clean_folder . '/')) {
            $real = realpath($absolute_path);
            if ($real && $root && str_starts_with($real, $root . DIRECTORY_SEPARATOR . $clean_folder . DIRECTORY_SEPARATOR) && is_file($real)) {
                fst_serve_static_file($real); 
                die(); // Halt execution after serving static file
            }
            break; // Stop checking other public folders if prefix matches but file doesn't exist
        }
    }
    return false;
}

function _fst_get_route_cache() {
    $cache_dir = FST_ROOT_DIR . '/cache';
    $cache_file = $cache_dir . '/cache-router.php';
    
    $is_dev = fst_is_dev();
    
    if (file_exists($cache_file)) {
        if (!$is_dev) {
            return require $cache_file;
        } else {
            $app_dir = FST_ROOT_DIR . '/app';
            $cache_time = filemtime($cache_file);
            $needs_rebuild = false;
            
            if (is_dir($app_dir)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($app_dir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                if (filemtime($app_dir) > $cache_time) {
                    $needs_rebuild = true;
                } else {
                    foreach ($iterator as $item) {
                        if ($item->isDir() && $item->getMTime() > $cache_time) {
                            $needs_rebuild = true;
                            break;
                        }
                    }
                }
            }
            if (!$needs_rebuild) {
                return require $cache_file;
            }
        }
    }
    
    $routes = ['STATIC' => [], 'DYNAMIC' => []];
    
    $scan = function($dir, $route_path, $layouts, $guards) use (&$scan, &$routes) {
        if (!is_dir($dir)) return;
        
        $rel = function($p) {
            $nP = str_replace('\\', '/', $p);
            $nR = str_replace('\\', '/', FST_ROOT_DIR);
            return str_starts_with($nP, $nR . '/') ? substr($nP, strlen($nR) + 1) : $nP;
        };
        
        $local_layout = $dir . '/_layout.fst.php';
        if (file_exists($local_layout)) {
            $layouts[] = $rel($local_layout);
        }
        
        $local_guard = $dir . '/_guard.php';
        if (file_exists($local_guard)) {
            $guards[] = $rel($local_guard);
        }
        
        $is_dynamic = strpos($route_path, '[') !== false;
        $clean_route = $route_path === '' ? '/' : $route_path;
        
        $content_file = $dir . '/content.fst.php';
        $client_file = $dir . '/client.js';
        $has_client = file_exists($client_file) ? $rel($client_file) : null;
        
        if (file_exists($content_file)) {
            $rel_content = $rel($content_file);
            $route_data = ['view' => $rel_content, 'layouts' => $layouts, 'guards' => $guards];
            if ($has_client) $route_data['client'] = $has_client;
            
            if ($is_dynamic) {
                $regex = preg_replace('/\[([^\]]+)\]/', '([^/]+)', $clean_route);
                $regex = '#^' . $regex . '$#';
                preg_match_all('/\[([^\]]+)\]/', $clean_route, $matches);
                $route_data['params'] = $matches[1];
                $routes['DYNAMIC']['GET'][$regex] = $route_data;
            } else {
                $routes['STATIC']['GET'][$clean_route] = $route_data;
            }
        }
        
        $action_file = $dir . '/action.php';
        if (file_exists($action_file)) {
            $rel_action = $rel($action_file);
            $route_data = ['handler' => $rel_action, 'guards' => $guards];
            
            $allowed_methods = !file_exists($content_file) ? ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] : ['POST', 'PUT', 'DELETE', 'PATCH'];
            
            if ($is_dynamic) {
                $regex = preg_replace('/\[([^\]]+)\]/', '([^/]+)', $clean_route);
                $regex = '#^' . $regex . '$#';
                preg_match_all('/\[([^\]]+)\]/', $clean_route, $matches);
                $route_data['params'] = $matches[1];
                foreach ($allowed_methods as $m) {
                    $routes['DYNAMIC'][$m][$regex] = $route_data;
                }
            } else {
                foreach ($allowed_methods as $m) {
                    $routes['STATIC'][$m][$clean_route] = $route_data;
                }
            }
        }
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $scan($path, $route_path . '/' . $item, $layouts, $guards);
            }
        }
    };
    
    if (is_dir(FST_ROOT_DIR . '/app')) {
        $scan(FST_ROOT_DIR . '/app', '', [], []);
    }
    
    if (!is_dir($cache_dir)) mkdir($cache_dir, 0755, true);
    $export = var_export($routes, true);
    $content = "<?php\nif(!defined('FST_ROOT_DIR')) { http_response_code(403); exit('Forbidden'); }\n// Auto-generated by FullStuck v0.4\nreturn " . $export . ";\n";
    file_put_contents($cache_file, $content, LOCK_EX);
    
    return $routes;
}

function _fst_execute_route($route, $matches = []) {
    // 1. Execute Guards (Russian Doll level outside-in)
    if (!empty($route['guards'])) {
        foreach ($route['guards'] as $guard) {
            $guard_path = FST_ROOT_DIR . '/' . $guard;
            if (file_exists($guard_path)) {
                require $guard_path;
            }
        }
    }
    
    // 2. Execute Action / Handler
    if (isset($route['handler'])) {
        require FST_ROOT_DIR . '/' . $route['handler'];
        fst_app('route_found', true);
        return true;
    } 
    // 3. Execute View & Layouts
    elseif (isset($route['view'])) {
        fst_app('current_route', $route);
        fst_render_view($route);
        fst_app('route_found', true);
        return true;
    }
    
    return false;
}

function _fst_match_colocation_routes() {
    $routes = _fst_get_route_cache();
    $uri = fst_uri();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // 1. Check static routes
    if (isset($routes['STATIC'][$method][$uri])) {
        $route = $routes['STATIC'][$method][$uri];
        return _fst_execute_route($route, []);
    }
    
    // 2. Check dynamic routes
    if (isset($routes['DYNAMIC'][$method])) {
        foreach ($routes['DYNAMIC'][$method] as $regex => $route) {
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); // discard full match
                
                // Inject param into $_GET and $_REQUEST (Per Addendum #1)
                $params = $route['params'] ?? [];
                foreach ($params as $idx => $key) {
                    if (isset($matches[$idx])) {
                        $_GET[$key] = $matches[$idx];
                        $_REQUEST[$key] = $matches[$idx];
                    }
                }
                
                return _fst_execute_route($route, $matches);
            }
        }
    }
    
    return false;
}

function fst_run() {
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
        if (_fst_match_colocation_routes()) {
            $handled = true;
        }
    }
    
    if (!$handled && !fst_app('route_found')) {
        fst_abort(404);
    }
    
    $output = ob_get_clean();
    if ($output === false) $output = ''; // Guard: buffer already flushed by exception handler

    // Evaluate Agent JS Option
    $agent_mode = fst_config('agent_js', false);
    $should_inject_agent = false;

    if ($agent_mode === true || $agent_mode === '1') {
        $should_inject_agent = true;
    }
    
    // HTML Whitelist: Only inject script if no specific Content-Type or Content-Type is text/html
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            if (stripos($header, 'text/html') === false) {
                $should_inject_agent = false;
            }
            break;
        }
    }
    if (fst_is_fragment_request()) {
        $target = fst_fragment_target();
        
        // 1. Rescue <title> tag from original output before truncating
        $title_tag = '';
        if (preg_match('/<title[^>]*>.*?<\/title>/is', $output, $matches)) {
            $title_tag = $matches[0];
        }

        // [PATCH] Get attributes from original <body> tag and send via Header
        if ($target === 'body' && preg_match('/<body([^>]*)>/is', $output, $matches)) {
            header('X-FST-Body-Attrs: ' . trim($matches[1]));
        }

        // 2. Truncate HTML according to target (usually 'body')
        $output = fst_extract_html_fragment($output, $target); 

        // 3. Re-insert <title> tag to the top of the fragment so it is read by the SPA Agent
        if (!empty($title_tag)) {
            $output = $title_tag . "\n" . $output;
        }
    } 
    else if ($should_inject_agent) {
        $history_cache = fst_config('fragment.history_cache', false) ? ' data-history-cache="true"' : '';
        $script_tag = '<script src="/fst-agent.js" id="fst-agent"' . $history_cache . '></script>';
        
        if (stripos($output, '</head>') !== false) {
            $output = str_ireplace('</head>', $script_tag . "\n</head>", $output);
        } elseif (stripos($output, '<body>') !== false) {
            $output = str_ireplace('<body>', "<body>\n" . $script_tag, $output);
        } else {
            $output = $script_tag . "\n" . $output;
        }
    }

    echo $output;
}

?>

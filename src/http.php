<?php
function fst_uri() {
    $fst_config = fst_app('config');
    $base_path = $fst_config['routing']['base_path'] ?? '/';
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    if ($base_path !== '/' && str_starts_with($uri, $base_path)) {
        $uri = substr($uri, strlen($base_path));
    }
    if (!str_starts_with($uri, '/')) $uri = '/' . $uri;
    if ($uri !== '/' && str_ends_with($uri, '/')) $uri = rtrim($uri, '/');
    return $uri ?: '/';
}
function fst_method() { return $_SERVER['REQUEST_METHOD']; }
function _fst_parsed_body() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = array_merge($_GET, $_POST);
    if (empty($_POST)) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $cache = array_merge($cache, $json);
            } else {
                // Fallback untuk application/x-www-form-urlencoded (PUT/PATCH)
                parse_str($raw, $parsed_vars);
                if (is_array($parsed_vars)) {
                    $cache = array_merge($cache, $parsed_vars);
                }
            }
        }
    }
    return $cache;
}
function fst_input($key, $default = null) { $data = _fst_parsed_body(); return $data[$key] ?? $default; }
function fst_request() { return _fst_parsed_body(); }
function fst_file($key) { return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK ? $_FILES[$key] : null; }
function fst_escape($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function e($str) { return fst_escape($str); }

function fst_json($data, $status = 200) { fst_status_code($status); header('Content-Type: application/json'); echo json_encode($data); die(); }
function fst_text($string, $status = 200) { fst_status_code($status); header('Content-Type: text/plain'); echo $string; die(); }
function fst_redirect($url, $code = 302, $allow_external = false) {
    $fst_config = fst_app('config');
    $base_path = $fst_config['routing']['base_path'] ?? '/';
    
    // [PATCH] Cegah Protocol-Relative URL Bypass (e.g., //evil.com)
    if (str_starts_with($url, '//')) {
        fst_abort(403, 'Protocol-relative redirect is not allowed.');
    }

    if (preg_match('/^https?:\/\//', $url)) {
        if (!$allow_external) {
            $url_host = parse_url($url, PHP_URL_HOST);
            $self_host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $self_host = preg_replace('/:\d+$/', '', $self_host);
            if ($url_host !== null && strtolower($url_host) !== strtolower($self_host)) {
                fst_abort(403, 'Redirect to external domain is not allowed. Use fst_redirect($url, 302, true) to allow.');
            }
        }
    } else {
        $url = '/' . ltrim($url, '/');
        $url = rtrim($base_path, '/') . $url;
    }
    
    if (fst_is_fragment_request()) {
        header("X-FST-Redirect: " . $url);
        die();
    }
    header("Location: " . $url, true, $code);
    die();
}
function fst_status_code($code) { http_response_code($code); }

function fst_session_set($key, $value) { $_SESSION[$key] = $value; }
function fst_session_get($key, $default = null) { return $_SESSION[$key] ?? $default; }
function fst_session_forget($key) { unset($_SESSION[$key]); }
function fst_flash_set($key, $message) { $_SESSION['_flash'][$key] = $message; }
function fst_flash_has($key) { return isset($_SESSION['_flash'][$key]); }
function fst_flash_get($key, $default = null) { $message = $_SESSION['_flash'][$key] ?? $default; unset($_SESSION['_flash'][$key]); return $message; }

function fst_csrf_token() { if (empty($_SESSION['_csrf_token'])) $_SESSION['_csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['_csrf_token']; }
function fst_csrf_field() { return '<input type="hidden" name="_token" value="' . fst_csrf_token() . '">'; }
function fst_csrf_check() {
    $data = fst_request(); // [PATCH] Mendukung PHP://input (JSON)
    $submitted_token = $data['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$submitted_token || !hash_equals(fst_csrf_token(), $submitted_token)) fst_abort(403, 'Invalid CSRF token.');
}

function fst_upload($key, $folder, $options = []) {
    if (!isset($_FILES[$key])) return ['success' => false, 'error' => 'No file uploaded.', 'path' => null];
    
    $files_input = $_FILES[$key];
    $is_multiple = is_array($files_input['name']);
    
    $process_single = function($name, $tmp_name, $size, $error) use ($folder, $options) {
        if ($error !== UPLOAD_ERR_OK) return ['success' => false, 'error' => 'Upload error code: ' . $error, 'path' => null];
        
        $max_size_kb = $options['max_size'] ?? 2048;
        if ($size > $max_size_kb * 1024) return ['success' => false, 'error' => "File is too large (max {$max_size_kb} KB).", 'path' => null];
        
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!empty($options['allowed_types']) && !in_array($ext, $options['allowed_types'])) {
            return ['success' => false, 'error' => "Extension `{$ext}` is not allowed.", 'path' => null];
        }
        
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $actual_mime = $finfo->file($tmp_name);
            $blocked_mimes = ['application/x-httpd-php', 'application/x-httpd-php-source', 'application/php', 'text/x-php', 'text/php'];
            if (in_array(strtolower($actual_mime), $blocked_mimes)) {
                return ['success' => false, 'error' => "Security Error: Malicious file signature detected.", 'path' => null];
            }
            if (!empty($options['allowed_mimes']) && !in_array($actual_mime, $options['allowed_mimes'])) {
                return ['success' => false, 'error' => "Invalid MIME type: " . $actual_mime, 'path' => null];
            }
        }
        
        $safe_basename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($name, ".".$ext));
        $filename = $safe_basename . '-' . uniqid() . '.' . $ext;
        $destination_folder = rtrim(FST_ROOT_DIR . '/' . trim($folder, '/'), '/');
        if (!is_dir($destination_folder) && !mkdir($destination_folder, 0755, true)) return ['success' => false, 'error' => "Failed to create upload directory.", 'path' => null];
        
        $real_destination = realpath($destination_folder);
        if (!$real_destination || !str_starts_with($real_destination, realpath(FST_ROOT_DIR))) {
            return ['success' => false, 'error' => 'Security Error: Invalid upload directory path.', 'path' => null];
        }
        
        $destination_path = $real_destination . DIRECTORY_SEPARATOR . $filename;
        $public_path = trim($folder, '/') . '/' . $filename;
        
        if (move_uploaded_file($tmp_name, $destination_path)) {
            return ['success' => true, 'path' => $public_path, 'error' => null, 'original_name' => $name];
        }
        return ['success' => false, 'error' => 'Failed to move uploaded file.', 'path' => null];
    };
    
    if (!$is_multiple) {
        return $process_single($files_input['name'], $files_input['tmp_name'], $files_input['size'], $files_input['error']);
    } else {
        $results = [];
        for ($i = 0; $i < count($files_input['name']); $i++) {
            $results[] = $process_single($files_input['name'][$i], $files_input['tmp_name'][$i], $files_input['size'][$i], $files_input['error'][$i]);
        }
        return $results;
    }
}
?>

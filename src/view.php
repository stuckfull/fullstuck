<?php
function fst_view_share($key, $value = null) {
    $shared = fst_app('shared_view_data') ?? [];
    if (is_array($key)) {
        foreach ($key as $k => $v) {
            $shared[$k] = $v;
        }
    } else {
        $shared[$key] = $value;
    }
    fst_app('shared_view_data', $shared);
}

function fst_view($path, $data = []) {
    $__fst_file = realpath(FST_ROOT_DIR . '/' . $path);
    $__fst_root = realpath(FST_ROOT_DIR);
    if (!$__fst_file || !$__fst_root || !str_starts_with($__fst_file, $__fst_root)) {
        fst_abort(500, "Invalid view path.");
    }
    $ext = strtolower(pathinfo($__fst_file, PATHINFO_EXTENSION));
    if (!in_array($ext, ['php', 'html', 'htm'])) {
        fst_abort(403, "Invalid view extension. Only php, html, and htm are allowed.");
    }
    $shared = fst_app('shared_view_data') ?? [];
    extract($shared, EXTR_SKIP);
    extract($data, EXTR_SKIP);
    require $__fst_file;
}
function fst_partial($path, $data = []) { fst_view($path, $data); }

function fst_serve_static_file($file_path) {
    $fst_config = fst_app('config');
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    $mime_types = $fst_config['mime_types'] ?? [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'svg'  => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'html' => 'text/html',
        'txt'  => 'text/plain',
        'map'  => 'application/json'
    ];
    
    $content_type = $mime_types[$ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: public, max-age=31536000');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
    
    readfile($file_path);
}

?>

<?php


function fst_compile_blade($templatePath) {
    $cacheDir = FST_ROOT_DIR . '/cache/views';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
    
    $relPath = str_replace(FST_ROOT_DIR, '', $templatePath);
    $relPath = trim(str_replace(['\\', '/'], '__', $relPath), '_');
    $cacheFile = $cacheDir . '/__' . $relPath . '.php';
    
    if (!fst_is_dev() && file_exists($cacheFile)) {
        return $cacheFile;
    }
    
    if (fst_is_dev() && file_exists($cacheFile)) {
        if (filemtime($templatePath) <= filemtime($cacheFile)) {
            return $cacheFile;
        }
    }
    
    $content = file_get_contents($templatePath);
    
    // Support @{{ escaped }} tags for JSON/JS frontend frameworks
    $content = str_replace('@{{', '@@FST_ESCAPE_OPEN@@', $content);
    $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?= e($1) ?>', $content);
    $content = str_replace('@@FST_ESCAPE_OPEN@@', '{{', $content);
    
    $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?= $1 ?>', $content);
    
    // Balanced parenthesis directives: @if(...), @elseif(...), @foreach(...)
    $content = preg_replace_callback('/@(if|elseif|foreach)\s*(\((?:[^)(]+|(?2))*\))/', function($m) {
        $directive = $m[1];
        $args = substr($m[2], 1, -1);
        if ($directive === 'if') return "<?php if({$args}): ?>";
        if ($directive === 'elseif') return "<?php elseif({$args}): ?>";
        if ($directive === 'foreach') return "<?php foreach({$args}): ?>";
        return $m[0];
    }, $content);
    
    $content = str_replace('@else', '<?php else: ?>', $content);
    $content = str_replace('@endif', '<?php endif; ?>', $content);
    $content = str_replace('@endforeach', '<?php endforeach; ?>', $content);
    
    // @yield('content', 'default')
    $content = preg_replace('/@yield\s*\(\'([^\']+)\'(?:,\s*\'([^\']+)\')?\)/', '<?= $__sections[\'$1\'] ?? \'$2\' ?>', $content);
    
    // @section('content')
    $content = preg_replace('/@section\s*\(\'([^\']+)\'\)/', '<?php ob_start(); $__section_name = \'$1\'; ?>', $content);
    $content = str_replace('@endsection', '<?php $__sections[$__section_name] = ob_get_clean(); ?>', $content);
    
    // @component('name', ['data' => 1])
    $content = preg_replace('/@component\s*\(\'([^\']+)\'\s*(?:,\s*(.+))?\)/', '<?php fst_render_component(\'$1\', $2 ?? []); ?>', $content);
    
    $lock = "<?php if(!defined('FST_ROOT_DIR')) { http_response_code(403); exit('Forbidden'); } /* FST_SOURCE_FILE: {$templatePath} */ ?>\n";
    file_put_contents($cacheFile, $lock . $content, LOCK_EX);
    return $cacheFile;
}

function fst_render_component($name, $data = []) {
    $path = FST_ROOT_DIR . '/components/' . $name . '.fst.php';
    if (!file_exists($path)) {
        echo "<!-- Component {$name} not found -->";
        return;
    }
    $compiled = fst_compile_blade($path);
    
    // IIFE Isolation
    call_user_func(function() use ($compiled, $data) {
        $shared = fst_app('shared_view_data') ?? [];
        extract($shared, EXTR_SKIP);
        extract($data, EXTR_SKIP);
        global $__sections; // pass sections down
        require $compiled;
    });
}

function fst_wrap_iife($jsCode) {
    $trimmed = trim($jsCode);
    if (preg_match('/^\s*\(\s*(?:function\s*\([^\)]*\)\s*\{|(?:\([^\)]*\)|[a-zA-Z0-9_]+)\s*=>\s*\{)/', $trimmed) && preg_match('/\}\s*\)\s*\(\s*\)\s*;?\s*$/', $trimmed)) {
        return $jsCode;
    }
    return "(() => {\n" . $jsCode . "\n})();";
}

function fst_render_view($route) {
    global $__sections;
    $__sections = [];
    
    // Shared data
    $shared = fst_app('shared_view_data') ?? [];
    
    // 1. Execute content.fst.php first
    $viewPath = FST_ROOT_DIR . '/' . $route['view'];
    if (file_exists($viewPath)) {
        $compiledView = fst_compile_blade($viewPath);
        
        ob_start();
        call_user_func(function() use ($compiledView, $shared) {
            extract($shared, EXTR_SKIP);
            global $__sections;
            require $compiledView;
        });
        $contentOutput = ob_get_clean();
        
        if (trim($contentOutput) !== '') {
            $__sections['content'] = ($__sections['content'] ?? '') . $contentOutput;
        }
    }
    
    // 2. Wrap with layouts from innermost to outermost (Reverse Russian Doll)
    if (!empty($route['layouts'])) {
        $layouts = array_reverse($route['layouts']);
        foreach ($layouts as $layout) {
            $layoutPath = FST_ROOT_DIR . '/' . $layout;
            if (file_exists($layoutPath)) {
                $compiledLayout = fst_compile_blade($layoutPath);
                
                ob_start();
                call_user_func(function() use ($compiledLayout, $shared) {
                    extract($shared, EXTR_SKIP);
                    global $__sections;
                    require $compiledLayout;
                });
                $layoutOutput = ob_get_clean();
                
                if (trim($layoutOutput) !== '') {
                    $__sections['content'] = $layoutOutput;
                }
            }
        }
    }
    
    // 3. Output the final HTML
    $final_html = $__sections['content'] ?? '';
    
    // 4. Inject client.js
    if (isset($route['client'])) {
        $clientPath = FST_ROOT_DIR . '/' . $route['client'];
        if (file_exists($clientPath)) {
            $jsCode = file_get_contents($clientPath);
            $jsCode = fst_wrap_iife($jsCode);
            $scriptTag = "<script>\n{$jsCode}\n</script>";
            
            if (stripos($final_html, '</body>') !== false) {
                $final_html = str_ireplace('</body>', $scriptTag . "\n</body>", $final_html);
            } else {
                $final_html .= "\n" . $scriptTag;
            }
        }
    }
    
    echo $final_html;
}

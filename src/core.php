<?php
if (session_status() === PHP_SESSION_NONE) { 
    $is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    
    session_set_cookie_params([
        'lifetime' => 0, 
        'path' => '/',
        'domain' => '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start(); 
}
define('FST_VERSION', '0.4.0');
define('FST_DOCS_URL', 'https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/v0.4/index.md');
if (!defined('FST_ROOT_DIR')) {
    $root = __DIR__;
    if (php_sapi_name() === 'cli-server') {
        $root = $_SERVER['DOCUMENT_ROOT'];
    } elseif (php_sapi_name() === 'cli') {
        $root = getcwd();
    }
    define('FST_ROOT_DIR', realpath($root) ?: $root);
}
define('FST_CONFIG_FILE', FST_ROOT_DIR . DIRECTORY_SEPARATOR . 'fullstuck.json');

// Handle CLI Commands
if (php_sapi_name() === 'cli') {
    global $argv;
    if (!isset($argv[1])) {
        echo "🚀 FullStuck.php v" . FST_VERSION . "\n";
        echo "Usage:\n";
        echo "  php fullstuck.php init  : Initialize a new project\n";
        echo "  php fullstuck.php docs  : Read the framework documentation (use docs:1, docs:2, etc.)\n";
        echo "  php -S localhost:8000 fullstuck.php : Start local web server\n";
        exit(0);
    }
    
    if (isset($argv[1])) {
        if ($argv[1] === 'init') {
            if (file_exists(FST_CONFIG_FILE)) {
                echo "Error: fullstuck.json already exists. Delete it first if you want to re-initialize.\n";
                exit(1);
            }
            fst_handle_installation();
            exit(0);
        }
        if (strpos($argv[1], 'docs') === 0) {
            $base_url = 'https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/v0.4/';
            $map = ['docs'=>'index.md', 'docs:1'=>'01-getting-started.md', 'docs:2'=>'02-routing.md', 'docs:3'=>'03-templates.md', 'docs:4'=>'04-components.md', 'docs:5'=>'05-fst-agent.md', 'docs:6'=>'06-action-api.md', 'docs:7'=>'07-security.md', 'docs:8'=>'08-database.md', 'docs:9'=>'09-config.md', 'docs:10'=>'10-logging.md', 'docs:11'=>'11-api-reference.md', 'docs:12'=>'12-cookbook.md', 'docs:13'=>'13-monolith-spa.md', 'docs:full'=>'FULL.md'];
            $cmd = $argv[1];
            if (isset($map[$cmd])) {
                $context = stream_context_create(['http' => ['header' => "User-Agent: FullStuck CLI\r\n"]]);
                $content = @file_get_contents($base_url . $map[$cmd], false, $context);
                if ($content) {
                    echo "\n" . $content . "\n";
                    if ($cmd === 'docs') {
                        echo "\n💡 Hint: To read a specific section, run e.g. `php fullstuck.php docs:1` or `docs:full`.\n";
                    }
                } else {
                    echo "Error: Failed to fetch documentation. Check your internet connection.\n";
                }
            } else {
                echo "Available docs commands:\n";
                foreach(array_keys($map) as $k) { echo "  php fullstuck.php $k\n"; }
            }
            exit(0);
        }
    }
}

if (!file_exists(FST_CONFIG_FILE)) {
    if (php_sapi_name() === 'cli') {
        echo "Error: fullstuck.json not found. Run 'php fullstuck.php init' with arguments to initialize, or access via web browser.\n";
        exit(1);
    }
    fst_handle_installation();
    die();
}

function fst_app($key = null, $value = null) {
    static $state = [
        'config' => null,
        'pdo' => null,
        'routes' => [],
        'route_prefix' => '',
        'group_middleware' => [],
        'route_found' => false,
    ];

    if ($key === null) return $state;
    if ($value !== null) $state[$key] = $value;
    return $state[$key] ?? null;
}


$config_content = @file_get_contents(FST_CONFIG_FILE);
$decoded_config = $config_content ? json_decode($config_content, true) : null;
if (fst_app('config') === null) fst_app('config', $decoded_config);
if ($decoded_config === null && file_exists(FST_CONFIG_FILE)) {
    if (function_exists('fst_abort')) fst_abort(500, "Failed to decode `fullstuck.json`. Check for syntax errors.");
    else die("Error: Failed to decode `fullstuck.json`. Check for syntax errors.");
}

if (fst_is_dev()) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}
ini_set('display_errors', '0'); // Disable native display_errors to replace it with our UI

function fst_log($level, $message, $context = []) {
    $log_entry = json_encode([
        'timestamp' => date('c'),
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context
    ]) . "\n";
    @file_put_contents(FST_ROOT_DIR . '/.fst.log', $log_entry, FILE_APPEND);
    
    // Also log to system error log for CLI or server monitoring
    if (strtoupper($level) === 'ERROR' || strtoupper($level) === 'FATAL') {
        error_log("[$level] $message");
    }
}

function _fst_error_handler($errno, $errstr, $errfile, $errline) { if (!(error_reporting() & $errno)) return false; throw new ErrorException($errstr, 0, $errno, $errfile, $errline); }

function _fst_exception_handler($e) {
    while (ob_get_level() > 0) { ob_end_clean(); } // [PATCH] Clear partial HTML buffer
    http_response_code(500);
    
    $custom_handler = fst_app('error_handler_callback');
    if ($custom_handler !== null) {
        call_user_func($custom_handler, $e);
    }
    
    fst_log('error', $e->getMessage(), [
        'class' => get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    if (!fst_is_dev()) {
        if (function_exists('fst_abort')) { 
            fst_abort(500, "Internal Server Error. Please check .fst.log for details."); 
        } else { 
            die("Internal Server Error."); 
        }
    }
    
    // Development Mode: Display Beautiful UI
    $message = htmlspecialchars($e->getMessage());
    $file = $e->getFile();
    $line = $e->getLine();
    $trace = htmlspecialchars($e->getTraceAsString());
    
    $source_file = $file;
    $source_line = $line;
    if (strpos($file, 'cache' . DIRECTORY_SEPARATOR . 'views') !== false && file_exists($file)) {
        $first_line = fgets(fopen($file, 'r'));
        if (preg_match('/FST_SOURCE_FILE:\s*(.+?)\s*\*\//', $first_line, $m)) {
            $source_file = trim($m[1]);
            $source_line = max(1, $line - 1);
        }
    }
    
    $file_display = htmlspecialchars($source_file);
    
    $code_snippet = '';
    if (file_exists($source_file)) {
        $lines = file($source_file);
        $start = max(0, $source_line - 5);
        $end = min(count($lines), $source_line + 4);
        for ($i = $start; $i < $end; $i++) {
            $current_line = $i + 1;
            $line_content = htmlspecialchars($lines[$i]);
            $highlight = ($current_line === $source_line) ? 'background-color: rgba(239, 68, 68, 0.2); border-left: 3px solid var(--error);' : 'border-left: 3px solid transparent;';
            $code_snippet .= "<div style='{$highlight} padding: 2px 5px;'><strong>" . str_pad($current_line, 4, ' ', STR_PAD_LEFT) . " |</strong> {$line_content}</div>";
        }
    }

    $class_name = get_class($e);

    $is_localhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost');
    $exposure_warning = '';
    if (!$is_localhost) {
        $exposure_warning = "<div style='background: #f59e0b; color: black; padding: 10px; font-weight: bold; text-align: center; border-radius: 4px; margin-bottom: 20px;'>⚠️ WARNING: You are exposing sensitive error details on a public domain. Please set &quot;production&quot;: true in fullstuck.json!</div>";
    }

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Exception: {$message}</title>
        <style>
            :root {
                --bg-main: #0b0f19;
                --bg-surface: #172033;
                --text-main: #f8fafc;
                --text-muted: #94a3b8;
                --primary: #6366f1;
                --accent: #10b981;
                --error: #ef4444;
                --font-sans: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                --font-mono: "JetBrains Mono", "Fira Code", "Cascadia Code", monospace;
            }
            body { font-family: var(--font-sans); background-color: var(--bg-main); color: var(--text-main); margin: 0; padding: 20px; }
            .container { max-width: 1000px; margin: 0 auto; background: var(--bg-surface); padding: 30px; border-radius: 8px; border: 1px solid #24324f; border-top: 8px solid var(--error); }
            h1 { color: var(--error); margin-top: 0; font-size: 24px; word-break: break-all; line-height: 1.3;}
            .badge { display: inline-block; background: var(--error); color: white; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: bold; margin-bottom: 15px; text-transform: uppercase;}
            .meta { background: var(--bg-main); padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: var(--font-mono); font-size: 14px; border: 1px solid #24324f;}
            .meta strong { color: var(--text-muted); display: inline-block; width: 60px;}
            .code-preview { background: var(--bg-main); color: var(--text-main); padding: 15px 0; border-radius: 5px; overflow-x: auto; font-family: var(--font-mono); font-size: 14px; line-height: 1.5; margin-bottom: 20px; border: 1px solid #24324f;}
            .code-preview div { white-space: pre; }
            h3 { border-bottom: 1px solid #24324f; padding-bottom: 10px; margin-top: 30px; font-size: 18px;}
            pre.trace { background: var(--bg-main); color: var(--text-muted); padding: 15px; border-radius: 5px; overflow-x: auto; font-family: var(--font-mono); font-size: 13px; line-height: 1.6; border: 1px solid #24324f;}
        </style>
    </head>
    <body>
        <div class="container fst-error-container">
            {$exposure_warning}
            <span class="badge">{$class_name}</span>
            <h1>{$message}</h1>
            <div class="meta">
                <strong>File:</strong> {$file_display}<br>
                <strong>Line:</strong> {$source_line}
            </div>
            
            <h3>Code Snippet</h3>
            <div class="code-preview">{$code_snippet}</div>
            
            <h3>Stack Trace</h3>
            <pre class="trace">{$trace}</pre>
        </div>
    </body>
    </html>
HTML;
    die();
}

function _fst_fatal_error_handler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
        _fst_exception_handler(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
    }
}

set_error_handler('_fst_error_handler');
set_exception_handler('_fst_exception_handler');
register_shutdown_function('_fst_fatal_error_handler');

function fst_error_handler(callable $callback) { fst_app('error_handler_callback', $callback); }
function fst_is_dev() { $fst_config = fst_app('config'); return !($fst_config['production'] ?? true); }

function _fst_interpolate_env($val) {
    if (is_string($val) && strpos($val, '${') !== false) {
        return preg_replace_callback('/\$\{([A-Za-z0-9_]+)\}/', function($m) {
            $env = getenv($m[1]);
            if ($env === false) fst_abort(500, "Configuration Error: Environment variable '{$m[1]}' is missing.");
            return $env;
        }, $val);
    }
    if (is_array($val)) {
        foreach ($val as $k => $v) $val[$k] = _fst_interpolate_env($v);
    }
    return $val;
}

function fst_config($key = null, $default = null) {
    $fst_config = fst_app('config');
    if ($key === null) return _fst_interpolate_env($fst_config);
    $keys = explode('.', $key);
    $val = $fst_config;
    foreach ($keys as $k) {
        if (is_array($val) && array_key_exists($k, $val)) {
            $val = $val[$k];
        } else {
            return _fst_interpolate_env($default);
        }
    }
    return _fst_interpolate_env($val);
}

function fst_is_fragment_request(): bool { return isset($_SERVER['HTTP_X_FST_REQUEST']); }
function fst_fragment_target(): string { return $_SERVER['HTTP_X_FST_FRAGMENT'] ?? 'body'; }

function fst_extract_html_fragment($html, $selector = 'body') {
    if (empty(trim($html))) return '';

    // [SECURITY PATCH 1] Strict Allowlist for Client-Controlled Header (X-FST-Fragment)
    // Prevent LFI/RCE via XPath functions (e.g., document('/etc/passwd')) by blocking
    // any character outside basic CSS selectors, explicitly forbidding '(' and ')'.
    $selector = trim($selector);
    if (!preg_match('/^[a-zA-Z0-9_\-\.\#\s,>\[\]="\']+$/', $selector)) {
        return $html; // Abort extraction and return full HTML if invalid characters detected
    }

    // [PATCH] Regex Fast-Path for main tag selectors (body & main)
    // Avoid double DOMDocument parsing when used with normal SSR views.
    $singleton_tags = ['body', 'main'];
    if (!str_starts_with($selector, '#') && !str_starts_with($selector, '.')) {
        $tag = strtolower($selector);
        if (in_array($tag, $singleton_tags)) {
            // Regex: get innerHTML with lazy match (.*?)
            if (preg_match('/<' . $tag . '[^>]*>(.*?)<\/' . $tag . '>/is', $html, $m)) {
                return $m[1];
            }
        }
    }

    // [PATCH] Convert CSS Selector to XPath dynamically and safely
    $paths = [];
    foreach (explode(',', $selector) as $sel) {
        $sel = trim($sel);
        $sel = preg_replace('/\s*>\s*/', '/', $sel); // Child
        $sel = preg_replace('/\s+/', '//', $sel); // Descendant
        $sel = preg_replace('/#([\w\-]+)/', '[@id="$1"]', $sel); // ID
        $sel = preg_replace('/\.([\w\-]+)/', '[contains(concat(" ", normalize-space(@class), " "), " $1 ")]', $sel); // Class
        
        // [SECURITY PATCH 2] Strict Attribute Value Normalization
        // Prevent Boolean-based XPath Injection (e.g., [x="1" or 1=1]) by strictly
        // bounding attribute values to safe characters (alphanumeric, dash, space, slash, dot).
        $sel = preg_replace('/\[([\w\-]+)=(["\']?)([\w\-\s\/\.]*)\2\]/', '[@$1="$3"]', $sel);
        
        // Convert [attr] to [@attr]
        $sel = preg_replace('/\[([\w\-]+)\]/', '[@$1]', $sel);
        // Handle tagless attributes by prepending *
        $sel = preg_replace('/(^|\/|\|)(\[)/', '$1*$2', $sel);
        
        // Prepend absolute context if not already set
        if (!str_starts_with($sel, '/')) {
            $sel = '//' . $sel;
        }
        
        $paths[] = $sel;
    }
    $xpath_query = implode(' | ', $paths);

    $is_modern_dom = class_exists('Dom\HTMLDocument');

    // --- PROGRESSIVE ENHANCEMENT: PHP 8.4+ Dom\HTMLDocument vs Legacy DOMDocument ---
    if ($is_modern_dom) {
        // ✅ MODE PHP 8.4+: WHATWG HTML5 Compliant & Native UTF-8
        try {
            $dom = \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR);
            $xpath = new \Dom\XPath($dom);
        } catch (\Throwable $e) {
            return $html;
        }
    } else {
        // ⚠️ LEGACY MODE (< PHP 8.4): Fallback using the old DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
    }
    // --- END ENHANCEMENT ---

    $nodes = $xpath->query($xpath_query);
    if ($nodes && $nodes->length > 0) {
        $inner_html = '';
        $target_node = $nodes->item(0);
        foreach ($target_node->childNodes as $child) {
            if ($is_modern_dom) {
                $inner_html .= $dom->saveHtml($child);
            } else {
                $inner_html .= $dom->saveHTML($child);
            }
        }
        return $inner_html;
    }
    return $html;
}



?>

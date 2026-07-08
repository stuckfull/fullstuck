<?php
/**
 * 🚀 FULLSTUCK.PHP (v0.3.0)
 * 🛡️ FST_HASH: 16f1891c6d1546e4ca1db286a25bffc75c9bb928e6c3982199da32ddabb07d05
 *
 * 🤖 AI ASSISTANT: DO NOT READ OR MODIFY THIS COMPILED CORE FILE!
 * To learn the API and framework rules, run `php fullstuck.php docs` in your terminal.
 */
define('FST_AGENT_JS_CODE', 'class fst_agent { constructor() { this.routes = []; this.baseUrl = \'\'; this.notFoundCallback = null; this.beforeHook = null; this.afterHook = null; this.fetchInterceptor = null; this._currentGroupPrefix = \'\'; this.init(); } init() { document.addEventListener(\'click\', (e) => { if (e.defaultPrevented) return; const link = e.target.closest(\'a\'); if (!link || !link.href) return; if (link.hasAttribute(\'data-fst-normal-load\') || link.target === \'_blank\' || link.hasAttribute(\'download\') || link.hostname !== window.location.hostname || e.ctrlKey || e.metaKey || e.shiftKey) return; const href = link.getAttribute(\'href\'); if (href && (href.startsWith(\'#\') || (link.pathname === window.location.pathname && link.search === window.location.search && link.hash !== \'\'))) { return; } e.preventDefault(); this.handleLinkClick(link); }); window.addEventListener(\'popstate\', async (e) => { const target = (e.state && e.state.fstTarget) || \'body\'; const savedScrollX = e.state?.scrollX; const savedScrollY = e.state?.scrollY; const useCache = e.state?.fstCache ?? (document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-history-cache\') === \'true\'); const path = window.location.pathname + window.location.search; const matchedRoute = this.matchRoute(path); if (matchedRoute) { this.route(path); return; } if (useCache && e.state && e.state.fstHtml) { const targetElement = document.querySelector(target); if (targetElement) { document.dispatchEvent(new Event(\'fst:unload\')); if (e.state.fstBodyAttrs && target === \'body\') { const parser = new DOMParser(); const doc = parser.parseFromString(`<div ${e.state.fstBodyAttrs}></div>`, \'text/html\'); const newBody = doc.body.firstChild; Array.from(document.body.attributes).forEach(attr => document.body.removeAttribute(attr.name)); Array.from(newBody.attributes).forEach(attr => document.body.setAttribute(attr.name, attr.value)); } targetElement.innerHTML = e.state.fstHtml; this.reexecuteScripts(targetElement); document.dispatchEvent(new Event(\'fst:load\')); } else { window.location.reload(); return; } } else { await this.fetchFragment(window.location.href, target, false, null, true); } if (savedScrollX !== undefined && savedScrollY !== undefined) { window.scrollTo({ left: savedScrollX, top: savedScrollY, behavior: \'instant\' }); } else if (window.location.hash) { const targetAnchor = document.querySelector(window.location.hash); if (targetAnchor) targetAnchor.scrollIntoView({ behavior: \'smooth\' }); } }); document.addEventListener(\'submit\', async (e) => { if (e.defaultPrevented) return; const form = e.target; if (form.hasAttribute(\'data-fst-normal-load\')) return; e.preventDefault(); this.handleFormSubmit(form); }); this.setNotFound((path, triggerElement) => { let targetSelector = \'body\'; let isHistoryOptOut = false; if (triggerElement) { targetSelector = triggerElement.getAttribute(\'data-fst-fragment\') || \'body\'; isHistoryOptOut = triggerElement.hasAttribute(\'data-fst-no-history\'); } this.fetchFragment(path, targetSelector, !isHistoryOptOut, triggerElement); }); window.addEventListener(\'DOMContentLoaded\', () => { if (!window.history.state) { const bodyAttrs = Array.from(document.body.attributes).map(a => `${a.name}="${a.value}"`).join(\' \'); window.history.replaceState({ fstHtml: document.body.innerHTML, fstTarget: \'body\', fstBodyAttrs: bodyAttrs }, \'\', window.location.href); } document.dispatchEvent(new Event(\'fst:load\')); const path = window.location.pathname + window.location.search; if (this.matchRoute(path)) { this.route(path); } }); } handleLinkClick(link) { const href = link.href; const path = this.getPathFromHref(href); if (path.startsWith(this.baseUrl)) { this.navigate(path, link); } else { window.location.href = href; } } async handleFormSubmit(form) { const targetSelector = form.getAttribute(\'data-fst-fragment\') || \'body\'; const isHistoryOptOut = form.hasAttribute(\'data-fst-no-history\'); const indicator = this.getIndicatorClass(form); const targetElement = document.querySelector(targetSelector); if (targetElement) targetElement.classList.add(...indicator.split(\' \')); try { const method = (form.getAttribute(\'method\') || \'GET\').toUpperCase(); const action = form.getAttribute(\'action\') || window.location.href; const formData = new FormData(form); const reqHeader = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-req-header\') || \'X-FST-Request\'; const targetHeader = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-target-header\') || \'X-FST-Target\'; const headers = { [reqHeader]: \'true\', [targetHeader]: targetSelector }; let fetchOptions = { method, headers }; let finalUrl = action; if (method === \'GET\') { const params = new URLSearchParams(formData); finalUrl = action.includes(\'?\') ? `${action}&${params.toString()}` : `${action}?${params.toString()}`; } else { fetchOptions.body = formData; } const loadingEvent = new CustomEvent(\'fst:loading\', { detail: { url: finalUrl, targetSelector, triggerElement: form }, cancelable: true }); if (!document.dispatchEvent(loadingEvent)) { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); return; } if (this.fetchInterceptor) { const intercepted = await this.fetchInterceptor(finalUrl, fetchOptions); if (intercepted) fetchOptions = intercepted; } const response = await fetch(finalUrl, fetchOptions); const redirectUrl = response.headers.get(\'X-FST-Redirect\'); if (redirectUrl) { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); await this.fetchFragment(redirectUrl, targetSelector, !isHistoryOptOut); return; } if (response.redirected) { window.location.href = response.url; return; } if (!response.ok && response.status !== 400 && response.status !== 422) { const errorHtml = await response.text(); document.open(); document.write(errorHtml); document.close(); return; } const html = await response.text(); this.processFragmentResponse(html, targetSelector, targetElement, response, form, !isHistoryOptOut, finalUrl, method); } catch (err) { window.location.reload(); } finally { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); } } getPathFromHref(href) { const url = new URL(href, window.location.origin); let path = url.pathname; if (url.search) path += url.search; return path; } navigate(path, triggerElement) { if (this.beforeHook && this.beforeHook(path) === false) return; if (window.history.state) { const currentState = window.history.state; currentState.scrollX = window.scrollX; currentState.scrollY = window.scrollY; window.history.replaceState(currentState, \'\'); } const isHistoryOptOut = triggerElement ? triggerElement.hasAttribute(\'data-fst-no-history\') : false; const href = window.location.origin + path; let routePath = path.replace(this.baseUrl, "") || "/"; const match = this.matchRoute(routePath); if (match) { if (!isHistoryOptOut) { window.history.pushState({}, "", href); } this.route(routePath, triggerElement); } else { this.notFoundCallback ? this.notFoundCallback(href, triggerElement) : console.log(`No route matched for: ${href}`); } } route(path, triggerElement) { for (const { pattern, callback } of this.routes) { const match = this.matchRouteCheck(pattern, path); if (match) { callback(match, triggerElement); if (this.afterHook) this.afterHook(path, triggerElement); return; } } } matchRoute(path) { for (const { pattern } of this.routes) { const match = this.matchRouteCheck(pattern, path); if (match) return match; } return null; } matchRouteCheck(pattern, path) { const [patternPath, patternQuery] = pattern.split("?"); const [urlPath, urlQuery] = path.split("?"); const regex = new RegExp("^" + patternPath.replace(/:\\w+/g, "([^/]+)") + "$"); const match = urlPath.match(regex); if (match) { const params = { param: path, query: {} }; const keys = patternPath.match(/:\\w+/g) || []; keys.forEach((key, i) => { params[key.substring(1)] = match[i + 1]; }); if (urlQuery) { const searchParams = new URLSearchParams(urlQuery); for (const [key, value] of searchParams) { params.query[key] = value; } } return params; } return null; } set(pattern, callback) { this.routes.push({ pattern: this._currentGroupPrefix + pattern, callback }); } group(prefix, callback) { const prevPrefix = this._currentGroupPrefix; this._currentGroupPrefix += prefix; callback(); this._currentGroupPrefix = prevPrefix; } setNotFound(callback) { this.notFoundCallback = callback; } setBefore(callback) { this.beforeHook = callback; } setAfter(callback) { this.afterHook = callback; } setInterceptor(callback) { this.fetchInterceptor = callback; } getIndicatorClass(triggerElement) { return (triggerElement && triggerElement.getAttribute("data-fst-indicator")) || document.querySelector("script#fst-agent")?.getAttribute("data-indicator-class") || "fst-loading"; } async fetchFragment(url, targetSelector, pushHistory, triggerElement = null, isPopstate = false) { const reqHeader = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-req-header\') || \'X-FST-Request\'; const targetHeader = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-target-header\') || \'X-FST-Target\'; const targetElement = document.querySelector(targetSelector); const indicator = this.getIndicatorClass(triggerElement); if (targetElement) targetElement.classList.add(...indicator.split(\' \')); const loadingEvent = new CustomEvent(\'fst:loading\', { detail: { url, targetSelector, triggerElement }, cancelable: !isPopstate }); if (!isPopstate && !document.dispatchEvent(loadingEvent)) { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); return; } try { const headers = { [reqHeader]: \'true\', [targetHeader]: targetSelector }; let fetchOptions = { headers }; if (this.fetchInterceptor) { const intercepted = await this.fetchInterceptor(url, fetchOptions); if (intercepted) fetchOptions = intercepted; } const response = await fetch(url, fetchOptions); if (!response.ok) { const errorHtml = await response.text(); document.open(); document.write(errorHtml); document.close(); return; } const redirectUrl = response.headers.get(\'X-FST-Redirect\'); if (redirectUrl) { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); if (isPopstate) { window.location.href = redirectUrl; return; } await this.fetchFragment(redirectUrl, targetSelector, pushHistory); return; } if (response.redirected) { window.location.href = response.url; return; } const contentType = response.headers.get(\'content-type\'); if (!contentType || !contentType.includes(\'text/html\')) { window.location.href = url; return; } const html = await response.text(); this.processFragmentResponse(html, targetSelector, targetElement, response, triggerElement, pushHistory, url, \'GET\', isPopstate); if (!isPopstate) { const noScroll = triggerElement ? triggerElement.hasAttribute(\'data-fst-no-scroll\') : false; if (!noScroll) { const scrollBehavior = triggerElement ? (triggerElement.getAttribute(\'data-fst-scroll\') || \'instant\') : \'instant\'; const behavior = scrollBehavior === \'smooth\' ? \'smooth\' : \'instant\'; if (window.location.hash) { const targetAnchor = document.querySelector(window.location.hash); if (targetAnchor) { targetAnchor.scrollIntoView({ behavior }); } else { if (targetSelector === \'body\') window.scrollTo({ top: 0, behavior }); else targetElement.scrollTo({ top: 0, behavior }); } } else { if (targetSelector === \'body\') window.scrollTo({ top: 0, behavior }); else targetElement.scrollTo({ top: 0, behavior }); } } } } catch (err) { window.location.href = url; } finally { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); } } processFragmentResponse(html, targetSelector, targetElement, response, triggerElement, pushHistory, url, method, isPopstate = false) { const newTitle = html.match(/<title[^>]*>([\\s\\S]*?)<\\/title>/i); if (newTitle) document.title = newTitle[1]; const bodyAttrs = response.headers.get(\'X-FST-Body-Attrs\'); if (bodyAttrs !== null && targetSelector === \'body\') { const parser = new DOMParser(); const doc = parser.parseFromString(`<div ${bodyAttrs}></div>`, \'text/html\'); const newBody = doc.body.firstChild; Array.from(document.body.attributes).forEach(attr => document.body.removeAttribute(attr.name)); Array.from(newBody.attributes).forEach(attr => document.body.setAttribute(attr.name, attr.value)); } if (!targetElement) throw new Error(\'Target not found\'); document.dispatchEvent(new Event(\'fst:unload\')); targetElement.innerHTML = html; if (pushHistory && method === \'GET\') { const cacheFlag = triggerElement ? triggerElement.getAttribute(\'data-fst-cache\') : null; const globalCache = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-history-cache\') === \'true\'; const fstCache = cacheFlag !== null ? cacheFlag === \'true\' : globalCache; window.history.pushState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs, fstCache: fstCache }, \'\', url); } else if (isPopstate) { window.history.replaceState({ fstHtml: html, fstTarget: targetSelector, fstBodyAttrs: bodyAttrs, fstCache: window.history.state?.fstCache ?? false }, \'\', url); } this.reexecuteScripts(targetElement); document.dispatchEvent(new Event(\'fst:load\')); } reexecuteScripts(targetElement) { if (!window._fst_executed_scripts) { window._fst_executed_scripts = new Set(); document.querySelectorAll(\'script[src]\').forEach(s => window._fst_executed_scripts.add(s.src)); } const scripts = targetElement.querySelectorAll(\'script\'); scripts.forEach(oldScript => { if (oldScript.id === \'fst-agent\' || oldScript.hasAttribute(\'data-fst-ignore\')) return; if (oldScript.src) { if (window._fst_executed_scripts.has(oldScript.src)) return; window._fst_executed_scripts.add(oldScript.src); } const newScript = document.createElement(\'script\'); Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value)); newScript.appendChild(document.createTextNode(oldScript.innerHTML)); oldScript.parentNode.replaceChild(newScript, oldScript); }); } go(url, options = {}) { const defaultTarget = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-default-target\') || \'body\'; const target = options.target || defaultTarget; const history = options.history !== false; const virtualTrigger = { getAttribute: (attr) => { if (attr === \'data-fst-scroll\') return options.scroll !== undefined ? String(options.scroll) : null; if (attr === \'data-fst-indicator\') return options.indicator || null; if (attr === \'data-fst-cache\') return options.cache !== undefined ? String(options.cache) : null; if (attr === \'data-fst-fragment\') return target; return null; }, hasAttribute: (attr) => { if (attr === \'data-fst-no-history\') return !history; if (attr === \'data-fst-no-scroll\') return options.scroll === false; return false; } }; const path = this.getPathFromHref(url); const match = this.matchRoute(path); if (match) { if (history) { window.history.pushState({}, "", url); } this.route(path, virtualTrigger); } else { this.fetchFragment(url, target, history, virtualTrigger); } } escape(str) { if (str === null || str === undefined) return \'\'; return String(str) .replace(/&/g, \'&amp;\') .replace(/</g, \'&lt;\') .replace(/>/g, \'&gt;\') .replace(/"/g, \'&quot;\') .replace(/\'/g, \'&#039;\'); } } window.fst = new fst_agent(); window.fst.e = window.fst.escape;');


// FILE: core.php
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
define('FST_VERSION', '0.3.0');
define('FST_DOCS_URL', 'https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/v0.3/index.md');
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
            $base_url = 'https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/v0.3/';
            $map = [
                'docs' => 'index.md',
                'docs:1' => '01-getting-started.md',
                'docs:2' => '02-routing.md',
                'docs:3' => '03-database.md',
                'docs:4' => '04-security.md',
                'docs:5' => '05-templates.md',
                'docs:6' => '06-fst-agent.md',
                'docs:7' => '07-logging.md',
                'docs:8' => '08-advanced-cookbook.md',
                'docs:9' => '09-api-reference.md',
                'docs:full' => 'FULL.md'
            ];
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


function fst_is_safe_to_debug() {
    return fst_is_dev();
}

$config_content = @file_get_contents(FST_CONFIG_FILE);
$decoded_config = $config_content ? json_decode($config_content, true) : null;
if (fst_app('config') === null) fst_app('config', $decoded_config);
if (fst_app('routes') === null) fst_app('routes', []);
if (fst_app('route_prefix') === null) fst_app('route_prefix', '');
if (fst_app('route_found') === null) fst_app('route_found', false);

if ($decoded_config === null && file_exists(FST_CONFIG_FILE)) {
    if (function_exists('fst_abort')) fst_abort(500, "Failed to decode `fullstuck.json`. Check for syntax errors.");
    else die("Error: Failed to decode `fullstuck.json`. Check for syntax errors.");
}

if (fst_is_dev()) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}
ini_set('display_errors', '0'); 

function fst_log($level, $message, $context = []) {
    $log_entry = json_encode([
        'timestamp' => date('c'),
        'level' => strtoupper($level),
        'message' => $message,
        'context' => $context
    ]) . "\n";
    @file_put_contents(FST_ROOT_DIR . '/.fst.log', $log_entry, FILE_APPEND);
    
    
    if (strtoupper($level) === 'ERROR' || strtoupper($level) === 'FATAL') {
        error_log("[$level] $message");
    }
}

function _fst_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function _fst_exception_handler($e) {
    while (ob_get_level() > 0) { ob_end_clean(); } 
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
    
    
    $message = htmlspecialchars($e->getMessage());
    $file = htmlspecialchars($e->getFile());
    $line = $e->getLine();
    $trace = htmlspecialchars($e->getTraceAsString());
    
    $code_snippet = '';
    if (file_exists($e->getFile())) {
        $lines = file($e->getFile());
        $start = max(0, $line - 5);
        $end = min(count($lines), $line + 4);
        for ($i = $start; $i < $end; $i++) {
            $current_line = $i + 1;
            $line_content = htmlspecialchars($lines[$i]);
            $highlight = ($current_line === $line) ? 'background-color: rgba(239, 68, 68, 0.2); border-left: 3px solid var(--error);' : 'border-left: 3px solid transparent;';
            $code_snippet .= "<div style='{$highlight} padding: 2px 5px;'><strong>" . str_pad($current_line, 4, ' ', STR_PAD_LEFT) . " |</strong> {$line_content}</div>";
        }
    }

    $class_name = get_class($e);

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
            <span class="badge">{$class_name}</span>
            <h1>{$message}</h1>
            <div class="meta">
                <strong>File:</strong> {$file}<br>
                <strong>Line:</strong> {$line}
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

function fst_error_handler(callable $callback) {
    fst_app('error_handler_callback', $callback);
}

function fst_is_dev() {
    $fst_config = fst_app('config');
    return !($fst_config['production'] ?? true);
}

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

function fst_is_fragment_request(): bool {
    $header_name = fst_config('fragment.header_request', 'X-FST-Request');
    $req_header = 'HTTP_' . str_replace('-', '_', strtoupper($header_name));
    return isset($_SERVER[$req_header]);
}

function fst_fragment_target(): string {
    $header_name = fst_config('fragment.header_target', 'X-FST-Target');
    $target_header = 'HTTP_' . str_replace('-', '_', strtoupper($header_name));
    return $_SERVER[$target_header] ?? 'body';
}

function fst_extract_html_fragment($html, $selector = 'body') {
    if (empty(trim($html))) return '';

    
    
    
    $singleton_tags = ['body'];
    if (!str_starts_with($selector, '#') && !str_starts_with($selector, '.')) {
        $tag = strtolower($selector);
        if (in_array($tag, $singleton_tags)) {
            
            if (preg_match('/<' . $tag . '[^>]*>(.*?)<\/' . $tag . '>/is', $html, $m)) {
                return $m[1];
            }
        }
        
    }

    
    
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    
    
    if (strpos($selector, ':') !== false || strpos($selector, '+') !== false || strpos($selector, '~') !== false) {
        return $html; 
    }

    $paths = [];
    foreach (explode(',', $selector) as $sel) {
        $sel = trim($sel);
        $sel = preg_replace('/\s*>\s*/', '/', $sel); 
        $sel = preg_replace('/\s+/', '//', $sel); 
        $sel = preg_replace('/#([\w\-]+)/', '[@id="$1"]', $sel); 
        $sel = preg_replace('/\.([\w\-]+)/', '[contains(concat(" ", normalize-space(@class), " "), " $1 ")]', $sel); 
        
        
        $sel = preg_replace('/\[([\w\-]+)=([\'"]?.*?[\'"]?)\]/', '[@$1=$2]', $sel);
        
        $sel = preg_replace('/\[([\w\-]+)\]/', '[@$1]', $sel);
        
        $sel = preg_replace('/(^|\/|\|)(\[)/', '$1*$2', $sel);
        
        
        if (!str_starts_with($sel, '/')) {
            $sel = '//' . $sel;
        }
        
        $paths[] = $sel;
    }
    $xpath_query = implode(' | ', $paths);

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query($xpath_query);
    if ($nodes && $nodes->length > 0) {
        $inner_html = '';
        foreach ($nodes->item(0)->childNodes as $child) {
            $inner_html .= $dom->saveHTML($child);
        }
        return $inner_html;
    }
    return $html;
}

// FILE: database.php
function fst_db_quote_ident($name, $connection = null) {
    $conn_name = $connection ?? fst_config('database.default', 'main');
    $db_config = fst_config("database.connections.{$conn_name}");
    $driver = strtolower($db_config['driver'] ?? 'sqlite');
    $q = ($driver === 'pgsql') ? '"' : '`';
    
    
    if (str_contains($name, '.')) {
        $parts = explode('.', $name);
        $quoted_parts = array_map(function($p) use ($q) {
            return $q . str_replace($q, $q . $q, $p) . $q;
        }, $parts);
        return implode('.', $quoted_parts);
    }
    return $q . str_replace($q, $q . $q, $name) . $q;
}


function _fst_sanitize_order_by($order_by, $connection = null) {
    $parts = array_map('trim', explode(',', $order_by));
    $safe_parts = [];
    foreach ($parts as $part) {
        
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_.]*)(\s+(ASC|DESC))?$/i', $part, $m)) {
            $safe_parts[] = fst_db_quote_ident($m[1], $connection) . (isset($m[3]) ? ' ' . strtoupper($m[3]) : '');
        }
    }
    return !empty($safe_parts) ? implode(', ', $safe_parts) : null;
}

function _fst_get_pdo($connection = null) {
    global $fst_pdo_pool;
    if (!isset($fst_pdo_pool)) $fst_pdo_pool = [];
    
    $conn_name = $connection ?? fst_config('database.default', 'main');
    
    if (!isset($fst_pdo_pool[$conn_name])) {
        $db_config = fst_config("database.connections.{$conn_name}");
        if (!$db_config) fst_abort(500, "Database connection '{$conn_name}' is not configured.");
        
        $driver = strtolower($db_config['driver'] ?? 'none');
        if ($driver === 'none') fst_abort(500, "Database is disabled for connection '{$conn_name}'.");
        
        try {
            $dsn = '';
            if ($driver === 'sqlite') {
                $path = $db_config['database_path'] ?? 'database.sqlite';
                $dsn = 'sqlite:' . FST_ROOT_DIR . '/' . ltrim($path, '/');
            } else if ($driver === 'mysql' || $driver === 'pgsql') {
                $host = $db_config['host'] ?? '127.0.0.1';
                $port = !empty($db_config['port']) ? ';port=' . $db_config['port'] : '';
                $dbname = $db_config['dbname'] ?? '';
                $dsn = "{$driver}:host={$host}{$port};dbname={$dbname}";
            } else {
                fst_abort(500, "Unsupported DB driver: {$driver}");
            }
            
            $user = $db_config['username'] ?? null;
            $pass = $db_config['password'] ?? null;
            $pdo_instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            
            if ($driver === 'sqlite') {
                $pdo_instance->exec('PRAGMA journal_mode = WAL;');
                $pdo_instance->exec('PRAGMA busy_timeout = 5000;');
                $pdo_instance->exec('PRAGMA foreign_keys = ON;');
            }
            
            $fst_pdo_pool[$conn_name] = $pdo_instance;
        } catch (PDOException $e) {
            fst_abort(500, "Database Connection Failed [{$conn_name}]: " . (fst_is_safe_to_debug() ? $e->getMessage() : 'Error.'));
        }
    }
    
    return $fst_pdo_pool[$conn_name];
}

function fst_db_begin($connection = null) {
    return _fst_get_pdo($connection)->beginTransaction();
}

function fst_db_commit($connection = null) {
    return _fst_get_pdo($connection)->commit();
}

function fst_db_rollback($connection = null) {
    return _fst_get_pdo($connection)->rollBack();
}

function fst_db($mode, $sql, $params = [], $connection = null) {
    $pdo = _fst_get_pdo($connection);
    
    
    foreach ($params as $k => $v) {
        if (is_array($v) || is_object($v)) {
            throw new Exception("Database Error: Parameter bind [{$k}] must not be an array or object.");
        }
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $normalizedSql = strtoupper(trim($sql));
    $isInsert = strpos($normalizedSql, 'INSERT') === 0;
    
    if (strtoupper($mode) === 'EXEC') {
        return [
            'affected_rows' => $stmt->rowCount(),
            'last_id' => $isInsert ? $pdo->lastInsertId() : null,
            'query_type' => strtok($normalizedSql, ' '),
            'success' => true
        ];
    }
    
    return match(strtoupper($mode)) { 
        'ROW' => ($r = $stmt->fetch()) !== false ? $r : null, 
        'SCALAR', 'ONE' => ($r = $stmt->fetchColumn()) !== false ? $r : null, 
        'ALL' => $stmt->fetchAll(), 
        default => $stmt->fetchAll() 
    };
}

function _fst_parse_condition($key, $conn) {
    $parts = preg_split('/\s+/', trim($key), 2);
    $field = fst_db_quote_ident($parts[0], $conn);
    $operator = isset($parts[1]) ? strtoupper(trim($parts[1])) : '=';
    $allowed_operators = ['=', '!=', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IS', 'IS NOT'];
    if (!in_array($operator, $allowed_operators)) $operator = '=';
    return $field . " " . $operator . " ?";
}

function fst_db_select($table, $conditions = [], $options = []) {
    $conn = $options['connection'] ?? null;
    $columns = $options['select'] ?? '*';
    $t = fst_db_quote_ident($table, $conn);
    $sql = "SELECT {$columns} FROM {$t}";
    $params = [];
    if (!empty($conditions)) {
        $where = [];
        foreach ($conditions as $k => $v) {
            $where[] = _fst_parse_condition($k, $conn);
            $params[] = $v;
        }
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    if (isset($options['order_by'])) {
        $safe_order = _fst_sanitize_order_by($options['order_by'], $conn);
        if ($safe_order) $sql .= " ORDER BY " . $safe_order;
    }
    if (isset($options['limit'])) $sql .= " LIMIT " . (int)$options['limit'];
    if (isset($options['offset'])) $sql .= " OFFSET " . (int)$options['offset'];
    
    $mode = $options['mode'] ?? 'ALL';
    return fst_db($mode, $sql, $params, $conn);
}

function fst_db_insert($table, $data, $options = []) {
    if (empty($data)) return false;
    $conn = $options['connection'] ?? null;
    $t = fst_db_quote_ident($table, $conn);
    $columns = array_map(fn($k) => fst_db_quote_ident($k, $conn), array_keys($data));
    $placeholders = array_fill(0, count($data), '?');
    $sql = "INSERT INTO {$t} (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
    $res = fst_db('EXEC', $sql, array_values($data), $conn);
    return $res['last_id'];
}

function fst_db_update($table, $data, $conditions = [], $options = []) {
    if (empty($conditions)) throw new Exception("Database Error: UPDATE statement requires conditions to prevent accidental mass updates."); 
    if (empty($data)) return false;
    $conn = $options['connection'] ?? null;
    $t = fst_db_quote_ident($table, $conn);
    $set = [];
    $params = [];
    foreach ($data as $k => $v) {
        $set[] = fst_db_quote_ident($k, $conn) . " = ?";
        $params[] = $v;
    }
    $sql = "UPDATE {$t} SET " . implode(", ", $set);
    
    $where = [];
    foreach ($conditions as $k => $v) {
        $where[] = _fst_parse_condition($k, $conn);
        $params[] = $v;
    }
    $sql .= " WHERE " . implode(" AND ", $where);
    $res = fst_db('EXEC', $sql, $params, $conn);
    return $res['affected_rows'];
}

function fst_db_delete($table, $conditions, $options = []) {
    if (empty($conditions)) throw new Exception("Database Error: DELETE statement requires conditions to prevent accidental mass deletion."); 
    $conn = $options['connection'] ?? null;
    $t = fst_db_quote_ident($table, $conn);
    $where = [];
    $params = [];
    foreach ($conditions as $k => $v) {
        $where[] = _fst_parse_condition($k, $conn);
        $params[] = $v;
    }
    $sql = "DELETE FROM {$t} WHERE " . implode(" AND ", $where);
    $res = fst_db('EXEC', $sql, $params, $conn);
    return $res['affected_rows'];
}

function fst_db_row($table, $conditions = [], $options = []) {
    $options['limit'] = 1;
    $options['mode'] = 'ROW';
    return fst_db_select($table, $conditions, $options);
}

function fst_db_exists($table, $conditions = [], $options = []) {
    $options['select'] = '1';
    $row = fst_db_row($table, $conditions, $options);
    return !empty($row);
}

// FILE: router.php
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

    
    $final_pattern = preg_replace_callback(
        '/\{([a-zA-Z0-9_]+)(?::([a-z]))?\}\?/',
        function ($matches) use ($shortcuts, $default_regex) {
             $type = $matches[2] ?? 'any';
             $regex = $shortcuts[$type] ?? $default_regex;
             $regex = str_starts_with($regex, '(') ? $regex : '(' . $regex . ')';
             return "(?:/" . $regex . ")?";
        },
        $path_for_regex);
    
    
    $final_pattern = preg_replace_callback(
        '/\{([a-zA-Z0-9_]+)(?::([a-z]))?\}/',
        function ($matches) use ($shortcuts, $default_regex) {
             $type = $matches[2] ?? 'any';
             $regex = $shortcuts[$type] ?? $default_regex;
             return str_starts_with($regex, '(') ? $regex : '(' . $regex . ')';
        },
        $final_pattern);

    $final_pattern = '#^' . str_replace('/', '\/', $final_pattern) . '$#';

    
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
                die(); 
            }
            break; 
        }
    }
    return false;
}

function _fst_match_static_routes() {
    $fst_routes = fst_app('routes');
    $uri = fst_uri();
    $method = fst_method();
    
    
    $routes_to_check = array_merge($fst_routes[$method] ?? [], $fst_routes['ANY'] ?? []);
    
    foreach ($routes_to_check as $route) {
        list($route_method, $pattern, $callback) = $route;
        if ($route_method !== 'ANY' && $route_method !== $method) continue;
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); 
            
            $middleware_list = $route[4] ?? [];

            
            $next = function() use ($callback, $matches) {
                return call_user_func_array($callback, $matches);
            };

            
            $middleware_list = array_reverse($middleware_list);

            
            foreach ($middleware_list as $mw) {
                if (is_callable($mw)) {
                    $current_next = $next;
                    
                    $next = function() use ($mw, $current_next) {
                        $called = false;
                        $next_wrapper = function() use ($current_next, &$called) {
                            $called = true;
                            return $current_next();
                        };

                        
                        $result = call_user_func($mw, $next_wrapper);
                        
                        
                        
                        if ($result === false) {
                            fst_abort(403, 'Forbidden by Middleware');
                        }
                        
                        
                        if (!$called && $result === null) {
                            fst_abort(500, "Middleware Logic Error: Middleware did not explicitly return a value or call \$next(). Security check failed."); 
                        }
                        
                        return $result;
                    };
                }
            }

            
            $next();
            
            fst_app('route_found', true); 
            return true; 
        }
    }
    return false;
}

function fst_run() {
    
    fst_app('route_found', false); 

    
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    ob_start();
    $handled = false;
    
    $req = _fst_get_request_paths(); 
    
    
    if ($req['uri_path'] === '/fst-agent.js') {
        $agent_mode = fst_config('agent_js', false);
        if ($agent_mode === true || $agent_mode === '1') {
            header('Content-Type: application/javascript');
            
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
    if ($output === false) $output = ''; 

    
    $agent_mode = fst_config('agent_js', false);
    $should_inject_agent = false;

    if ($agent_mode === true || $agent_mode === '1') {
        $should_inject_agent = true;
    }
    
    
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
        
        
        $title_tag = '';
        if (preg_match('/<title[^>]*>.*?<\/title>/is', $output, $matches)) {
            $title_tag = $matches[0];
        }

        
        if ($target === 'body' && preg_match('/<body([^>]*)>/is', $output, $matches)) {
            header('X-FST-Body-Attrs: ' . trim($matches[1]));
        }

        
        $output = fst_extract_html_fragment($output, $target); 

        
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

// FILE: http.php
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
    $data = fst_request(); 
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

// FILE: view.php
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

// FILE: utility.php
function fst_dump(...$vars) {
    if (!fst_is_dev()) {
        return;
    }
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $caller = $trace[0] ?? null;
    $file = $caller ? htmlspecialchars($caller['file']) : 'unknown';
    $line = $caller ? $caller['line'] : 'unknown';
    
    echo '<pre style="background-color: var(--bg-main, #0b0f19); color: var(--text-main, #f8fafc); padding: 15px; border: 1px solid #24324f; margin: 10px; border-radius: 5px; text-align: left; overflow-x: auto; font-family: var(--font-mono, \'JetBrains Mono\', monospace); font-size: 13px; line-height: 1.5;">';
    echo "<div style='color: var(--text-muted, #94a3b8); margin-bottom: 10px; border-bottom: 1px solid #24324f; padding-bottom: 5px; font-size: 11px;'><strong>{$file}</strong>:{$line}</div>";
    foreach ($vars as $var) { var_dump($var); }
    echo '</pre>';
}
function fst_dd(...$vars) { fst_dump(...$vars); die(); }


function _fst_strlen($str) {
    return function_exists('mb_strlen') ? mb_strlen($str, 'UTF-8') : strlen($str);
}

function fst_validate($data, $rules) {
    $errors = [];
    $sanitized = [];

    foreach ($rules as $field => $rule_string) {
        $value = $data[$field] ?? null;
        $rules_array = is_array($rule_string) ? $rule_string : explode('|', $rule_string);
        
        $field_valid = true;

        foreach ($rules_array as $rule) {
            $params = [];
            if (str_contains($rule, ':')) {
                list($rule_name, $param_str) = explode(':', $rule, 2);
                $params = explode(',', $param_str);
            } else {
                $rule_name = $rule;
            }

            
            if ($rule_name !== 'required' && ($value === null || trim((string)$value) === '')) {
                continue;
            }

            if ($rule_name === 'required') {
                if ($value === null || trim((string)$value) === '') {
                    $errors[$field][] = "The field '{$field}' is required.";
                    $field_valid = false;
                }
            } elseif ($rule_name === 'email') {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "The field '{$field}' must be a valid email.";
                    $field_valid = false;
                }
            } elseif ($rule_name === 'min') {
                $min = (int)($params[0] ?? 0);
                if (_fst_strlen((string)$value) < $min) {
                    $errors[$field][] = "The field '{$field}' must be at least {$min} characters.";
                    $field_valid = false;
                }
            } elseif ($rule_name === 'max') {
                $max = (int)($params[0] ?? 0);
                if (_fst_strlen((string)$value) > $max) {
                    $errors[$field][] = "The field '{$field}' must not exceed {$max} characters.";
                    $field_valid = false;
                }
            } elseif ($rule_name === 'numeric') {
                if (!is_numeric($value)) {
                    $errors[$field][] = "The field '{$field}' must be a number.";
                    $field_valid = false;
                }
            } elseif ($rule_name === 'in') {
                if (!in_array($value, $params)) {
                    $errors[$field][] = "The field '{$field}' must be one of: " . implode(', ', $params) . ".";
                    $field_valid = false;
                }
            } elseif ($rule_name === 'min_value') {
                $min_val = (float)($params[0] ?? 0);
                if (!is_numeric($value) || (float)$value < $min_val) {
                    $errors[$field][] = "The field '{$field}' must be at least {$min_val}.";
                    $field_valid = false;
                }
            } elseif ($rule_name === 'max_value') {
                $max_val = (float)($params[0] ?? 0);
                if (!is_numeric($value) || (float)$value > $max_val) {
                    $errors[$field][] = "The field '{$field}' must not exceed {$max_val}.";
                    $field_valid = false;
                }
            }
        }
        
        if ($value !== null) {
            $sanitized[$field] = is_string($value) ? trim($value) : $value;
        }
    }

    return [
        'valid' => count($errors) === 0,
        'errors' => $errors,
        'data' => $sanitized
    ];
}

// FILE: install.php
function _fst_cli_output(string $status, string $message): void {
    $colors = [
        'success' => "\033[1;32m✓\033[0m \033[32m", 
        'error'   => "\033[1;31m✗\033[0m \033[31m", 
        'info'    => "\033[1;34mℹ\033[0m \033[36m", 
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
                
                @mkdir(FST_ROOT_DIR . '/assets', 0755, true);
                
                
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
        <span class="auth-menu">
            <a href="/tasks" data-fst-fragment="main">Tasks</a>
            <a href="/logout" data-fst-normal-load>Logout</a>
        </span>
        <span class="guest-menu">
            <a href="/login" data-fst-fragment="main">Login</a>
        </span>
    </nav>
    <hr>
    <main></main>
    <script src="/assets/app.js"></script>
</body>
</html>
HTML;
                @file_put_contents(FST_ROOT_DIR . '/views/_layout.html', $layout_html);

                
                $index_html = <<<HTML
<section>
    <h1>Selamat Datang</h1>
    <p>Aplikasi FullStuck.php Anda siap digunakan.</p>
    <a href="/tasks">Lihat Tasks &rarr;</a>
</section>
HTML;
                @file_put_contents(FST_ROOT_DIR . '/views/index.html', $index_html);

                
                $login_html = <<<HTML
<div>
    <h1>Login</h1>
    <p class="error-msg"></p>
    <!-- Gunakan atribut data-fst-normal-load (atau no-history) pada link/form 
         jika Anda membutuhkan sistem untuk melakukan hard-reload. -->
    <form action="/login" method="POST" data-fst-normal-load>
        <div class="fst-csrf"></div>
        <div>
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div style="margin-top: 0.5rem;">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div style="margin-top: 0.5rem;">
            <button type="submit">Masuk</button>
        </div>
    </form>
</div>
HTML;
                @file_put_contents(FST_ROOT_DIR . '/views/login.html', $login_html);

                
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
                <p>Task dengan ID #\${fst.e(params.id)} tidak ditemukan di database.</p>
                <a href="/tasks" data-fst-fragment="main">Kembali</a>
            </div>
        `;
        return;
    }

    const task = await res.json();
    main.innerHTML = `  
        <h1>Detail Task #\${fst.e(task.id)}</h1>  
        <h3>\${fst.e(task.title)}</h3>
        <p>\${task.description ? fst.e(task.description) : '<i>Tidak ada deskripsi.</i>'}</p>  
        <small>Dibuat: \${fst.e(task.created_at)}</small>
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

                
                $router_php = <<<PHP
<?php  
// --- SETUP DATABASE OTOMATIS (Hanya untuk SQLite Scaffold. Hapus jika menggunakan DB lain/produksi) ---
try {
    fst_db('EXEC', "CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, password TEXT)");
    fst_db('EXEC', "CREATE TABLE IF NOT EXISTS tasks (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, description TEXT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    if (!fst_db_exists('users')) {
        fst_db_insert('users', ['name' => 'Demo User', 'email' => 'demo@example.com', 'password' => password_hash('123456', PASSWORD_DEFAULT)]);
    }
} catch (Exception \$e) {
    // Abaikan jika driver bukan SQLite
}
// ------------------------------------------------------------------------------------------------------

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
    ], ['title' => '\$title', 'main' => ['@html' => '\$content'], '.auth-menu' => ['@if' => 'fst_session_get("user_id") !== null'], '.guest-menu' => ['@if' => 'fst_session_get("user_id") === null']]);  
});  
  
fst_get('/login', function() {  
    \$error = fst_flash_get('error');
    \$content = fst_template_render(FST_ROOT_DIR . '/views/login.html', ['error' => \$error], [  
        'p.error-msg' => ['@if' => '\$error !== null', '@text' => '\$error'],  
        '.fst-csrf' => ['@html' => 'fst_csrf_field()'],  
    ]);  
    fst_template(FST_ROOT_DIR . '/views/_layout.html', [  
        'title' => 'Login',  
        'content' => \$content,  
    ], ['title' => '\$title', 'main' => ['@html' => '\$content'], '.auth-menu' => ['@if' => 'fst_session_get("user_id") !== null'], '.guest-menu' => ['@if' => 'fst_session_get("user_id") === null']]);  
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
    ], ['title' => '\$title', 'main' => ['@html' => '\$content'], '.auth-menu' => ['@if' => 'fst_session_get("user_id") !== null'], '.guest-menu' => ['@if' => 'fst_session_get("user_id") === null']]);  
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
    ], ['title' => '\$title', 'main' => ['@html' => '\$content'], '.auth-menu' => ['@if' => 'fst_session_get("user_id") !== null'], '.guest-menu' => ['@if' => 'fst_session_get("user_id") === null']]);
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
            }
        }

        
        $ai_readme = <<<TXT
# FullStuck.php AI Assistant Guidelines

Welcome! You are working on a FullStuck.php project. 
FullStuck is a custom micro-framework. To understand its syntax, features, and strict rules, you MUST read the documentation before writing any code.

**Run the following command in your terminal to view the table of contents:**
`php fullstuck.php docs`

**To view the comprehensive API Reference directly, run:**
`php fullstuck.php docs:9`
TXT;
        @file_put_contents(FST_ROOT_DIR . '/fullstuck_readme.md', $ai_readme);


        _fst_cli_output('success', 'FullStuck initialized successfully!');
        return;
    } catch (Exception $e) { 
        _fst_cli_output('error', 'Initialization failed: ' . $e->getMessage());
        exit(1);
    }
}

// FILE: template.php
function fst_template(string $templatePath, array $data, array $rules, ?string $cacheDir = null, bool $forceRebuild = false): void {
    if ($cacheDir === null) {
        $cacheDir = defined('FST_ROOT_DIR') ? FST_ROOT_DIR . '/view-cache' : sys_get_temp_dir() . '/fst_view_cache';
    }
    
    if (!file_exists($templatePath)) {
        throw new \RuntimeException("Template not found: {$templatePath}");
    }

    if (!file_exists($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $base = defined('FST_ROOT_DIR') ? FST_ROOT_DIR : '';
    $relative_path = str_replace([$base, '/', '\\', ':'], ['', '__', '__', ''], $templatePath);
    $relative_path = ltrim($relative_path, '_');
    $cacheFile = $cacheDir . '/' . $relative_path . '.php';

    array_walk_recursive($rules, function($item) {
        if ($item instanceof \Closure) {
            if (function_exists('fst_abort')) {
                fst_abort(500, "AI Warning: fst_template does not support Closures. Use PHP expression strings instead!");
            }
            throw new \Exception("AI Warning: fst_template does not support Closures. Use PHP expression strings instead!");
        }
    });

    $useHtml5 = class_exists('\Dom\HTMLDocument');
    $rules_hash = md5(serialize($rules) . ($useHtml5 ? 'html5' : 'legacy'));
    $cache_valid = false;
    if (!$forceRebuild && file_exists($cacheFile) && filemtime($templatePath) <= filemtime($cacheFile)) {
        $fp = fopen($cacheFile, 'r');
        if ($fp) {
            $first_line = fgets($fp);
            fclose($fp);
            if (preg_match('/^\/\/\s*fst_rules_hash:\s*([a-f0-9]{32})/', trim(str_replace(['<?php', '?>'], '', $first_line)), $matches)) {
                if ($matches[1] === $rules_hash) {
                    $cache_valid = true;
                }
            }
        }
    }

    
    if (!$cache_valid) {
        
        $html = file_get_contents($templatePath);
        
        if ($useHtml5) {
            $dom = \Dom\HTMLDocument::createFromString($html, \LIBXML_NOERROR | \LIBXML_HTML_NOIMPLIED | \Dom\HTML_NO_DEFAULT_NS);
        } else {
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            if ($html) {
                
                $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            }
            libxml_clear_errors();
        }
        
        $replacements = [];
        $markerCount = 0;
        
        
        $getMarker = function() use (&$markerCount) {
            $markerCount++;
            return "@@__FST_MARKER_{$markerCount}__@@";
        };
        
        
        $getAttrMarker = function() use (&$markerCount) {
            $markerCount++;
            return "fst_attr_marker_{$markerCount}";
        };

        $css2xpath = function(string $selector): string {
            $selector = trim($selector);
            
            
            if (str_starts_with($selector, '//') || str_starts_with($selector, './/')) {
                return $selector;
            }
            
            
            if (strpos($selector, ':') !== false || strpos($selector, '+') !== false || strpos($selector, '~') !== false) {
                return './/FST_BLACKLISTED_NODE';
            }
            
            $paths = [];
            foreach (explode(',', $selector) as $sel) {
                $sel = trim($sel);
                $sel = preg_replace('/\s*>\s*/', '/', $sel); 
                $sel = preg_replace('/\s+/', '//', $sel); 
                $sel = preg_replace('/#([\w\-]+)/', '[@id="$1"]', $sel); 
                $sel = preg_replace('/\.([\w\-]+)/', '[contains(concat(" ", normalize-space(@class), " "), " $1 ")]', $sel); 
                
                
                $sel = preg_replace('/\[([\w\-]+)=([\'"]?.*?[\'"]?)\]/', '[@$1=$2]', $sel);
                
                $sel = preg_replace('/\[([\w\-]+)\]/', '[@$1]', $sel);
                
                $sel = preg_replace('/(^|\/|\|)(\[)/', '$1*$2', $sel);
                
                
                if (!str_starts_with($sel, '/') && !str_starts_with($sel, '.')) {
                    $sel = './/' . $sel;
                }
                
                $paths[] = $sel;
            }
            return implode(' | ', $paths);
        };

        $xpath = $useHtml5 ? null : new DOMXPath($dom);
        $xpath5 = $useHtml5 ? new \Dom\XPath($dom) : null;

        
        $applyRules = function(array $currentRules, $context = null) use (&$applyRules, $dom, $xpath, $xpath5, $useHtml5, &$replacements, $getMarker, $getAttrMarker, $css2xpath) {
            foreach ($currentRules as $key => $value) {
                
                
                if (str_starts_with($key, '[') && str_ends_with($key, ']') && is_object($context) && method_exists($context, 'setAttribute') && strpos($key, '=') === false) {
                    $attrName = substr($key, 1, -1);
                    if ($value === '@remove') {
                        $context->removeAttribute($attrName);
                    } else {
                        $marker = $getMarker();
                        $context->setAttribute($attrName, $marker);
                        $replacements[$marker] = "<?= htmlspecialchars({$value} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                    }
                    continue;
                }

                
                if (str_starts_with($key, '@')) {
                    continue;
                }

                
                $isSingleSelection = false;
                if (str_starts_with($key, '^')) {
                    $isSingleSelection = true;
                    $key = substr($key, 1);
                }

                $targetNodes = [];
                $useXPath = false;
                if ($useHtml5 && !str_starts_with($key, '//') && !str_starts_with($key, './/')) {
                    try {
                        if ($isSingleSelection) {
                            $node = $context ? $context->querySelector($key) : $dom->querySelector($key);
                            if ($node) $targetNodes[] = $node;
                        } else {
                            $nodeList = $context ? $context->querySelectorAll($key) : $dom->querySelectorAll($key);
                            if ($nodeList->length > 0) {
                                foreach ($nodeList as $n) $targetNodes[] = $n;
                            }
                        }
                    } catch (\Exception $e) {
                        
                        $useXPath = true;
                    }
                } else {
                    $useXPath = true;
                }

                if ($useXPath) {
                    $xpathSel = $css2xpath($key);
                    $xp = $useHtml5 ? $xpath5 : $xpath;
                    $nodeList = $xp->query($xpathSel, $context ?? $dom);
                    
                    if ($nodeList !== false && $nodeList->length > 0) {
                        if ($isSingleSelection) {
                            $targetNodes[] = $nodeList->item(0);
                        } else {
                            foreach ($nodeList as $n) $targetNodes[] = $n;
                        }
                    }
                }

                if (empty($targetNodes)) continue;

                
                if (is_string($value)) {
                    if ($value === '@remove') {
                        foreach ($targetNodes as $node) {
                            if ($node->parentNode) {
                                $node->parentNode->removeChild($node);
                            }
                        }
                        continue;
                    }

                    foreach ($targetNodes as $node) {
                        $marker = $getMarker();
                        $node->textContent = $marker;
                        $replacements[$marker] = "<?= htmlspecialchars({$value} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                    }
                } 
                
                elseif (is_array($value)) {
                    
                    if (isset($value['@attrs'])) {
                        foreach ($targetNodes as $node) {
                            $attrMarker = $getAttrMarker();
                            if (is_object($node) && method_exists($node, 'setAttribute')) {
                                $node->setAttribute($attrMarker, $attrMarker);
                                
                                $replacements[$attrMarker . '="' . $attrMarker . '"'] = "<?= {$value['@attrs']} ?>";
                            }
                        }
                        unset($value['@attrs']);
                    }

                    if (isset($value['@if'])) {
                        foreach ($targetNodes as $node) {
                            $startMarker = $getMarker();
                            $endMarker = $getMarker();
                            
                            $replacements[$startMarker] = "<?php if ({$value['@if']}): ?>";
                            $replacements[$endMarker] = "<?php endif; ?>";
                            
                            
                            
                            $startCommentNode = $dom->createComment($startMarker);
                            $endCommentNode = $dom->createComment($endMarker);
                            
                            $node->parentNode->insertBefore($startCommentNode, $node);
                            if ($node->nextSibling) {
                                $node->parentNode->insertBefore($endCommentNode, $node->nextSibling);
                            } else {
                                $node->parentNode->appendChild($endCommentNode);
                            }
                        }
                        unset($value['@if']);
                    }

                    if (isset($value['@text'])) {
                        
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $node->textContent = $marker;
                            $replacements[$marker] = "<?= htmlspecialchars({$value['@text']} ?? '', ENT_QUOTES, 'UTF-8') ?>";
                        }
                        unset($value['@text']);
                    }

                    if (isset($value['@html'])) {
                        
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $node->textContent = $marker;
                            $replacements[$marker] = "<?= {$value['@html']} ?? '' ?>";
                        }
                        unset($value['@html']);
                    }

                    if (isset($value['@append'])) {
                        
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $replacements[$marker] = "<?= {$value['@append']} ?? '' ?>";
                            $node->appendChild($dom->createComment($marker));
                        }
                        unset($value['@append']);
                    }

                    if (isset($value['@prepend'])) {
                        
                        foreach ($targetNodes as $node) {
                            $marker = $getMarker();
                            $replacements[$marker] = "<?= {$value['@prepend']} ?? '' ?>";
                            $node->insertBefore($dom->createComment($marker), $node->firstChild);
                        }
                        unset($value['@prepend']);
                    }

                    if (isset($value['@foreach'])) {
                        
                        $templateNode = $targetNodes[0];
                        $container = $templateNode->parentNode;
                        
                        $foreachStr = $value['@foreach'];
                        unset($value['@foreach']); 
                        
                        $startMarker = $getMarker();
                        $endMarker = $getMarker();
                        
                        $replacements[$startMarker] = "<?php foreach ({$foreachStr}): ?>";
                        $replacements[$endMarker] = "<?php endforeach; ?>";
                        
                        $startCommentNode = $dom->createComment($startMarker);
                        $endCommentNode = $dom->createComment($endMarker);
                        
                        
                        $container->insertBefore($startCommentNode, $templateNode);
                        if ($templateNode->nextSibling) {
                            $container->insertBefore($endCommentNode, $templateNode->nextSibling);
                        } else {
                            $container->appendChild($endCommentNode);
                        }
                        
                        
                        for ($i = 1; $i < count($targetNodes); $i++) {
                            $nodeToRemove = $targetNodes[$i];
                            if ($nodeToRemove->parentNode) {
                                $nodeToRemove->parentNode->removeChild($nodeToRemove);
                            }
                        }
                        
                        
                        if (!empty($value)) {
                            $applyRules($value, $templateNode);
                        }
                        
                    } else {
                        
                        if (!empty($value)) {
                            foreach ($targetNodes as $node) {
                                $applyRules($value, $node);
                            }
                        }
                    }
                }
            }
        };

        
        $applyRules($rules);
        
        $htmlOut = $useHtml5 ? $dom->saveHtml() : $dom->saveHTML();
        
        
        if ($useHtml5) {
            $htmlOut = preg_replace_callback('/(<(?:script|style)[^>]*>)(.*?)(<\/(?:script|style)>)/is', function($m) {
                return $m[1] . htmlspecialchars_decode($m[2], ENT_QUOTES) . $m[3];
            }, $htmlOut);
            
            
            $htmlOut = str_replace(['<!--?', '?-->'], ['<?', '?>'], $htmlOut);
        } else {
            $htmlOut = str_replace('<?xml encoding="utf-8" ?>', '', $htmlOut);
        }
        
        
        $trans = [];
        foreach ($replacements as $marker => $phpCode) {
            $trans['<!--' . $marker . '-->'] = $phpCode; 
            $trans[$marker] = $phpCode; 
        }
        $htmlOut = strtr($htmlOut, $trans);
        
        $htmlOut = "<?php // fst_rules_hash: {$rules_hash} ?>\n" . $htmlOut;
        file_put_contents($cacheFile, $htmlOut);
    }

    
    $shared_data = function_exists('fst_app') ? (fst_app('shared_view_data') ?? []) : [];
    $data = array_merge($shared_data, $data);
    extract($data, EXTR_SKIP);
    require $cacheFile;
}




function fst_template_render(string $templatePath, array $data, array $rules, ?string $cacheDir = null, bool $forceRebuild = false): string {
    ob_start();
    fst_template($templatePath, $data, $rules, $cacheDir, $forceRebuild);
    return ob_get_clean();
}

// FILE: bootstrap.php
$fst_config = fst_app('config');


if (php_sapi_name() !== 'cli' && strpos($_SERVER['REQUEST_URI'], 'fullstuck.php') !== false) {
    http_response_code(500);
    die('
        <div style="font-family: system-ui, -apple-system, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #24324f; border-radius: 8px; background: #172033; color: #f8fafc;">
            <h2 style="color: #ef4444; margin-top: 0;">🚨 Routing Misconfigured!</h2>
            <p style="color: #94a3b8;">Framework mendeteksi <code>fullstuck.php</code> di dalam URL. Ini menandakan URL Rewriting di web server Anda belum aktif.</p>
            <p style="color: #94a3b8;"><strong>Solusi:</strong> Pastikan Anda menggunakan web server yang mendukung single-entry routing (Apache dengan .htaccess, Nginx, atau FrankenPHP). Silakan baca dokumentasi FullStuck bagian Deployment.</p>
        </div>
    ');
}



$require_items = $fst_config['routing']['require'] ?? [];
foreach ($require_items as $item) {
    $raw_path = FST_ROOT_DIR . '/' . ltrim($item, '/');
    
    
    if (is_dir($raw_path)) {
        $raw_path = rtrim($raw_path, '/\\') . DIRECTORY_SEPARATOR . '*.php';
    }
    
    
    $matched_files = glob($raw_path);
    if ($matched_files) {
        foreach ($matched_files as $file) {
            $real_path = realpath($file);
            
            if ($real_path && str_starts_with($real_path, realpath(FST_ROOT_DIR)) && is_file($real_path) && str_ends_with($real_path, '.php')) {
                require_once $real_path;
            }
        }
    }
}


$routes_files = (array) ($fst_config['routing']['routes_file'] ?? ['router.php']);
foreach ($routes_files as $file) {
    if (file_exists(FST_ROOT_DIR . '/' . $file)) {
        require FST_ROOT_DIR . '/' . $file;
    } elseif (!fst_is_dev()) {
        fst_abort(500, "Configuration Error: Routes file not found at '{$file}'");
    }
}


if (php_sapi_name() !== 'cli') {
    fst_run();
}

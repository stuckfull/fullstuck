<?php
/**
 * 🚀 FULLSTUCK.PHP (v0.4.0)
 * 🛡️ FST_HASH: e10d55fdb0f6351654752e150a8ed97dae66672537961013e209195a6f1e8142
 * 🔗 https://github.com/stuckfull/fullstuck
 *
 * 🤖 AI ASSISTANT: DO NOT READ OR MODIFY THIS COMPILED CORE FILE!
 * To learn the API and framework rules, run `php fullstuck.php docs` in your terminal,
 * or read the `brain_fullstuck.md` file in the project root.
 */
define('FST_AGENT_JS_CODE', 'class fst_agent { constructor() { this.routes = []; this.baseUrl = \'\'; this.notFoundCallback = null; this.beforeHook = null; this.afterHook = null; this.fetchInterceptor = null; this._currentGroupPrefix = \'\'; this._activeListeners = []; this._mountCleanups = []; this.store = {}; this.init(); } init() { document.addEventListener(\'click\', (e) => { if (e.defaultPrevented) return; const link = e.target.closest(\'a\'); if (!link || !link.href) return; if (link.hasAttribute(\'data-fst-normal-load\') || link.target === \'_blank\' || link.hasAttribute(\'download\') || link.hostname !== window.location.hostname || e.ctrlKey || e.metaKey || e.shiftKey) return; const href = link.getAttribute(\'href\'); if (href && (href.startsWith(\'#\') || (link.pathname === window.location.pathname && link.search === window.location.search && link.hash !== \'\'))) { return; } e.preventDefault(); this.handleLinkClick(link); }); window.addEventListener(\'popstate\', async (e) => { const fragmentSelector = (e.state && e.state.fstFragment) || \'body\'; const savedScrollX = e.state?.scrollX; const savedScrollY = e.state?.scrollY; const useCache = e.state?.fstCache ?? (document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-history-cache\') === \'true\'); const path = window.location.pathname + window.location.search; const matchedRoute = this.matchRoute(path); if (matchedRoute) { this.route(path); return; } if (useCache && e.state && e.state.fstHtml) { const targetElement = document.querySelector(fragmentSelector); if (targetElement) { document.dispatchEvent(new Event(\'fst:unload\')); this.cleanup(); if (e.state.fstBodyAttrs && fragmentSelector === \'body\') { const parser = new DOMParser(); const doc = parser.parseFromString(`<div ${e.state.fstBodyAttrs}></div>`, \'text/html\'); const newBody = doc.body.firstChild; Array.from(document.body.attributes).forEach(attr => document.body.removeAttribute(attr.name)); Array.from(newBody.attributes).forEach(attr => document.body.setAttribute(attr.name, attr.value)); } targetElement.innerHTML = e.state.fstHtml; this.reexecuteScripts(targetElement); document.dispatchEvent(new Event(\'fst:load\')); } else { window.location.reload(); return; } } else { await this.fetchFragment(window.location.href, fragmentSelector, false, null, true); } if (savedScrollX !== undefined && savedScrollY !== undefined) { window.scrollTo({ left: savedScrollX, top: savedScrollY, behavior: \'instant\' }); } else if (window.location.hash) { const targetAnchor = document.querySelector(window.location.hash); if (targetAnchor) targetAnchor.scrollIntoView({ behavior: \'smooth\' }); } }); document.addEventListener(\'submit\', async (e) => { if (e.defaultPrevented) return; const form = e.target; if (form.hasAttribute(\'data-fst-normal-load\')) return; e.preventDefault(); this.handleFormSubmit(form); }); this.setNotFound((path, triggerElement) => { let fragmentSelector = \'body\'; let pushHistory = true; if (triggerElement) { fragmentSelector = triggerElement.getAttribute(\'data-fst-fragment\') || \'body\'; if (triggerElement.getAttribute(\'data-fst-history\') === \'false\') pushHistory = false; } this.fetchFragment(path, fragmentSelector, pushHistory, triggerElement); }); window.addEventListener(\'DOMContentLoaded\', () => { if (!window.history.state) { const bodyAttrs = Array.from(document.body.attributes).map(a => `${a.name}="${a.value}"`).join(\' \'); window.history.replaceState({ fstHtml: document.body.innerHTML, fstFragment: \'body\', fstBodyAttrs: bodyAttrs }, \'\', window.location.href); } document.dispatchEvent(new Event(\'fst:load\')); const path = window.location.pathname + window.location.search; if (this.matchRoute(path)) { this.route(path); } }); } handleLinkClick(link) { const href = link.href; const path = this.getPathFromHref(href); if (path.startsWith(this.baseUrl)) { this.navigate(path, link); } else { window.location.href = href; } } async handleFormSubmit(form) { const fragmentSelector = form.getAttribute(\'data-fst-fragment\') || \'body\'; const pushHistory = form.getAttribute(\'data-fst-history\') !== \'false\'; const indicator = this.getIndicatorClass(form); const targetElement = document.querySelector(fragmentSelector); if (targetElement) targetElement.classList.add(...indicator.split(\' \')); try { const method = (form.getAttribute(\'method\') || \'GET\').toUpperCase(); const action = form.getAttribute(\'action\') || window.location.href; const formData = new FormData(form); const reqHeader = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-req-header\') || \'X-FST-Request\'; const targetHeader = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-fragment-header\') || \'X-FST-Fragment\'; const headers = { [reqHeader]: \'true\', [targetHeader]: fragmentSelector }; let fetchOptions = { method, headers }; let finalUrl = action; if (method === \'GET\') { const params = new URLSearchParams(formData); finalUrl = action.includes(\'?\') ? `${action}&${params.toString()}` : `${action}?${params.toString()}`; } else { fetchOptions.body = formData; } const loadingEvent = new CustomEvent(\'fst:loading\', { detail: { url: finalUrl, fragmentSelector, triggerElement: form }, cancelable: true }); if (!document.dispatchEvent(loadingEvent)) { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); return; } if (this.fetchInterceptor) { const intercepted = await this.fetchInterceptor(finalUrl, fetchOptions); if (intercepted) fetchOptions = intercepted; } const response = await fetch(finalUrl, fetchOptions); const redirectUrl = response.headers.get(\'X-FST-Redirect\'); if (redirectUrl) { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); await this.fetchFragment(redirectUrl, fragmentSelector, pushHistory); return; } if (response.redirected) { window.location.href = response.url; return; } if (!response.ok && response.status !== 400 && response.status !== 422) { const errorHtml = await response.text(); document.open(); document.write(errorHtml); document.close(); return; } const html = await response.text(); this.processFragmentResponse(html, fragmentSelector, targetElement, response, form, pushHistory, finalUrl, method); } catch (err) { window.location.reload(); } finally { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); } } getPathFromHref(href) { const url = new URL(href, window.location.origin); let path = url.pathname; if (url.search) path += url.search; return path; } navigate(path, triggerElement) { if (this.beforeHook && this.beforeHook(path) === false) return; if (window.history.state) { const currentState = window.history.state; currentState.scrollX = window.scrollX; currentState.scrollY = window.scrollY; window.history.replaceState(currentState, \'\'); } const pushHistory = triggerElement ? triggerElement.getAttribute(\'data-fst-history\') !== \'false\' : true; const href = window.location.origin + path; let routePath = path.replace(this.baseUrl, "") || "/"; const match = this.matchRoute(routePath); if (match) { if (pushHistory) { window.history.pushState({}, "", href); } this.route(routePath, triggerElement); } else { this.notFoundCallback ? this.notFoundCallback(href, triggerElement) : console.log(`No route matched for: ${href}`); } } route(path, triggerElement) { for (const { pattern, callback } of this.routes) { const match = this.matchRouteCheck(pattern, path); if (match) { callback(match, triggerElement); if (this.afterHook) this.afterHook(path, triggerElement); return; } } } matchRoute(path) { for (const { pattern } of this.routes) { const match = this.matchRouteCheck(pattern, path); if (match) return match; } return null; } matchRouteCheck(pattern, path) { const [patternPath, patternQuery] = pattern.split("?"); const [urlPath, urlQuery] = path.split("?"); const regex = new RegExp("^" + patternPath.replace(/:\\w+/g, "([^/]+)") + "$"); const match = urlPath.match(regex); if (match) { const params = { param: path, query: {} }; const keys = patternPath.match(/:\\w+/g) || []; keys.forEach((key, i) => { params[key.substring(1)] = match[i + 1]; }); if (urlQuery) { const searchParams = new URLSearchParams(urlQuery); for (const [key, value] of searchParams) { params.query[key] = value; } } return params; } return null; } set(pattern, callback) { this.routes.push({ pattern: this._currentGroupPrefix + pattern, callback }); } group(prefix, callback) { const prevPrefix = this._currentGroupPrefix; this._currentGroupPrefix += prefix; callback(); this._currentGroupPrefix = prevPrefix; } setNotFound(callback) { this.notFoundCallback = callback; } setBefore(callback) { this.beforeHook = callback; } setAfter(callback) { this.afterHook = callback; } setInterceptor(callback) { this.fetchInterceptor = callback; } getIndicatorClass(triggerElement) { return (triggerElement && triggerElement.getAttribute("data-fst-indicator")) || document.querySelector("script#fst-agent")?.getAttribute("data-indicator-class") || "fst-loading"; } async fetchFragment(url, fragmentSelector, pushHistory, triggerElement = null, isPopstate = false) { const reqHeader = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-req-header\') || \'X-FST-Request\'; const targetHeader = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-fragment-header\') || \'X-FST-Fragment\'; const targetElement = document.querySelector(fragmentSelector); const indicator = this.getIndicatorClass(triggerElement); if (targetElement) targetElement.classList.add(...indicator.split(\' \')); const loadingEvent = new CustomEvent(\'fst:loading\', { detail: { url, fragmentSelector, triggerElement }, cancelable: !isPopstate }); if (!isPopstate && !document.dispatchEvent(loadingEvent)) { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); return; } try { const headers = { [reqHeader]: \'true\', [targetHeader]: fragmentSelector }; let fetchOptions = { headers }; if (this.fetchInterceptor) { const intercepted = await this.fetchInterceptor(url, fetchOptions); if (intercepted) fetchOptions = intercepted; } const response = await fetch(url, fetchOptions); if (!response.ok) { const errorHtml = await response.text(); document.open(); document.write(errorHtml); document.close(); return; } const redirectUrl = response.headers.get(\'X-FST-Redirect\'); if (redirectUrl) { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); if (isPopstate) { window.location.href = redirectUrl; return; } await this.fetchFragment(redirectUrl, fragmentSelector, pushHistory); return; } if (response.redirected) { window.location.href = response.url; return; } const contentType = response.headers.get(\'content-type\'); if (!contentType || !contentType.includes(\'text/html\')) { window.location.href = url; return; } const html = await response.text(); this.processFragmentResponse(html, fragmentSelector, targetElement, response, triggerElement, pushHistory, url, \'GET\', isPopstate); if (!isPopstate) { const doScroll = triggerElement ? triggerElement.getAttribute(\'data-fst-scroll\') !== \'false\' : true; if (doScroll) { const scrollBehavior = triggerElement ? (triggerElement.getAttribute(\'data-fst-scroll\') === \'smooth\' ? \'smooth\' : \'instant\') : \'instant\'; const behavior = scrollBehavior; if (window.location.hash) { const targetAnchor = document.querySelector(window.location.hash); if (targetAnchor) { targetAnchor.scrollIntoView({ behavior }); } else { if (fragmentSelector === \'body\') window.scrollTo({ top: 0, behavior }); else targetElement.scrollTo({ top: 0, behavior }); } } else { if (fragmentSelector === \'body\') window.scrollTo({ top: 0, behavior }); else targetElement.scrollTo({ top: 0, behavior }); } } } } catch (err) { window.location.href = url; } finally { if (targetElement) targetElement.classList.remove(...indicator.split(\' \')); } } processFragmentResponse(html, fragmentSelector, targetElement, response, triggerElement, pushHistory, url, method, isPopstate = false) { const newTitle = html.match(/<title[^>]*>([\\s\\S]*?)<\\/title>/i); if (newTitle) document.title = newTitle[1]; const bodyAttrs = response.headers.get(\'X-FST-Body-Attrs\'); if (bodyAttrs !== null && fragmentSelector === \'body\') { const parser = new DOMParser(); const doc = parser.parseFromString(`<div ${bodyAttrs}></div>`, \'text/html\'); const newBody = doc.body.firstChild; Array.from(document.body.attributes).forEach(attr => document.body.removeAttribute(attr.name)); Array.from(newBody.attributes).forEach(attr => document.body.setAttribute(attr.name, attr.value)); } if (!targetElement) throw new Error(\'Target not found\'); document.dispatchEvent(new Event(\'fst:unload\')); this.cleanup(); targetElement.innerHTML = html; if (pushHistory && method === \'GET\') { const cacheFlag = triggerElement ? triggerElement.getAttribute(\'data-fst-cache\') : null; const globalCache = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-history-cache\') === \'true\'; const fstCache = cacheFlag !== null ? cacheFlag === \'true\' : globalCache; window.history.pushState({ fstHtml: html, fstFragment: fragmentSelector, fstBodyAttrs: bodyAttrs, fstCache: fstCache }, \'\', url); } else if (isPopstate) { window.history.replaceState({ fstHtml: html, fstFragment: fragmentSelector, fstBodyAttrs: bodyAttrs, fstCache: window.history.state?.fstCache ?? false }, \'\', url); } this.reexecuteScripts(targetElement); document.dispatchEvent(new Event(\'fst:load\')); } reexecuteScripts(targetElement) { if (!window._fst_executed_scripts) { window._fst_executed_scripts = new Set(); document.querySelectorAll(\'script[src]\').forEach(s => window._fst_executed_scripts.add(s.src)); } const scripts = targetElement.querySelectorAll(\'script\'); scripts.forEach(oldScript => { if (oldScript.id === \'fst-agent\' || oldScript.hasAttribute(\'data-fst-ignore\')) return; if (oldScript.src) { if (window._fst_executed_scripts.has(oldScript.src)) { oldScript.remove(); return; } window._fst_executed_scripts.add(oldScript.src); } const newScript = document.createElement(\'script\'); Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value)); newScript.appendChild(document.createTextNode(oldScript.innerHTML)); oldScript.parentNode.replaceChild(newScript, oldScript); }); } go(url, options = {}) { const defaultFragment = document.querySelector(\'script#fst-agent\')?.getAttribute(\'data-default-fragment\') || \'body\'; const fragmentSelector = options.fragment || defaultFragment; const history = options.history !== false; const virtualTrigger = { getAttribute: (attr) => { if (attr === \'data-fst-history\') return history ? \'true\' : \'false\'; if (attr === \'data-fst-scroll\') return options.scroll !== undefined ? String(options.scroll) : null; if (attr === \'data-fst-indicator\') return options.indicator || null; if (attr === \'data-fst-cache\') return options.cache !== undefined ? String(options.cache) : null; if (attr === \'data-fst-fragment\') return fragmentSelector; return null; }, hasAttribute: (attr) => false // deprecated, use getAttribute }; const path = this.getPathFromHref(url); const match = this.matchRoute(path); if (match) { if (history) { window.history.pushState({}, "", url); } this.route(path, virtualTrigger); } else { this.fetchFragment(url, fragmentSelector, history, virtualTrigger); } } escape(str) { if (str === null || str === undefined) return \'\'; return String(str) .replace(/&/g, \'&amp;\') .replace(/</g, \'&lt;\') .replace(/>/g, \'&gt;\') .replace(/"/g, \'&quot;\') .replace(/\'/g, \'&#039;\'); } emit(eventName, detail = {}) { window.dispatchEvent(new CustomEvent(\'fst:\' + eventName, { detail })); } on(event, arg2, arg3) { let selector = typeof arg2 === \'string\' ? arg2 : null; let callback = selector ? arg3 : arg2; let options = (selector ? arguments[3] : arg3) || {}; let target = window; let eventName = event; let wrapper; if (selector) { target = document; wrapper = (e) => { const el = e.target.closest(selector); if (el) callback(e, el); }; } else if (!event.startsWith(\'fst:\') && ![\'click\', \'scroll\', \'resize\', \'keydown\', \'keyup\', \'submit\', \'change\', \'input\'].includes(event)) { eventName = \'fst:\' + event; wrapper = (e) => callback(e.detail); } else { wrapper = callback; } target.addEventListener(eventName, wrapper, options); if (!options.global) { this._activeListeners.push({ target, event: eventName, wrapper, options }); } } onMount(callback) { const wrapper = () => { const cleanup = callback(); if (typeof cleanup === \'function\') { this._mountCleanups.push(cleanup); } }; document.addEventListener(\'fst:load\', wrapper); this._activeListeners.push({ target: document, event: \'fst:load\', wrapper, options: {} }); } cleanup() { this._activeListeners.forEach(({ target, event, wrapper, options }) => { target.removeEventListener(event, wrapper, options); }); this._activeListeners = []; this._mountCleanups.forEach(cleanupFn => cleanupFn()); this._mountCleanups = []; } } window.fst = new fst_agent(); window.fst.e = window.fst.escape;');


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

function _fst_error_handler($errno, $errstr, $errfile, $errline) { if (!(error_reporting() & $errno)) return false; throw new ErrorException($errstr, 0, $errno, $errfile, $errline); }

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

    
    
    
    $selector = trim($selector);
    if (!preg_match('/^[a-zA-Z0-9_\-\.\#\s,>\[\]="\']+$/', $selector)) {
        return $html; 
    }

    
    
    $singleton_tags = ['body', 'main'];
    if (!str_starts_with($selector, '#') && !str_starts_with($selector, '.')) {
        $tag = strtolower($selector);
        if (in_array($tag, $singleton_tags)) {
            
            if (preg_match('/<' . $tag . '[^>]*>(.*?)<\/' . $tag . '>/is', $html, $m)) {
                return $m[1];
            }
        }
    }

    
    $paths = [];
    foreach (explode(',', $selector) as $sel) {
        $sel = trim($sel);
        $sel = preg_replace('/\s*>\s*/', '/', $sel); 
        $sel = preg_replace('/\s+/', '//', $sel); 
        $sel = preg_replace('/#([\w\-]+)/', '[@id="$1"]', $sel); 
        $sel = preg_replace('/\.([\w\-]+)/', '[contains(concat(" ", normalize-space(@class), " "), " $1 ")]', $sel); 
        
        
        
        
        $sel = preg_replace('/\[([\w\-]+)=(["\']?)([\w\-\s\/\.]*)\2\]/', '[@$1="$3"]', $sel);
        
        
        $sel = preg_replace('/\[([\w\-]+)\]/', '[@$1]', $sel);
        
        $sel = preg_replace('/(^|\/|\|)(\[)/', '$1*$2', $sel);
        
        
        if (!str_starts_with($sel, '/')) {
            $sel = '//' . $sel;
        }
        
        $paths[] = $sel;
    }
    $xpath_query = implode(' | ', $paths);

    $is_modern_dom = class_exists('Dom\HTMLDocument');

    
    if ($is_modern_dom) {
        
        try {
            $dom = \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR);
            $xpath = new \Dom\XPath($dom);
        } catch (\Throwable $e) {
            return $html;
        }
    } else {
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
    }
    

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
            fst_abort(500, "Database Connection Failed [{$conn_name}]: " . (fst_is_dev() ? $e->getMessage() : 'Error.'));
        }
    }
    
    return $fst_pdo_pool[$conn_name];
}

function fst_db_begin($connection = null) { return _fst_get_pdo($connection)->beginTransaction(); }
function fst_db_commit($connection = null) { return _fst_get_pdo($connection)->commit(); }
function fst_db_rollback($connection = null) { return _fst_get_pdo($connection)->rollBack(); }

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

function fst_db_row($table, $conditions = [], $options = []) { $options['limit'] = 1; $options['mode'] = 'ROW'; return fst_db_select($table, $conditions, $options); }
function fst_db_exists($table, $conditions = [], $options = []) { $options['select'] = '1'; return !empty(fst_db_row($table, $conditions, $options)); }

// FILE: router.php
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
    
    
    $default_titles = [404 => 'Not Found', 403 => 'Forbidden', 405 => 'Method Not Allowed', 500 => 'Internal Server Error'];
    $title = $default_titles[$code] ?? 'Error';
    
    $route = fst_app('current_route');
    $view_path = $route['view'] ?? '';
    $layouts = $route['layouts'] ?? [];
    
    
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
                die(); 
            }
            break; 
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
    
    if (!empty($route['guards'])) {
        foreach ($route['guards'] as $guard) {
            $guard_path = FST_ROOT_DIR . '/' . $guard;
            if (file_exists($guard_path)) {
                require $guard_path;
            }
        }
    }
    
    
    if (isset($route['handler'])) {
        require FST_ROOT_DIR . '/' . $route['handler'];
        fst_app('route_found', true);
        return true;
    } 
    
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
    
    
    if (isset($routes['STATIC'][$method][$uri])) {
        $route = $routes['STATIC'][$method][$uri];
        return _fst_execute_route($route, []);
    }
    
    
    if (isset($routes['DYNAMIC'][$method])) {
        foreach ($routes['DYNAMIC'][$method] as $regex => $route) {
            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches); 
                
                
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
        if (_fst_match_colocation_routes()) {
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
function fst_method() { return strtoupper($_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET'); }
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
            $url_port = parse_url($url, PHP_URL_PORT);
            
            $self_host_raw = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $self_host = parse_url('http://' . $self_host_raw, PHP_URL_HOST);
            $self_port = parse_url('http://' . $self_host_raw, PHP_URL_PORT) ?: $_SERVER['SERVER_PORT'] ?? null;
            
            if ($url_host !== null) {
                if (strtolower($url_host) !== strtolower((string)$self_host)) {
                    fst_abort(403, 'Redirect to external domain is not allowed. Use fst_redirect($url, 302, true) to allow.');
                }
                if ($url_port !== null && $self_port !== null && $url_port != $self_port) {
                    if (!($url_port == 80 && $self_port == 443) && !($url_port == 443 && $self_port == 80)) {
                        fst_abort(403, 'Redirect to different port is not allowed.');
                    }
                }
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
function fst_session_regenerate(bool $delete_old = true) { if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id($delete_old); }

function fst_upload($key, $folder, $options = []) {
    if (!isset($_FILES[$key])) return ['success' => false, 'error' => 'No file uploaded.', 'path' => null];
    
    $files_input = $_FILES[$key];
    $is_multiple = is_array($files_input['name']);
    
    $process_single = function($name, $tmp_name, $size, $error) use ($folder, $options) {
        if ($error !== UPLOAD_ERR_OK) return ['success' => false, 'error' => 'Upload error code: ' . $error, 'path' => null];
        
        $max_size_kb = $options['max_size'] ?? 2048;
        if ($size > $max_size_kb * 1024) return ['success' => false, 'error' => "File is too large (max {$max_size_kb} KB).", 'path' => null];
        
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        $default_allowed = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','csv','txt','zip'];
        $allowed_types = $options['allowed_types'] ?? $default_allowed;
        
        if (!in_array($ext, $allowed_types)) {
            return ['success' => false, 'error' => "Extension `{$ext}` is not allowed.", 'path' => null];
        }

        $blocked_ext = ['php','php3','php4','php5','php7','phtml','phar','pht','shtml','htaccess','asp','aspx','jsp','cgi','exe'];
        if (in_array($ext, $blocked_ext)) {
            return ['success' => false, 'error' => "Security Error: Executable extension blocked.", 'path' => null];
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
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) && !str_starts_with(strtolower($actual_mime), 'image/')) {
                return ['success' => false, 'error' => "Security Error: File extension does not match its actual content type.", 'path' => null];
            }
        }
        
        $safe_basename = preg_replace("/[^a-zA-Z0-9\._-]/", "_", basename($name, ".".$ext));
        $filename = $safe_basename . '-' . uniqid() . '.' . $ext;
        if (str_contains($folder, '..')) {
            return ['success' => false, 'error' => 'Security Error: Path traversal detected.', 'path' => null];
        }
        
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
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    static $mime_types = ['css'=>'text/css', 'js'=>'application/javascript', 'json'=>'application/json', 'xml'=>'application/xml', 'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'png'=>'image/png', 'gif'=>'image/gif', 'webp'=>'image/webp', 'ico'=>'image/x-icon', 'svg'=>'image/svg+xml', 'woff'=>'font/woff', 'woff2'=>'font/woff2', 'ttf'=>'font/ttf', 'mp4'=>'video/mp4', 'webm'=>'video/webm', 'html'=>'text/html', 'txt'=>'text/plain', 'map'=>'application/json', 'pdf'=>'application/pdf', 'zip'=>'application/zip', 'avif'=>'image/avif'];

    
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
                $len = function_exists('mb_strlen') ? mb_strlen((string)$value, 'UTF-8') : strlen((string)$value);
                if ($len < $min) {
                    $errors[$field][] = "The field '{$field}' must be at least {$min} characters.";
                    $field_valid = false;
                }
            } elseif ($rule_name === 'max') {
                $max = (int)($params[0] ?? 0);
                $len = function_exists('mb_strlen') ? mb_strlen((string)$value, 'UTF-8') : strlen((string)$value);
                if ($len > $max) {
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

// FILE: template.php
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
    
    
    $content = str_replace('@{{', '@@FST_ESCAPE_OPEN@@', $content);
    $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?= e($1) ?>', $content);
    $content = str_replace('@@FST_ESCAPE_OPEN@@', '{{', $content);
    
    $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?= $1 ?>', $content);
    
    
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
    
    
    $content = preg_replace('/@yield\s*\(\'([^\']+)\'(?:,\s*\'([^\']+)\')?\)/', '<?= $__sections[\'$1\'] ?? \'$2\' ?>', $content);
    
    
    $content = preg_replace('/@section\s*\(\'([^\']+)\'\)/', '<?php ob_start(); $__section_name = \'$1\'; ?>', $content);
    $content = str_replace('@endsection', '<?php $__sections[$__section_name] = ob_get_clean(); ?>', $content);
    
    
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
    
    
    call_user_func(function() use ($compiled, $data) {
        $shared = fst_app('shared_view_data') ?? [];
        extract($shared, EXTR_SKIP);
        extract($data, EXTR_SKIP);
        global $__sections; 
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
    
    
    $shared = fst_app('shared_view_data') ?? [];
    
    
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
    
    
    $final_html = $__sections['content'] ?? '';
    
    
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

// FILE: bootstrap.php
$fst_config = fst_app('config');


if (php_sapi_name() !== 'cli' && strpos($_SERVER['REQUEST_URI'], 'fullstuck.php') !== false) {
    http_response_code(500);
    die('
        <div style="font-family: system-ui, -apple-system, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #24324f; border-radius: 8px; background: #172033; color: #f8fafc;">
            <h2 style="color: #ef4444; margin-top: 0;">🚨 Routing Misconfigured!</h2>
            <p style="color: #94a3b8;">The framework detected <code>fullstuck.php</code> in the URL. This indicates that URL Rewriting is not active on your web server.</p>
            <p style="color: #94a3b8;"><strong>Solution:</strong> Ensure you are using a web server that supports single-entry routing (Apache with .htaccess, Nginx, or FrankenPHP). Please read the Deployment section of the FullStuck documentation.</p>
        </div>
    ');
}



$require_items = $fst_config['require'] ?? ['globals'];
foreach ($require_items as $item) {
    $raw_path = FST_ROOT_DIR . '/' . ltrim($item, '/');
    $real_path = realpath($raw_path);
    
    if ($real_path && is_dir($real_path)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($real_path));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require_once $file->getPathname();
            }
        }
    } else {
        if (is_dir($raw_path)) {
            $raw_path = rtrim($raw_path, '/\\') . DIRECTORY_SEPARATOR . '*.php';
        }
        $matched_files = glob($raw_path);
        if ($matched_files) {
            foreach ($matched_files as $file) {
                $rp = realpath($file);
                if ($rp && str_starts_with($rp, realpath(FST_ROOT_DIR)) && is_file($rp) && str_ends_with($rp, '.php')) {
                    require_once $rp;
                }
            }
        }
    }
}


if (php_sapi_name() !== 'cli') {
    fst_run();
}

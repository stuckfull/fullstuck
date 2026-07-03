# FullStuck.php v0.2.0 Cheatsheet
**Zero-config, single-file PHP micro-framework. No Composer. No `vendor/`. Deploy anywhere.**

📚 Full Documentation & Tutorials: https://github.com/stuckfull/fullstuck/blob/main/docs/v0.2.0.md

---

## Quick Start

**AI Agent setup:**
```
https://raw.githubusercontent.com/stuckfull/fullstuck/main/docs/ai-setup.md
```

**CLI init (headless):**
```bash
php fullstuck.php init --db=sqlite --admin-pass=stuck --admin-url=/stuck --spa=yes --scaffold=yes --htaccess=yes
# --db: sqlite | mysql | pgsql | none
# --spa: yes | no
# --scaffold: yes | minimal | no
```

**Dev server (GUI wizard):**
```bash
php -S localhost:8000 fullstuck.php
```

**Web server config:**
```apache
# Apache .htaccess
RewriteEngine On
RewriteBase /
RewriteRule ^(.*)$ fullstuck.php [L]
```
```nginx
# Nginx
location / { try_files $uri $uri/ /fullstuck.php?$query_string; }
```
```bash
# FrankenPHP
frankenphp php-server -r fullstuck.php
```

---

## Project Structure

```
my-project/
├── assets/          # Static files (CSS, JS, images)
├── fst-plugins/     # Plugins — manage via Admin Dashboard ONLY
├── views/           # HTML/PHP templates
├── fullstuck.json   # Config (single source of truth)
├── fullstuck.php    # Framework core — DO NOT MODIFY
└── router.php       # Route definitions
```

---

## `fullstuck.json` — Config Reference

```json
{
    "environment": "development",
    "ai_sop": true,
    "admin": {
        "page_url": "/stuck",
        "password": "$2y$12$...",
        "allowed_ips": []
    },
    "database": {
        "default": "main",
        "connections": {
            "main": { "driver": "sqlite", "database_path": "database.sqlite" },
            "mysql_db": {
                "driver": "mysql",
                "host": "${DB_HOST}",
                "dbname": "my_database",
                "username": "${DB_USER}",
                "password": "${DB_PASS}"
            }
        }
    },
    "routing": {
        "base_path": "/",
        "require": ["models", "utils.php", "helpers/api_*.php"],
        "public_folders": ["assets", "uploads", "storage/public"],
        "routes_file": ["router.php"],
        "error_handlers": {
            "404": "views/errors/404.php",
            "403": "Sorry, you do not have permission.",
            "500": "views/errors/500.php"
        },
        "regex_shortcuts": {
            "i": "([0-9]+)",
            "a": "([a-zA-Z0-9]+)",
            "s": "([a-zA-Z0-9\\-]+)",
            "h": "([a-fA-F0-9]+)",
            "any": "([^/]+)"
        }
    },
    "spa": {
        "enabled": true,
        "default_target": "body",
        "header_request": "X-FST-Request",
        "header_target": "X-FST-Target",
        "indicator_class": "fst-loading",
        "history_cache": false
    }
}
```

**Key notes:**
- `environment`: `"development"` shows full error traces; `"production"` hides them (logged to `.fst-error.log`).
- `ai_sop`: `true` = AI follows guided phase SOP; `false` = freestyle mode.
- `${ENV_VAR}` syntax interpolates OS environment variables — no `.env` file needed.
- `routing.require`: auto-includes PHP files/folders/globs before routes execute.
- `spa.enabled`: `true` (global), `false` (off), or `"manual"` (opt-in per page via `fst_spa_page()`).
- `spa.default_target`: change from `"body"` to `"#app"` if you have persistent sidebar/player.
- `spa.history_cache`: `false` (default) = back/forward always re-fetches from server. `true` = replay cached HTML (instant but stale).

---

## Strict Rules for AI

> **These rules are mandatory. Do not deviate.**

1. **Use `fst_*` helpers only.** Never use `$_POST`, `$_GET`, `$_FILES` raw or `new PDO()`.
2. **Never modify `fullstuck.php`.**
3. **Never manually edit files in `fst-plugins/`.** Use the Admin Dashboard.
4. **Always call `fst_csrf_check()` at the top of POST/PUT/DELETE route callbacks.**
5. **Validate input with `fst_validate()` only.**

---

## Admin Dashboard (`/stuck`)

Built-in control panel:
- Config Editor, Route Viewer
- Plugin Manager (install from GitHub Store)
- File Integrity Monitor, 1-Click Update

**Production hardening:** change the URL and set `allowed_ips` in `fullstuck.json`.

---

## Core Concepts

### Routing

```php
fst_get('/hello', fn() => print "Hello!");
fst_get('/user/{id:i}', fn($id) => print "ID: $id");        // :i = integer
fst_get('/post/{slug:any}?', fn($slug = 'home') => ...);    // ? = optional
fst_post('/submit', fn() => ...);
fst_any('/catch-all', fn() => ...);
```

**Middleware (onion model):**
```php
function require_login($next) {
    if (!fst_session_get('user_id')) return fst_redirect('/login');
    return $next();
}

fst_group('/admin', function() {
    fst_get('/dashboard', fn() => print "Admin Area");
}, 'require_login');
```

---

### Database

**Query Builder** — supports AND + `=` conditions only. For OR, LIKE, IN, `>`, etc., use `fst_db()`.

```php
// SELECT
$users  = fst_db_select('users', ['status' => 'active'], ['order_by' => 'id DESC']);
$user   = fst_db_row('users', ['email' => 'a@b.com']);         // single row or null
$exists = fst_db_exists('users', ['email' => 'a@b.com']);      // bool

// INSERT / UPDATE / DELETE
$id = fst_db_insert('users', ['name' => 'Budi', 'email' => 'budi@a.com']);
fst_db_update('users', ['status' => 'inactive'], ['id' => 5]);
fst_db_delete('users', ['id' => 5]);

// Multi-connection
fst_db_select('customers', [], ['connection' => 'mysql_db']);
```

**Raw Query `fst_db($mode, $sql, $params, $connection)`:**

| Mode | Returns |
|------|---------|
| `'ALL'` | `array` of associative arrays |
| `'ROW'` | single associative array |
| `'SCALAR'` | single primitive value |
| `'EXEC'` | `['affected_rows', 'last_insert_id']` |

```php
// JOIN
$posts = fst_db('ALL', "SELECT p.*, u.name FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = ?", ['published']);

// OR / LIKE
$products = fst_db('ALL', "SELECT * FROM products WHERE (name LIKE ? OR description LIKE ?) AND status = ?", ['%shoe%', '%shoe%', 'active']);

// IN
$cats = [1, 2, 5];
$placeholders = implode(',', array_fill(0, count($cats), '?'));
$items = fst_db('ALL', "SELECT * FROM items WHERE category_id IN ($placeholders)", $cats);

// SCALAR
$total = fst_db('SCALAR', "SELECT COUNT(*) FROM users WHERE status = ?", ['active']);

// EXEC
fst_db('EXEC', "UPDATE users SET last_login = NOW() WHERE id = ?", [1]);
```

**Transactions:**
```php
try {
    fst_db_begin();
    fst_db_insert('orders', [...]);
    fst_db_update('stock', ['qty' => 0], ['id' => 1]);
    fst_db_commit();
} catch (Exception $e) {
    fst_db_rollback();
}
```

---

### Request, Validation & CSRF

**CSRF — mandatory on all mutating routes:**
```php
fst_post('/register', function() {
    fst_csrf_check(); // MUST be first line

    $val = fst_validate(fst_request(), [
        'name'  => 'required|min:3',
        'email' => 'required|email',
        'age'   => 'required|min_value:18|max_value:60'
    ]);

    if (!$val['valid']) {
        fst_flash_set('error', implode(', ', array_merge(...array_values($val['errors']))));
        return fst_redirect('/register');
    }

    $clean = $val['data']; // trimmed & sanitized input
});
```

**Validation rules:** `required`, `email`, `numeric`, `in:a,b`, `min:X`, `max:X`, `min_value:X`, `max_value:X`.
For `unique` or `regex`, do manual checks after `fst_validate()`.

**CSRF in HTML templates:**
```php
// .php view
<?= fst_csrf_field() ?>

// fst_template ruleset (for .html files)
"form" => ["@append" => 'fst_csrf_field()']
```

**Flash messages (PRG pattern):**
```php
// Set (before redirect)
fst_flash_set('success', 'Saved!');

// Get (in next GET request)
$msg = fst_flash_get('success'); // null after first read
```

---

### Views

**PHP views:**
```php
// router.php
fst_view('profile.php', ['name' => 'Budi', 'age' => 25]);

// views/profile.php
<p>Name: <?= e($name) ?></p>  <!-- e() = XSS escape, always use for user data -->
```

**Layout nesting:**
```php
// router.php
fst_view('layout.php', ['view_path' => 'content.php', 'view_data' => ['title' => 'Hello']]);

// views/layout.php
<main><?php fst_view($view_path, $view_data); ?></main>
```

**Shared global variables (available in all views):**
```php
fst_view_share('site_name', 'MyApp');
fst_view_share(['user_role' => 'admin', 'theme' => 'dark']);
// fst_view_share vars are auto-injected into fst_template too
```

---

### File Upload

```php
fst_post('/upload', function() {
    fst_csrf_check();

    $result = fst_upload('photo', 'assets/uploads', [
        'max_size'      => 2048,        // KB
        'allowed_types' => ['jpg', 'png'],
        'allowed_mimes' => ['image/jpeg', 'image/png']
    ]);

    // Single file: $result = ['success', 'path', 'error', 'original_name']
    // Multiple (name="photo[]"): $result = array of the above
});
```

---

## Procedural DOM Templating (`fst_template`)

HTML files stay **100% static** — no PHP tags. Logic is declared as a PHP ruleset. Auto-escapes text by default (XSS-safe).

**Signature:**
```php
fst_template(string $html_path, array $data, array $rules, ?string $cache_dir = null, bool $force_rebuild = false);
```

In development, pass `fst_is_dev()` as `$force_rebuild` for hot-reloading.

### How Rules Work

Rules are **string PHP expressions** evaluated at render time. Always wrap in single quotes so they're not executed at definition time:

```php
"selector" => '$variable'         // ✅ evaluated at render
"selector" => "$variable"         // ❌ evaluated immediately (undefined var error)
```

### Ruleset DSL — Full Reference

```php
$rules = [

    // --- TEXT & HTML ---

    "title"        => '$pageTitle',                    // set innerText (XSS-safe)
    "h3"           => ["@text" => '$heading'],         // explicit text (use when mixing with attrs)
    "span.content" => ["@html" => '$htmlContent'],     // raw innerHTML (trusted content only)
    "head"         => ["@append"  => '"<style>...</style>"'],  // insertAdjacentHTML beforeend
    "div.wrap"     => ["@prepend" => '"<div class=\"alert\">!</div>"'],  // afterbegin

    // ⚠️ @append/@prepend render raw HTML — escape user data manually:
    // "div.comment" => ["@append" => '"<p>" . fst_escape($userComment) . "</p>"']


    // --- ATTRIBUTES ---

    "a.external"   => ["[href]" => '$linkUrl', "[target]" => '"_blank"'],
    "[data-fst=\"my-form\"]" => ["[action]" => '"/submit"', "h2" => '"Login"'],
    "a[data-type='link']"    => '"New Link Text"',    // CSS attribute selector as key


    // --- SINGLE NODE (prefix ^ = querySelector, not querySelectorAll) ---

    "^div.alert"   => '"First alert only"',


    // --- COMPILE-TIME REMOVAL (permanent, runs before cache is written) ---

    "div.debug-panel" => "@remove",                   // removes element entirely
    "img.thumbnail"   => ["[style]" => "@remove", "[src]" => '$realUrl'],  // removes attribute


    // --- CONDITIONALS & LOOPS (runtime PHP logic) ---

    // @if — show/hide element
    "div.promo"       => ["@if" => '$isPromoActive'],
    "a.btn-dashboard" => ["@if" => '$isLoggedIn'],
    "a.btn-login"     => ["@if" => '!$isLoggedIn'],

    // Ternary — change content/attrs on same element
    "button.auth"     => [
        "@text"   => '$isLoggedIn ? "Logout" : "Login"',
        "[href]"  => '$isLoggedIn ? "/logout" : "/login"',
        "[class]" => '$isLoggedIn ? "btn-danger" : "btn-primary"'
    ],

    // @foreach — first child node = template, extra dummy nodes auto-removed
    "ul.nav > li"     => [
        "@foreach" => '$menus as $menu',
        "a"        => ["[href]" => '$menu["url"]', "@text" => '$menu["label"]']
    ],

];
```

**Selector scoping:** Avoid generic selectors like `span` or `h2` — they match globally. Prefer `#content span.price` to avoid unintentional overwrites.

**Cache:** Auto-invalidates when the HTML file or ruleset (MD5 hash) changes. Cache files are in `view-cache/` — inspect them if you get a `ParseError`.

**Use cases:** SEO meta tags, dark mode hydration, injecting `json_encode()` into `<script type="application/json">`.

---

## SPA (Single Page Application)

SPA is **on by default** — all `<a>` clicks and `<form>` submits are intercepted and fetched in the background.

### HTML Data Attributes

| Attribute | Effect |
|-----------|--------|
| `data-fst-target="#id"` | Update only this element instead of `default_target` |
| `data-fst-indicator="class"` | Override `indicator_class` for this element only |
| `data-fst-history="false"` | Don't push to browser history (use on delete/action forms) |
| `data-fst-no-spa` or class `no-spa` | Force full page reload (logout, file downloads) |
| `data-fst-ignore` (on `<script>`) | Don't re-execute this script on SPA navigation |
| `data-fst-scroll="instant\|smooth\|false"` | Scroll behavior after load |
| `data-fst-cache="true\|false"` | Per-link back/forward cache control (overrides global `history_cache`) |

```html
<!-- Fragment update -->
<a href="/tab-profile" data-fst-target="#content">Profile</a>
<form action="/search" method="GET" data-fst-target=".results" data-fst-scroll="false">...</form>

<!-- No history pollution for action forms -->
<form action="/task/delete" method="POST" data-fst-history="false">...</form>

<!-- Force hard reload -->
<a href="/logout" data-fst-no-spa>Logout</a>
```

### Scroll Behavior

- Default on new route: scroll to top (`instant`)
- Back/Forward: re-fetches from server by default (ensures fresh data + security). Scroll position is auto-restored after load
- Set `"history_cache": true` in config or `data-fst-cache="true"` per-link to replay cached HTML instead (instant but may be stale)
- Anchor links (`#section`): handled natively by browser; cross-page anchors (`/about#team`) load page then smooth-scroll to target

### Lifecycle Events

```javascript
// cancelable — call e.preventDefault() to abort navigation (e.g., dirty form check)
document.addEventListener('fst:loading', (e) => {
    // e.detail: { url, targetSelector, triggerElement }
    if (window.isFormDirty && !confirm('Unsaved changes. Leave?')) e.preventDefault();
    NProgress.start();
});

// fires after fetch, before DOM injection — destroy old plugins here
document.addEventListener('fst:unload', () => {
    $('.select').select2('destroy');
});

// fires after DOM injection and scripts re-run — re-init plugins here
document.addEventListener('fst:load', () => {
    NProgress.done();
    $('.select').select2();
});
```

### Programmatic Navigation

```javascript
// Basic
fst.go('/dashboard');

// With options
fst.go('/profile', {
    target:    '#main-content',   // CSS selector
    history:   false,             // don't push to browser history
    scroll:    'smooth',          // 'instant' | 'smooth' | false
    indicator: 'loading-class',   // CSS class for loading state
    cache:     true               // cache this page for back/forward (overrides global)
});
```

---

## API Reference

### Routing & Response

```php
fst_get|post|put|patch|delete|any($path, $callback, $middleware)
fst_group($prefix, $callback, $middleware)

fst_view($path, $data)                          // render PHP view → void
fst_partial($path, $data)                       // alias for small components → void
fst_template($path, $data, $rules, $cache?, $force?) // render HTML template → void
fst_view_share($key, $value)                    // share var to all views → void

fst_json($data, $status = 200)                  // send JSON → exit
fst_text($string, $status = 200)                // send plain text → exit
fst_redirect($url, $code = 302, $external = false) // redirect (sends X-FST-Redirect in SPA) → exit
fst_abort($code, $message)                      // HTTP error response → exit

fst_uri()                                       // → string: current path
fst_method()                                    // → string: HTTP method
fst_input($key, $default = null)                // → mixed: single GET/POST/JSON value
fst_request()                                   // → array: all input
fst_file($key)                                  // → array|null: $_FILES entry
fst_upload($key, $folder, $options)             // → ['success','path','error','original_name'] or array[]
fst_is_spa()                                    // → bool
fst_spa_target()                                // → string|null: CSS selector from SPA agent
fst_spa_page()                                  // manually activate SPA injection for current page
fst_status_code($code)                          // set response status code → void
fst_serve_static_file($path)                    // serve file with cache headers → bool
```

### Database

```php
fst_db($mode, $sql, $params = [], $connection = null)
// mode: 'ALL' → array[], 'ROW' → array, 'SCALAR' → mixed, 'EXEC' → ['affected_rows','last_insert_id']

fst_db_select($table, $cond = [], $opts = [])   // opts: select, limit, offset, order_by, mode, connection → array
fst_db_row($table, $cond = [], $opts = [])      // → array|null
fst_db_exists($table, $cond = [], $opts = [])   // → bool
fst_db_insert($table, $data, $opts = [])        // → last insert ID or bool
fst_db_update($table, $data, $cond, $opts = []) // → int (affected rows)
fst_db_delete($table, $cond, $opts = [])        // → int (deleted rows)

fst_db_begin($connection = null)                // → bool
fst_db_commit($connection = null)               // → bool
fst_db_rollback($connection = null)             // → bool
fst_db_quote_ident($ident, $connection = null)  // safe table/column quoting → string
```

### Security, Validation & Session

```php
e($str) | fst_escape($str)                      // XSS-safe HTML escape → string (always use when echoing user data)
fst_csrf_field()                                // → string: <input type="hidden" name="_token" value="...">
fst_csrf_token()                                // → string: raw CSRF token
fst_csrf_check()                                // validate CSRF — call at top of POST/PUT/DELETE handlers

fst_validate($data, $rules)
// → ['valid' => bool, 'errors' => ['field' => ['msg']], 'data' => array (sanitized)]
// rules: required | email | numeric | in:a,b | min:X | max:X | min_value:X | max_value:X

fst_session_set($key, $val)
fst_session_get($key)                           // → mixed
fst_session_forget($key)

fst_flash_set($key, $val)
fst_flash_get($key)                             // → mixed (cleared after first read)
fst_flash_has($key)                             // → bool
```

### Config & Utilities

```php
fst_config($key, $default = null)               // read from fullstuck.json → mixed
fst_is_dev()                                    // → bool: is development mode?
fst_is_safe_to_debug()                          // → bool: are error traces visible to user?
fst_app($key, $value = null)                    // internal state container (request lifecycle cache) → mixed
fst_register_plugin($id, $config)               // register plugin → void
fst_dump(...$vars)                              // pretty var dump → void
fst_dd(...$vars)                                // pretty var dump + die → void (exit)
```

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Template**: Added support for native HTML5 parsing in `fst_template()` via `\Dom\HTMLDocument` when running on PHP 8.4+, ensuring WHATWG-compliant rendering without unwanted tag injections.
- **Template**: Added `@attrs` directive to the ruleset DSL for injecting raw dynamic attributes directly into elements, solving the boolean attribute issue (e.g., `<button disabled>`).
- **Template**: Added `^` prefix to the ruleset DSL for explicitly triggering single node selection (`querySelector` instead of `querySelectorAll`), optimizing performance for unique elements.
- **Template**: Added engine identifier (`html5` or `legacy`) to the template cache hash to prevent silent collisions across servers with different PHP versions.
- **Template**: Added automatic fallback to `css2xpath` if `querySelector()` encounters an unsupported pseudo-selector.

### Changed
- **Template**: Replaced `$node->nodeValue` assignments with `$node->textContent` to comply with the standard DOM specification where `Element::$nodeValue` is strictly readonly (PHP 8.4 `ext-dom`).
- **Template**: Upgraded all structural logic markers (`@if`, `@foreach`, `@prepend`, `@append`) from Text Nodes to Comment Nodes. This completely eliminates libxml "foster parenting" bugs and `HierarchyRequestError` exceptions when modifying strict elements like `<table>` or `<tbody>`.

### Fixed
- **Template**: Fixed severe performance bottleneck and potential double-replace corruption by replacing the `str_replace` loop with a single-pass `strtr()` (Aho-Corasick algorithm) for marker substitutions.
- **Template**: Fixed undefined variable `$getAttrMarker` in the closure by correctly passing it via the `use()` statement.
- **Template**: Fixed uninitialized `$useXPath` warning prior to selector logic execution.
- **Template**: Fixed potential `Fatal Error` on `removeChild()` for `@remove` directives by adding a defensive `if ($node->parentNode)` guard, protecting against sequential nested removals.

## [v0.3.0] - 2026-07-03

### Refactored
- **Core**: Evolved FullStuck from a CMS/Admin structure to a minimal, high-performance Micro-Framework.
- **Core**: Removed Admin Dashboard, Plugin Manager, and legacy CMS-specific codes.
- **Core**: Removed `min_value` and `max_value` from `fst_validate()` to keep it minimalist.
- **SPA**: Replaced `MutationObserver` based SPA logic with Event Delegation pattern for massive performance gains.
- **SPA**: Renamed internal JS engine to `FST Agent`.
- **Router**: Moved FST Agent JS serving to a dedicated route `/fst-agent.js` with 1-year aggressive caching, replacing the old inline script injection.

### Added
- **Core**: Introduced `fst_template_render()` for returning rendered DOM templates as string, enabling Nested Layouting.
- **SPA**: Added Hybrid Front-End Routing API (`fst.set()`, `fst.group()`) to handle client-side logic alongside server-side fragments.
- **SPA**: Standardized FST Agent HTML attributes (`data-fst-fragment`, `data-fst-normal-load`, `data-fst-no-history`, `data-fst-no-scroll`, `data-fst-indicator`, `data-fst-cache`).
- **Core**: Introduced centralized JSON logging via `fst_log()` and `.fst.log`.

### Fixed
- **Admin**: Fixed `fst_view_share` and `fst_spa_page` not being registered in the Scan Project function groups, causing them to appear as "Unknown".
- **Database**: Removed redundant condition check in `fst_db_update()` (dead code after empty conditions validation).
- **Router**: Improved duplicate route detection to compare compiled regex patterns (`$final_pattern`) instead of raw path strings, preventing false negatives when different path definitions produce identical routing patterns. Also fixed a stray closing brace introduced during the change.

### Added
- **DX**: Replaced the bloated full documentation file (`fullstuck_v*.md`) downloaded during `php fullstuck.php init` with a concise, AI-friendly cheatsheet (`fullstuck_cheatsheet.md`) containing all essential API references, database helpers, validation rules, ruleset DSL syntax, and SPA events in a token-efficient format.
- **Docs**: Published the new cheatsheet as `docs/v0.2.0_cheatsheet.md` and integrated it into the VitePress configuration and documentation website.

### Fixed
- **SPA**: Fixed back/forward navigation bypassing server entirely — `popstate` now re-fetches from the backend instead of replaying stale cached HTML. This ensures middleware, auth checks, and controller hooks always execute on history navigation. Developers can opt-in to the old instant-cache behavior by adding `data-history-cache="true"` to the SPA agent script tag.
- **Template**: Fixed template compiler parser to correctly recognize CSS Attribute Selectors starting with `[` and ending with `]` (like `[data-fst="..."]`) at root level or when containing comparison operators, preventing them from being mistakenly intercepted as Attribute Modification Directives.

## [v0.2.0] - 2026-06-23

### Added
- **Core**: Added CLI Installer support `php fullstuck.php init [args]` for headless setup and scaffolding (e.g., `--db=sqlite --scaffold=yes --htaccess=yes`).
- **Database**: Automatically inject high-performance PRAGMA settings (`journal_mode=WAL`, `busy_timeout=5000`, `foreign_keys=ON`) for SQLite connections to enable robust concurrency and prevent "database is locked" errors.
- **Database**: Added `fst_db_begin()`, `fst_db_commit()`, and `fst_db_rollback()` helpers for safe and easy PDO transaction management.
- **Installer**: Upgraded the default auto-scaffolding template to a fully functional interactive "To-Do List" application. This showcase directly demonstrates SPA form submissions, `fst_template()` directives, and SQLite auto-migration out-of-the-box.
- **SPA**: Added global client-side router method `fst.go(url, options)` for programmatic SPA navigation.
- **SPA**: Made `fst:loading` custom event cancelable using `e.preventDefault()`, allowing developers to abort navigations (e.g., for dirty-form checking).

### Fixed
- **SPA**: Fixed critical DX issue where 500 Internal Server Errors were swallowed during SPA form submissions/navigations and forced a GET redirect to the original URL (resulting in 404). Unsuccessful responses now correctly render the error HTML directly into the DOM (via `document.open()`) to preserve the stack trace.
- **SPA**: Fixed `X-FST-Redirect` handler in both link click and form submit doing hard reload instead of SPA navigation. Redirect now triggers `_fstNavigate()` for seamless PRG (Post/Redirect/Get) without page reload.
- **SPA**: Restored hard reload fallback using `document.open()` to preserve POST method stack traces on 500 errors.
- **SPA**: Fixed back button reload glitch on the initial landing page by initializing history state on page load.
- **SPA**: Fixed form redirect ignoring `data-fst-history="false"` history opt-out preferences.
- **Template**: Fixed cache file path collisions in `fst_template()` by escaping absolute paths into flattened filenames (replacing `/`, `\`, and `:` with `__`) instead of using `basename()`.
- **Template**: Fixed relative XPath selectors (`.//`) in `fst_template()` failing to match root elements by defaulting the context node to `$dom` when `$context` is null.
- **Template**: Fixed PHP 8.4 deprecation warning by explicitly declaring `$cacheDir` as nullable (`?string $cacheDir = null`).
- **Template**: Fixed `fst_template()` not inheriting global variables registered via `fst_view_share()`. Shared data is now merged automatically just like `fst_view()`.
- **Router**: Fixed `fst_group('')` with empty prefix producing double-slash paths (`//add`) that resulted in 404 errors.
- **Database**: Fixed `fst_db_insert`, `fst_db_update`, `fst_db_delete` to return scalar values (`last_id` / `affected_rows`) to strictly adhere to API documentation.
- **Database**: Fixed silent failure on mass `UPDATE`/`DELETE` by throwing explicit exceptions when `$conditions` are empty.
- **Database**: Fixed PDO generic exceptions when passing array as bind parameter by explicitly checking and throwing readable errors.
- **Template**: Fixed `fst_template()` default `$cacheDir` targeting `__DIR__` (inside core) to `FST_ROOT_DIR/view-cache`.
- **Router**: Fixed security gap where a middleware returning `false` resulted in a blank page (status 200). Now it correctly aborts with a 403 Forbidden.
- **SPA**: Fixed navigation bug where clicking standard `<a>` links ignored server-side `X-FST-Redirect` headers, resulting in blank pages.
- **SPA**: Enhanced `fst_extract_html_fragment()` to support complex CSS selectors (`.class > div`, `[attr="value"]`) securely via dynamic XPath conversion, replacing the old strict whitelist.
- **Database**: Fixed `fst_db('ROW')` and `fst_db_row()` returning `false` instead of `null` when no data is found to respect type safety.
- **HTTP**: Hardened `fst_upload()` MIME type validation by replacing loose `strpos` with strict matching against a malicious signature whitelist.
- **CLI**: Removed redundant CLI `$argv` parsing loop inside `fst_handle_installation()`.
- **Core**: Fixed silent failure in `_fst_interpolate_env()` by explicitly throwing an error when an expected environment variable is missing.

### Docs
- **API Reference**: Added explicit return types to the entire API Cheat Sheet (Database, Security, HTTP, Session, etc) to improve DX and eliminate guesswork.
- **CSRF**: Added explicit documentation that the CSRF field name must be `_token` when using static `.html` forms.
- **Template**: Added `@text` directive to DSL API reference (was implemented but undocumented).
- **Security**: Fixed XSS vulnerability in Admin Configuration Editor by escaping raw JSON output.
- **Security**: Fixed potential XSS execution in SPA `X-FST-Body-Attrs` injection by replacing `innerHTML` with `DOMParser`.
- **Security**: Hardened `fst_view()` with an extension whitelist (`php`, `html`, `htm`) to prevent sensitive data exposure via path traversal.
- **Admin**: Removed dead `_fst_connect_db()` call in System Monitor that caused fatal errors on first load.
- **Admin**: Fixed database driver configuration path check in System Monitor.
- **Core**: Synchronized `FST_VERSION` to `0.2.0` to match the compiled output, fixing remote OTA comparisons and docs URLs.
- **SPA**: Fixed missing inline `<script>` re-execution when processing `fst.js` SPA form submissions.
- **SPA**: Implemented regex fast-path for singleton tags (`body`, `main`) during HTML extraction to prevent double `DOMDocument` parsing corruption.
- **Router**: Added buffer safety check in `fst_run()` to prevent blank pages if an exception handler flushes the output buffer early.
- **Docs**: Massively updated `docs/v0.2.0.md` to resolve AI feedback regarding undocumented features, broken examples, false contradictions, and API limitations.

## [v0.1.0] - 2026-05-15

### Added
- **Core**: Implemented **Smart Procedural Require** via `fullstuck.json` (`require` array) with wildcard glob support.
- **Core**: Added **Multi-Database connection pooling** and ENV variable interpolation (`${ENV_VAR}`) in configuration.
- **Core**: Centralized state management via `fst_app()` static state container.
- **Core**: Upgraded Middleware system to **Onion Model** supporting recursive `$next()` calls.
- **Core**: Added **Strict Route Detection** to prevent duplicate route definitions.
- **Core**: Added **PostgreSQL** driver support via PDO.
- **Core**: Added `fst-plugins/` Auto-Discovery for modular framework extension.
- **Core**: Added `fst_spa_page()` helper for manual SPA rendering mode.
- **Security**: Added **Routing Leakage Protection** middleware to detect misconfigured URL rewriting.
- **Security**: Hardened Error Handler with **Double-Layer Safety Net** via `fst_is_safe_to_debug()`.
- **SPA**: Upgraded to support **Fragment Rendering** (target-specific swapping via class/ID selectors).
- **SPA**: Added **Lifecycle Events** support (`fst:unload` and `fst:load`).
- **SPA**: Implemented **Server Re-fetch on History Navigation** — back/forward triggers a fresh server request to guarantee data freshness and security.
- **SPA**: Added opt-out capability via `data-no-spa` / `no-spa` and respect for `e.defaultPrevented`.
- **Installer**: Added **Auto-Scaffolding** to generate starter project files (`router.php`, `views/`, `assets/`) during installation.
- **Installer**: Added **Zero-Config SPA** toggle to the installation wizard.
- **Installer**: Added **CLI Headless Init** (`php fullstuck.php init --db=... --admin-pass=... --spa=yes --scaffold=yes`) for advanced developer setup bypass.
- **Admin**: Added **Plugin Marketplace** with remote fetching and one-click installation.
- **Admin**: Enhanced **Integrity Monitor** with local hash verification and remote update checker.
- **Admin**: Implemented **OTA (Over-The-Air) Update System** with automatic backup and integrity verification.
- **Documentation**: Added comprehensive **Admin Dashboard** documentation section.

### Removed
- **Repository**: Removed legacy `tests/` and `examples/` folders for a clean 0.1.0 release structure.
- **Core**: Removed **Dynamic Routing** mode (dead code amputation) to enforce strict, whitelist-based routing.
- **View**: Removed `fst_serve_dynamic_file` and `fst_show_directory_listing` public functions.

### Changed
- **Core**: Optimized database initialization with lazy-loading connections.
- **Core**: Refactored router internal storage to use HTTP method bucketing for faster static route matching.
- **Core**: Reset request-scoped state in `fst_run()` to prevent state bleeding in persistent environments like FrankenPHP.
- **Core**: Simplified `fullstuck.json` schema by removing nested routing modes (`static_config`/`dynamic_config`).
- **SPA**: Bypassed SPA script injection on all admin dashboard routes.
- **Admin**: Streamlined **System Monitor** by removing routing mode status display.
- **Admin**: Updated **Scan Project** registry to remove deleted view functions and include new core helpers.
- **Documentation**: Enhanced **Deployment Guide** with full `.htaccess` templates for Apache/LiteSpeed.

### Fixed
- **Installer**: Fixed undefined array key warnings in CLI headless mode.
- **Documentation**: Fixed JSON schema typo in `fullstuck.example.json`.
- **Compiler**: Fixed aggressive PHP tag removal that corrupted string literals in source files (e.g., scaffolding templates in `install.php`).
- **FIM**: Fixed `fst_check_integrity()` failing on Windows due to CRLF line endings — replaced `explode(" */\n", ...)` with `preg_split` to handle both `\r\n` and `\n`.
- **FIM**: Fixed `fst_check_integrity()` unable to locate `fullstuck.php` when running `php -S` from test subfolders.
- **Security**: Implemented `session_regenerate_id(true)` on admin login to prevent **Session Fixation**.
- **Security**: Hardened session cookies (HttpOnly, Secure, SameSite=Lax).
- **Security**: Implemented deep MIME-type verification for file uploads via `finfo`.
- **Security**: Added XPath injection protection with strict tag whitelist in HTML extractor.
- **Security**: Added global security headers (`X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`).
- **Database**: Added mass-update protection in `fst_db_update()` and `table.column` identifier support.
- **SPA**: Implemented **SPA Form Submission** interceptor in `fst.js` (supports GET/POST).
- **SPA**: Improved script injection fallback for HTML without `</body>` tag.
- **Fix**: Enhanced `fst_redirect()` with `$allow_external` parameter and protocol-relative bypass fix.
- **Build**: Improved `fst.js` minification in compiler to strip comments (// and /* */) properly.
- **Fix**: Optional route parameter parsing order in `src/router.php`.
- **Admin**: Fixed false-positive database connection failure in System Monitor due to lazy-loading connection state.
- **Admin**: Fixed installed plugin filenames to use `fst-` prefix, ensuring they are correctly discovered by the framework.

### Security (Code Review Hardening)
- **Database**: Fixed **SQL Injection** vulnerability in `fst_db_select()` `order_by` option — user input is now sanitized via `_fst_sanitize_order_by()` with whitelist regex.
- **View**: Fixed **Path Traversal** vulnerability in `fst_view()` — added `realpath()` validation to ensure views cannot escape the project root.
- **HTTP**: Fixed **Open Redirect** vulnerability in `fst_redirect()` — blocked protocol-relative URLs (`//evil.com`) and added hostname validation for absolute URLs.
- **Admin**: Fixed **XSS** vulnerability in flash message rendering — output is now escaped via `htmlspecialchars()`.
- **Admin**: Hardened **Plugin Install** endpoint — enforced HTTPS-only downloads and domain whitelist (GitHub only) to prevent arbitrary code injection.
- **Compiler**: Replaced regex-based comment stripping with PHP's native `token_get_all()` tokenizer.
- **Core**: Replaced `session_start()` with `session_status()` check to prevent duplicate session errors.
- **Core**: Removed legacy `global` variables in favor of `fst_app()` single-source-of-truth state container.
- **Database**: Simplified redundant double `try/catch` wrapping in database initialization.
- **Database**: Fixed default identifier quoting fallback from `mysql` to `sqlite` (safest common denominator).
- **View**: Added 13 additional MIME types (webp, woff2, gif, json, mp4, etc.) to static file server.
- **Security**: Hardened CSRF check (removed GET support, added header support) to prevent leakage.
- **Security**: Added `realpath` validation in `fst_upload()` to prevent path traversal.
- **Security**: Hardened plugin installation with HTTPS requirement and domain white-listing.
- **Admin**: Added IP Whitelisting (`allowed_ips`) support for the admin dashboard.
- **Admin**: Enforced production safety by automatically blocking access to the default `/stuck` admin URL.
- **Core**: Silenced detailed stack traces in production, logging errors securely to `.fst-error.log`.
- **Installer**: Enhanced `.htaccess` generator to deny access to all hidden dotfiles (e.g., `.fst-error.log`).

### Features & Improvements
- **Feature**: Added `data-spa-ignore` support for scripts in SPA agent.
- **Feature**: Added `min_value` and `max_value` validation rules.
- **Architecture**: Disabled auto-run in CLI mode to support unit testing.
- **Architecture**: Improved state initialization to prevent resets on multiple includes.
- **Improvement**: Replaced regex-based comment stripping in compiler with `token_get_all()`.
- **Improvement**: Expanded MIME types for modern static assets.
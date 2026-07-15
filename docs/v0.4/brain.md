# FullStuck v0.4 — AI Coding Rules

Framework PHP 1-file. Routing = struktur folder. Tanpa Composer.
Jalankan dev server: `php -S localhost:8000 fullstuck.php`

## Struktur Proyek

```
fullstuck.php    → Core engine (read-only)
fullstuck.json   → Konfigurasi
app/             → Routing (folder = URL)
components/      → UI reusable (@component)
globals/         → Helper PHP (auto-loaded)
cache/           → Auto-generated (.gitignore)
```

## Routing (4-Pillars)

Setiap folder di `app/` = endpoint URL. Aktifkan dengan file:

| File | Peran |
|---|---|
| `content.fst.php` | Tampilan GET. Syntax Blade. |
| `action.php` | POST/PUT/PATCH/DELETE. Tanpa `content.fst.php` di folder sama = juga terima GET (headless API). |
| `client.js` | JS frontend. Auto IIFE wrap. |
| `_guard.php` | Middleware pelindung. Eksekusi **Top-Down** (luar ke dalam). |
| `_layout.fst.php` | Layout wrapper. Rangkaian **Bottom-Up** (Matryoshka / dalam ke luar). |

```
app/
├── content.fst.php           → GET /
├── _layout.fst.php           → Layout global
├── blog/
│   ├── content.fst.php       → GET /blog
│   └── [slug]/
│       ├── content.fst.php   → GET /blog/apapun
│       └── action.php        → POST /blog/apapun
├── api/products/
│   └── action.php            → GET|POST|PUT|DELETE /api/products
└── admin/
    ├── _guard.php             → Lindungi /admin/*
    ├── _layout.fst.php        → Layout admin
    └── users/
        └── content.fst.php   → GET /admin/users
```

**Dynamic route:** folder `[param]` → akses via `fst_input('param')` atau `$_GET['param']`.
**Isomorphic Routing:** Rute SPA (`fst.set()`) di JS akan 404 saat *hard reload*. Wajib buat direktori kembar (beserta `content.fst.php`) di backend untuk rute publik.

## Template (.fst.php)

```
{{ $var }}                   → echo e($var) — XSS-safe
{!! $html !!}                → echo $html — raw
@{{ literal }}               → {{ literal }} — escape braces untuk JS
@if(...) @elseif(...) @else @endif
@foreach(...) @endforeach
@yield('name', 'default')
@section('name') ... @endsection
@component('name', ['key' => $val])
```

Konten di luar `@section` otomatis masuk ke `@yield('content')`.

## Component

Lokasi: `components/nama.fst.php` → panggil `@component('nama', ['data' => $val])`
Subfolder: `components/admin/card.fst.php` → `@component('admin/card')`
Variabel terisolasi (scoped closure). **Haram** ada `<script>` statis dalam komponen (hindari identifier redeclaration di loop). Gunakan delegasi `fst.on` di `client.js`.

## client.js (Frontend SPA)
Otomatis terbungkus IIFE. Fungsi tidak bisa dipanggil via inline HTML `onclick="x()"`. Gunakan `fst.on` atau ekspon via `window.x = ...`

### FST-Agent API (JavaScript)
| Method | Keterangan |
|---|---|
| `fst.on(event, selector, callback, opts)` | Event listener aman (auto-cleanup saat navigasi) |
| `fst.onMount(callback)` | Lifecycle hook setelah DOM render |
| `fst.emit(event, detail)` | Kirim custom event |
| `fst.go(url, options)` | Navigasi programatik (opsi: `target`, `history`, `scroll`) |
| `fst.set(pattern, callback)` | Daftarkan client-side route |
| `fst.group(prefix, callback)` | Grouping client-side routes |
| `fst.setInterceptor(callback)` | Intercept fetch request |
| `fst.setBefore(callback)` | Hook sebelum navigasi |
| `fst.setAfter(callback)` | Hook setelah navigasi |
| `fst.e(str)` / `fst.escape(str)` | Escape HTML XSS-safe |

### Contoh Penggunaan
```javascript
// Event listener — auto-cleanup saat navigasi
fst.on('click', '#btn', (e, el) => { ... });

// Persist lintas navigasi
fst.on('click', '#btn', (e, el) => { ... }, { global: true });

// Lifecycle — teardown saat pindah halaman
fst.onMount(() => {
    const x = initPlugin();
    return () => x.destroy();
});

// Event bus
fst.emit('event_name', { data });
fst.on('event_name', (detail) => { ... });

// Navigasi programatik
fst.go('/path');
fst.go('/path', { target: '#el', history: false, scroll: 'smooth' });

// XSS escape untuk innerHTML
fst.e(untrustedString);
```

Atribut HTML: `data-fst-fragment="#id"` · `data-fst-normal-load` · `data-fst-no-history` · `data-fst-no-scroll` · `data-fst-indicator="class"` · `data-fst-ignore`

## PHP API

### Request / Response
```php
fst_input('key', 'default')    // GET/POST/JSON body
fst_request()                  // semua input
fst_method()                   // GET/POST/PUT/DELETE dll (termasuk spoofing)
fst_file('key')                // file upload
fst_redirect('/url')           // redirect aman
fst_json($data, 200)           // JSON + die
fst_text($str, 200)            // plain text + die
fst_abort(404, 'msg')          // error + die
fst_uri()                      // current path
```

### Fragment (Backend)
```php
fst_is_fragment_request()      // true jika request dari FST-Agent
fst_fragment_target()          // selector CSS target fragment
```

### Session
```php
fst_session_set('key', $val)
fst_session_get('key', 'default')
fst_session_forget('key')
fst_session_regenerate(true)   // wajib setelah login
```

### Database (PDO wrapper)
```php
// CRUD
fst_db_select('table', ['status' => 'active'], ['order_by' => 'id DESC', 'limit' => 10])
fst_db_row('table', ['id' => 1])
fst_db_exists('table', ['email' => 'x@y.com'])
fst_db_insert('table', ['col' => 'val'])               // return last_id
fst_db_update('table', ['col' => 'val'], ['id' => 1])  // return affected_rows
fst_db_delete('table', ['id' => 1])                     // return affected_rows

// Operator pada key: 'col >' => 50, 'name LIKE' => '%x%'

// Raw query — untuk JOIN/COUNT/GROUP BY
fst_db('ALL', 'SELECT ... WHERE x = ?', [$val])   // mode: ALL, ROW, SCALAR, EXEC
fst_db('EXEC', 'INSERT INTO ...', [$val])          // return ['affected_rows','last_id',...]

// Transaction
fst_db_begin(); fst_db_commit(); fst_db_rollback();

// Multi-connection: parameter terakhir 'conn_name' atau opsi ['connection' => 'conn_name']
```

### Utilitas
```php
e($str)                      // XSS escape (alias fst_escape)
fst_validate($data, ['field' => 'required|email|min:3|max:255|numeric|in:a,b'])
fst_upload('key', 'uploads/', ['max_size' => 1024, 'allowed_types' => ['jpg','png']])
fst_dump($var)               // debug (dev only)
fst_dd($var)                 // debug + die
fst_log('ERROR', 'msg', [])  // tulis ke .fst.log
fst_config('database.default')  // baca fullstuck.json (dot notation)
fst_is_dev()                 // true jika production: false
fst_view('path.php', $data)  // render file PHP
fst_view_share('key', $val)  // share variabel ke semua view
fst_error_handler(fn($e) => ...) // callback error kustom
```

## fullstuck.json

```jsonc
{
  "production": false,
  "routing": {
    "base_path": "/",
    "public_folders": ["assets", "uploads"]
  },
  "require": ["globals"],
  "agent_js": true,
  "fragment": { "history_cache": false },
  "database": {
    "default": "main",
    "connections": {
      "main": { "driver": "sqlite", "database_path": "database.sqlite" }
    }
  }
}
```

String value mendukung env interpolation: `"${DB_HOST}"`.

## Keamanan

- Form dilindungi `SameSite=Lax` cookie + header `X-FST-Request` (otomatis).
- `fst_session_regenerate(true)` wajib dipanggil setelah login.
- `fst_upload()` memblokir ekstensi `.php/.exe/.phar` secara hardcoded.
- `{{ $var }}` auto XSS-safe. Output mentah: `{!! $html !!}`.
- `fst_redirect()` mencegah open redirect. Untuk domain luar gunakan parameter `$allow_external = true`.

## Error

- `fst_abort(code, msg)` → otomatis JSON untuk `/api/*` atau `Accept: application/json`.
- Custom error page: `app/404.fst.php` (cascade dari folder error ke `app/`).

## CLI

```bash
php fullstuck.php init          # Inisialisasi proyek baru
php fullstuck.php docs          # Daftar isi dokumentasi
php fullstuck.php docs:2        # Baca bab tertentu (contoh: routing)
php fullstuck.php docs:11       # API Reference
php fullstuck.php docs:full     # Seluruh dokumentasi
```

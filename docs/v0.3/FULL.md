# 🚀 Pengenalan & Quick Start

**FullStuck.php**: Framework mikro 1 file core. Tidak ada `vendor/`, tanpa *Composer*.

### 💻 Instalasi CLI / Headless
```bash
php fullstuck.php init --db=sqlite --agent_js=yes --scaffold=yes --htaccess=yes
```
*Flag: `--db` (`sqlite`/`mysql`/`pgsql`/`none`), `--agent_js` (`yes`/`no`), `--scaffold` (`yes`/`minimal`/`no`), `--htaccess` (`yes`/`no`).*

### 🤖 Install via Coding Agent
Jika Anda menggunakan AI Coding Agent (seperti Cline, Cursor, Copilot Workspace), gunakan prompt berikut agar agen menginstal dan mematuhi panduan:
```text
Install fullstuck.php. Lalu baca dan patuhi panduan di https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/ai-setup.md
```

Atau jalankan `php -S localhost:8000 fullstuck.php` untuk menjalankan server dev bawaan.

### 🔧 Web Server Deployment

**1. Apache / LiteSpeed** (`.htaccess` di root):
```apache
Options -Indexes -MultiViews
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^(.*)$ fullstuck.php [L]
</IfModule>
```

**2. Nginx** (Server Block):
```nginx
location / {
    try_files $uri $uri/ /fullstuck.php?$query_string;
}
```

**3. FrankenPHP / Caddy** (Gunakan `Caddyfile`):
```caddyfile
:8000 {
    root * .
    php_server {
        index fullstuck.php
    }
}
```
Jalankan dengan: `frankenphp run`

---

## 📂 Arsitektur & Struktur Folder

```text
my-project/
├── assets/         # File statis (CSS, JS, Images)
├── models/         # Fungsi model & query helper  ← auto-loaded via "require"
├── middleware/      # Fungsi middleware             ← auto-loaded via "require"
├── routes/          # File rute per modul           ← loaded via "routes_file"
├── views/           # Template HTML / PHP
├── fullstuck.json   # File konfigurasi utama
├── fullstuck.php    # Framework Core (HARAM dimodifikasi!)
└── router.php       # Definisi rute utama (entry point)
```

> ⚠️ **Jangan menumpuk semua logika di `router.php`!** Pecahkan model ke `models/`, middleware ke `middleware/`, dan rute per modul ke `routes/`. Daftarkan di `fullstuck.json`:
> ```json
> "routing": {
>     "require": ["models", "middleware"],
>     "routes_file": ["router.php", "routes/admin.php", "routes/api.php"]
> }
> ```
> `require` auto-load folder/file/glob sebelum routing. `routes_file` mendefinisikan file rute yang dimuat berurutan.

### Konfigurasi `fullstuck.json`
Seluruh pengaturan framework berpusat pada file `fullstuck.json`. File ini wajib ada di root project.

```json
{
    "production": false,
    "database": {
        "default": "main",
        "connections": {
            "main": {
                "driver": "sqlite",
                "database_path": "database.sqlite"
            }
        }
    },
    "routing": {
        "base_path": "/",
        "require": ["models", "middleware"],
        "public_folders": ["assets", "uploads", "storage/public"],
        "routes_file": ["router.php"],
        "error_handlers": {
            "404": "views/404.html"
        },
        "regex_shortcuts": {
            ":id": "([0-9]+)",
            ":slug": "([a-zA-Z0-9_-]+)"
        }
    },
    "agent_js": true,
    "fragment": {
        "header_request": "HTTP_X_FST_FRAGMENT",
        "header_target": "X-FST-Target",
        "indicator_class": "fst-loading",
        "history_cache": true
    },
    "mime_types": {
        "custom": "application/x-custom"
    }
}
```

> **Tips:** Anda dapat menggunakan sintaks `${NAMA_ENV}` untuk mengambil nilai secara aman dari variabel sistem operasi (Environment Variables) tanpa perlu `.env`.

### Penjelasan Opsi:
- **`production`**: `true` menyembunyikan *error stack trace* dari browser dan mengalihkannya ke log file `.fst.log`. `false` akan menampilkan error detil di browser.
- **`routing.require`**: Auto-load file/folder/glob sebelum rute dieksekusi. Ideal untuk model, helper, dan middleware.
- **`routing.routes_file`**: Array file rute. Pecahkan saat project membesar (misal: `["router.php", "routes/admin.php"]`).
- **`agent_js`**: `true` akan otomatis menyuntikkan script FST Agent (`<script src="/fst-agent.js">`) ke setiap output HTML.

---

## 🤖 Strict Rules for AI
**WAJIB DITAATI OLEH AI ASSISTANT:**
1. **Wajib pakai Helper `fst_*`**: Dilarang pakai `$_POST`/`$_GET`/`$_FILES` mentah atau `new PDO()`.
2. **Jangan Sentuh Core**: Dilarang memodifikasi `fullstuck.php`.
3. **Proteksi CSRF**: Rute POST/PUT/DELETE **WAJIB** panggil `fst_csrf_check()`.
4. **Validasi**: Gunakan hanya fungsi `fst_validate()`.
5. **Pecah Kode**: Dilarang menumpuk semua logika di `router.php`. Pecahkan model, middleware, dan rute per modul ke file/folder terpisah. Update `fullstuck.json` (`require` & `routes_file`).
# 📖 Routing & Middleware

**Basic Routing:**
```php
fst_get('/halo', function() { echo "Halo Dunia!"; });
fst_get('/user/{id:i}', function($id) { echo "ID: " . $id; }); // :i (integer)
fst_get('/post/{slug:any}?', function($slug = 'default') { echo "Opsional: " . $slug; });
```

**Middleware (Onion Model):**
```php
function cek_login($next) {
    if (!fst_session_get('user_id')) return fst_redirect('/login');
    return $next(); // Lanjut ke rute tujuan
}

fst_group('/admin', function() {
    fst_get('/dashboard', function() { echo "Admin Area"; });
}, 'cek_login');
```

**Pengambilan Data Request:**
```php
fst_post('/submit', function() {
    // fst_input(key, default) secara otomatis mencari dari $_POST, $_GET, atau JSON body
    $username = fst_input('username');
    $status = fst_input('status', 'active'); // Default fallback
    
    // Semua request data
    $all = fst_request(); 
});
```

> ⚠️ **Peringatan Internal API:** Fungsi `_fst_route()` adalah API internal framework (bersifat *private*) dan DILARANG untuk dipanggil secara langsung. Pengembang (dan Agen AI) WAJIB menggunakan fungsi *wrapper* publik seperti `fst_get()`, `fst_post()`, `fst_put()`, `fst_delete()`, dan `fst_any()`.
# 🗄️ Database & Query Builder

Gunakan key `connection` untuk pindah database sesuai konfigurasi di `fullstuck.json`.

> **Advanced Operators:** Query Builder telah diinjeksi dengan parser khusus yang mendukung penambahan operator langsung pada *key* kondisi (seperti `=`, `!=`, `<`, `>`, `<=`, `>=`, `LIKE`, `NOT LIKE`, `IS`, dan `IS NOT`).

### Select, Insert, Update, Delete
```php
$users = fst_db_select('users', ['status' => 'active'], ['order_by' => 'id DESC']);

// Menggunakan operator lanjutan (Advanced Operators)
$products = fst_db_select('products', [
    'status' => 'active',
    'price >' => 50000,
    'name LIKE' => '%baju%'
]);

fst_db_insert('users', ['nama' => 'Budi', 'email' => 'budi@a.com']);
fst_db_update('users', ['status' => 'inactive'], ['id' => 5]);
fst_db_delete('users', ['id' => 5]);
```

### Raw Query (`fst_db`)
Mendukung 4 mode kembalian: `'ALL'` (Array of arrays), `'ROW'` (Single associative array), `'SCALAR'` (Primitive value), `'EXEC'` (Affected rows & Last insert ID).

```php
$posts = fst_db('ALL', "SELECT p.*, u.nama FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = ?", ['published']);
$user = fst_db('ROW', "SELECT id, nama FROM users WHERE email = ? LIMIT 1", ['budi@a.com']);
$total = fst_db('SCALAR', "SELECT COUNT(*) FROM users WHERE status = ?", ['active']);
fst_db('EXEC', "UPDATE users SET last_login = NOW() WHERE id = ?", [1]);
```

### Transactions
```php
try {
    fst_db_begin();
    fst_db_insert('users', ['nama' => 'A']);
    fst_db_update('saldo', ['jumlah' => 0], ['id' => 1]);
    fst_db_commit();
} catch (Exception $e) {
    fst_db_rollback();
}
```
# 🛡️ Request, Validasi & Keamanan

### Proteksi CSRF (Wajib pada Form)
- **File `.php`**: `<?= fst_csrf_field() ?>`
- **File `.html` (via `fst_template`)**:
  Beri elemen penanda di dalam form HTML Anda, contoh: `<div class="fst-csrf"></div>`
  ```php
  ".fst-csrf" => ["@html" => 'fst_csrf_field()']
  ```

### Validasi Controller
```php
fst_post('/register', function() {
    fst_csrf_check(); // Wajib untuk POST/PUT/DELETE

    $val = fst_validate(fst_request(), [
        'nama'  => 'required|min:3',
        'email' => 'required|email',
        'umur'  => 'required|numeric'
    ]);

    if (!$val['valid']) {
        fst_flash_set('error', implode(', ', array_merge(...array_values($val['errors']))));
        fst_redirect('/register');
    }

    $clean = $val['data']; // Data tersanitasi
});
```

### Pencegahan XSS (Cross-Site Scripting)

1. **Sisi Backend (PHP)**:
   Gunakan helper global `e($str)` atau `fst_escape($str)` saat merender variabel dinamis di dalam file HTML/PHP Anda.
   ```php
   <p>Selamat datang, <?= e($username) ?></p>
   ```

2. **Sisi Frontend (Client-side Router)**:
   Saat menggunakan rute JavaScript dinamis (`fst.set()`), hindari menyuntikkan data mentah ke `.innerHTML`. Gunakan `fst.e()` atau `fst.escape()` untuk mensterilkan variabel.
   ```javascript
   document.querySelector('main').innerHTML = `
       <p>Menampilkan ID: \${fst.e(params.id)}</p>
   `;
   ```

# 🌻 Procedural DOM Templating (`fst_template`)

Pisahkan murni file HTML statis (tanpa tag PHP) dan definisikan logikanya menggunakan Array (Ruleset) dari controller. Templating ini secara otomatis me-*escape* variabel (mencegah XSS).

### Signature API
```php
// Merender langsung ke browser
fst_template(string $html_path, array $data, array $rules);

// Mengembalikan string HTML (Berguna untuk Layouting/Nested Template)
$html_string = fst_template_render(string $html_path, array $data, array $rules);
```

### Ruleset (DSL)
```php
$rules = [
    // --- TEXT & HTML ---
    "title"        => '$pageTitle',                    // Men-set innerText (XSS-safe)
    "h3"           => ["@text" => '$heading'],         // Sama seperti di atas
    "span.content" => ["@html" => '$htmlContent'],     // Raw innerHTML (hanya untuk trusted content)
    "head"         => ["@append"  => '"<style>...</style>"'],  // insertAdjacentHTML (beforeend)
    "body"         => ["@prepend" => '"<div>Notif</div>"'],    // insertAdjacentHTML (afterbegin)

    // --- ATTRIBUTES ---
    "a.external"   => ["[href]" => '$linkUrl', "[target]" => '"_blank"'],
    "[data-fst=\"my-form\"]" => ["[action]" => '"/submit"'],

    // --- BOOLEAN / RAW DYNAMIC ATTRIBUTES ---
    // Jangan gunakan `[disabled] => '$cond ? "disabled" : ""'` karena string kosong HTML5 tetap menghitungnya ada.
    // Gunakan `@attrs` untuk merender string raw langsung ke dalam tag.
    "button.submit" => ["@attrs" => '$isDisabled ? "disabled" : ""'],

    // --- SINGLE TARGETING (Optimasi Performa) ---
    // Gunakan prefix `^` untuk memicu querySelector() secara native (bukan querySelectorAll).
    // Sangat berguna jika Anda yakin elemen hanya ada 1 di halaman.
    "^div.alert"   => '"Ini alert pertama saja"',

    // --- COMPILE-TIME REMOVAL ---
    "div.debug-panel" => "@remove",                   // Hapus elemen selamanya dari cache

    // --- CONDITIONALS & LOOPS ---
    "div.promo"       => ["@if" => '$isPromoActive'], // Jika false, elemen dihapus permanen dari DOM output (bukan display: none!)

    // Ternary 
    "button.auth"     => [
        "@text"   => '$isLoggedIn ? "Logout" : "Login"',
        "[href]"  => '$isLoggedIn ? "/logout" : "/login"',
    ],

    // @foreach — elemen child pertama menjadi template loop
    "ul.nav > li"     => [
        "@foreach" => '$menus as $menu',
        "a"        => ["[href]" => '$menu["url"]', "@text" => '$menu["label"]']
    ],
];
```

> ⚠️ **Peringatan DX:** Array `$rules` **tidak mendukung** *Closure* (`fn() => ...`). Semua logika HARUS ditulis dalam bentuk *String Literal Ekspresi PHP* agar dapat di-*serialize* untuk *caching*. Jika butuh variabel kompleks, suntikkan melalui array `$data`.

### Contoh Layouting Bersarang
```php
fst_get('/tentang', function() {  
    // Render anak menjadi string
    $content = fst_template_render('views/tentang.html', [], []);  
    
    // Suntikkan string ke layout utama
    fst_template('views/_layout.html', ['content' => $content], [
        'main' => ['@html' => '$content']
    ]);  
});
```
# ⚡ FST Agent & Fragment Routing (Client-Side)

Jika `"agent_js": true` dihidupkan, navigasi Anda secara otomatis bekerja bagaikan *Single Page Application* tanpa *Full Page Reload*. FST Agent akan mencegat setiap klik tag `<a>` dan pengiriman `<form>`, kemudian mengambil *Fragment HTML* dari server.

Anda juga bisa mengatur **Rute Client-Side Murni** melalui object `fst` secara global!

### JavaScript API (`window.fst`)
Definisikan ini di file `.js` eksternal atau tag `<script>` bawaan HTML.
```javascript
// Rute Javascript Murni (Tidak hit server PHP)
fst.set('/editor', (params) => {
    document.querySelector('#app').innerHTML = `<h1>Canvas Editor</h1>`;
});

// Rute Dinamis dengan Regex Extractor
fst.set('/user/:id', (params) => {
    console.log("ID User: " + params.id);
    console.log("Query Params: ", params.query);
});

// Grouping Rute (Sangat berguna untuk struktur aplikasi dalam)
fst.group('/dashboard', () => {
    fst.set('/settings', () => { /* Rute /dashboard/settings */ });
});

// Pemanggilan Programmatik
fst.go('/sebagian', { target: '#widget', history: false, scroll: 'smooth' });

// Penangkal DOM-based XSS (HTML Escaper)
// Mengamankan data dinamis/parameter URL sebelum disuntikkan ke innerHTML
let safeText = fst.e(params.id); 
let safeTitle = fst.escape(task.title);
```

### HTML Data Attributes
| Atribut HTML | Penjelasan |
| --- | --- |
| `data-fst-fragment="#id"` | Menentukan di dalam elemen mana hasil HTML disuntikkan (Default: dari config). |
| `data-fst-normal-load` | *Bypass* FST Agent. Tag A / Form akan melakukan *full page reload* biasa. |
| `data-fst-no-history` | Mencegah navigasi untuk dicatat dalam *URL Bar* (browser history). Sangat cocok untuk action form DELETE / POST. |
| `data-fst-no-scroll` | Mematikan efek `scroll-to-top` otomatis setelah perpindahan halaman. |
| `data-fst-indicator="class"`| Menimpa *CSS Class* loading untuk elemen spesifik ini saat di-fetch. |
| `data-fst-ignore` | Ditaruh di dalam `<script>`, menandakan script ini hanya di-eksekusi 1 kali seumur hidup. |

> 💡 **Tips Menghindari Eksekusi Script Ganda:** 
> Saat FST Agent melakukan pergantian konten (*Fragment Routing*), ia akan mengeksekusi ulang seluruh tag `<script>` yang **berada di dalam** target elemen (misal `<main>`).
> 1. Letakkan skrip global aplikasi Anda (seperti `app.js`) **di luar** elemen target fragment (misal di akhir `<body>` atau `<head>`), sehingga tidak ikut terekskusi ulang setiap berpindah halaman.
> 2. Untuk *inline script* yang terpaksa harus berada di dalam *fragment* namun hanya ingin dijalankan sekali seumur hidup, tambahkan atribut `data-fst-ignore`.
> 3. *Script* eksternal (dengan `src`) yang terdeteksi sudah berada di `<head>` akan otomatis di- *skip* (tidak direload ganda).

### Integrasi JSON & API
FST Agent memiliki sistem **MIME-Type Whitelist** internal. Ini memastikan kerangka kerja **TIDAK AKAN** menyuntikkan skrip `fst-agent.js` ke dalam *response* backend yang dideklarasikan secara spesifik (misalnya JSON).
```php
fst_get('/api/data', function() {
    // fst_json otomatis set header Content-Type: application/json
    // FST-Agent otomatis mundur. Aman untuk dikonsumsi Frontend/Fetch!
    fst_json(['status' => 'ok']);
});
```
Jika tidak ada deklarasi `Content-Type`, PHP secara *default* akan menganggap `text/html`, sehingga kerangka kerja akan kembali menyuntikkan skrip agen untuk kebutuhan navigasi.

*Untuk referensi Javascript API dan Event Hooks lebih lengkap, lihat [FULL.md](./FULL.md).*

### Javascript Event Hooks
Anda dapat memberikan reaksi saat halaman sedang memuat atau selesai memuat (Sangat membantu untuk menghancurkan & me-load ulang library jQuery / pihak ke-3):
```javascript
// Sebelum Fetch
document.addEventListener('fst:loading', (e) => { 
    // e.detail: { url, targetSelector, triggerElement }
    // Memungkinkan e.preventDefault()
});

// HTML Lama dihapus
document.addEventListener('fst:unload', () => { /* destroy plugins */ });

// HTML Baru masuk & dirender
document.addEventListener('fst:load', () => { /* init plugins */ });
```
# 📝 Logging & Error Handling

FullStuck v0.3.0 memusatkan seluruh *output error* dan *logging* pada file di dalam root direktori: `.fst.log`. 

- Jika `"production": true` dalam file `fullstuck.json`, pesan *Exception* PHP (*stack trace* yang rawan mengekspos letak path server) akan di-*mute* di layar pengguna (menjadi pesan error 500 generik) dan detail lengkapnya akan dicatat di `.fst.log`.
- Format `.fst.log` ditulis baris demi baris dalam sintaks **JSON**, mempermudah pencarian/filtrasi log bagi developer.

### Manual Logging API
Anda dapat menggunakan fungsi `fst_log` secara global:
```php
fst_log('INFO', 'Pembayaran berhasil dikonfirmasi.', ['invoice' => 'INV-001']);
fst_log('ERROR', 'Koneksi ke pihak ketiga gagal.', ['endpoint' => $api_url]);
```
# 8. 🌶️ Advanced Cookbook (Scale-Up Guide)

FullStuck didesain seringan mungkin untuk mempercepat fase **Zero to One** (Prototyping & MVP). Namun, ketika aplikasi Anda mulai membesar menuju fase **One to Scale** (Production), Anda membutuhkan pendekatan arsitektural tingkat lanjut. 

Bab ini memandu Anda menangani skenario kompleks tanpa perlu mengotori kesederhanaan core framework.

## Component Pattern
Saat UI Anda semakin kompleks (tabel relasi, *nested list*, komponen kondisional), menulis `fst_template` secara berurutan dalam satu file akan menyebabkan "Array Hell" (kode yang sulit dibaca dan dipelihara).

**Solusi:** Gunakan **Component Pattern** dengan memecah UI menjadi fungsi PHP murni.

```php
// file: components/user_card.php
function render_user_card($user) {
    return [
        "div" => [
            "@class" => "card mb-3",
            "div" => [
                "@class" => "card-body",
                "h5" => e($user['name']),
                "p"  => ["@class" => "text-muted", "@text" => e($user['email'])]
            ]
        ]
    ];
}

// file: router.php
fst_get('/users', function() {
    $users = fst_db_select('users');
    
    // Alih-alih merender iterasi langsung di sini, panggil komponen!
    $user_cards = array_map('render_user_card', $users);

    fst_template('BaseLayout', [
        "h2" => "Daftar Pengguna",
        "div" => [
            "@class" => "user-list",
            // Array of nodes dapat langsung disuntikkan ke parent
            "@append" => $user_cards 
        ]
    ]);
});
```
Dengan cara ini, template utama Anda tetap bersih, dan komponen `<UserCard>` dapat digunakan kembali (reusable) di berbagai rute.

---

## Global Middleware
Untuk mendukung *Mobile Apps* atau aplikasi eksternal, Anda membutuhkan mekanisme **CORS** (Cross-Origin Resource Sharing) dan Autentikasi Stateless (seperti JWT/Bearer Token).

**Solusi:** Manfaatkan fitur `require` di `fullstuck.json` atau gunakan rute `fst_any('*')` sebagai middleware global.

**Cara Terbaik (Pre-booting via `require`):**
Buat file `middleware/api_global.php` dan daftarkan di config `"require": ["middleware/api_global.php"]`.

```php
// middleware/api_global.php

// 1. Injeksi Header CORS Global
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-FST-Request");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    fst_status_code(200);
    die();
}

// 2. Fungsi Helper Verifikasi JWT/Bearer Token
function authenticate_api() {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';
    
    if (empty($auth) || !str_starts_with($auth, 'Bearer ')) {
        fst_json(['error' => 'Unauthorized'], 401);
    }
    
    $token = substr($auth, 7);
    // ... lakukan verifikasi token (misal via Firebase Auth / JWT library)
    return $user_payload;
}
```

Di dalam `router.php`, Anda tinggal memanggil fungsi auth di rute API yang diamankan:
```php
fst_get('/api/saldo', function() {
    $user = authenticate_api();
    fst_json(['saldo' => 50000, 'user_id' => $user['id']]);
});
```

---

## Request Interceptor
Jika Anda menggunakan FST Agent (Fragment Routing) tapi butuh menyuntikkan *header* tambahan di setiap request (misal untuk mengirim token Bearer atau preferensi bahasa).

**Solusi:** Gunakan metode `fst.setInterceptor()` di client-side.

```javascript
// Letakkan di dalam file js utama (main.js)
fst.setInterceptor((url, fetchOptions) => {
    // Tambahkan header khusus sebelum request AJAX dieksekusi
    const token = localStorage.getItem('api_token');
    if (token) {
        fetchOptions.headers['Authorization'] = `Bearer ${token}`;
    }
    fetchOptions.headers['X-Timezone'] = Intl.DateTimeFormat().resolvedOptions().timeZone;
    
    // Kembalikan konfigurasi yang sudah dimodifikasi
    return fetchOptions; 
});
```
Agen FST otomatis akan mematuhi *interceptor* ini untuk setiap klik link bersyarat SPA maupun *submit form*!

---

## Global Exception Handler
Saat masuk *production* (`production: true`), `fullstuck.php` hanya menampilkan layar putih/error statis jika terjadi HTTP 500. Jika Anda ingin mengirim notifikasi spesifik (seperti Webhook Telegram/Slack) setiap kali terjadi *Fatal Error* atau masalah PDO, gunakan `fst_error_handler`.

**Solusi:**
```php
// Letakkan di ujung awal router.php atau via fitur "require" di config
fst_error_handler(function(Throwable $e) {
    // 1. Ekstrak info
    $msg = $e->getMessage();
    $file = $e->getFile();
    
    // 2. Kirim notifikasi darurat (contoh via Telegram)
    // send_telegram_alert("🚨 ERROR: $msg di $file");
    
    // Exception tidak dihentikan di sini, ia akan tetap merender statis HTTP 500
    // atau 'error_handlers' config bawaan FullStuck setelah callback ini selesai.
});
```

---

## Database Migrations
Micro-framework ini tidak dilengkapi *migration engine* yang *bloated*. Jangan mengeksekusi DDL secara manual berulang-ulang di server *production*!

**Solusi:**
Buatlah file skrip khusus (misal `tools/migrate.php`) yang dieksekusi hanya lewat Terminal untuk meng-*upgrade* versi *schema* secara prosedural.

```php
// tools/migrate.php
require 'fullstuck.php'; // Load environment db

echo "Migrating DB...\n";

// Versi 1
fst_db('EXEC', "CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT)");
// Versi 2
fst_db('EXEC', "ALTER TABLE users ADD COLUMN email TEXT");

echo "Done.\n";
```
*Tip: Untuk skala Enterprise yang butuh migrasi up/down kompleks, pertimbangkan menggunakan library standar seperti [Phinx](https://phinx.org/) tanpa perlu menanamnya ke dalam core FullStuck.*

---

## Testing Strategy
Sulit melakukan pengujian murni (*Unit Testing*) fungsi-fungsi internal FullStuck karena ia beroperasi menggunakan ruang global monolitik.

**Solusi:** Lakukan pendekatan **Feature Testing** (menembak langsung ke Endpoint). Gunakan **Pest PHP** atau **Playwright** untuk mensimulasikan permintaan klien nyata.

Contoh Pengujian HTTP dengan ekstensi PHP sederhana:
```php
// test.php
$response = file_get_contents("http://localhost:8000/api/users");
if (!str_contains($response, "Budi")) {
    die("Test Failed: Budi not found in response.");
}
echo "Test Passed!";
```
Metode "*Outside-in*" ini jauh lebih efektif dan menjamin fungsionalitas keseluruhan aplikasi tanpa terganggu masalah manipulasi memori internal framework.

---

## CRUD Recipe
Untuk melihat bagaimana semua konsep (Router, Validasi, Database, CSRF, Flash Message, dan DOM Templating) bekerja sama, ini adalah contoh alur utuh sebuah aplikasi "Tugas":

```php
// 1. Skema Database (Misal di tools/migrate.php)
fst_db('EXEC', "CREATE TABLE tasks (id INTEGER PRIMARY KEY, title TEXT, is_done INTEGER DEFAULT 0)");

// 2. Daftar Rute (di router.php)

// Tampilkan Daftar & Form
fst_get('/tasks', function() {
    $tasks = fst_db_select('tasks', [], ['order_by' => 'id DESC']);
    
    // Pesan Flash jika ada
    $alert = '';
    if (fst_flash_has('msg')) {
        $alert = ["div" => ["@class" => "alert success", "@text" => fst_flash_get('msg')]];
    }

    fst_template('BaseLayout', [
        "h1" => "Daftar Tugas",
        ".flash-container" => ["@html" => $alert],
        
        // Render List Tugas
        "ul.task-list" => ["@append" => array_map(function($t) {
            return ["li" => [
                "span" => e($t['title']),
                "form" => [
                    "@action" => "/tasks/delete/".$t['id'], "@method" => "POST",
                    "@style" => "display:inline; margin-left: 10px;",
                    "@append" => [ fst_csrf_field(), ["button" => "Hapus"] ]
                ]
            ]];
        }, $tasks)],
        
        // Render Form Tambah (Akan disuntik ke tag main atau penampung yang ada di BaseLayout)
        "form.add-task" => [
            "@action" => "/tasks", "@method" => "POST",
            "@append" => [
                fst_csrf_field(),
                ["input" => ["@type" => "text", "@name" => "title", "@placeholder" => "Tugas baru", "@required" => true]],
                ["button" => "Simpan"]
            ]
        ]
    ]);
});

// Proses Simpan Data
fst_post('/tasks', function() {
    fst_csrf_check(); // Validasi token keamanan
    
    // Validasi input
    $input = fst_validate($_POST, [ 'title' => 'required|min:3' ]);
    
    if (!$input['valid']) {
        // Jika gagal, kembalikan dengan error 400
        fst_abort(400, "Input tidak valid: " . $input['errors']['title'][0]);
    }
    
    // Simpan ke database
    fst_db_insert('tasks', ['title' => $input['data']['title']]);
    
    // Set Flash Message
    fst_flash_set('msg', 'Tugas berhasil ditambahkan!');
    
    // Redirect kembali
    fst_redirect('/tasks');
});

// Proses Hapus Data
fst_post('/tasks/delete/:id', function($params) {
    fst_csrf_check();
    fst_db_delete('tasks', ['id' => $params['id']]);
    fst_flash_set('msg', 'Tugas dihapus!');
    fst_redirect('/tasks');
});
```
*Dengan skrip ringkas ini, Anda sudah mendapatkan SSR CRUD super cepat tanpa load halaman jika digabungkan dengan agen SPA (`agent_js: true`)!*

# 📚 9. API Reference

Dokumen ini menyediakan referensi lengkap untuk semua fungsi publik (API) yang tersedia di framework FullStuck, baik untuk backend (PHP) maupun frontend (`agent.js`).

---

## 🐘 PHP Backend API

Berikut adalah fungsi-fungsi pembantu (*helper*) PHP yang disediakan oleh core FullStuck. Sebagian besar fungsi diawali dengan prefiks `fst_`, kecuali fungsi `e()`.

### 1. Core & Konfigurasi

- **`fst_app($key = null, $value = null)`**
  Mengambil atau menyimpan state/variabel global aplikasi yang hidup selama satu siklus request.
- **`fst_config($key = null, $default = null)`**
  Mengambil nilai konfigurasi dari file `fullstuck.json`. Mendukung notasi titik (dot notation) misal: `fst_config('database.default')`.
- **`fst_is_dev()`**
  Mengembalikan `true` jika aplikasi sedang berjalan di environment lokal/development (berdasarkan ada tidaknya file `.fst_dev_mode`).
- **`fst_is_safe_to_debug()`**
  Mengembalikan `true` jika aplikasi berada di environment development, atau nilai konfigurasi `"production"` diset ke `false`.
- **`fst_log($level, $message, $context = [])`**
  Menulis pesan ke file log internal `.fst.log`. Level bisa berupa string seperti `'INFO'`, `'WARNING'`, `'ERROR'`.
- **`fst_error_handler(callable $callback)`**
  Mendaftarkan penangan error (error handler) kustom yang akan dieksekusi jika terjadi *Exception* atau *Fatal Error*.

### 2. Routing & Middleware

- **`fst_get($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP GET.
- **`fst_post($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP POST.
- **`fst_put($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP PUT.
- **`fst_patch($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP PATCH.
- **`fst_delete($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP DELETE.
- **`fst_any($path, $callback, $middleware = [])`**
  Mendaftarkan rute yang menerima metode HTTP apapun (Universal Route).
- **`fst_group($prefix, $callback, $middleware = [])`**
  Mengelompokkan rute dengan prefiks URL dan middleware bersama.
- **`fst_abort($code, $message = '')`**
  Menghentikan eksekusi dan menampilkan halaman error HTTP sesuai `$code` (mendukung integrasi dengan `error_handlers` di config).

### 3. HTTP Request & Response

- **`fst_uri()`**
  Mengembalikan URI dari request saat ini (tanpa menyertakan query string).
- **`fst_method()`**
  Mengembalikan metode HTTP dari request (misal: `GET`, `POST`, `PUT`, `DELETE`).
- **`fst_input($key, $default = null)`**
  Mendapatkan nilai parameter request (dari `$_POST`, `$_GET`, atau payload JSON) berdasarkan `$key`.
- **`fst_request()`**
  Mengembalikan seluruh payload data request dalam bentuk array asosiatif.
- **`fst_file($key)`**
  Mendapatkan array informasi file hasil unggahan (dari `$_FILES`) yang berhasil di-upload.
- **`fst_redirect($url, $code = 302, $allow_external = false)`**
  Mengalihkan (redirect) pengguna ke URL yang diberikan.
- **`fst_json($data, $status = 200)`**
  Mengembalikan respon HTTP berformat JSON dan segera menghentikan eksekusi script.
- **`fst_text($string, $status = 200)`**
  Mengembalikan respon HTTP berformat plain-text dan segera menghentikan eksekusi script.
- **`fst_status_code($code)`**
  Mengatur kode status HTTP untuk respons (misal 200, 404, 500).

### 4. Database (Query Builder)

- **`fst_db($mode, $sql, $params = [], $connection = null)`**
  Mengeksekusi *raw query* SQL. `$mode` dapat bernilai: `'ALL'` (banyak baris), `'ROW'` (1 baris), `'SCALAR'` (1 nilai tunggal), `'EXEC'` (menjalankan command, mengembalikan affected rows/last insert id).
- **`fst_db_select($table, $conditions = [], $options = [])`**
  Mengambil banyak baris dari `$table` sesuai array asosiatif `$conditions` (mendukung operator otomatis). Opsi `$options` bisa berisi `['order_by' => '...', 'limit' => 10]`.
- **`fst_db_row($table, $conditions = [], $options = [])`**
  Sama seperti select, namun hanya mengembalikan baris pertama (satu array asosiatif).
- **`fst_db_exists($table, $conditions = [], $options = [])`**
  Mengembalikan nilai `true` jika minimal ada satu baris yang memenuhi `$conditions`.
- **`fst_db_insert($table, $data, $options = [])`**
  Menyisipkan sebuah baris baru dengan pasangan key-value pada `$data`.
- **`fst_db_update($table, $data, $conditions = [], $options = [])`**
  Memperbarui baris-baris pada tabel yang memenuhi `$conditions` menggunakan nilai-nilai di `$data`.
- **`fst_db_delete($table, $conditions, $options = [])`**
  Menghapus baris-baris yang memenuhi `$conditions`.
- **`fst_db_begin($connection = null)`**, **`fst_db_commit($connection = null)`**, **`fst_db_rollback($connection = null)`**
  Membuka, mengonfirmasi (commit), atau membatalkan (rollback) *Database Transaction*.
- **`fst_db_quote_ident($name, $connection = null)`**
  Melindungi/meng-quote nama tabel atau kolom secara aman sesuai driver DB (misal mem-backtick ``` `table` ``` di MySQL).

### 5. Templating & View

- **`fst_view($path, $data = [])`**
  Melakukan *require* ke file PHP biasa (`$path`) dan mengekstrak array `$data` agar menjadi variabel lokal. (Hanya untuk Legacy Template).
- **`fst_partial($path, $data = [])`**
  Sama seperti `fst_view()`, berguna sebagai penanda (semantic alias) untuk file potongan tampilan.
- **`fst_view_share($key, $value = null)`**
  Berbagi variabel global agar dapat diakses oleh semua file view/template.
- **`fst_template(string $templatePath, array $data, array $rules, ?string $cacheDir = null, bool $forceRebuild = false): void`**
  Merender file HTML Murni ke browser menggunakan FullStuck Template Compiler berbasis Array Ruleset (Cara Modern).
- **`fst_template_render(string $templatePath, array $data, array $rules, ?string $cacheDir = null, bool $forceRebuild = false): string`**
  Sama seperti `fst_template`, tetapi mengembalikan kode HTML hasil render dalam bentuk tipe data String (sangat berguna untuk menyuntikkan template ke dalam sub-komponen layout parent).
- **`fst_serve_static_file($file_path)`**
  Menyajikan isi sebuah file statis ke klien langsung.

### 6. Session & Flash Message

- **`fst_session_set($key, $value)`**, **`fst_session_get($key, $default = null)`**, **`fst_session_forget($key)`**
  Pengelolaan nilai sesi (`$_SESSION`) user saat ini.
- **`fst_flash_set($key, $message)`**, **`fst_flash_get($key, $default = null)`**, **`fst_flash_has($key)`**
  Pengelolaan data `Flash Message` yang hanya hidup 1 kali. Jika di-get, data akan otomatis terhapus untuk request selanjutnya (berguna untuk notifikasi "Berhasil Disimpan").

### 7. Security, Upload & Utility

- **`fst_escape($str)`** / **`e($str)`**
  Sterilisasi string mencegah Cross-Site Scripting (XSS). Disarankan setiap kali mencetak output dari user.
- **`fst_csrf_token()`**
  Men-generate (jika belum ada) dan mengembalikan string raw token CSRF pengguna saat ini.
- **`fst_csrf_field()`**
  Mengembalikan tag elemen `<input type="hidden" name="_token" value="...">` yang berisi Token CSRF.
- **`fst_csrf_check()`**
  Memvalidasi kecocokan token dari request (via POST/_token atau Header) dengan sesi. Langsung abort `403` jika gagal (Wajib digunakan di semua handler rute perubah data).
- **`fst_upload($key, $folder, $options = [])`**
  Fungsi utilitas pintar untuk memindahkan dan memvalidasi file unggahan.
- **`fst_validate($data, $rules)`**
  Memvalidasi associative array input berdasar format tertentu (seperti `'nama' => 'required|min:3'`). Mengembalikan `['valid' => bool, 'errors' => array, 'data' => array]`.
- **`fst_dump(...$vars)`** / **`fst_dd(...$vars)`**
  Mencetak output terstruktur (menggunakan `var_dump`) ke layar untuk keperluan debugging. `fst_dd()` langsung memanggil `die()` sesudahnya.

### 8. Fragment Routing (Khusus Backend)

- **`fst_is_fragment_request(): bool`**
  Menandakan apakah request HTTP masuk via Javascript FST Agent (bukan Full Page Reload).
- **`fst_fragment_target(): string`**
  Mengembalikan *CSS Selector Target* (misal `#app` atau `body`) yang dicari oleh FST Agent.
- **`fst_extract_html_fragment($html, $selector = 'body')`**
  Fungsi utilitas untuk memotong HTML besar dan hanya mengambil bagian dalam/inner HTML dari selector yang ditentukan.

---

## ⚡ FST Agent API (Client-Side / `agent.js`)

Jika FST Agent dinyalakan (`"agent_js": true`), objek utama dapat diakses di sisi klien browser melalui **`window.fst`**.

### 1. Navigasi & Fragment Fetch

- **`fst.go(url, options = {})`**
  Fungsi serbaguna untuk memaksa navigasi fragment secara programmatik (tanpa klik `<a>`).
  Opsi `options` mendukung *property*:
  - `target` (string selector DOM)
  - `history` (boolean - simpan ke browser history?)
  - `scroll` (string/boolean - `'smooth'`, `'instant'`, atau `false`)
  - `indicator` (string class untuk ditambahkan ke target ketika dimuat)
  - `cache` (boolean - paksa simpan/jangan simpan cache fragment)

- **`fst.fetchFragment(url, targetSelector, pushHistory, triggerElement = null, isPopstate = false)`**
  API internal tingkat bawah untuk sekadar mengambil HTML dari server ke *Selector* spesifik. Berguna jika butuh logic asinkron (Lebih disarankan menggunakan `fst.go`).

### 2. Client-Side Routing Configuration

- **`fst.set(pattern, callback)`**
  Menentukan fungsi rute statis/dinamis yang berjalan eksklusif di Frontend JS.
  - Parameter URL akan diekstrak otomatis: `fst.set('/user/:id', (params) => { ... })`.
- **`fst.group(prefix, callback)`**
  Mengelompokkan deklarasi `fst.set()` agar menempel di belakang rute awalan (*prefix*).
- **`fst.matchRoute(path)`**
  Menyocokkan *path* URL dengan kumpulan rute yang telah diregistrasi.

### 3. Hooks & Interceptors (Mencegat Request)

- **`fst.setInterceptor(callback)`**
  Menyisipkan logika *Fetch API*. Sangat bermanfaat untuk menyelipkan Custom Headers, Authorization Token (JWT), dll. Callback menerima argumen `(url, fetchOptions)` dan **harus** mengembalikan obejct `fetchOptions` utuh, atau tidak mengembalikan apapun (falsy) jika modifikasi dilakukan langsung via referensi.
  ```javascript
  fst.setInterceptor(async (url, options) => {
      options.headers['X-Timezone'] = Intl.DateTimeFormat().resolvedOptions().timeZone;
      return options;
  });
  ```
- **`fst.setBefore(callback)`**
  Hook pre-navigasi. Callback menerima target `path`. Jika mengembalikan `false`, FST Agent akan menghentikan navigasi SPA (bisa digunakan untuk *Confirm Dialog/Unsaved Changes warning*).
- **`fst.setAfter(callback)`**
  Hook yang dipanggil seketika *setelah* route callback lokal (`fst.set`) dieksekusi.
- **`fst.setNotFound(callback)`**
  Meng-override perlakuan default jika URL dipanggil namun tidak ada rute frontend yang cocok (Bawaannya, dia akan fallback *fetching* fragmen dari server).

### 4. Utility Security

- **`fst.escape(str)`** / **`fst.e(str)`**
  Membersihkan/men-sanitize nilai string dari karakter-karakter spesial HTML (`&`, `<`, `>`, `"`, `'`) sebelum di-inject ke properti `innerHTML` (Menghindari kerentanan DOM-based XSS).

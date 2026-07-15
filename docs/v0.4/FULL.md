# 🚀 FullStuck v0.4 — Documentation

**FullStuck.php** adalah micro-framework PHP 1-file yang menggunakan **Path-Based Colocation** — URL website Anda dicerminkan langsung dari struktur folder. Tanpa `vendor/`, tanpa Composer, tanpa konfigurasi routing manual.

---

## 📦 1. Instalasi & Quick Start

### 🤖 Install via AI Coding Agent
```text
Install fullstuck.php. Panduan: https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/ai-setup.md
```

### Download Manual
```bash
curl -O https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/fullstuck.php
```

### CLI (Rekomendasi)
```bash
php fullstuck.php init --db=sqlite --agent_js=yes --scaffold=yes --htaccess=yes
```

| Flag | Pilihan | Default |
|---|---|---|
| `--db` | `sqlite`, `mysql`, `pgsql`, `none` | `sqlite` |
| `--agent_js` | `yes`, `no` | `yes` |
| `--scaffold` | `yes`, `no` | `yes` |
| `--htaccess` | `yes`, `no` | `yes` |

### Menjalankan Server Lokal
```bash
php -S localhost:8000 fullstuck.php
```

### Web Server Deployment

**Apache / LiteSpeed** — `.htaccess` sudah di-generate otomatis oleh `init`.

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /fullstuck.php?$query_string;
}
```

**FrankenPHP / Caddy:**
```caddyfile
:8000 {
    root * .
    php_server { index fullstuck.php }
}
```

### Membaca Dokumentasi via CLI
```bash
php fullstuck.php docs       # Daftar isi
php fullstuck.php docs:1     # Bab tertentu
php fullstuck.php docs:9     # API Reference
php fullstuck.php docs:full  # Seluruh dokumen
```

---

## 🏗️ 2. Struktur Folder & Routing

### Topologi Proyek
```text
my-project/
├── .htaccess              # Pengaman web server
├── fullstuck.json         # Konfigurasi utama
├── fullstuck.php          # Core engine (jangan diedit)
├── cache/                 # Auto-generated (masukkan ke .gitignore)
├── components/            # Komponen UI reusable
├── globals/               # Helper PHP (auto-loaded)
└── app/                   # Pusat routing (Path-Based Colocation)
```

### Cara Kerja Routing
Setiap folder di `app/` otomatis menjadi endpoint URL. Letakkan salah satu dari **4 file standar** (4-Pillars) untuk mengaktifkannya:

| File | Fungsi |
|---|---|
| `content.fst.php` | Tampilan halaman (GET). Menggunakan syntax Blade. |
| `action.php` | Logika mutasi data (POST/PUT/PATCH/DELETE). |
| `client.js` | Script frontend SPA. Otomatis dibungkus IIFE. |
| `_guard.php` | Middleware pelindung. Berjalan sebelum content/action. |

### Contoh Pemetaan
```text
app/
├── content.fst.php                → GET /
├── _layout.fst.php                → Layout global
├── _guard.php                     → Guard global
├── blog/
│   ├── content.fst.php            → GET /blog
│   ├── client.js                  → JS untuk /blog
│   └── [slug]/
│       ├── content.fst.php        → GET /blog/artikel-saya
│       └── action.php             → POST /blog/artikel-saya
├── api/
│   └── products/
│       └── action.php             → GET|POST|PUT|DELETE /api/products
└── admin/
    ├── _guard.php                 → Melindungi /admin/*
    ├── _layout.fst.php            → Layout khusus admin
    └── users/
        └── content.fst.php        → GET /admin/users
```

### Dynamic Routing (Parameter URL)
Buat folder dengan nama `[parameter]` (kurung siku). Nilainya otomatis tersedia di:
- `fst_input('slug')`
- `$_GET['slug']`
- `$_REQUEST['slug']`

```php
// Di app/blog/[slug]/content.fst.php
<?php $slug = fst_input('slug'); ?>
<h1>Artikel: {{ $slug }}</h1>
```

### Mode Headless API
Jika sebuah folder **hanya** berisi `action.php` (tanpa `content.fst.php`), maka `action.php` juga akan melayani request `GET`. Cocok untuk endpoint REST API murni.

```text
app/api/products/action.php  → Menerima GET, POST, PUT, DELETE
```

### Pewarisan Layout & Guard (Top-Down & Bottom-Up)
Kedua file ini bersifat **kumulatif**, namun dengan arah eksekusi yang berbeda:
- **`_guard.php` dieksekusi *Top-Down* (Luar ke Dalam):** Guard terluar (misal `/app/_guard.php`) akan dijalankan lebih dulu. Ini memungkinkan penghentian akses sedini mungkin sebelum mencapai rute di dalamnya.
- **`_layout.fst.php` dirangkai *Bottom-Up* (Dalam ke Luar / Matryoshka):** Konten dari anak akan di-render terlebih dahulu, dimasukkan ke layout anak, lalu keseluruhan hasilnya dibungkus oleh layout induk (bapak).

---

## 🎨 3. Syntax Templating

File `.fst.php` dikompilasi otomatis menjadi PHP murni dan di-cache. Syntax mirip Laravel Blade:

### Output & Escaping
```html
<!-- Otomatis di-escape (aman dari XSS) -->
{{ $user['name'] }}

<!-- Output mentah (hanya untuk HTML terpercaya) -->
{!! $html_content !!}

<!-- Escape kurung kurawal untuk JS/JSON literal -->
@{{ variableVueJs }}
```

### Percabangan
```html
@if($user['role'] === 'admin')
    <button>Hapus</button>
@elseif($user['role'] === 'editor')
    <button>Edit</button>
@else
    <span>Hanya lihat</span>
@endif
```

### Perulangan
```html
<ul>
@foreach($items as $item)
    <li>{{ $item['name'] }}</li>
@endforeach
</ul>
```

### Layout: Yield & Section
**Di `app/_layout.fst.php`:**
```html
<html>
<head><title>@yield('title', 'Default Title')</title></head>
<body>
    <nav>...</nav>
    <main>@yield('content')</main>
</body>
</html>
```

**Di `app/dashboard/content.fst.php`:**
```html
@section('title')
Dashboard
@endsection

<!-- Konten tanpa @section otomatis masuk ke @yield('content') -->
<h1>Selamat Datang</h1>
<p>Ini adalah dashboard.</p>
```

---

## 🧩 4. Components

Komponen adalah potongan UI reusable di folder `components/`. Dipanggil on-demand via `@component`.

**Membuat:** `components/alert.fst.php`
```html
<div class="alert alert-{{ $type ?? 'info' }}">
    <p>{{ $message }}</p>
</div>
```

**Menggunakan:**
```html
@component('alert', ['type' => 'danger', 'message' => 'Login gagal!'])
```

**Subfolder:**
```text
components/admin/sidebar.fst.php  → @component('admin/sidebar')
```

Setiap komponen berjalan dalam **Scoped Closure** — variabel di dalamnya tidak bocor ke parent.

> **⚠️ Peringatan JavaScript pada Komponen:**
> Jangan meletakkan tag `<script>` statis (terutama deklarasi `const` atau `let`) di dalam komponen, terlebih jika komponen tersebut dipanggil di dalam perulangan `@foreach`. Script tersebut akan tercetak berulang kali dan memicu *Error: Identifier has already been declared*. Sebagai solusinya, gunakan **Event Delegation (`fst.on`)** secara terpusat di `client.js` untuk memberikan interaktivitas pada komponen.

---

## 🔌 5. FST-Agent (SPA Engine)

Jika `agent_js` aktif di `fullstuck.json`, setiap klik link `<a>` dan submit `<form>` otomatis dimuat via AJAX tanpa full reload.

> **💡 Catatan Ruang Lingkup (Scope):**
> File `client.js` otomatis dibungkus dengan metode *IIFE (Immediately Invoked Function Expression)* untuk mencegah kebocoran status global (*global state*). Oleh karena itu, fungsi atau variabel yang Anda deklarasikan di dalamnya **tidak bisa** dipanggil lewat atribut HTML *inline* seperti `<button onclick="sapa()">`. Jika Anda harus membuat fungsi global, daftarkan secara eksplisit ke dalam objek *window*: `window.sapa = () => { ... }`.

### Event Listener (WAJIB pakai `fst.on`)
Di dalam `client.js`, **jangan** pakai `document.addEventListener` secara langsung. Gunakan `fst.on()` agar listener otomatis dibersihkan saat navigasi halaman:

```javascript
// ✅ BENAR: Otomatis dibersihkan saat pindah halaman
fst.on('click', '#btn-hapus', (e, el) => {
    // ...
});

// ❌ SALAH: Akan menumpuk di memory setiap buka halaman
document.getElementById('btn-hapus').addEventListener('click', ...);
```

### Lifecycle Hook: `fst.onMount`
Callback dijamin berjalan setelah DOM selesai dirender. Jika mengembalikan fungsi, fungsi tersebut dieksekusi saat halaman ditinggalkan (teardown):

```javascript
fst.onMount(() => {
    const chart = new Chart(document.getElementById('myChart'), { ... });
    
    // Teardown: bersihkan saat pindah halaman
    return () => chart.destroy();
});
```

### Event Bus (Komunikasi Antar-Modul)
```javascript
// Pengirim
fst.emit('cart_updated', { total: 50000 });

// Penerima
fst.on('cart_updated', (detail) => {
    console.log('Total:', detail.total);
});
```

### Global Listener
Listener yang **tidak** boleh dibersihkan saat navigasi (misal: theme toggle):
```javascript
fst.on('click', '#btn-theme', (e, el) => { ... }, { global: true });
```

### Navigasi Programatik
```javascript
fst.go('/dashboard');
fst.go('/users', { fragment: '#content', history: false, scroll: 'smooth' });
```

### HTML Data Attributes
| Atribut | Fungsi |
|---|---|
| `data-fst-fragment="#id"` | Target elemen untuk injeksi HTML |
| `data-fst-normal-load` | Bypass SPA, lakukan full page reload |
| `data-fst-history="false"` | Jangan catat di browser history |
| `data-fst-scroll="false"` | Matikan auto scroll-to-top (dukung nilai "smooth") |
| `data-fst-indicator="class"` | CSS class loading kustom |
| `data-fst-ignore` | Script hanya dieksekusi 1 kali |

### Script Deduplication
Script eksternal (dengan `src`) yang sudah dimuat **tidak** akan dimuat ulang saat navigasi SPA. Library seperti Chart.js atau Swiper aman dari eksekusi ganda.

### ⚠️ Batasan `innerHTML` & Aturan "Wadah Bisu" (Dumb Wrapper)
FST-Agent memotong dan menyuntikkan HTML ke klien menggunakan metode **`innerHTML`**. Artinya, hanya *isi konten* dari elemen target yang akan diganti. **Class, *inline style*, atau atribut yang melekat pada tag pembungkus target itu sendiri TIDAK akan diperbarui saat terjadi navigasi SPA.**

**Praktik Terbaik:** Jangan tempatkan class/styling yang bisa berubah antar-halaman langsung di elemen yang menjadi target `X-FST-Fragment` (misalnya `#app` atau `#page-content`). Jadikan elemen target tersebut sebagai "Wadah Bisu" yang statis.

```html
<!-- ✅ BENAR: Wadah statis, class desain ditaruh DI DALAM konten -->
<div id="page-content"> 
    <div class="bg-hitam text-putih">
        <h1>Tentang Kami</h1>
    </div>
</div>

<!-- ❌ SALAH: Class 'bg-putih' tidak akan terganti (hilang konteks) saat FST-Agent me-load halaman baru -->
<div id="page-content" class="bg-putih text-hitam">
    <h1>Beranda</h1>
</div>
```

### 🎯 Dukungan Selector X-FST-Fragment
FST-Agent menggunakan konversi parser HTML kustom di *backend*, sehingga hanya mendukung *CSS selector* standar:

**✅ DIDUKUNG:**
- **ID:** `#app` *(Paling cepat & direkomendasikan)*
- **Class:** `.container`
- **Tag:** `main`, `body`
- **Atribut:** `[data-active]`, `[data-role="admin"]` *(Hanya nilai alfanumerik, spasi, `/`, `-`, `.`)*
- **Hierarki/Kombinasi:** `#app .container`, `#app > .sidebar`, `#app, .cart`

**❌ TIDAK DIDUKUNG (Otomatis Diblokir):**
- **Pseudo-classes/elements:** `:hover`, `:nth-child()`, `::before`
- **Sibling:** `~`, `+`
- **Regex atribut:** `[attr^=val]`, `[attr$=val]`, `[attr*=val]`

---

## 🛠️ 6. Action & Headless API

File `action.php` menangani logika mutasi data. Fokus ke PHP murni:

```php
<?php
// app/users/action.php
$method = fst_method();

if ($method === 'POST') {
    $val = fst_validate(fst_request(), [
        'nama' => 'required|min:3',
        'email' => 'required|email'
    ]);
    if (!$val['valid']) {
        fst_json(['errors' => $val['errors']], 422);
    }
    $id = fst_db_insert('users', $val['data']);
    fst_redirect('/admin/users');
}

if ($method === 'DELETE') {
    $id = fst_input('id');
    fst_db_delete('users', ['id' => $id]);
    fst_json(['success' => true]);
}
```

### Method Spoofing (Form HTML)
Karena elemen `<form>` HTML standar hanya mendukung metode `GET` dan `POST`, Anda dapat melakukan *spoofing* untuk metode lain (seperti `PUT` atau `DELETE`) dengan menyisipkan input tersembunyi bernama `_method`:
```html
<form method="POST" action="/admin/users">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="id" value="5">
    <button type="submit">Hapus</button>
</form>
```
Fungsi `fst_method()` di sisi server akan otomatis membaca nilai `_method` ini sebagai metode *request* aktual.

### Error Handling
```php
fst_abort(404, "User tidak ditemukan");
```
- Request ke `/api/*` atau dengan header `Accept: application/json` → response JSON otomatis.
- Request biasa → framework mencari file `404.fst.php` dari folder error naik ke `app/`.

---

## 🔒 7. Keamanan & Session

### Session Fixation Protection
**Wajib** dipanggil setelah login berhasil:
```php
fst_session_regenerate(true);
```

### Keamanan Form (Tanpa CSRF Token)
v0.4 tidak menggunakan CSRF token. Perlindungan form mengandalkan:
- Cookie `SameSite=Lax` (otomatis diset framework)
- Header `X-FST-Request` (disuntikkan FST-Agent)

### File Upload (Secure by Default)
```php
$result = fst_upload('foto', 'uploads/avatar', [
    'max_size' => 1024,                        // KB
    'allowed_types' => ['jpg','png','webp'],   // Whitelist
]);

if ($result['success']) {
    echo $result['path']; // "uploads/avatar/foto-abc123.jpg"
}
```
Ekstensi berbahaya (`.php`, `.exe`, dll) di-*block* secara hardcoded dan tidak bisa di-override.

### XSS Prevention
- **Backend:** Gunakan <code v-pre>{{ $var }}</code> di template (auto-escape) atau `e($var)` di PHP murni.
- **Frontend:** Gunakan `fst.e(string)` sebelum menyuntikkan ke `innerHTML`.

---

## 🗄️ 8. Database

Fungsi `fst_db_*` adalah wrapper tipis di atas PDO. **Bukan ORM.** Mendukung SQLite, MySQL, dan PostgreSQL.

### CRUD Dasar
```php
// Select
$users = fst_db_select('users', ['status' => 'active'], [
    'order_by' => 'id DESC',
    'limit' => 10
]);

// Select dengan operator
$products = fst_db_select('products', [
    'price >' => 50000,
    'name LIKE' => '%baju%'
]);

// Satu baris
$user = fst_db_row('users', ['id' => 1]);

// Cek keberadaan
$exists = fst_db_exists('users', ['email' => 'budi@mail.com']);

// Insert (return last_id)
$id = fst_db_insert('users', ['nama' => 'Budi', 'email' => 'budi@mail.com']);

// Update (return affected_rows)
fst_db_update('users', ['status' => 'inactive'], ['id' => 5]);

// Delete (return affected_rows)
fst_db_delete('users', ['id' => 5]);
```

### Raw Query
Untuk query kompleks (JOIN, COUNT, GROUP BY), gunakan `fst_db()`:
```php
// Mode: 'ALL' (banyak baris), 'ROW' (1 baris), 'SCALAR' (1 nilai), 'EXEC' (eksekusi)
$posts = fst_db('ALL', 
    'SELECT p.*, u.nama FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = ?', 
    ['published']
);
$total = fst_db('SCALAR', 'SELECT COUNT(*) FROM users WHERE status = ?', ['active']);
```

### Transactions
```php
try {
    fst_db_begin();
    fst_db_insert('orders', ['total' => 100000]);
    fst_db_update('stock', ['qty' => 0], ['id' => 1]);
    fst_db_commit();
} catch (Exception $e) {
    fst_db_rollback();
}
```

### Multi-Connection
Semua fungsi database mendukung parameter `$connection` atau opsi `['connection' => 'nama']`:
```php
$remote = fst_db('ALL', 'SELECT * FROM logs', [], 'mysql_remote');
$users = fst_db_select('users', [], ['connection' => 'mysql_remote']);
```

---

## ⚙️ 9. Konfigurasi `fullstuck.json`

```jsonc
{
  // Wajib. true = produksi (cache terkunci, error tersembunyi).
  "production": false,

  // Konfigurasi routing.
  "routing": {
    "base_path": "/",                        // Untuk deploy di subfolder
    "public_folders": ["assets", "uploads"]   // Folder yang boleh diakses publik
  },

  // Auto-load file PHP sebelum routing. Default: ["globals"].
  "require": ["globals"],

  // Aktifkan SPA engine.
  "agent_js": true,

  // Opsi fragment SPA.
  "fragment": {
    "history_cache": false   // Cache navigasi di sisi klien
  },

  // Database (opsional). Mendukung multi-koneksi.
  "database": {
    "default": "main",
    "connections": {
      "main": {
        "driver": "sqlite",
        "database_path": "database.sqlite"
      },
      "mysql_remote": {
        "driver": "mysql",
        "host": "${DB_HOST}",
        "port": 3306,
        "dbname": "${DB_NAME}",
        "username": "${DB_USER}",
        "password": "${DB_PASS}"
      }
    }
  }
}
```

Semua nilai string mendukung interpolasi variabel lingkungan: `${NAMA_ENV}`.

---

## 🧾 10. Logging & Error Handling

### Logging
```php
fst_log('INFO', 'Pembayaran berhasil.', ['invoice' => 'INV-001']);
fst_log('ERROR', 'Koneksi gagal.', ['endpoint' => $url]);
```
Log ditulis ke file `.fst.log` dalam format JSON per baris.

### Error Handler Kustom
Daftarkan callback untuk mengirim notifikasi (Telegram, Slack, dll) saat error terjadi:
```php
// Letakkan di globals/error_handler.php
fst_error_handler(function(Throwable $e) {
    $msg = $e->getMessage();
    // send_telegram_alert("🚨 ERROR: $msg");
});
```

### Mode Production vs Development
| Aspek | Development | Production |
|---|---|---|
| Error display | Stack trace visual di browser | Pesan generik, detail di `.fst.log` |
| Cache view | Re-compile jika file berubah | Langsung dimuat tanpa cek |
| Cache router | Re-scan jika folder `app/` berubah | Terkunci, tidak pernah re-scan |

---

## 📚 11. API Reference

### Core & Konfigurasi
| Fungsi | Keterangan |
|---|---|
| `fst_app($key, $value)` | Get/set state runtime aplikasi |
| `fst_config($key, $default)` | Baca nilai dari `fullstuck.json` (dot notation: `database.default`) |
| `fst_is_dev()` | Cek apakah mode development |
| `fst_log($level, $message, $context)` | Tulis log ke `.fst.log` |
| `fst_error_handler(callable)` | Daftarkan callback error kustom |

### HTTP Request & Response
| Fungsi | Keterangan |
|---|---|
| `fst_uri()` | URI request saat ini (tanpa query string) |
| `fst_input($key, $default)` | Ambil data dari GET/POST/JSON body |
| `fst_request()` | Seluruh data request sebagai array |
| `fst_method()` | Metode HTTP (menangani spoofing dari _method) |
| `fst_file($key)` | Data file upload dari `$_FILES` |
| `fst_redirect($url, $code, $allow_external)` | Redirect aman (cegah open redirect) |
| `fst_json($data, $status)` | Response JSON lalu `die()` |
| `fst_text($string, $status)` | Response plain text lalu `die()` |
| `fst_status_code($code)` | Set HTTP status code |
| `fst_abort($code, $message)` | Hentikan eksekusi + tampilkan error page/JSON |

### Session
| Fungsi | Keterangan |
|---|---|
| `fst_session_set($key, $value)` | Simpan ke session |
| `fst_session_get($key, $default)` | Baca dari session |
| `fst_session_forget($key)` | Hapus dari session |
| `fst_session_regenerate($delete_old)` | Regenerasi ID session (**wajib** setelah login) |

### Database
| Fungsi | Keterangan |
|---|---|
| `fst_db($mode, $sql, $params, $conn)` | Raw query. Mode: `ALL`, `ROW`, `SCALAR`, `EXEC` |
| `fst_db_select($table, $cond, $opts)` | Select banyak baris |
| `fst_db_row($table, $cond, $opts)` | Select satu baris |
| `fst_db_exists($table, $cond, $opts)` | Cek keberadaan data (boolean) |
| `fst_db_insert($table, $data, $opts)` | Insert. Return: `last_id` |
| `fst_db_update($table, $data, $cond, $opts)` | Update. Return: `affected_rows` |
| `fst_db_delete($table, $cond, $opts)` | Delete. Return: `affected_rows` |
| `fst_db_begin/commit/rollback($conn)` | Transaction control |

### Keamanan & Utilitas
| Fungsi | Keterangan |
|---|---|
| `e($str)` / `fst_escape($str)` | Escape string untuk mencegah XSS |
| `fst_upload($key, $folder, $opts)` | Upload file secure (whitelist + blacklist) |
| `fst_validate($data, $rules)` | Validasi input (`required`, `email`, `min`, `max`, `numeric`, `in`, `min_value`, `max_value`) |
| `fst_dump(...$vars)` | Debug output (hanya di mode dev) |
| `fst_dd(...$vars)` | Debug output lalu `die()` |

### Templating & View
| Fungsi | Keterangan |
|---|---|
| `fst_view($path, $data)` | Render file PHP biasa dengan data |
| `fst_partial($path, $data)` | Alias semantik untuk `fst_view` |
| `fst_view_share($key, $value)` | Bagikan variabel ke semua view |

### Fragment (Backend)
| Fungsi | Keterangan |
|---|---|
| `fst_is_fragment_request()` | Apakah request dari FST-Agent (SPA) |
| `fst_fragment_target()` | CSS selector target fragment |

### FST-Agent API (JavaScript)
| Method | Penjelasan & Parameter Ekstra |
|---|---|
| `fst.on(event, sel, cb, opts)` | Event delegation aman. `opts` menerima konfigurasi seperti `{ global: true }` agar event tidak dihapus otomatis saat pindah halaman. |
| `fst.onMount(cb)` | Hook lifecycle yang berjalan saat halaman/fragment selesai dirender. Return sebuah function di dalamnya untuk melakukan *cleanup* (mirip `useEffect`). |
| `fst.emit(event, detail)` | Mengirim custom event ke window (otomatis menggunakan prefix `fst:` jika perlu). Parameter `detail` bisa diisi data object. |
| `fst.go(url, options)` | Navigasi programatik SPA. <br>**Opsi:**<br>• `target`: string CSS selector (default: 'body')<br>• `history`: boolean (default: true)<br>• `scroll`: boolean \| 'smooth' \| 'instant'<br>• `indicator`: string (class loading kustom) |
| `fst.set(pattern, cb)` | Mendaftarkan *client-side route*. Callback menerima `(match, triggerElement)`. |
| `fst.group(prefix, cb)` | Mengelompokkan pendaftaran route client dengan awalan path yang sama. |
| `fst.setInterceptor(cb)` | Menyisipkan logika sebelum request `fetch()`. Callback menerima `(url, fetchOptions)` dan bisa me-return objek `fetchOptions` baru (berguna untuk menyisipkan header token/auth). |
| `fst.setBefore(cb)` | Hook navigasi. Menerima `(url)`. Jika fungsi ini me-return `false`, navigasi dibatalkan. |
| `fst.setAfter(cb)` | Hook navigasi. Menerima `(url, triggerElement)`. Dipanggil setelah route SPA berhasil diproses. |
| `fst.e(str)` / `fst.escape(str)`| Utility untuk *HTML escape* string (mencegah XSS) sebelum dimasukkan ke dalam DOM (e.g. `innerHTML`). |

---

## 🌶️ 12. Cookbook & Tips Lanjutan

### Global Middleware (CORS / Auth)
Buat file di `globals/` — otomatis dimuat sebelum routing:

```php
// globals/cors.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-FST-Request");

if (fst_method() === 'OPTIONS') {
    fst_status_code(200);
    die();
}

function authenticate_api() {
    $auth = getallheaders()['Authorization'] ?? '';
    if (!str_starts_with($auth, 'Bearer ')) {
        fst_json(['error' => 'Unauthorized'], 401);
    }
    return verify_jwt(substr($auth, 7));
}
```

### Request Interceptor (Frontend)
Sisipkan token atau header kustom ke setiap request SPA:
```javascript
fst.setInterceptor((url, fetchOptions) => {
    const token = localStorage.getItem('api_token');
    if (token) fetchOptions.headers['Authorization'] = `Bearer ${token}`;
    return fetchOptions;
});
```

### Database Migration
Buat file skrip terpisah, jalankan via terminal:
```php
// tools/migrate.php
<?php require 'fullstuck.php';

fst_db('EXEC', "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama TEXT NOT NULL,
    email TEXT UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

echo "Migration done.\n";
```
```bash
php tools/migrate.php
```

### Custom Error Page
Buat file `app/404.fst.php` atau `app/500.fst.php`. Framework otomatis menggunakannya saat terjadi error. File error juga bisa diletakkan per-section (misal `app/admin/404.fst.php`).

### Folder `globals/` — Apa yang Boleh & Tidak
| ✅ Boleh | ❌ Dilarang |
|---|---|
| Helper functions | File view/template |
| Konstanta | Logika routing |
| Koneksi database | File HTML |
| Middleware global | |

### Konvensi Arsitektur: HTML-over-the-wire (HOTW)
FullStuck menggunakan pola *HTML-over-the-wire* (Single Source of Truth). **Dilarang** merakit HTML (seperti *Card* atau *Modal*) dengan *string literal* di JavaScript karena rentan XSS dan menduplikasi kode.

Untuk memuat komponen dinamis, ambil *render* HTML langsung dari server via `fst.go()`:

```javascript
// Ambil komponen dari server, sisipkan ke #wadah
fst.go('/api/komponen/card', { target: '#wadah', history: false });
```

### Menghindari 404 pada SPA (Isomorphic Routing)
Rute SPA yang didaftarkan via `fst.set('/path', ...)` di `client.js` akan menghasilkan **404 Not Found** jika URL diakses langsung (atau *hard reload*). Hal ini terjadi karena direktori tersebut tidak ada di sisi backend (PHP).

**Solusi:** URL publik **wajib** memiliki direktori pendamping di server. Contoh rute `/dashboard/view/123`:

**1. Pendekatan CSR:**
Backend hanya memuat tata letak (*layout*) kosong. Data diambil terpisah oleh `client.js`.

```php
<!-- app/dashboard/view/[id]/content.fst.php -->
@layout('dashboard')
<div id="detail-container">
    <div class="skeleton-loader">Memuat...</div>
</div>
```

**2. Pendekatan SSR (Direkomendasikan):**
Backend merender tampilan utuh beserta data. Halaman langsung tampil seketika (*first paint*), memaksimalkan performa *Hybrid* FullStuck.

```php
<!-- app/dashboard/view/[id]/content.fst.php -->
<?php
$task = fst_db_row('tasks', ['id' => fst_input('id')]);
if (!$task) fst_abort(404, 'Tugas tidak ditemukan');
?>

@layout('dashboard')
<div id="detail-container">
    <h1>{{ $task['title'] }}</h1>
    <p>{{ $task['description'] }}</p>
</div>
```

## ⚛️ 13. Integrasi Eksternal: Monolith SPA (React/Vue/Svelte)
Jika Anda menggunakan framework JavaScript eksternal (React/Vue/Svelte), FullStuck bisa berperan sebagai REST API murni sekaligus menyajikan file statis SPA (*Monolith*), membebaskan Anda dari kendala CORS.

**1. Penyesuaian `fullstuck.json`:**
Matikan FST-Agent dan ekspos direktori *build* frontend Anda (misal: `public`).
```jsonc
{
  "agent_js": false,
  "routing": { "public_folders": ["assets", "public"] }
}
```

**2. Struktur Direktori:**
Buat endpoint API Anda di dalam `app/api/` (menggunakan `action.php`), lalu letakkan hasil *build* statis React/Vue/Svelte di `public/`.

**3. Trik Catch-All Routing:**
Untuk mencegah halaman 404 saat pengguna melakukan *hard reload* pada rute React/Vue/Svelte, manfaatkan *error handler* bawaan melalui file `app/404.fst.php`:
```php
<?php
// app/404.fst.php
// Berikan 404 asli jika request ini ditujukan ke API
if (str_starts_with(fst_uri(), '/api/')) fst_json(['error' => 'Not Found'], 404);

// Selain itu, beri status 200 dan serahkan kepada router React/Vue/Svelte
fst_status_code(200);
echo file_get_contents(FST_ROOT_DIR . '/public/index.html');
die();
```

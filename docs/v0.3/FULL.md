# FullStuck.php (v0.3.0)
### The Zero-Config, Single-File, AI-Friendly PHP Framework

FullStuck.php adalah framework mikro yang dirancang untuk kecepatan pengembangan maksimal tanpa mengorbankan fitur modern. Seluruh core framework berada dalam **satu file tunggal**, membuatnya sangat mudah di-deploy ke shared hosting mana pun tanpa Composer.

---

## 📑 Daftar Isi
1. [🚀 Pengenalan & Quick Start](#quick-start)
2. [📂 Arsitektur & Struktur Folder](#struktur-folder)
3. [🤖 Strict Rules for AI](#ai-rules)
4. [📖 Panduan Inti (Core Concepts)](#core-concepts)
5. [🌻 Templating (Dom Based)](#dom-template)
6. [✨ FST Agent & 3-Level Routing](#spa)
7. [📚 API Reference (Kamus Fungsi)](#api-reference)

---





<a name="quick-start"></a>
## 1. 🚀 Pengenalan & Quick Start

**FullStuck.php**: Framework mikro 1 file core. Tidak ada `vendor/`, tanpa *Composer*.

### 🤖 AI Agent / Vibe Coder Setup (Recommended)
Untuk AI Agent/LLM yang mendampingi Anda di workspace, silakan merujuk pada:
1. **Alur Kerja & SOP AI**: [ai-setup.md](ai-setup.md) (`https://raw.githubusercontent.com/milio48/fullstuck/main/docs/ai-setup.md`)
2. **AI Cheatsheet (API Hemat Token)**: [v0.3.0_cheatsheet.md](v0.3.0_cheatsheet.md) (`https://raw.githubusercontent.com/milio48/fullstuck/main/docs/v0.3.0_cheatsheet.md`)

*Catatan: Saat ini cheatsheet dan dokumentasi utuh dapat dipanggil langsung dari terminal Anda melalui perintah `php fullstuck.php docs` tanpa perlu mengunduh file markdown ke proyek.*

### 💻 Instalasi CLI / Headless
```bash
# Otomatis bypass web installer, setup JSON, dan file starter
php fullstuck.php init --db=sqlite --agent=yes --scaffold=yes --htaccess=yes
```
*Flag: `--db` (`sqlite`/`mysql`/`pgsql`/`none`), `--agent` (`yes`/`no`), `--scaffold` (`yes`/`minimal`/`no`), `--htaccess` (`yes`/`no`).*

Atau jalankan `php -S localhost:8000 fullstuck.php` untuk **GUI Setup Wizard** di browser.

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

**3. FrankenPHP / Caddy**:
```bash
frankenphp php-server -r fullstuck.php
```

---





<a name="struktur-folder"></a>
## 2. 📂 Arsitektur & Struktur Folder

```text
my-project/
├── assets/         # File statis (CSS, JS, Images)
├── fst-plugins/    # Folder plugin (HANYA dikelola via Admin Dashboard)
├── views/          # Template HTML / PHP
├── fullstuck.json  # File konfigurasi utama
├── fullstuck.php   # Framework Core (HARAM dimodifikasi!)
└── router.php      # Definisi rute utama
```

### Konfigurasi `fullstuck.json`
Seluruh pengaturan framework berpusat pada file `fullstuck.json`. File ini wajib ada di root project. Berikut adalah skema standar konfigurasi:

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
        "require": [],
        "public_folders": [
            "assets",
            "uploads"
        ],
        "routes_file": [
            "router.php"
        ]
    },
    "agent_js": true,
    "mime_types": {
        "custom": "application/x-custom"
    }
}
```

> **Tips:** Anda dapat menggunakan sintaks `${NAMA_ENV}` untuk mengambil nilai secara aman dari variabel sistem operasi (Environment Variables) tanpa perlu file `.env`.

### Penjelasan Opsi Konfigurasi:
- **`production`**: Set ke `false` untuk melihat *error trace* secara detail di layar, atau `true` untuk menyembunyikan error sensitif (pesan akan dicatat ke `.fst-error.log`) dan memunculkan custom error 500.
- **`database`**: Mengatur koneksi database. Mendukung banyak koneksi melalui key `connections` dan menentukan koneksi utama via key `default`. Anda dapat menggunakan `${ENV_VAR}` untuk menginterpolasi nilai dari variabel lingkungan.
- **`routing`**: 
  - `require`: Array berisi daftar path untuk me-load file fungsi/model PHP secara otomatis (*auto-include*) sebelum rute dieksekusi. Mendukung nama folder (otomatis me-load semua file `.php`), file spesifik, maupun *wildcard* Glob. (Contoh: `["models", "utils.php", "helpers/api_*.php"]`).
  - `public_folders`: Array direktori yang diizinkan untuk diakses langsung oleh publik (bypass engine framework).
  - `routes_file`: Array file yang berisi definisi rute (seperti `router.php`).
- **`agent_js`**: Mengaktifkan atau menonaktifkan agen JS bawaan. Jika `true`, skrip FST Agent akan otomatis disuntikkan di bagian bawah elemen `<body>`. Agen JS inilah yang memberdayakan Fragment Routing dan navigasi hybrid SPA.
- **`mime_types`**: (Optional) Daftar ekstensi file dan MIME type terkait untuk melakukan *override* atau menambah dukungan tipe file statis baru.

---





<a name="ai-rules"></a>
## 3. 🤖 Strict Rules for AI
**WAJIB DITAATI OLEH AI ASSISTANT:**
1. **Wajib pakai Helper `fst_*`**: Dilarang pakai `$_POST`/`$_GET`/`$_FILES` mentah atau `new PDO()`.
2. **Jangan Sentuh Core**: Dilarang modifikasi `fullstuck.php`.
3. **Proteksi CSRF**: Rute POST/PUT/DELETE **WAJIB** panggil `fst_csrf_check()`.
4. **Validasi**: Gunakan hanya fungsi `fst_validate()`.

---











<a name="core-concepts"></a>
## 4. 📖 Panduan Inti (Core Concepts)

### A. Routing & Middleware

**Basic Routing:**
```php
fst_get('/halo', function() { echo "Halo Dunia!"; });
fst_get('/user/{id:i}', function($id) { echo "ID: " . $id; });
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

### B. Database & Query Builder
Gunakan key `connection` untuk pindah database sesuai `fullstuck.json`.

> **Batasan Query Builder:** `fst_db_select()`, `fst_db_update()`, dll hanya mendukung kondisi `AND` dengan operator `=`. Untuk query lebih kompleks (seperti `OR`, `LIKE`, `>`, `IN`), Anda wajib menggunakan **Raw Query** (`fst_db()`).

**Select:**
```php
$users = fst_db_select('users', ['status' => 'active'], ['order_by' => 'id DESC']);
$pelanggan = fst_db_select('pelanggan', [], ['connection' => 'mysql_db']);
$admin = fst_db_select('users', ['role' => 'admin'], ['mode' => 'ROW']); // 1 baris
```

**Insert, Update, Delete:**
```php
fst_db_insert('users', ['nama' => 'Budi', 'email' => 'budi@a.com']);
fst_db_update('users', ['status' => 'inactive'], ['id' => 5], ['connection' => 'mysql_db']);
fst_db_delete('users', ['id' => 5]);
```

**Raw Query (JOIN, Kompleks, atau Operator non-sama dengan seperti OR, LIKE, IN, >, <):**
Gunakan `fst_db()` untuk query kompleks dengan parameter *binding* yang kebal SQL Injection.

```php
// 1. JOIN Multi-tabel & Ambil Banyak Baris (ALL) -> Return: Array of Arrays
$posts = fst_db('ALL', "SELECT p.*, u.nama FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = ?", ['published']);

// 2. Query Pencarian menggunakan OR & LIKE
$search = '%sepatu%';
$products = fst_db('ALL', "SELECT * FROM products WHERE (name LIKE ? OR description LIKE ?) AND status = ?", [$search, $search, 'active']);

// 3. Query dengan Operator IN (Menggunakan array parameter binding)
$categories = [1, 2, 5];
$placeholders = implode(',', array_fill(0, count($categories), '?')); // Menghasilkan: ?,?,?
$items = fst_db('ALL', "SELECT * FROM items WHERE category_id IN ($placeholders)", $categories);

// 4. Ambil 1 baris saja (ROW) -> Return: Array Asosiatif (Misal: ['id' => 1, 'nama' => 'Budi'])
$user = fst_db('ROW', "SELECT id, nama FROM users WHERE email = ? LIMIT 1", ['budi@a.com']);

// 5. Ambil 1 nilai tunggal (SCALAR) -> Return: Primitive Value (Misal: Int 42 atau String "Budi")
$total = fst_db('SCALAR', "SELECT COUNT(*) FROM users WHERE status = ?", ['active']);

// 6. Eksekusi Query Tanpa Return Value (EXEC)
fst_db('EXEC', "UPDATE users SET last_login = NOW() WHERE id = ?", [1]);
```

**Database Transactions:**
Untuk memastikan integritas data (terutama jika melakukan banyak aksi Insert/Update sekaligus), gunakan fungsi helper transaksi:
```php
try {
    fst_db_begin();
    
    fst_db_insert('users', ['nama' => 'A']);
    fst_db_update('saldo', ['jumlah' => 0], ['id' => 1]);
    
    fst_db_commit(); // Simpan permanen jika semua sukses
} catch (Exception $e) {
    fst_db_rollback(); // Batalkan semua aksi di atas jika ada error
}
```


### C. Request & Validasi

> **⚠️ CSRF pada Form HTML:** Nama field CSRF **wajib** `_token`. Cara menyisipkan:
> - **File `.php`**: `<?= fst_csrf_field() ?>`
> - **File `.html` (tanpa `fst_template`)**: Tambahkan manual `<input type="hidden" name="_token" value="...">`.
> - **File `.html` (dengan `fst_template`)**: Gunakan DSL `@append` pada rules:
>   ```php
>   "form" => ["@append" => 'fst_csrf_field()']
>   ```

```php
fst_post('/register', function() {
    fst_csrf_check(); // Wajib untuk POST/PUT/DELETE

    $email = fst_input('email'); // Input tunggal
    
    // Validasi massal (rules: required, email, numeric, in:a,b, min:X, max:X, min_value:X, max_value:X)
    // Catatan: Validasi kompleks seperti 'unique' atau 'regex' harus dilakukan manual di controller.
    $val = fst_validate(fst_request(), [
        'nama'  => 'required|min:3',
        'email' => 'required|email',
        'umur'  => 'required|min_value:18|max_value:60'
    ]);

    if (!$val['valid']) {
        fst_flash_set('error', 'Error: ' . implode(', ', array_merge(...array_values($val['errors']))));
        fst_redirect('/register');
    }
});
```

> **💡 Mengapa menggunakan Flash Session? (PRG Pattern & SPA Integration)**
> Saat Anda memproses form via POST (seperti *Create*, *Update*, *Delete*), *best-practice* adalah me-redirect user kembali (GET) agar saat browser di-refresh, form tidak tersubmit ulang (dikenal sebagai *Post/Redirect/Get*). Opsi SPA FullStuck juga memanfaatkan pola ini dengan secara otomatis memotong respon HTML statis dan memuatnya ke DOM.
> 
> **Contoh Integrasi Validasi Form dengan HTML Statis (menggunakan `@if` rule):**
> 
> 1. **File HTML (`views/register.html`):**
> ```html
> <form action="/register" method="POST">
>     <!-- Elemen alert ini disembunyikan secara default, tapi akan muncul jika ada error -->
>     <div class="alert alert-danger">
>         <span class="error-msg">Pesan Error di sini</span>
>     </div>
>     <input type="text" name="nama" placeholder="Nama Anda">
>     <button type="submit">Daftar</button>
> </form>
> ```
> 
> 2. **File Rute (`router.php`):**
> ```php
> fst_get('/register', function() {
>     $error = fst_flash_get('error'); // Dapatkan pesan flash error (jika ada)
> 
>     $data = [
>         'error' => $error
>     ];
> 
>     $rules = [
>         // Menampilkan kontainer alert jika variabel $error tidak kosong
>         "div.alert-danger" => [
>             "@if" => '$error',
>             "span.error-msg" => '$error'
>         ],
>         // Selalu sertakan CSRF Token
>         "form" => [
>             "@append" => 'fst_csrf_field()'
>         ]
>     ];
> 
>     fst_template('views/register.html', $data, $rules);
> });
> ```

### D. Views & Templates

**Render View:**
```php
// Di router.php
fst_view('profil.php', ['nama' => 'Budi', 'umur' => 25]);

// Di views/profil.php
<p>Nama: <?= e($nama) ?></p> <!-- e() untuk cegah XSS -->
```

**Layouting (Nested Views):**
```php
// Di router.php
fst_view('layout.php', ['view_path' => 'konten.php', 'view_data' => ['judul' => 'Halo']]);

// Di views/layout.php
<main><?php fst_view($view_path, $view_data); ?></main>
```

**Global / Shared View Data:**
```php
// Variabel tersedia di seluruh file view
fst_view_share('site_name', 'FullStuck CMS');
fst_view_share(['user_role' => 'admin', 'theme' => 'dark']);
```

> **Note untuk `fst_template`**: Variabel dari `fst_view_share` **otomatis** di-inject ke dalam procedural templating. Anda tidak perlu meneruskannya secara manual, framework akan menggabungkannya ke dalam argumen `$data` Anda secara otomatis.

### E. File Upload

**Single / Multiple Upload:**
```php
fst_post('/upload', function() {
    fst_csrf_check();

    // Auto-detect single file / array (multiple) input
    $result = fst_upload('foto', 'assets/uploads', [
        'max_size' => 2048, 
        'allowed_types' => ['jpg', 'png'],
        'allowed_mimes' => ['image/jpeg', 'image/png']
    ]);

    // Jika input form name="foto[]" (multiple), $result berupa array of array
    // Jika single name="foto", $result adalah single array
    if (!empty($result['success']) || !empty($result[0]['success'])) {
        echo "Berhasil upload!";
    }
});
```

---





<a name="dom-template"></a>
## 5. 🚀 Procedural DOM Templating (`fst_template`)
File HTML dijaga 100% statis tanpa tag PHP (`<?= ?>` / `{{ }}`). Logic injeksi diurus via array deklaratif di PHP. **Zero XSS by Default**.

**1. File HTML (`views/blog.html`)**
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Judul Placeholder</title>
</head>
<body>
    <div id="blog-container">
        <!-- Item di bawah ini akan bertindak sebagai template iterasi -->
<article class="post-item">
            <h2>Judul Sementara</h2>
            <p>Ringkasan Sementara</p>
            <a href="#" title="placeholder">Baca selengkapnya</a>
</article>
        
        <!-- Item dummy di bawah ini otomatis akan di-hapus oleh @foreach -->
        <article class="post-item">
            <h2>Judul 2</h2>
            <p>Ringkasan 2</p>
            <a href="#">Baca</a>
        </article>
    </div>
</body>
</html>
```

**2. File PHP (`router.php`)**
```php
<?php
// 1. Siapkan Data
$data = [
    'pageTitle' => 'Eksperimen DOM Templating Deklaratif',
    'blogs' => [
        [
            'title' => 'Vibe Coding', 
            'summary' => 'Sangat menyenangkan...',
            'url' => '/blog/vibe-coding'
        ]
    ]
];

// 2. Tentukan Aturan (Ruleset/DSL)
$rules = [
    "title" => '$pageTitle',
    "article.post-item" => [
        "@foreach" => '$blogs as $blog',
        "h2" => '$blog["title"]',
        "p"  => '$blog["summary"]',
        "a"  => [
            "[href]"  => '$blog["url"]',
            "[title]" => '$blog["title"]'
        ]
    ]
];

// 3. Render
// - Parameter 4 (Opsional): Direktori output cache (default: akan menggunakan folder cache bawaan framework / temporary).
// - Parameter 5 (Opsional): Set `true` untuk memaksa recompile mengabaikan cache (berguna saat Hot-Reloading/Development).
fst_template(FST_ROOT_DIR . '/views/blog-list.html', $data, $rules, FST_ROOT_DIR . '/view-cache', fst_is_dev());
```

### 📐 Ruleset Syntax (DSL) — Referensi Lengkap

```php
$rules = [
    // ==========================================
    // 1. MANIPULASI TEKS & HTML (Mass Execution)
    // Berlaku untuk SEMUA elemen yang cocok (querySelectorAll)
    // ==========================================

    // Shorthand Text (Aman dari XSS)
    "title" => '$pageTitle', 
    // JS Murni: document.querySelectorAll("title").forEach(el => el.innerText = pageTitle);

    // Explicit Text (Dipakai jika ada manipulasi atribut di blok yang sama)
    "h3" => ["@text" => '$subJudul'],
    // JS Murni: document.querySelectorAll("h3").forEach(el => el.innerText = subJudul);

    // Raw HTML (Bypass XSS - Khusus untuk output WYSIWYG)
    "span.content" => ["@html" => '$htmlContent'],
    // JS Murni: document.querySelectorAll("span.content").forEach(el => el.innerHTML = htmlContent);

    // Menyisipkan HTML di akhir elemen (Append)
    "head" => ["@append" => '"<style>body { background: red; }</style>"'],
    // JS Murni: document.querySelectorAll("head").forEach(el => el.insertAdjacentHTML("beforeend", "..."));

    // Menyisipkan HTML di awal elemen (Prepend)
    "div.container" => ["@prepend" => '"<div class=\"alert\">Warning</div>"'],
    // JS Murni: document.querySelectorAll("div.container").forEach(el => el.insertAdjacentHTML("afterbegin", "..."));
    
    // ⚠️ PERINGATAN XSS: Karena @append dan @prepend merender Raw HTML (seperti @html), 
    // jika Anda menyisipkan data dari user di dalam struktur HTML tersebut, WAJIB di-escape!
    // Contoh: "div.comment" => ["@append" => '"<p>" . fst_escape($userComment) . "</p>"']


    // ==========================================
    // 2. MANIPULASI ATRIBUT & CSS ATTRIBUTE SELECTOR
    // ==========================================

    // Mengganti/Menambah atribut (Teks di dalam elemen tetap utuh)
    "a.external" => ["[href]" => '$linkUrl', "[target]" => '"_blank"'],
    // JS Murni: el.setAttribute("href", linkUrl); el.setAttribute("target", "_blank");

    // CSS Attribute Selector murni (dapat digunakan sebagai top-level selector atau jika mengandung operator '=')
    "[data-fst=\"my-form\"]" => [
        "[action]" => '"/submit"',
        "[method]" => '"post"',
        "h2"       => '"Form Login"'
    ],

    // Mengubah isi teks PADA elemen yang memiliki atribut spesifik
    "a[data-type='link']" => '"Teks Link Baru"',
    // JS Murni: document.querySelectorAll("a[data-type='link']").forEach(el => el.innerText = "...");


    // ==========================================
    // 3. TARGETING TUNGGAL (Single Node Selection)
    // Gunakan prefix `^` untuk menghentikan pencarian di elemen pertama
    // ==========================================
    
    "^div.alert" => '"Ini alert pertama saja"',
    // JS Murni: document.querySelector("div.alert").innerText = "Ini alert pertama saja";


    // ==========================================
    // 4. COMPILE-TIME CLEANUP (Pembersihan DOM sebelum masuk Cache)
    // Menggunakan string mutlak "@remove"
    // ==========================================

    // Memusnahkan elemen secara utuh beserta anak-anaknya
    "div.debug-panel" => "@remove",
    // JS Murni: document.querySelectorAll("div.debug-panel").forEach(el => el.remove());
    // CATATAN: "@remove" dieksekusi pada saat compile-time (sebelum di-cache). Elemen akan musnah secara permanen di HTML, bukan disembunyikan dinamis secara run-time.

    // Membuang atribut sampah/dummy dari FE
    "img.thumbnail" => [
        "[style]" => "@remove",       // Hapus inline style
        "[data-dummy]" => "@remove",  // Hapus atribut dummy
        "[src]" => '$realImageUrl'    // Set src asli
    ],


    // ==========================================
    // 5. RUN-TIME LOGIC (Kondisional & Iterasi di eksekusi PHP)
    // ==========================================

    // IF Murni (Menyembunyikan/Menampilkan Elemen)
    "div.banner-promo" => [
        "@if" => '$isPromoActive'
    ],

    // IF & ELSE (Gunakan Inverse Logic ! pada class berbeda)
    "a.btn-dashboard" => ["@if" => '$isLoggedIn'],
    "a.btn-login" => ["@if" => '!$isLoggedIn'],

    // TERNARY (Logika sebaris untuk elemen yang sama)
    "button.btn-auth" => [
        "@text"   => '$isLoggedIn ? "Logout" : "Login"',
        "[href]"  => '$isLoggedIn ? "/logout" : "/login"',
        "[class]" => '$isLoggedIn ? "btn-danger" : "btn-primary"'
    ],

    // FOREACH (Looping Data Array)
    // Otomatis mengambil node pertama sebagai cetakan,
    // dan menghapus node duplikat (dummy) lainnya.
    "ul.nav > li" => [
        "@foreach" => '$menus as $menu',
        "a" => [
            "[href]" => '$menu["url"]',
            "@text"  => '$menu["label"]'
        ]
    ]
];
```

> **💡 Tips Debugging & Cara Kerja Evaluasi Variabel:**
> Saat Anda menuliskan aturan seperti `"span.price" => '"Rp " . number_format($p["price"], 0, ",", ".")'`, ruleset tersebut disimpan sebagai **string ekspresi PHP** yang akan dievaluasi pada saat *runtime* di dalam file PHP terkompilasi.
> - **Kunci Penting**: String yang dideklarasikan harus dievaluasi menjadi ekspresi PHP yang valid. Selalu gunakan petik tunggal di luar (`'...'`) dan petik ganda di dalam (`"..."`) untuk melestarikan literal teks PHP, atau sebaliknya.
> - **Kesalahan Umum**: Menulis `"span.price" => "Rp " . number_format($p["price"])` (tanpa petik tunggal pembungkus) akan membuat fungsi `number_format()` langsung dieksekusi di file `router.php` sebelum framework sempat melakukan kompilasi cache. Hal ini akan memicu error `Undefined variable $p` jika data tersebut belum di-fetch saat mendefinisikan ruleset.
> - **Lokasi Cache**: Jika terjadi *ParseError*, Anda selalu bisa meneliti file cache PHP hasil kompilasi yang ada di dalam folder cache Anda (misal: `view-cache/*.html.php`) untuk melihat langsung bagaimana kode PHP Anda digabungkan dan dieksekusi.
> - **Mekanisme Auto-Invalidation Cache**: Cache template akan dibangun ulang secara otomatis apabila file HTML asli dimodifikasi *atau* jika ruleset DSL yang Anda definisikan di `router.php` berubah. Framework melacak perubahan aturan ini dengan menyisipkan sidik jari (hash MD5) ruleset pada bagian atas file cache.
> 
> **⚠️ WARNING: Efek Samping Overlapping Selektor CSS (Side-Effect querySelectorAll):**
> Karena engine `fst_template()` memanggil `querySelectorAll` untuk menerapkan ruleset secara massal, selektor class yang terlalu umum (seperti `span.text-gold` atau `p.text-sm`) akan secara tidak sengaja menimpa elemen global layout (seperti logo header, link menu samping, atau teks footer) jika elemen-elemen tersebut kebetulan memiliki tag/class yang sama.
> - **Solusi**: Biasakan selalu membidik dengan selektor CSS yang sangat spesifik, misalnya diawali dengan ID pembungkus konten dinamis Anda: `#product-content span.text-gold` atau `.post-item p.text-sm`. Jangan gunakan selektor tag tunggal global seperti `span` atau `h2` kecuali jika Anda berniat mengubah semuanya.

> **💡 Ide Eksplorasi fst_template:**
> *   **SEO Dinamis**: Ubah atribut `[content]` pada tag `meta[property='og:image']` sebelum dirender.
> *   **State Hydration**: Injeksi *output* `json_encode()` ke dalam `<script type="application/json">` menggunakan perintah `@html`.
> *   **Cegah FOUC Dark Mode**: Sisipkan atribut `[class]` secara kondisional langsung membidik elemen *root* `^html`.

---





<a name="spa"></a>
## 6. ✨ FST Agent & 3-Level Routing
Mulai dari v0.3.0, FullStuck tidak hanya beroperasi sebagai SPA otomatis, namun juga memperkenalkan konsep **3-Level Routing**. Konsep ini memungkinkan pengembang membangun aplikasi hybrid secara mulus tanpa framework Frontend yang berat.

### Konsep 3-Level Routing
1. **Level 1: Server-Side Routing (PHP)**
   Ini adalah layer terkuat. Didefinisikan di `router.php` via fungsi PHP seperti `fst_get()`, `fst_post()`, dll. Seluruh interaksi database, pengamanan (Middleware & CSRF), dan autentikasi dilakukan di sini.
2. **Level 2: Fragment Routing (HTML Deklaratif)**
   Ketika aplikasi berjalan di browser dan agen JS hidup, tag HTML yang memiliki atribut tertentu dapat mencegat navigasi (link & form) untuk *hanya mengambil sebagian* halaman dari server (Fragment Loading). Fitur ini membuat aplikasi terasa seketika (instant).
3. **Level 3: Client-Side Routing (Javascript Murni)**
   Rute di level ini tidak akan menyentuh server PHP sama sekali! Didefinisikan via JS Object `fst.set()` di Frontend. Berguna untuk State-Management UI interaktif, seperti Popups, tab, atau Editor Canvas lokal yang tidak memerlukan validasi backend.

---

### Level 2: Atribut HTML Deklaratif (Fragment Routing)
*   **`data-fst-fragment="#id_elemen"`**
    Alih-alih merender ulang seluruh halaman, Anda bisa meng-update sebagian kecil saja. Sangat berguna saat menyimpan Sidebar persisten, dan hanya me-*replace* tag `<main>`.
    ```html
    <!-- Hanya mengganti DOM yang berada di dalam tag ber-ID #konten -->
    <a href="/tab-profil" data-fst-fragment="#konten">Profil</a>
    
    <!-- Hasil form pencarian langsung disuntikkan ke class .hasil -->
    <form action="/cari" method="POST" data-fst-fragment=".hasil">...</form>
    ```

*   **`data-fst-indicator="class-animasi"`**
    Class CSS yang akan disuntikkan khusus selama masa tunggu *fetch API*.
    ```html
    <form action="/upload" method="POST" data-fst-indicator="sedang-upload">...</form>
    ```

*   **`data-fst-no-history`**
    Mencegah SPA mencatat state URL ke dalam history browser (*pushState* dinonaktifkan). **Sangat penting** bagi Form `POST` Delete/Update kecil agar tombol Back Browser tidak terjebak mengulangi aksi kotor (meminimalisir re-submit polusi HTTP).
    ```html
    <a href="/tab-2" data-fst-fragment="#isi-tab" data-fst-no-history>Buka Tab 2</a>
    ```

*   **`data-fst-normal-load` (atau class `no-spa`)**
    Mem-bypass agen FST, memaksa peramban untuk *full reload*.
    ```html
    <a href="/logout" data-fst-normal-load>Keluar Normal</a>
    ```

*   **`data-fst-no-scroll`**
    Mencegah halaman memantul ke atas ketika fetch selesai. Fitur ini cocok untuk halaman Pagination "Load More" agar user fokus ke bawah.

*   **`data-fst-ignore`**
    Ditaruh di dalam `<script>`, menandakan script ini hanya di-eksekusi 1 kali seumur hidup, sangat berguna bagi SDK Analytic yang tidak boleh meledak saat SPA routing bertransisi berulang-ulang.

---

### Level 3: Client-Side Routing (JS Murni)
Definisikan ini di file JS Frontend Anda untuk mencegat lalu-lintas seketika tanpa koneksi.

```javascript
// Rute Javascript Murni (Tidak hit server PHP)
fst.set('/editor', (params) => {
    document.querySelector('#app').innerHTML = `<h1>Canvas Editor</h1>`;
});

// Rute Dinamis dengan Regex Extractor
fst.set('/user/:id', (params) => {
    console.log("ID User: " + params.id);
});

// Grouping Rute Client-side
fst.group('/dashboard', () => {
    fst.set('/settings', () => { /* Logic rute statis dashboard/settings */ });
});

// 🚀 Pemanggilan Navigasi Programmatik via JS:
fst.go('/sebagian', { target: '#widget', history: false, scroll: 'smooth' });
```

---

### Hook Lifecycle Event JS
FST Agent melemparkan event HTML setiap *fetching* dari server (Level 2).
```javascript
// Sebelum Fetch
document.addEventListener('fst:loading', (e) => { 
    // e.detail memiliki { url, targetSelector, triggerElement }
    if (isDirty) e.preventDefault(); // Batalkan SPA jika form belum disimpan
});

// DOM lama dihilangkan
document.addEventListener('fst:unload', () => { /* Destroy JS Plugin */ });

// HTML Baru selesai dirender
document.addEventListener('fst:load', () => { /* Re-Init Select2/Maps dll */ });
```

---





<a name="api-reference"></a>
## 7. 📚 API Reference (Cheat Sheet)

### Routing & HTTP/Request
*   `fst_get|post|put|patch|delete|any($path, $callback, $middleware)`: Mendefinisikan rute HTTP. **Return**: `void`.
*   `fst_group($prefix, $callback, $middleware)`: Mengelompokkan rute. **Return**: `void`.
*   `fst_view($path, $data)`: Merender template. **Return**: `void` (Langsung output).
*   `fst_template($path, $data, $rules, $cacheDir?, $forceRebuild?)`: Merender template HTML secara dinamis. **Return**: `void` (Langsung output).
*   `fst_view_share($key, $value)`: Membagikan variabel ke seluruh view secara global. **Return**: `void`.
*   `fst_partial($path, $data)`: Alias `fst_view` untuk komponen kecil. **Return**: `void` (Langsung output).
*   `fst_json($data, $status)`: Kirim response JSON. **Return**: `void` (Otomatis exit).
*   `fst_text($string, $status)`: Kirim response Teks. **Return**: `void` (Otomatis exit).
*   `fst_redirect($url, $code = 302, $allow_external = false)`: Redirect. (Otomatis mengirim `X-FST-Redirect` di mode SPA). **Return**: `void` (Otomatis exit).
*   `fst_abort($code, $message)`: Hentikan dengan error code HTTP (misal: 404, 500). **Return**: `void` (Otomatis exit).
*   `fst_serve_static_file($path)`: Menyajikan file aset dengan cache headers. (Digunakan internal, tapi bisa dipanggil manual untuk custom file server). **Return**: `bool`.
*   `fst_extract_html_fragment($html, $selector)`: (*Internal*) Filter output HTML untuk SPA. **Return**: `string`.
*   `fst_uri()`: Ambil path URI saat ini. **Return**: `string`.
*   `fst_method()`: Ambil HTTP Method aktif. **Return**: `string`.
*   `fst_status_code($code)`: Set header response code. **Return**: `void`.
*   `fst_input($key, $default)`: Ambil nilai input tunggal (GET/POST/JSON). **Return**: `mixed`.
*   `fst_request()`: Ambil seluruh array request input. **Return**: `array`.
*   `fst_file($key)`: Ambil detail upload file dari `$_FILES`. **Return**: `array|null`.
*   `fst_upload($key, $folder, $options)`: Proses upload aman.
    **Return**: `['success' => bool, 'path' => string|null, 'error' => string|null, 'original_name' => string|null]` (Jika multi-upload, mereturn array of results).
*   `fst_is_fragment_request()`: Mengembalikan *true* jika dipanggil via agen SPA. **Return**: `bool`.
*   `fst_fragment_target()`: Mengambil ID/Class target DOM dari agen SPA. **Return**: `string|null`.
*   `fst_run()`: Menjalankan engine routing framework (biasanya dipanggil otomatis).

### Database
*   `fst_db($mode, $sql, $params, $connection)`: Raw Query PDO manual. Mode: `'ROW'`, `'ALL'`, `'SCALAR'` (atau `'ONE'`), dan `'EXEC'`.
    **Return**: `array` (ALL/ROW/EXEC), atau `mixed` (SCALAR). Untuk EXEC, mengembalikan metadata baris terdampak & id terakhir.
*   `fst_db_select($table, $cond, $opts)`: Mengambil data dari tabel. Opsi didukung: `select`, `limit`, `offset`, `order_by`, `mode` ('ALL'/'ROW'), `connection`.
    **Return**: `array` (List array atau baris tunggal).
*   `fst_db_row($table, $cond, $opts)`: Mengambil 1 baris data saja.
    **Return**: `array|null` (Associative array atau null jika tidak ada).
*   `fst_db_exists($table, $cond, $opts)`: Cek keberadaan data.
    **Return**: `bool`.
*   `fst_db_insert($table, $data, $opts)`: Menambahkan baris data.
    **Return**: `string|int|bool` (Last Insert ID atau status boolean).
*   `fst_db_update($table, $data, $cond, $opts)`: Mengubah data.
    **Return**: `int` (Jumlah baris yang terpengaruh).
*   `fst_db_delete($table, $cond, $opts)`: Menghapus data.
    **Return**: `int` (Jumlah baris yang dihapus).
*   `fst_db_begin($connection = null)`: Memulai transaksi database (Transaction). **Return**: `bool`.
*   `fst_db_commit($connection = null)`: Menyimpan hasil transaksi secara permanen. **Return**: `bool`.
*   `fst_db_rollback($connection = null)`: Membatalkan seluruh perubahan dalam transaksi aktif. **Return**: `bool`.
*   `fst_db_quote_ident($ident, $connection)`: Keamanan penamaan tabel/kolom lintas-DB (contoh: ubah string dinamis "users" jadi "`users`"). **Return**: `string`.

### Security, Validation, & Session
*   `e($str)` atau `fst_escape($str)`: Anti-XSS (HTML Escape). Wajib saat *echo* variabel ke layar. **Return**: `string`.
*   `fst_csrf_field()`: Generate elemen `<input hidden>` token CSRF. Nama field: **`_token`**. Pada `fst_template`, gunakan: `"form" => ["@append" => 'fst_csrf_field()']`. **Return**: `string` (HTML).
*   `fst_csrf_token()`: Ambil string murni dari token CSRF aktif. **Return**: `string`.
*   `fst_csrf_check()`: Validasi CSRF wajib di awal *router callback* POST/PUT/DELETE.
*   `fst_validate($data, $rules)`: Engine validasi *array* input. Mendukung `required`, `email`, `numeric`, `in:a,b`, `min:X`, `max:X`, `min_value:X`, `max_value:X`.
    * **Struktur Return `fst_validate`:**
        Fungsi ini mengembalikan *array associative*:
        * `['valid']` (boolean): `true` jika semua aturan lolos.
        * `['errors']` (array): Kumpulan pesan error yang dikelompokkan per-field (misal: `$val['errors']['email'][0]`).
        * `['data']` (array): Data input yang sudah di-*trim* dan di-*sanitize*.
*   `fst_session_set|get|forget($key, $val)`: Membaca dan menulis *session*. **Return**: `mixed` (untuk `get`).
*   `fst_flash_set|get($key, $val)`: Session pesan *flash* (sekali baca langsung hilang). **Return**: `mixed` (untuk `get`).
*   `fst_flash_has($key)`: Cek keberadaan pesan *flash*. **Return**: `bool`.
*   `fst_is_safe_to_debug()`: Cek visibilitas detail *error trace* ke layar pengguna. **Return**: `bool`.
*   `fst_config($key, $default)`: Baca dari `fullstuck.json`. **Return**: `mixed`.
*   `fst_is_dev()`: Apakah mode *development* sedang aktif? **Return**: `bool`.
*   `fst_app($key, $value)`: Akses ke memori internal (kontainer state) framework. Gunakan untuk menyimpan cache state yang konsisten selama lifecycle request. **Return**: `mixed`.
*   `fst_dump(...$vars)`: *Debug variable* cantik. **Return**: `void`.
*   `fst_dd(...$vars)`: *Debug variable* cantik lalu *die*. **Return**: `void` (Otomatis exit).
*   `fst_register_plugin($id, $config)`: Mendaftarkan plugin ke dalam framework. **Return**: `void`.

---





# FullStuck.php (v0.1.0)
### The Zero-Config, Single-File, AI-Friendly PHP Framework

FullStuck.php adalah framework mikro yang dirancang untuk kecepatan pengembangan maksimal tanpa mengorbankan fitur modern. Seluruh core framework berada dalam **satu file tunggal**, membuatnya sangat mudah di-deploy ke shared hosting mana pun tanpa Composer.

---

## 📑 Daftar Isi
1. [🚀 Pengenalan & Quick Start](#quick-start)
2. [📂 Arsitektur & Struktur Folder](#struktur-folder)
3. [📖 Panduan Inti (Core Concepts)](#core-concepts)
4. [🎛️ Admin Dashboard (Built-in)](#admin-dashboard)
5. [✨ Keajaiban SPA (Single Page Application)](#spa)
6. [🛠️ Studi Kasus: Membuat Mini CMS](#mini-cms)
7. [📚 API Reference (Kamus Fungsi)](#api-reference)
8. [🤖 Strict Rules for AI](#ai-rules)

---

<a name="quick-start"></a>
## 1. 🚀 Pengenalan & Quick Start

### Apa itu FullStuck?
FullStuck.php dibangun dengan filosofi **1 file core**. Cukup _drop_ file `fullstuck.php` di hosting Anda, dan Anda sudah memiliki framework berkinerja tinggi yang mendukung routing statis, database PDO, templating view, dan Zero-Config SPA. Tidak ada `vendor/`, tidak perlu *composer install*.

### 🤖 AI Agent / Vibe Coder Setup (Recommended)
Jika Anda menggunakan *AI coding assistant* (seperti Cursor, Windsurf, atau Cline), cukup salin dan tempel *prompt* di bawah ini ke dalam chat agent Anda untuk *setup* instan:

> "Unduh `https://raw.githubusercontent.com/milio48/fullstuck/main/fullstuck.php` lalu jalankan `php fullstuck.php init --db=sqlite --admin-pass=admin --scaffold=yes`. Setelah selesai, langsung pelajari file dokumentasi `.md` yang baru terunduh agar kamu paham aturan mainnya. Terakhir, jalankan `php -S localhost:8000 fullstuck.php` lalu laporkan ringkasan setup (versi, jenis DB, password admin, dan URL lokal). Tanyakan apakah ada konfigurasi yang ingin saya ubah sebelum kita mulai membuat aplikasi!"

**Mengapa menggunakan cara ini?**
* **Instant Start:** Agent akan mengunduh, mengonfigurasi database, dan membuat file starter secara otomatis.
* **Konteks Penuh:** Agent membaca dokumentasi ini secara mandiri, mencegah halusinasi penggunaan *Composer* atau fungsi PHP *native*.
* **Feedback Loop:** Agent akan memberikan laporan status instalasi sebelum menawarkan diri untuk mulai menulis kode aplikasi Anda.

### Instalasi & Setup Wizard

1. **Download:** Unduh file `fullstuck.php` dan letakkan di root folder proyek Anda.
2. **Jalankan Server:** Gunakan PHP Built-in Server untuk mode *development*:
   ```bash
   php -S localhost:8000 fullstuck.php
   ```
3. **Setup Wizard:** Buka `http://localhost:8000` di browser Anda. Framework akan otomatis mendeteksi instalasi baru dan menampilkan **GUI Setup Wizard**.
   * Framework akan membantu membuat `fullstuck.json`.
   * Anda dapat men-generate file *Starter Project* (`router.php`, `views/`).
   * Tersedia opsi untuk men-download file panduan ini (`v0.1.0.md`) untuk referensi asisten AI Anda.

### 💻 Headless CLI Initialization (Untuk Advance Developer)
Jika Anda tidak menyukai instalasi GUI atau ingin melakukan otomatisasi (misalnya di CI/CD atau Docker), FullStuck menyediakan fitur inisialisasi via CLI.

Buka terminal dan jalankan:
```bash
php fullstuck.php init --db=sqlite --admin-pass=stuck --admin-url=/stuck --spa=yes --scaffold=yes
```
Perintah ini akan langsung mem-bypass web installer, men-generate `fullstuck.json`, mengunduh dokumentasi `v0.1.0.md`, dan membuat file *starter* jika `--scaffold=yes` disertakan. Jika `fullstuck.json` sudah ada, perintah ini otomatis ditolak.

**Parameter Opsional CLI:**

| Flag | Deskripsi | Default |
|---|---|---|
| `--db` | Driver database (`sqlite`, `mysql`, `pgsql`, atau `none`) | `sqlite` |
| `--admin-pass` | Kata sandi untuk Dashboard Admin | `admin` |
| `--admin-url` | URL akses Dashboard Admin | `/stuck` |
| `--spa` | Mengaktifkan mode SPA (`yes` atau `no`) | `yes` |
| `--scaffold` | Generate file starter (`yes` atau `no`) | `yes` |

### 🔧 Web Server Deployment

FullStuck bekerja dengan merutekan semua request ke file `fullstuck.php`. Jika Anda melihat URL Anda mengandung `/fullstuck.php/`, berarti web server Anda belum terkonfigurasi dengan benar.

**1. Apache / LiteSpeed**
Pastikan modul `mod_rewrite` aktif di server Anda. Buat file `.htaccess` di *root* folder (sejajar dengan `fullstuck.php`) dengan isi persis seperti ini:

```apache
# 1. Nonaktifkan fitur "Index of" dan "MultiViews"
Options -Indexes -MultiViews

# Blokir akses ke file hidden (dotfiles)
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    
    # 2. Aturan "Rakus" (Kirim SEMUA ke fullstuck.php)
    RewriteRule ^(.*)$ fullstuck.php [L]
</IfModule>
```

**2. Nginx**
Tambahkan konfigurasi *location* berikut pada file server block (vhost) Anda:
```nginx
location / {
    try_files $uri $uri/ /fullstuck.php?$query_string;
}
```

**3. FrankenPHP / Caddy**
Untuk menjalankan framework menggunakan eksekutor single-binary modern seperti FrankenPHP, Anda bisa menggunakan perintah sederhana:

```bash
frankenphp php-server -r fullstuck.php
```

---

<a name="struktur-folder"></a>
## 2. 📂 Arsitektur & Struktur Folder

Berikut adalah struktur folder standar yang disarankan untuk aplikasi FullStuck:

```text
my-project/
├── assets/         # File statis (CSS, JS, Images)
├── fst-plugins/    # Folder otomatis untuk plugin FullStuck
├── views/          # Template HTML / PHP
├── fullstuck.json  # File konfigurasi utama
├── fullstuck.php   # Framework Core
└── router.php      # Definisi rute utama
```

> **🚨 PERINGATAN PENTING!**
> 
> **✅ Boleh disentuh / ditambah:**
> File `router.php`, folder `views/`, folder `assets/`, atau folder tambahan buatan Anda sendiri seperti `controllers/` atau `models/`.
> 
> **❌ HARAM disentuh / dimodifikasi manual:**
> * `fullstuck.php` (Core Framework)
> * folder `fst-plugins/`
> 
> **Aturan Plugin:** Plugin **HANYA BOLEH** diinstal dan dikelola melalui **Admin Dashboard** (`/stuck`). Developer (dan asisten AI) dilarang keras membuat, mengubah, atau menghapus file PHP secara manual di dalam direktori `fst-plugins/`.

### Konfigurasi `fullstuck.json`
Seluruh pengaturan framework berpusat pada file `fullstuck.json`. File ini wajib ada di root project. Berikut adalah skema standar konfigurasi:

```json
{
    "environment": "development",
    "admin": {
        "page_url": "/stuck",
        "password": "$2y$12$...",
        "allowed_ips": []
    },
    "database": {
        "default": "main",
        "connections": {
            "main": {
                "driver": "sqlite",
                "database_path": "database.sqlite"
            },
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
        "require": [],
        "public_folders": [
            "assets",
            "uploads",
            "storage/public"
        ],
        "routes_file": [
            "router.php"
        ],
        "error_handlers": {
            "404": "views/errors/404.php",
            "403": "Sorry, you do not have permission.",
            "405": "Method not allowed.",
            "500": "views/errors/500.php"
        },
        "regex_shortcuts": {
            "i": "([0-9]+)",
            "a": "([a-zA-Z0-9]+)",
            "s": "([a-zA-Z0-9\\-]+)",
            "any": "([^/]+)"
        }
    },
    "spa": {
        "enabled": true,
        "default_target": "body",
        "header_request": "X-FST-Request",
        "header_target": "X-FST-Target",
        "indicator_class": "fst-loading"
    },
    "mime_types": {
        "custom": "application/x-custom"
    }
}
```

> **Tips:** Anda dapat menggunakan sintaks `${NAMA_ENV}` untuk mengambil nilai secara aman dari variabel sistem operasi (Environment Variables) tanpa perlu file `.env`.

### Penjelasan Opsi Konfigurasi:
- **`environment`**: Set ke `"development"` untuk melihat *error trace* secara detail di layar, atau `"production"` untuk menyembunyikan error sensitif (pesan akan dicatat ke `.fst-error.log`) dan memunculkan custom error 500.
- **`admin`**: Mengatur URL akses halaman *dashboard admin*, menyimpan sandi dalam bentuk *hash bcrypt*, serta filter **`allowed_ips`** untuk *IP Whitelisting* (kosongkan untuk mengizinkan semua IP).
- **`database`**: Mengatur koneksi database. Mendukung banyak koneksi melalui key `connections` dan menentukan koneksi utama via key `default`. Anda dapat menggunakan `${ENV_VAR}` untuk menginterpolasi nilai dari variabel lingkungan.
- **`routing`**: 
  - `require`: Array berisi daftar path untuk me-load file fungsi/model PHP secara otomatis (*auto-include*) sebelum rute dieksekusi. Mendukung nama folder (otomatis me-load semua file `.php`), file spesifik, maupun *wildcard* Glob. (Contoh: `["models", "utils.php", "helpers/api_*.php"]`).
  - `public_folders`: Array direktori yang diizinkan untuk diakses langsung oleh publik (bypass engine framework).
  - `routes_file`: Array file yang berisi definisi rute (seperti `router.php`).
  - `error_handlers`: Anda dapat me-render file template `.php`, merespons dengan teks polos, atau HTML mentah untuk kode status HTTP spesifik.
  - `regex_shortcuts`: (Advanced) Alias untuk pola regex yang sering digunakan dalam pendefinisian rute (misal: `:i` untuk integer).
- **`spa`**: Mengontrol opsi tingkat lanjut mode Single Page Application (`"enabled"` mendukung `true`, `false`, atau `"manual"`), termasuk HTTP header *custom* untuk interaksi Ajax, *class indicator* saat proses `fetch` berjalan, dan target spesifik dari fragment SPA.
- **`mime_types`**: (Optional) Daftar ekstensi file dan MIME type terkait untuk melakukan *override* atau menambah dukungan tipe file statis baru.

---

<a name="core-concepts"></a>
## 3. 📖 Panduan Inti (Core Concepts)

Bagian ini berisi panduan untuk fitur-fitur yang paling sering Anda butuhkan sehari-hari.

### A. Routing & Middleware
Mendefinisikan rute dan menangani *request*. FullStuck menggunakan Static Routing untuk kecepatan tinggi.

**Contoh Rute GET dan POST:**
```php
// Rute sederhana
fst_get('/halo', function() {
    echo "Halo Dunia!";
});

// Menangkap parameter dari URL (contoh: /user/42)
fst_get('/user/{id:i}', function($id) {
    echo "ID User: " . $id;
});
```

**Membuat Filter Login (Middleware):**
Anda bisa melindungi sekumpulan rute dengan `fst_group` dan *middleware*.
```php
// 1. Definisikan fungsi middleware (Onion Model)
function cek_login($next) {
    if (!fst_session_get('user_id')) {
        fst_flash_set('error', 'Silakan login terlebih dahulu.');
        fst_redirect('/login');
        return false; // Hentikan eksekusi
    }
    
    // Wajib panggil $next() jika lolos validasi!
    return $next();
}

// 2. Terapkan pada grup rute
fst_group('/admin', function() {
    fst_get('/dashboard', function() {
        echo "Selamat datang, Admin!";
    });
}, 'cek_login'); // panggil nama fungsinya di sini
```

### B. Database & Query Builder
FullStuck mendukung banyak koneksi database sekaligus. Anda dapat menentukan koneksi default di `fullstuck.json` dan memanggil koneksi lain saat dibutuhkan.

**Select Data:**
```php
// Menggunakan koneksi default
$users = fst_db_select('users', ['status' => 'active'], ['order_by' => 'id DESC']);

// Menggunakan koneksi database spesifik (contoh: mysql_db)
$pelanggan = fst_db_select('pelanggan', [], ['connection' => 'mysql_db']);

// Ambil 1 baris saja
$admin = fst_db_select('users', ['role' => 'admin'], ['mode' => 'ROW']);
```

**Insert, Update, Delete:**
```php
// Insert ke koneksi default
fst_db_insert('users', [
    'nama' => 'Budi',
    'email' => 'budi@contoh.com'
]);

// Update pada koneksi spesifik
fst_db_update('users', ['status' => 'inactive'], ['id' => 5], ['connection' => 'mysql_db']);

// Delete
fst_db_delete('users', ['id' => 5]);
```

### C. Request & Validasi
Bagaimana cara menangkap input dari user dan memvalidasinya secara aman?

```php
fst_post('/register', function() {
    fst_csrf_check(); // Wajib untuk rute POST!

    // Tangkap input tunggal
    $email = fst_input('email');
    
    // Atau validasi langsung seluruh request
    $val = fst_validate(fst_request(), [
        'nama'  => 'required|min:3',
        'email' => 'required|email',
        'umur'  => 'required|min_value:18|max_value:60'
    ]);

    // Aturan validasi yang didukung:
    // required, email, numeric, min:X (panjang minimal string), 
    // max:X (panjang maksimal string), min_value:X (angka minimal), 
    // max_value:X (angka maksimal)

    if (!$val['valid']) {
        // Tampilkan error ke user
        fst_flash_set('error', 'Cek kembali form Anda: ' . implode(', ', $val['errors']));
        fst_redirect('/register');
    }

    // Jika lolos, simpan ke database...
});
```

### D. Views & Templates
Pisahkan logika PHP Anda dengan tampilan HTML.

**Di `router.php`:**
```php
fst_get('/profil', function() {
    $data = ['nama' => 'Budi', 'umur' => 25];
    fst_view('profil.php', $data); // melempar $data ke view
});
```

**Di `views/profil.php`:**
```php
<h1>Profil User</h1>
<!-- Gunakan e() untuk mencegah celah XSS! -->
<p>Nama: <?= e($nama) ?></p> 
<p>Umur: <?= e($umur) ?></p>

**Pola Layout Standar (Nested Views):**
Untuk menghindari pemanggilan `require` manual atau `ob_start`, gunakan pola pemanggilan `fst_view` di dalam file view (Layouting).

*File: views/layout.php*
```html
<html>
    <body>
        <header>Menu</header>
        <main><?php fst_view($view_path, $view_data); ?></main>
    </body>
</html>
```

```php
fst_get('/halaman', function() {
    fst_view('layout.php', [
        'view_path' => 'halaman_konten.php', 
        'view_data' => ['judul' => 'Halo']
    ]);
});
```

**Global / Shared View Data:**
Jika Anda memiliki data yang perlu diakses oleh banyak *view* sekaligus (misalnya data *user* yang sedang *login* atau pengaturan situs), gunakan `fst_view_share()` agar Anda tidak perlu mengirimnya berulang kali di setiap rute.

*Contoh di router atau middleware:*
```php
// Simpan data ke memori global
fst_view_share('site_name', 'FullStuck CMS');
fst_view_share(['user_role' => 'admin', 'theme' => 'dark']);

```

*Di dalam file view mana pun, variabel `$site_name`, `$user_role`, dan `$theme` akan otomatis tersedia!*
```

### E. Penanganan File Upload
Mengunggah file foto atau dokumen menjadi sangat mudah dengan validasi otomatis.

```php
fst_post('/upload-foto', function() {
    fst_csrf_check();

    // Upload file dari input name="foto" ke folder "assets/uploads"
    $result = fst_upload('foto', 'assets/uploads', [
        'max_size' => 2048, // maksimal 2 MB
        'allowed_types' => ['jpg', 'png', 'jpeg'],
        'allowed_mimes' => ['image/jpeg', 'image/png'] // Validasi MIME yang ketat
    ]);

    if ($result['success']) {
        echo "Berhasil! File tersimpan di: " . $result['path'];
    } else {
        echo "Gagal: " . $result['error'];
    }
});
```

**Dukungan Multi-Upload Otomatis:**
Fungsi `fst_upload()` sangat cerdas. Jika formulir HTML Anda menggunakan input *array* untuk mengunggah banyak file sekaligus (`<input type="file" name="dokumen[]" multiple>`), Anda cukup memanggil fungsinya satu kali:

```php
// Tetap panggil dengan nama 'dokumen' (tanpa [])
$hasil = fst_upload('dokumen', 'uploads/docs');

// $hasil akan otomatis berupa Array of Objects!
foreach ($hasil as $file) {
    if ($file['success']) {
        echo "Tersimpan di: " . $file['path'];
    }
}

```

*Jika yang diunggah hanya 1 file (tanpa atribut multiple), `$hasil` akan langsung mengembalikan satu objek tunggal.*

---

<a name="admin-dashboard"></a>
## 4. 🎛️ Admin Dashboard (Built-in)

FullStuck dilengkapi dengan *Control Panel* bawaan yang ditanamkan langsung di dalam *core* untuk memudahkan pemeliharaan aplikasi Anda.

### Cara Mengakses
Secara *default*, Anda dapat mengakses dashboard ini melalui URL:
**`http://localhost:8000/stuck`**

Kata sandi *default* Anda tentukan saat menjalankan instalasi *Wizard* atau perintah CLI `init` (misalnya: `admin`). Anda dapat mengubah URL dan kata sandi ini kapan saja melalui file `fullstuck.json`.

### Fitur Unggulan Panel Admin:
1. **System Monitor**: Mengecek status koneksi database, kompatibilitas ekstensi PHP (*mbstring*, *fileinfo*, dll), dan memberikan peringatan keamanan jika Anda masih di lingkungan `development`.
2. **Config Editor**: Web-based JSON Editor untuk mengubah isi `fullstuck.json` langsung dari browser, dilengkapi dengan *Generator Hash Password*.
3. **Route Viewer**: Melihat daftar seluruh rute HTTP yang aktif beserta pola Regex-nya. Sangat berguna untuk *debugging* rute yang bertabrakan.
4. **Project Scanner**: Memindai seluruh file `.php` di direktori proyek Anda untuk melacak di mana saja fungsi `fst_*` digunakan.
5. **File Integrity Monitor (FIM)**: Memastikan file *core* `fullstuck.php` Anda asli dan tidak disisipi kode berbahaya dengan membandingkan *hash* lokal terhadap *registry* resmi GitHub.
6. **Plugin Manager**: Menginstal, menonaktifkan, atau menghapus *plugin* secara instan langsung dari *GitHub Store*.
7. **1-Click System Update**: Memperbarui *core framework* FullStuck ke versi rilis terbaru di GitHub hanya dengan satu klik (file lama otomatis di-*backup*).

> **🛡️ Keamanan Mode Production:**
> Saat aplikasi Anda *live*, sangat disarankan untuk mengubah URL *default* `/stuck` menjadi sesuatu yang rahasia. Untuk keamanan absolut, gunakan fitur **`allowed_ips`** di pengaturan `admin` pada file `fullstuck.json` agar panel ini hanya bisa dibuka oleh IP address Anda sendiri.

---

<a name="spa"></a>
## 5. ✨ Keajaiban SPA (Single Page Application)

Aplikasi FullStuck beroperasi sebagai **SPA secara otomatis**. Semua klik link `<a>` dan pengiriman `<form>` diproses di latar belakang tanpa *full page reload*. Ini membuat aplikasi terasa super cepat layaknya aplikasi mobile.

### Atribut Ajaib SPA
Tambahkan atribut HTML ini untuk mengontrol bagaimana SPA bekerja:

*   **`data-fst-target="#id_elemen"`**
    Berguna untuk *fragment rendering*. Alih-alih merender ulang seluruh halaman, Anda bisa meng-update sebagian kecil saja (misal: isi tabel atau isi tab).
    ```html
    <!-- Klik ini hanya akan mengubah isi dari <div id="konten"> -->
    <a href="/tab-profil" data-fst-target="#konten">Profil</a>
    
    <!-- Submit form ini hasilnya hanya me-replace <div class="hasil"> -->
    <form action="/cari" method="POST" data-fst-target=".hasil">...</form>
    ```

*   **`data-fst-history="false"`**
    Secara default, SPA akan mengubah URL di *address bar*. Jika Anda hanya membuat fitur kecil (seperti tab atau modal) dan tidak ingin URL-nya berubah, gunakan ini:
    ```html
    <a href="/tab-2" data-fst-target="#isi-tab" data-fst-history="false">Buka Tab 2</a>
    ```

*   **`data-no-spa` (atau class `no-spa`)**
    Bypass fitur SPA dan memaksakan *reload* penuh secara normal. Berguna untuk form unduh file atau aksi *logout*.
    ```html
    <a href="/logout" data-no-spa>Keluar</a>
    ```

*   **`data-spa-ignore`**
    Gunakan atribut ini pada elemen `<script>` jika Anda tidak ingin script tersebut dieksekusi ulang setiap kali navigasi SPA terjadi. Sangat berguna untuk script analitik atau inisialisasi SDK global.

### Menangani Plugin JS Pihak Ketiga (Lifecycle Event)
Jika Anda menggunakan Select2, Chart.js, atau jQuery, terkadang plugin tersebut rusak setelah navigasi SPA. Anda harus mematikan dan menghidupkan ulang plugin di *event* ini:
```javascript
document.addEventListener('fst:unload', function() {
    // Bersihkan sebelum pindah halaman (cegah memory leak)
    $('.my-select').select2('destroy'); 
});

document.addEventListener('fst:load', function() {
    // Inisialisasi ulang setelah halaman dimuat via SPA
    $('.my-select').select2();
});
```

---

<a name="mini-cms"></a>
## 6. 🛠️ Studi Kasus: Membuat Mini CMS

Berikut adalah kode CRUD utuh yang menggabungkan semua konsep di atas ke dalam satu *file* cerdas (menggabungkan Routing, View, Database, Validasi, dan SPA).

**Di dalam `router.php`:**
```php
<?php
fst_group('/admin/artikel', function() {
    
    // Tampilkan List & Form (Menggunakan Fragment Rendering SPA)
    fst_get('/', function() {
        $artikel = fst_db_select('artikel', [], ['order_by' => 'id DESC']);
        fst_view('admin/artikel_index.php', ['artikel' => $artikel]);
    });

    // Handle Form Submit
    fst_post('/simpan', function() {
        fst_csrf_check(); // WAJIB untuk POST

        $val = fst_validate(fst_request(), [
            'judul' => 'required|min:5|max:100',
            'konten' => 'required'
        ]);

        if (!$val['valid']) {
            fst_flash_set('error', 'Gagal: Cek isian form Anda!');
        } else {
            fst_db_insert('artikel', [
                'judul' => fst_input('judul'),
                'konten' => fst_input('konten')
            ]);
            fst_flash_set('success', 'Artikel berhasil disimpan.');
        }

        fst_redirect('/admin/artikel');
    });

    // Handle Delete
    fst_post('/hapus/{id:i}', function($id) {
        fst_csrf_check();
        fst_db_delete('artikel', ['id' => $id]);
        fst_flash_set('success', 'Artikel dihapus.');
        fst_redirect('/admin/artikel');
    });

});
```

**Di dalam `views/admin/artikel_index.php`:**
```html
<!-- Area ini yang akan selalu di-update oleh SPA -->
<div id="area-artikel">
    <p><?= fst_flash_get('success') ?></p>
    <p><?= fst_flash_get('error') ?></p>
    
    <!-- Form Tambah Data -->
    <form action="/admin/artikel/simpan" method="POST" data-fst-target="#area-artikel">
        <?= fst_csrf_field() ?>
        <input type="text" name="judul" required placeholder="Judul">
        <textarea name="konten" required placeholder="Isi..."></textarea>
        <button type="submit">Simpan</button>
    </form>

    <hr>

    <!-- Daftar Artikel -->
    <ul>
        <?php foreach ($artikel as $row): ?>
            <li>
                <?= e($row['judul']) ?> 
                <!-- Form Hapus (Inline) -->
                <form action="/admin/artikel/hapus/<?= $row['id'] ?>" method="POST" data-fst-target="#area-artikel" style="display:inline;">
                    <?= fst_csrf_field() ?>
                    <button type="submit">Hapus</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
```

---

<a name="api-reference"></a>
## 7. 📚 API Reference (Kamus Fungsi)

Contekan cepat fungsi core FullStuck (*Cheat Sheet*):

### Routing & HTTP/Request
*   `fst_get|post|put|patch|delete|any($path, $callback, $middleware)`: Mendefinisikan rute HTTP.
*   `fst_group($prefix, $callback, $middleware)`: Mengelompokkan rute.
*   `fst_view($path, $data)`: Merender template.
*   `fst_view_share($key, $value)`: Membagikan variabel ke seluruh view secara global.
*   `fst_partial($path, $data)`: Alias `fst_view` untuk komponen kecil.
*   `fst_json($data, $status)`: Kirim response JSON.
*   `fst_text($string, $status)`: Kirim response Teks.
*   `fst_redirect($url, $code = 302, $allow_external = false)`: Redirect dengan proteksi *open redirect* eksternal.
*   `fst_abort($code, $message)`: Hentikan dengan error code HTTP (misal: 404, 500).
*   `fst_serve_static_file($path)`: Menyajikan file aset dengan cache headers.
*   `fst_extract_html_fragment($html, $selector)`: (*Internal*) Filter output HTML untuk SPA.
*   `fst_uri()`: Ambil path URI saat ini.
*   `fst_method()`: Ambil HTTP Method aktif.
*   `fst_status_code($code)`: Set header response code.
*   `fst_input($key, $default)`: Ambil nilai input tunggal (GET/POST/JSON).
*   `fst_request()`: Ambil seluruh array request input.
*   `fst_file($key)`: Ambil detail upload file dari `$_FILES`.
*   `fst_upload($key, $folder, $options)`: Proses upload dengan validasi (`max_size`, `allowed_types`, `allowed_mimes`).
*   `fst_is_spa()`: Mengembalikan *true* jika dipanggil via agen SPA.
*   `fst_spa_target()`: Mengambil ID/Class target DOM dari agen SPA.
*   `fst_spa_page()`: Mengaktifkan injeksi SPA secara manual untuk halaman yang sedang diproses.
*   `fst_run()`: Menjalankan engine routing framework (biasanya dipanggil otomatis).

### Database
*   `fst_db($mode, $sql, $params, $connection)`: Raw Query PDO manual. Mode yang didukung: `'ROW'`, `'ALL'`, dan `'SCALAR'` (atau alias `'ONE'` untuk mengambil nilai kolom tunggal).
*   `fst_db_select($table, $cond, $opts)`: Mengambil data dari tabel. Gunakan `$opts['connection']` untuk pindah DB.
*   `fst_db_row($table, $cond, $opts)`: Mengambil 1 baris data saja.
*   `fst_db_exists($table, $cond, $opts)`: Cek keberadaan data (boolean).
*   `fst_db_insert($table, $data, $opts)`: Menambahkan baris data. Gunakan `$opts['connection']` untuk pindah DB.
*   `fst_db_update($table, $data, $cond, $opts)`: Mengubah data. Gunakan `$opts['connection']` untuk pindah DB.
*   `fst_db_delete($table, $cond, $opts)`: Menghapus data. Gunakan `$opts['connection']` untuk pindah DB.
*   `fst_db_quote_ident($ident, $connection)`: Keamanan penamaan tabel/kolom lintas-DB.

### Security, Validation, & Session
*   `e($str)` atau `fst_escape($str)`: Anti-XSS (HTML Escape). Wajib saat *echo* variabel ke layar.
*   `fst_csrf_field()`: Generate elemen `<input hidden>` token CSRF.
*   `fst_csrf_token()`: Ambil string murni dari token CSRF aktif.
*   `fst_csrf_check()`: Validasi CSRF wajib di awal *router callback* POST/PUT/DELETE.
*   `fst_validate($data, $rules)`: Engine validasi *array* input. Mendukung `required`, `email`, `numeric`, `in:a,b`, `min:X`, `max:X`, `min_value:X`, `max_value:X`.
    * **Struktur Return `fst_validate`:**
        Fungsi ini mengembalikan *array associative*:
        * `['valid']` (boolean): `true` jika semua aturan lolos.
        * `['errors']` (array): Kumpulan pesan error yang dikelompokkan per-field (misal: `$val['errors']['email'][0]`).
        * `['data']` (array): Data input yang sudah di-*trim* dan di-*sanitize*.
*   `fst_session_set|get|forget($key, $val)`: Membaca dan menulis *session*.
*   `fst_flash_set|get($key, $val)`: Session pesan *flash* (sekali baca langsung hilang).
*   `fst_flash_has($key)`: Cek keberadaan pesan *flash*.
*   `fst_is_safe_to_debug()`: Cek visibilitas detail *error trace* ke layar pengguna.
*   `fst_config($key, $default)`: Baca dari `fullstuck.json`.
*   `fst_is_dev()`: Apakah mode *development* sedang aktif?
*   `fst_app($key, $value)`: Akses ke memori internal (kontainer state) framework. Gunakan untuk menyimpan cache state yang konsisten selama lifecycle request.
*   `fst_dump(...$vars)`: *Debug variable* cantik.
*   `fst_dd(...$vars)`: *Debug variable* cantik lalu *die*.
*   `fst_register_plugin($id, $config)`: Mendaftarkan plugin ke dalam framework.

---

<a name="ai-rules"></a>
## 8. 🤖 Strict Rules for AI

**PERHATIAN MUTLAK BAGI ASISTEN AI (ChatGPT/Claude/Gemini dll)**

Bila pengguna memberikan Anda referensi ini untuk memandu pemrograman, Anda **WAJIB** menaati pantangan berikut:

1.  **Helper `fst_*` adalah Harga Mati**: Dilarang keras menggunakan superglobal secara mentah (`$_POST`, `$_GET`, `$_FILES`) atau instansiasi kelas `PDO` secara natif. Selalu gunakan helper yang tersedia (`fst_input()`, `fst_request()`, `fst_db_select()`, dll).
2.  **Jangan Merusak Core**: **DILARANG** menyarankan modifikasi manual apapun ke file induk `fullstuck.php`.
3.  **Folder Plugin Tabu**: Jangan menyuruh manusia membuat, mengubah, atau menghapus skrip secara manual di direktori `fst-plugins/`. Semua harus diarahkan ke antarmuka Web Admin Dashboard (`/stuck`).
4.  **Proteksi CSRF Wajib**: Rute `fst_post()`, `fst_put()`, `fst_delete()` **HARUS** diamankan di baris pertama blok eksekusinya menggunakan `fst_csrf_check()`, lengkap dengan penempatan `fst_csrf_field()` pada form HTML di view.
5.  **Validasi Lewat Fungsi**: Gunakan hanya fungsi terpadu `fst_validate()` untuk mengevaluasi parameter masukan dari pengguna dan balas dengan respons yang pantas (Flash Message Error atau JSON HTTP 400).



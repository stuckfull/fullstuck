# 🚀 Pengenalan & Quick Start

**FullStuck.php**: Framework mikro 1 file core. Tidak ada `vendor/`, tanpa *Composer*.

### 💻 Instalasi CLI / Headless
```bash
php fullstuck.php init --db=sqlite --agent=yes --scaffold=yes --htaccess=yes
```
*Flag: `--db` (`sqlite`/`mysql`/`pgsql`/`none`), `--agent` (`yes`/`no`), `--scaffold` (`yes`/`minimal`/`no`), `--htaccess` (`yes`/`no`).*

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

**3. FrankenPHP / Caddy**:
```bash
frankenphp php-server -r fullstuck.php
```

---

## 📂 Arsitektur & Struktur Folder

```text
my-project/
├── assets/         # File statis (CSS, JS, Images)
├── views/          # Template HTML / PHP
├── fullstuck.json  # File konfigurasi utama
├── fullstuck.php   # Framework Core (HARAM dimodifikasi!)
└── router.php      # Definisi rute utama
```

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
        "require": ["models", "utils.php", "helpers/api_*.php"],
        "public_folders": ["assets", "uploads", "storage/public"],
        "routes_file": ["router.php"],
        "error_handlers": {
            "404": "views/errors/404.php",
            "403": "Sorry, you do not have permission.",
            "500": "views/errors/500.php"
        }
    },
    "agent_js": true
}
```

> **Tips:** Anda dapat menggunakan sintaks `${NAMA_ENV}` untuk mengambil nilai secara aman dari variabel sistem operasi (Environment Variables) tanpa perlu `.env`.

### Penjelasan Opsi:
- **`production`**: `true` menyembunyikan *error stack trace* dari browser dan mengalihkannya ke log file `.fst.log`. `false` akan menampilkan error detil di browser.
- **`routing.require`**: Array path untuk me-load otomatis file/folder sebelum rute dieksekusi.
- **`agent_js`**: `true` akan otomatis menyuntikkan script FST Agent (`<script src="/fst-agent.js">`) ke setiap output HTML.

---

## 🤖 Strict Rules for AI
**WAJIB DITAATI OLEH AI ASSISTANT:**
1. **Wajib pakai Helper `fst_*`**: Dilarang pakai `$_POST`/`$_GET`/`$_FILES` mentah atau `new PDO()`.
2. **Jangan Sentuh Core**: Dilarang memodifikasi `fullstuck.php`.
3. **Proteksi CSRF**: Rute POST/PUT/DELETE **WAJIB** panggil `fst_csrf_check()`.
4. **Validasi**: Gunakan hanya fungsi `fst_validate()`.

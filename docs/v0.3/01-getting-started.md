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

*Untuk referensi konfigurasi dan penjelasan lebih lengkap, silakan lihat [FULL.md](./FULL.md).*

---

## 🤖 Strict Rules for AI
**WAJIB DITAATI OLEH AI ASSISTANT:**
1. **Wajib pakai Helper `fst_*`**: Dilarang pakai `$_POST`/`$_GET`/`$_FILES` mentah atau `new PDO()`.
2. **Jangan Sentuh Core**: Dilarang memodifikasi `fullstuck.php`.
3. **Proteksi CSRF**: Rute POST/PUT/DELETE **WAJIB** panggil `fst_csrf_check()`.
4. **Validasi**: Gunakan hanya fungsi `fst_validate()`.
5. **Pecah Kode**: Dilarang menumpuk semua logika di `router.php`. Pecahkan model, middleware, dan rute per modul ke file/folder terpisah. Update `fullstuck.json` (`require` & `routes_file`).

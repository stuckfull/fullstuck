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

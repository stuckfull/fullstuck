<div align="center">
  <img src="docs/public/logo.svg" alt="FullStuck.php Logo" width="200" height="120">
  <h1>FullStuck.php</h1>
  <p><b>Next.js App Router experience, HTMX simplicity, Blade syntax — all in a single PHP file.</b></p>

  ![Version](https://img.shields.io/badge/version-v0.4.0-6366f1?style=flat-square)
  ![PHP](https://img.shields.io/badge/php-%3E%3D_8.2-8892BF?style=flat-square)
  ![License](https://img.shields.io/badge/license-MIT-10b981?style=flat-square)
  ![Architecture](https://img.shields.io/badge/architecture-single--file-orange?style=flat-square)
  [![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/stuckfull/fullstuck)
</div>

---

**FullStuck.php** adalah inovasi *micro-framework* yang mendisrupsi cara Anda membangun aplikasi PHP. Kami membuang `vendor/`, `composer.json`, dan kerumitan konfigurasi. Semuanya digantikan oleh **satu file tunggal (`fullstuck.php`)** berukuran kurang dari 100KB yang sangat bertenaga.

Di **v0.4.0**, FullStuck menghadirkan era baru pengembangan web:
- **Routing sekelas Next.js:** Gunakan *Path-Based Colocation*. Tidak ada lagi `router.php`. URL Anda adalah cerminan langsung dari struktur folder `app/`.
- **SPA secepat HTMX:** Dengan *FST-Agent* bawaan, aplikasi Anda otomatis menjadi Single Page Application (SPA) *HTML-over-the-wire* tanpa perlu menulis kode JavaScript yang rumit.
- **Templating senyaman Blade:** Tulis view Anda menggunakan file `.fst.php` dengan sintaks intuitif `{{ $var }}`, `@foreach`, dan `@component`.

🌐 **[Kunjungi Landing Page & Dokumentasi Resmi](https://fullstuck.biz.id/)**

---

## 🤖 AI Agent / Vibe Coder Setup (Recommended)

FullStuck dirancang 100% *AI-Native*. Framework ini memastikan AI Assistant Anda (Cursor, Windsurf, Cline) tidak pernah kehilangan konteks *(Zero-Grep Architecture)*.

Cukup berikan *prompt* ini ke AI Anda:

```text
Install fullstuck.php. Panduan: https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/ai-setup.md
```

---

## 🚀 Quick Start (Manual)

1.  **Download Engine**: Unduh file `fullstuck.php` ke folder kosong.
2.  **Initialize**: Jalankan perintah *scaffold* awal:
    ```bash
    php fullstuck.php init
    ```
3.  **Run Server**: Jalankan PHP Built-in Server:
    ```bash
    php -S localhost:8000 fullstuck.php
    ```
4.  **Explore**: Buka `http://localhost:8000` di browser Anda!

---

## 📁 Struktur Folder (Colocation)

Cara kerja FullStuck sangat sederhana. Struktur folder Anda = URL Anda:

```text
app/
├── content.fst.php           → GET /
├── _layout.fst.php           → Layout global untuk semua rute
├── _guard.php                → Middleware global (opsional)
├── blog/
│   ├── content.fst.php       → GET /blog
│   └── [slug]/
│       ├── content.fst.php   → GET /blog/{slug}
│       └── action.php        → POST|PUT|DELETE /blog/{slug} (REST API)
└── api/
    └── products/
        └── action.php        → Endpoint murni JSON /api/products
```

- **`content.fst.php`**: File template (UI).
- **`action.php`**: File logika (Controller/API).
- **`_layout.fst.php`**: Wrapper layout.
- **`_guard.php`**: Security middleware pelindung rute.

Semua file yang relevan berada di folder yang sama. AI Anda tidak perlu lagi melompat-lompat antar file (Zero-Grep)!

---

## ✨ Fitur Unggulan v0.4.0

*   **Zero-Grep Architecture**: Logika (`action.php`) dan View (`content.fst.php`) selalu berdampingan.
*   **FST-Agent (SPA Engine)**: Navigasi perpindahan halaman tanpa *full-page reload* secara otomatis.
*   **Matryoshka Security**: Letakkan `_guard.php` di dalam sebuah folder, dan seluruh rute di bawah folder tersebut (dan sub-foldernya) akan terlindungi secara otomatis.
*   **DB Bebas Hambatan**: *Query builder* fungsional bawaan yang mendukung SQLite, MySQL, dan PostgreSQL.
*   **Anti-OOP (Procedural First)**: Sepenuhnya digerakkan oleh fungsi (`fst_*`). Kode lebih mudah dibaca, dilacak, dan di-*debug* tanpa hirarki *class* yang berbelit-belit.
*   **Monolith SPA Ready**: Berfungsi mulus untuk melayani file statis hasil *build* React/Vue/Svelte sekaligus bertindak sebagai backend API bebas CORS.

---

## 📚 Dokumentasi CLI

Dokumentasi FullStuck telah tertanam dan dapat diakses langsung dari terminal. Sangat disukai oleh AI!

```bash
php fullstuck.php docs          # Lihat daftar isi
php fullstuck.php docs:2        # Baca bab 2 (Routing)
php fullstuck.php docs:11       # Baca API Reference
php fullstuck.php docs:full     # Tampilkan seluruh dokumentasi
```

Anda juga bisa membacanya secara online:
- 📖 **[Dokumentasi Online v0.4.0](https://fullstuck.biz.id/v0.4/)**

---

## 🛠️ Pengembangan (Internal FST)

Jika Anda ingin memodifikasi *core engine* FullStuck:
1. Edit file-file terpisah di dalam folder `src/`.
2. Kompilasi menjadi satu file dengan menjalankan:
   ```bash
   php src/compiler-fullstuck.php
   ```

---

<div align="center">
  <sub>Dibuat dengan ❤️ untuk ekosistem PHP yang lebih lincah dan menyenangkan.</sub>
</div>

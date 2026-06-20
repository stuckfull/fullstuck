<div align="center">
  <h1>🚀 FullStuck.php</h1>
  <p><b>The Zero-Config, AI-Friendly, Single-File PHP Framework</b></p>

  [![Version](https://img.shields.io/badge/version-0.2.0-green.svg)](docs/v0.2.0.md)
  [![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.0-8892BF.svg)](https://php.net/)
  [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
  [![AI-Friendly](https://img.shields.io/badge/AI--Agent-Ready-orange.svg)](#-ai-agent--vibe-coder-setup)
</div>

---

**FullStuck.php** adalah *micro-framework* yang mengembalikan kesederhanaan PHP. Seluruh *core engine* berada dalam **satu file tunggal**, memberikan Anda *routing* statis, *database* PDO (SQLite/PostgreSQL/MySQL), templating, dan keajaiban **Zero-Config SPA** tanpa butuh folder `vendor/` atau instalasi *Composer*.

🌐 **[Kunjungi Landing Page Resmi](https://milio48.github.io/fullstuck/)**

## 🤔 Mengapa FullStuck?

Aplikasi Anda berjalan dengan kecepatan tinggi layaknya Single Page Application (SPA) hanya dengan PHP murni:

```php
// router.php
fst_get('/halo/{nama}', function($nama) {
    fst_template('halo.html', ['nama' => $nama], [
        'h1' => ['@text' => 'Halo ' . $nama]
    ]);
});

fst_post('/simpan', function() {
    fst_csrf_check(); // Keamanan bawaan
    fst_db_insert('users', ['nama' => fst_input('nama')]);
    fst_redirect('/halo/sukses');
});
```

## ✨ Fitur Unggulan v0.2.0

*   **📦 Single File Distribution**: Cukup satu file `fullstuck.php` untuk menjalankan seluruh aplikasi.
*   **🎨 Procedural DOM Templating**: Engine templating `fst_template()` memisahkan murni HTML dan PHP. Tidak perlu `<br>` atau `echo` lagi di dalam HTML!
*   **⚡ Zero-Config SPA**: Navigasi instan dengan *Fragment Rendering* dan *History Caching* tanpa menulis JavaScript. Cukup tambahkan atribut ajaib seperti `data-fst-target` atau `data-fst-indicator`!
*   **🤖 AI-Native Design**: Struktur kode yang sangat ramah untuk asisten AI (Cursor, Windsurf, Cline) untuk memahami konteks proyek secara utuh.
*   **🎛️ Built-in Admin Dashboard**: Panel administrasi modern (`/stuck`) untuk manajemen konfigurasi, *Integrity Monitor*, dan *Plugin Marketplace*.
*   **🔌 Plugin Ecosystem**: Perluas fitur framework dengan sistem plugin *one-click install* langsung dari dashboard.
*   **🔒 Hardened Security**: Proteksi CSRF, XPath injection protection, Path Traversal validation, dan *Session Fixation protection* bawaan.
*   **🐘 Database Flexible**: Dukungan penuh untuk SQLite, MySQL, dan sekarang **PostgreSQL** dengan *connection pooling*.

---

## 🤖 AI Agent / Vibe Coder Setup (Recommended)

Jika Anda menggunakan *AI coding assistant*, cukup salin *prompt* di bawah ini untuk memulai proyek secara instan:

> "Unduh `https://raw.githubusercontent.com/milio48/fullstuck/main/fullstuck.php` lalu jalankan `php fullstuck.php init --db=sqlite --admin-pass=admin --scaffold=yes --spa=yes`. Setelah selesai, langsung pelajari file dokumentasi di `docs/v0.2.0.md` agar kamu paham aturan mainnya. Terakhir, jalankan `php -S localhost:8000 fullstuck.php` lalu laporkan ringkasan setup."

---

## 🚀 Quick Start (Manual)

1.  **Download**: Unduh file `fullstuck.php` ke folder kosong Anda.
2.  **Initialize**: Jalankan perintah inisialisasi otomatis:
    ```bash
    php fullstuck.php init --admin-pass=rahasia --scaffold=yes
    ```
3.  **Run**: Jalankan server bawaan PHP:
    ```bash
    php -S localhost:8000 fullstuck.php
    ```
4.  **Explore**:
    *   🌐 Aplikasi: `http://localhost:8000`
    *   🛠️ Admin Dashboard: `http://localhost:8000/stuck`

---

## 📚 Dokumentasi

Dokumentasi FullStuck bersifat **versioned single-file** agar memudahkan pencarian dan memberikan konteks penuh bagi AI:

- 📖 **[Dokumentasi v0.2.0 (Terbaru)](docs/v0.2.0.md)**
- 📖 **[Dokumentasi v0.1.0 (Legacy)](docs/v0.1.0.md)**
- 📋 **[Riwayat Perubahan (Changelog)](CHANGELOG.md)**
- 🏗️ **[Arsitektur Framework](docs/ARCHITECTURE.md)**
- 🔌 **[Panduan Pembuatan Plugin](docs/PLUGIN_DEVELOPMENT.md)**

---

## 🛠️ Pengembangan & Kontribusi

Framework ini dikembangkan secara modular di folder `src/`. File `fullstuck.php` adalah hasil kompilasi dari modul-modul tersebut.

Bagi Anda yang ingin berkontribusi:
1.  Baca **[CONTRIBUTING.md](CONTRIBUTING.md)** untuk memahami SOP pengembangan.
2.  Gunakan `php src/compiler-fullstuck.php` untuk membuild ulang core engine.

---

<div align="center">
  <sub>Dibuat dengan ❤️ untuk ekosistem PHP yang lebih sederhana.</sub>
</div>
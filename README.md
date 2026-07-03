<div align="center">
  <h1>🚀 FullStuck.php</h1>
  <p><b>The Zero-Config, AI-Friendly, Single-File PHP Micro-Framework</b></p>

  [![Version](https://img.shields.io/badge/version-0.3.0-green.svg)](docs/v0.3.0.md)
  [![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.0-8892BF.svg)](https://php.net/)
  [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
  [![AI-Friendly](https://img.shields.io/badge/AI--Agent-Ready-orange.svg)](#-ai-agent--vibe-coder-setup)
</div>

---

**FullStuck.php** adalah *micro-framework* yang mengembalikan kesederhanaan PHP. Seluruh *core engine* berada dalam **satu file tunggal**, memberikan Anda *routing* statis, *database* PDO (SQLite/PostgreSQL/MySQL), templating, dan keajaiban **Hybrid Fragment Routing** (FST Agent) tanpa butuh folder `vendor/` atau instalasi *Composer*. Di versi **v0.3.0**, framework ini telah direvolusi menjadi sangat ringan dengan menghilangkan sistem CMS bawaan dan lebih fokus sebagai fondasi *headless* atau *micro-services* yang sangat lincah.

🌐 **[Kunjungi Landing Page Resmi](https://fullstuck.biz.id/)**

---

## 🤖 AI Agent / Vibe Coder Setup (Recommended)

```text
Install FullStuck.php : https://raw.githubusercontent.com/milio48/fullstuck/main/docs/ai-setup.md
```

---

## 🚀 Quick Start (Manual)

1.  **Download**: Unduh file `fullstuck.php` ke folder kosong Anda.
2.  **Initialize**: Jalankan perintah inisialisasi otomatis:
    ```bash
    php fullstuck.php init --scaffold=yes
    ```
3.  **Run**: Jalankan server bawaan PHP:
    ```bash
    php -S localhost:8000 fullstuck.php
    ```
4.  **Explore**:
    *   🌐 Aplikasi Anda berjalan di: `http://localhost:8000`

---

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

## ✨ Fitur Unggulan v0.3.0

*   **📦 Single File Distribution**: Cukup satu file `fullstuck.php` untuk menjalankan seluruh aplikasi.
*   **🎨 Procedural DOM Templating**: Engine templating `fst_template()` memisahkan murni HTML dan PHP. Tidak perlu `<br>` atau `echo` lagi di dalam HTML!
*   **⚡ Hybrid Front-End Routing (FST Agent)**: Navigasi instan dengan *Fragment Fetching* dari PHP atau atur rute spesifik Anda secara *client-side* menggunakan `fst.set()`!
*   **🤖 AI-Native Design**: Struktur kode yang sangat ramah untuk asisten AI (Cursor, Windsurf, Cline) untuk memahami konteks proyek secara utuh.
*   **🔒 Hardened Security**: Proteksi CSRF, perlindungan dari injeksi di templating, dan *Session Security* bawaan.
*   **🐘 Database Flexible**: Dukungan penuh untuk SQLite, MySQL, dan **PostgreSQL** dengan *connection pooling*.

---

## 📚 Dokumentasi

Dokumentasi FullStuck bersifat **versioned single-file** agar memudahkan pencarian dan memberikan konteks penuh bagi AI:

- 📖 **[Dokumentasi v0.3.0 (Terbaru)](docs/v0.3/index.md)**
- 📖 **[Dokumentasi v0.2.0 (Legacy)](docs/v0.2/index.md)**
- 📖 **[Dokumentasi v0.1.0 (Legacy)](docs/v0.1/index.md)**
- 📋 **[Riwayat Perubahan (Changelog)](CHANGELOG.md)**

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
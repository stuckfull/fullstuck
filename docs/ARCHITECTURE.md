# Arsitektur "Dua Dunia" FullStuck.php

Sesuai dengan filosofi framework ini, FullStuck.php didesain agar sangat mudah digunakan sebagai sebuah *single-file framework* (hanya butuh menaruh 1 file `fullstuck.php` ke root server). Namun, hal ini bisa membuat pengembangan framework itu sendiri menjadi sangat sulit apabila semuanya tertumpuk di dalam satu file raksasa.

Oleh karena itu, framework ini menggunakan konsep **Dua Dunia**:

## 1. Dunia 1 (Pengembangan / Framework Development)
Di dunia ini, pengembangan source code framework dilakukan secara modular agar mudah dibaca, di-*maintain*, dan dikembangkan (*Developer Experience* yang baik). Kode dipecah berdasarkan tanggung jawab fungsinya dan diletakkan di dalam folder `src/`.

### Struktur File Modular (`src/`):
- **`core.php`**: Inisialisasi awal, manajemen *error reporting*, konstanta environment, dan sistem konfigurasi JSON dengan dukungan interpolasi variabel environment (`${ENV_VAR}`).
- **`database.php`**: Wrapper koneksi `PDO` multi-koneksi yang mendukung driver **SQLite, MySQL, dan PostgreSQL**.
- **`router.php`**: Jantung framework. Menangani registrasi rute, dispatching, middleware (*Onion Model*), dan *Fragment Rendering* untuk SPA.
- **`http.php`**: Menangani URI, method, payload (`$_GET`/`$_POST`), CSRF Protection, mekanisme Session/Flash, validasi input (`fst_validate`), dan File Upload.
- **`view.php`**: Engine rendering HTML (`fst_view`, `fst_partial`) dan manajemen sajian file aset statik dengan *cache headers*.
- **`utility.php`**: Fungsi pembantu umum dan debugging (`fst_dump`, `fst_dd`, `fst_app`).
- **`template.php`**: Engine templating berbasis DOM Manipulation untuk merender HTML secara aman tanpa menyisipkan tag PHP.
- **`install.php`**: GUI Setup Wizard otomatis untuk inisialisasi proyek dan pembuatan `fullstuck.json`.
- **`admin.php`**: Dashboard `/stuck`. Berisi monitoring sistem, editor konfigurasi, scanner rute, dan manajemen plugin.
- **`bootstrap.php`**: Penggabung instruksi rute pengguna, *Auto-Discovery* plugin dari `fst-plugins/`, dan eksekusi akhir `fst_run()`.

---

## 2. Dunia 2 (Rilis / Penggunaan oleh End-User)
Di dunia ini, pengguna hanya membutuhkan satu file rilis: `fullstuck.php`. File ini adalah hasil kompilasi otomatis dari seluruh modul di Dunia 1.

### Proses Kompilasi (`src/compiler-fullstuck.php`)
Ini adalah *build tool* yang menyatukan potongan kode di `src/` menjadi satu file utuh:
1. Membaca modul secara berurutan sesuai dependensi.
2. Menghapus tag pembuka/penutup PHP yang berlebih menggunakan `token_get_all()`.
3. Melakukan minifikasi pada aset internal (seperti `fst.js`).
4. Menghitung `FST_HASH` untuk fitur pemantau integritas file.

**Cara Build:**
Setiap kali Anda mengubah kode di `src/`, jalankan perintah ini:
```bash
php src/compiler-fullstuck.php
```

---

## 3. Peta Sistem & Lokasi File

Berikut adalah struktur direktori resmi FullStuck.php v0.2.0:

```text
/ (Project Root)
├── fullstuck.php               # [DUNIA 2] Framework rilis single-file. (JANGAN DIEDIT!)
├── fullstuck.json              # Konfigurasi aktif proyek.
├── fullstuck.example.json      # Template konfigurasi referensi.
├── CHANGELOG.md                # Riwayat perubahan dan perbaikan.
├── CONTRIBUTING.md             # SOP Pengembangan Dunia 1 (Wajib baca!).
├── README.md                   # Gerbang utama informasi proyek.
├── version.json                # Registry versi dan hash integritas.
├── docs/                       # Dokumentasi resmi
│   ├── ARCHITECTURE.md         # Penjelasan arsitektur (Dokumen ini).
│   ├── v0.2.0.md               # Referensi API & Fitur lengkap.
│   └── PLUGIN_DEVELOPMENT.md   # Panduan membuat plugin.
├── src/                        # [DUNIA 1] Source code modular (Edit kode di sini).
└── store/                      # Repository plugin resmi (Local Store).
```

## 4. Alur Eksekusi (Runtime Flow)
1. **Entry**: Permintaan masuk ke `index.php` (yang melakukan `require 'fullstuck.php'`).
2. **Setup**: Framework memuat konfigurasi, mengatur error handler, dan memulai session.
3. **Routing**: Framework mencocokkan URI dengan daftar rute di `router.php`.
4. **Middleware**: Jika rute cocok, middleware dieksekusi secara berurutan.
5. **Logic**: Callback rute dijalankan (mengakses DB, memproses input, dll).
6. **Rendering**: Output dihasilkan (HTML/JSON). Jika request dari agen SPA, framework secara cerdas melakukan *clipping* hanya pada fragment yang diminta.
7. **Finish**: Framework mengirimkan response headers dan konten ke browser.

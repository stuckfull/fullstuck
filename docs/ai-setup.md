# 🤖 FullStuck AI Developer SOP & Setup

> **KONTEKS PENGEMBANGAN:** Anda adalah FullStuck Senior Engineer yang bertugas mengelola proyek ini. Patuhi seluruh aturan main di bawah ini demi efisiensi dan DX.

## 🚀 1. Unduh & Inisialisasi
- **Cek Dependensi (Windows):** Jika `php`/`git` belum ada, arahkan pengguna untuk memasangnya via Scoop (misal: `scoop install php git nodejs python tailwindcss`).
- **Langkah Persiapan:**
  1. **Unduh:** `curl -O https://raw.githubusercontent.com/milio48/fullstuck/main/fullstuck.php`
  2. **Inisialisasi:** `php fullstuck.php init --db=sqlite --admin-pass=stuck --admin-url=/stuck --spa=yes --scaffold=yes --htaccess=yes` *(menghasilkan `fullstuck.json` & manual `fullstuck_v*.md`)*
  3. **Jalankan:** `php -S localhost:8000 fullstuck.php`

## 🔓 2. Kesepakatan Alur Kerja (Opt-Out Mode)
Pada chat pertama, **WAJIB** tanyakan langsung ke pengguna:
> *"Apakah Anda ingin mengikuti alur kerja terpandu (SOP Phases dengan tracker `fullstuck_brief.md`) atau ingin bekerja secara bebas (Free-Style Mode)?"*

- **Free-Style:** Set `"ai_sop": false` di `fullstuck.json`. **Abaikan sisa SOP ini sepenuhnya** (jangan buat tracker/fase). Bekerja bebas mengikuti instruksi langsung pengguna.
- **SOP Terpandu:** Biarkan `"ai_sop": true`. Wajib ikuti aturan memori, perilaku, keamanan, dan Fase di bawah.

## 🤝 3. Aturan Ingatan AI (Hanya jika `"ai_sop": true`)
- **Tracker:** Wajib buat `fullstuck_brief.md` di root sebagai memori utama & index navigasi.
- **Header Tracker:** Baris pertama wajib tertulis: `"AI BARU: WAJIB BACA SOP DI https://raw.githubusercontent.com/milio48/fullstuck/main/docs/ai-setup.md DAN BACA API DI fullstuck_v*.md DULU!"`
- **Batasan Ukuran:** Jika salah satu sub-bagian (section) di tracker melebihi **30 baris**, pindahkan detailnya ke berkas terpisah di dalam folder `docs/` dan tautkan di bagian Referensi Dokumen agar tracker tetap ringkas.
- **Format Tracker:** Catat status secara ringkas. Rencana Rute bertindak sebagai To-Do list utama. Gunakan template berikut:
  ```markdown
  # 📌 Project Brief & Status Tracker

  ## 💡 Ringkasan Cepat (Quick Summary)
  - [Ringkasan instan kondisi/tujuan proyek saat ini - maksimal 5 baris]

  ## 1. Status Proyek
  - **Fase Aktif:** [Fase / Siklus Mikro saat ini]
  - **Fitur Sedang Dikerjakan:** [Deskripsi singkat]

  ## 2. Rencana Rute (Routing / To-Do List)
  - [x] `/` -> `views/home.html` (Selesai)
  - [ ] `/route` -> `controllers/file.php` (Rencana)

  ## 3. Skema Database (Jika ada)
  - Tabel `nama_tabel` (kolom1, kolom2, ...)

  ## 4. Referensi Dokumen (Pola Hub & Spoke)
  - [User Flow & UI Design](docs/user-flow.md)
  *Catatan: Buat berkas terpisah untuk rancangan detail agar berkas tracker tetap ringkas.*
  ```

## 🛡️ 4. Aturan Perilaku & Keamanan (Workflow Ethics)
- **Security:** Selalu gunakan `fst_escape()` / `e()` untuk output HTML, dan `fst_csrf_field()` di dalam form. Jangan percaya input pengguna. Saat menyisipkan konten pengguna melalui `@append` atau `@prepend` di `fst_template`, wajib lakukan escape manual karena kedua direktif tersebut merender HTML mentah.
- **Credentials:** Jangan hardcode sandi/kredensial database. Rekomendasikan penggunaan variabel lingkungan (seperti `\${DB_HOST}`) di berkas `fullstuck.json` agar nilainya dibaca dinamis dari env vars.
- **Git:** Wajib `git commit` dengan pesan deskriptif setelah setiap fitur fungsional selesai (misal: `git commit -m "feat: tambah login controller"`). Jangan biarkan perubahan menumpuk.
- **Struktur & Adaptabilitas:** Pisahkan logika dari `router.php` ke berkas eksternal (misal `controllers/`) dan daftarkan di opsi `"require"` di `fullstuck.json`. **DILARANG** melakukan refaktorisasi atau pemindahan folder struktur proyek yang sudah berjalan (misal jika proyek menggunakan folder `src/`, `pages/`, dll.). AI wajib menyesuaikan diri dengan struktur folder pengguna dan cukup mengubah `"require"` di `fullstuck.json`. Struktur standar (`views/`, `controllers/`) hanya dianjurkan untuk proyek baru dari nol.
- **Testing:** Gunakan script/cURL untuk test. Jangan pakai browser automation kecuali untuk interaksi visual UI yang kritis.
- **DX & Feedback:** Jika menemukan bug, kendala performa, atau keterbatasan framework/dokumentasi, Anda **WAJIB** membuat berkas `fullstuck_issues.md` di root proyek. Catat kendala secara terstruktur (deskripsi bug, lokasi kode, reproduksi, dan usulan solusi). **PENTING:** Wajib sensor (masking) seluruh data sensitif, kredensial, API key, domain pribadi, atau data privasi pengguna sebelum menulis laporan. Pengguna bisa langsung menyalin laporan bersih ini untuk dilaporkan sebagai **Issue** atau **PR** di GitHub `github.com/milio48/fullstuck`.
- **Integritas Core (FST_HASH):** Selalu perhatikan integritas file `fullstuck.php`. Jika Anda mencurigai adanya kerusakan file (corruption) atau salah kompilasi, verifikasi kode rilis resmi di GitHub. Jika hash `FST_HASH` di header file tampak usang/tidak valid, sarankan pengguna untuk mengunduh ulang core tersebut via perintah inisialisasi `php fullstuck.php init`.


## 📋 5. Fase Kerja Terpandu (Phases - Hanya jika `"ai_sop": true`)

### 🔄 Siklus Iterasi & Fitur Baru
Jika proyek sudah berjalan dan Anda ingin menambah fitur baru atau memperbaiki bug, jalankan siklus mikro berikut (jangan ulangi Phase 1 dari nol agar tidak menghapus berkas yang sudah ada). *Catatan: Setelah setiap Micro-Phase selesai, Anda wajib memperbarui status fase aktif di berkas `fullstuck_brief.md` sebelum melanjutkan.*
1. **Menambah Fitur Baru:**
   * **Rencana (Micro-Phase 1):** Daftarkan rute baru dan rancangan tabel DB tambahan di `fullstuck_brief.md`.
   * **Tampilan (Micro-Phase 2):** Rancang UI statis baru (atau modifikasi berkas HTML lama dengan menambahkan atribut `data-fst` baru) dan minta persetujuan visual pengguna.
   * **Integrasi (Micro-Phase 3):** Tulis controller baru, sesuaikan database, dan buat aturan `$rules` template yang baru.
2. **Memperbaiki Bug:**
   * **Bug Data/Backend:** Langsung lakukan perbaikan di **Phase 3** (Logika).
   * **Bug Tampilan/Layout:** Lakukan perbaikan di **Phase 2** (HTML Statis) lalu verifikasi visual sebelum diikat kembali di Phase 3.


### PHASE 1: Cek Proyek & Rencana
1. **Analisis:** Baca berkas `fullstuck.json` terlebih dahulu untuk mengetahui konfigurasi folder, basis data, dan berkas yang di-require, kemudian baca berkas panduan API `fullstuck_v*.md` untuk memahami API yang tersedia sebelum menulis kode. Pelajari berkas/HTML lama di folder jika ada. Jangan asal hapus.
2. **Bersihkan:** Hapus scaffold contoh bawaan jika memulai proyek dari nol.
3. **Perencanaan:** Tulis daftar rute (URLs) dan skema tabel DB di `fullstuck_brief.md`.
4. **Lompat Fase:** Jika HTML statis sudah siap pakai, langsung lompat ke **Phase 3**.

### PHASE 2: Tampilan Dulu (Frontend-First)
*ATURAN: Pisahkan HTML & PHP. Tidak boleh ada tag `<?php ?>` di file view!*
1. Buat HTML statis (gunakan Tailwind CDN jika perlu) di folder `views/`.
2. **Tandai Elemen Dinamis:** Berikan atribut `data-fst="nama_kunci"` unik pada setiap elemen yang akan di-render dinamis (misal: `<span data-fst="user_name">`). Hindari nama kunci generik (seperti `name`) jika terdapat beberapa daftar/komponen dalam satu halaman agar tidak terjadi tabrakan selector.
3. Daftarkan rute statis di `router.php` menggunakan `fst_view()`.
4. **SPA Navigation:** Jika SPA aktif (`spa.enabled` di `fullstuck.json`), pastikan setiap link atau form navigasi utama menyertakan `data-fst-target` yang sesuai (misal `#app` atau target container lainnya) agar tidak melakukan full page reload.
5. **BERHENTI!** Minta pengguna verifikasi visual di browser. Jangan tulis PHP/backend sebelum tampilan disetujui.

### PHASE 3: Logika & Integrasi (Backend & Binding)
1. Hubungkan SQLite via `fst_db_*` dan pisahkan file logika/controller dari `router.php`.
2. Gunakan `fst_template($data, $rules)` untuk memanipulasi DOM HTML dinamis.
3. **Penyusunan Rules:**
   - Hubungkan penanda data-fst: `'[data-fst=user_name]' => '$username'` *(selalu gunakan tanda kutip tunggal dan sertakan tanda `$` karena rules akan dikompilasi menjadi PHP mentah `<?=\$username?>` yang dieksekusi setelah array $data di-extract)*.
   - Untuk atribut khusus (gambar/tautan/input), gunakan kurung siku: `'[data-fst=avatar]' => ['[src]' => '$avatar_url']` *(juga dikompilasi menjadi PHP mentah)*.
   - Di dalam loop, pastikan selector anak ditulis bersarang (nested) di bawah aturan `@foreach` induknya agar pencarian XPath bersifat relatif.
   - *Rujukan detail: Seluruh sintaks ruleset DSL (seperti `@if`, `@foreach`, `@append`, `@prepend`), cara validasi data form, dan upload berkas wajib dibaca langsung di `fullstuck_v*.md`.*
4. **Database & Migrasi:** Jika menambah kolom/tabel baru pada database berjalan, gunakan perintah SQL `ALTER TABLE` atau `CREATE TABLE IF NOT EXISTS` di file inisialisasi/controller. Dilarang melakukan drop tabel demi keamanan data.
5. **Debugging & Errors:** Jika terjadi *ParseError* pada kompilasi view, periksa berkas PHP hasil kompilasi di dalam folder cache (default: `view-cache/`) untuk menganalisis kode yang salah. Jika terjadi runtime error di mode `"production"`, periksa log di `.fst-error.log`.

### PHASE 4: Rilis (Deployment)
1. **CSS:** Ingatkan pengguna untuk compile Tailwind CSS CDN ke produksi (misal menggunakan Tailwind CLI: `npx tailwindcss -o assets/css/style.css --minify`).
2. **Server:** Pastikan file `.htaccess` terunggah agar rute dinamis tidak error 404.
3. **Environment:** Ubah opsi `"environment"` dari `"development"` menjadi `"production"` di berkas `fullstuck.json` sebelum dilepas ke publik untuk menyembunyikan detail log kesalahan/trace di layar dan mengalihkan catatan error ke berkas `.fst-error.log`.
4. **Pre-Deploy Checklist:** AI wajib mengingatkan/memverifikasi poin-poin berikut sebelum menyatakan siap rilis:
   - [ ] Mengubah opsi `"environment"` ke `"production"` di `fullstuck.json`.
   - [ ] Mengamankan/menghapus token/middleware demo (seperti `?token=123`) dari file `router.php`.
   - [ ] Mengompilasi Tailwind CSS (jika menggunakan Tailwind CDN).
   - [ ] Mengunggah berkas konfigurasi server `.htaccess`.
   - [ ] Merotasi kata sandi panel admin default (`stuck`) pada konfigurasi `fullstuck.json`.

## 🚨 5. Panduan Pemulihan Kesalahan (Error Recovery Playbook)
Jika terjadi error selama pengembangan, ikuti langkah berikut:
- **HTTP 500 (Internal Server Error):** Periksa berkas `.fst-error.log` di root. Jika terjadi kesalahan kompilasi view, periksa juga file PHP terkompilasi di dalam folder cache (default: `view-cache/`).
- **Gagal Migrasi Basis Data:** Jangan lakukan `DROP TABLE` jika tabel sudah berisi data. Gunakan instruksi `ALTER TABLE` secara terukur.
- **Cache Template Rusak/Korup:** Hapus seluruh isi folder `view-cache/` untuk memaksa sistem membangun ulang (rebuild) cache secara otomatis.
- **SPA Error / Konflik Script:** Periksa apakah file `.htaccess` atau konfigurasi URL server sudah benar, dan gunakan atribut `data-fst-no-spa` untuk menonaktifkan SPA secara selektif pada tautan/form tertentu jika terjadi konflik script eksternal.

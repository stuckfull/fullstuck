# Standard Operating Procedure (SOP) Pengembangan - Dunia 1

Dokumen ini adalah **panduan mutlak** bagi Developer dan **AI Agent** yang bertugas mengembangkan *source code* framework `FullStuck.php` (pekerjaan di Dunia 1).

## 1. Alur Kerja (Workflow)
Setiap kali menerima instruksi untuk menambah fitur atau memperbaiki *bug*, ikuti urutan langkah kerja ini:
1. **Analisis Konteks**: Pahami modul mana di dalam direktori `src/` yang paling tepat untuk diubah.
2. **Modifikasi Kode**: **DILARANG KERAS** memodifikasi file `fullstuck.php` di root. Lakukan perubahan hanya pada file-file di dalam direktori `src/`.
3. **Kompilasi Otomatis**: Satukan kembali seluruh *source code* ke dalam file rilis menggunakan script compiler:
   ```bash
   php src/compiler-fullstuck.php
   ```
4. **Validasi (Testing)**: Lakukan uji coba fitur. Pendekatan *Outside-In* (Feature Testing) sangat dianjurkan.
5. **Pencatatan**: Lakukan pembaruan dokumen laporan dan referensi API.

## 2. Aturan Dokumentasi dan Pelaporan (Reporting)
Setiap perubahan fungsional atau arsitektural **WAJIB** didokumentasikan agar *memory/context* tetap sinkron:
- **`CHANGELOG.md`**: Rekam setiap penambahan fitur, perubahan penting, atau fungsi yang dihapus di bawah section `[Unreleased]` agar histori versi terlihat jelas.
- **`TODO.md`**: Ubah status pekerjaan yang sudah beres menjadi *checked* (`- [x]`). Jika Anda mendeteksi bug atau ide baru, tambahkan ke dalam list.
- **`docs/v0.3/FULL.md`**: Apabila Anda membuat fungsi pembantu (*helper*) baru (misal: `fst_sesuatu()`), Anda WAJIB menambahkan deskripsi dan cara panggilannya di file dokumentasi utama ini (bagian *API Reference*).
- **`version.json`**: File registry publik untuk mencatat versi dan hash `fullstuck.php` terbaru.

## 3. Branching & Git Commit
Gunakan strategi percabangan (Branching) saat mengembangkan fitur besar:
- `main` / `master`: Rilis stabil.
- `experiment/*` atau `feature/*`: Branch pengembangan.

Pesan commit harus rapi, ringkas, dan mengikuti standar *Conventional Commits*:
- `feat: [nama fitur]` - Penambahan fungsionalitas / *helper* baru.
- `fix: [nama bug]` - Perbaikan *error* / logika yang salah.
- `docs: [penjelasan]` - Update pada folder `docs/` atau `README.md`.
- `refactor: [penjelasan]` - Merombak kode di `src/` tanpa mengubah fitur *end-user*.
- `test: [penjelasan]` - Penambahan/uji coba kasus pada folder `test/`.
- `build: [penjelasan]` - Perubahan pada `src/compiler-fullstuck.php`.

## 4. Checklist Wajib Saat Menambah Fungsi `fst_*` Baru
Setiap kali menambah fungsi baru ke framework (contoh: `fst_db_select`, `fst_validate`, dll), Anda **WAJIB** melakukan update pada lokasi-lokasi berikut agar seluruh ekosistem tetap sinkron:

| # | Lokasi File | Yang Diupdate |
|---|-------------|---------------|
| 1 | `src/*.php` | Implementasi fungsi baru. |
| 2 | `docs/v0.3/FULL.md` | Tambahkan deskripsi fungsi baru di bagian API Reference. |
| 3 | `CHANGELOG.md` | Catat di bawah section `[Unreleased]`. |
| 4 | `php src/compiler-fullstuck.php` | Compile ulang agar `fullstuck.php` di root sinkron. |
| 5 | `version.json` | Update **hash** di file ini dengan nilai `FST_HASH` terbaru dari header `fullstuck.php` jika diperlukan. |

*(Note untuk AI Agent: Sebelum Anda memberikan summary final ke user, pastikan file `fullstuck.php` di root selalu ikut ter-update akibat proses build, agar sinkron dengan perubahan pada `src/`).*

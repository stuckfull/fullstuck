# 🤖 FullStuck AI Developer SOP & Setup

> **KONTEKS PENGEMBANGAN:** Anda adalah FullStuck Senior Engineer yang bertugas mengelola proyek ini. Patuhi seluruh aturan main di bawah ini demi efisiensi dan DX.

## 🚀 1. Persiapan Proyek
Asumsikan pengguna sudah berada di direktori proyek dengan PHP terinstal.
1. **Instalasi Framework:** `php fullstuck.php init --db=sqlite --agent=yes --scaffold=yes --htaccess=yes` *(menghasilkan `fullstuck.json` dan struktur dasar)*.
2. **Jalankan Server:** `php -S localhost:8000 fullstuck.php`
3. **Dokumentasi AI:** Selalu jalankan `php fullstuck.php docs` di terminal jika Anda kehilangan konteks API terbaru.

## 🛡️ 2. Aturan Perilaku & Keamanan (Workflow Ethics)
- **Security:** Selalu gunakan `fst_escape()` / `e()` untuk output HTML, dan `fst_csrf_field()` di dalam form. Jangan percaya input pengguna. Saat menyisipkan konten melalui `@append` atau `@prepend` di `fst_template`, pastikan lolos escaping (kecuali Anda yakin isinya aman).
- **Credentials:** Gunakan variabel lingkungan (seperti `${DB_HOST}`) di `fullstuck.json` agar rahasia tidak bocor.
- **Git:** Wajib `git commit` dengan pesan deskriptif setelah setiap fitur fungsional selesai (misal: `git commit -m "feat: tambah login controller"`).
- **Struktur Folder:** AI wajib menyesuaikan diri dengan struktur folder pengguna dan cukup mengubah `"require"` di `fullstuck.json`. Standar (`views/`, `controllers/`) hanya dianjurkan untuk proyek dari nol.
- **Integritas Core:** DILARANG keras menyentuh / memodifikasi `fullstuck.php`.

## 📋 3. Panduan Implementasi Fitur Baru

### Aturan Logika vs Tampilan
*ATURAN: Pisahkan HTML & PHP. Tidak boleh ada tag `<?php ?>` di file view!*
1. Buat HTML statis (gunakan Tailwind CDN jika perlu).
2. **Tandai Elemen Dinamis:** Berikan atribut unik `data-fst="kunci"` (misal `<span data-fst="nama">`).
3. Daftarkan rute di `router.php` atau `controllers/*.php`.
4. **FST Agent (SPA):** Jika `agent_js` bernilai `true`, pastikan form kecil menggunakan atribut `data-fst-no-history` agar tak merusak tombol Back browser. Gunakan atribut seperti `data-fst-fragment` jika hanya sebagian halaman yang perlu di-update.
5. Hubungkan DOM dengan data via sintaks deklaratif: `fst_template($data, $rules)`. 
   - Contoh sederhana: `'[data-fst=nama]' => '$user_name'`
   - *(Jalankan `php fullstuck.php docs` untuk melihat contoh sintaks Array templating lengkap)*

### Deployment & Produksi
- Set `"production": true` di `fullstuck.json` agar error code/PHP Exception tidak bocor ke publik.
- Error akan dialihkan ke file `.fst.log`.

## 🚨 4. Panduan Pemulihan Kesalahan (Error Recovery Playbook)
- **HTTP 500:** Periksa berkas `.fst.log`. Jika error di view, periksa file ter-kompilasi di `view-cache/`.
- **Cache Template:** Hapus isi folder `view-cache/` untuk memaksa sistem me-rebuild cache jika Anda merasa perubahannya tidak terlihat.
- **Gagal Migrasi DB:** Jangan lakukan `DROP TABLE` jika sudah ada data, gunakan `ALTER TABLE`.

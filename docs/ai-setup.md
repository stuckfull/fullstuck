# FullStuck v0.4 — AI Skill

> **Instruksi ini ditujukan untuk kamu sebagai AI Assistant.** Baca dan patuhi sebelum menulis kode apapun di proyek FullStuck.

## 1. Setup Proyek Baru

Jika proyek belum diinisialisasi (belum ada `fullstuck.json`):

```bash
# 1. Download core engine
curl -O https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/fullstuck.php

# 2. Inisialisasi dengan scaffold lengkap (default)
php fullstuck.php init

# Opsi tersedia:
#   --db=sqlite|mysql|pgsql|none  (default: sqlite)
#   --scaffold=yes|no             (default: yes)
#   --agent_js=yes|no             (default: yes)
#   --htaccess=yes|no             (default: yes)
#   --db_host=... --db_name=... --db_user=... --db_pass=... --db_port=...
```

Menjalankan server lokal:
```bash
php -S localhost:8000 fullstuck.php
```

## 2. Konteks Framework

Proyek ini menggunakan **FullStuck v0.4**, micro-framework PHP 1-file dengan arsitektur **Path-Based Colocation**.

**Sebelum menulis kode, kamu WAJIB:**
1. Baca file `brain_fullstuck.md` di root proyek. File ini berisi seluruh aturan sintaksis, arsitektur, dan API reference framework.
2. Jika `brain_fullstuck.md` tidak ditemukan, gunakan CLI untuk membaca dokumentasi langsung:
   ```bash
   php fullstuck.php docs      # daftar isi
   php fullstuck.php docs:2    # routing & folder
   php fullstuck.php docs:5    # FST-Agent (SPA)
   php fullstuck.php docs:11   # API Reference
   php fullstuck.php docs:full # seluruh dokumentasi
   ```

## 3. Workflow

1. **Identifikasi rute target.** URL = struktur folder di `app/`. Contoh: rute `/blog/[slug]` → folder `app/blog/[slug]/`.
2. **Buat/edit file di folder rute tersebut.** Setiap folder rute maksimal berisi:
   - `content.fst.php` — tampilan GET (Blade syntax)
   - `action.php` — handler POST/PUT/DELETE atau JSON API
   - `client.js` — script frontend (auto IIFE, auto cleanup)
   - `_guard.php` — middleware pelindung (opsional)
3. **Logika reusable** → taruh di `globals/`. **Komponen UI reusable** → taruh di `components/`.
4. **Jangan pernah** membuat file `router.php` atau mendefinisikan rute manual (`fst_get`, `fst_post`). Routing sepenuhnya berbasis folder.
5. **Jangan pernah** memodifikasi `fullstuck.php` secara langsung. Jika perlu mengubah core, edit file di `src/` lalu jalankan:
   ```bash
   php src/compiler-fullstuck.php
   ```

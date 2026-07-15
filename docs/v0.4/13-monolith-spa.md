## ⚛️ 13. Integrasi Eksternal: Monolith SPA (React/Vue/Svelte)
Jika Anda menggunakan framework JavaScript eksternal (React/Vue/Svelte), FullStuck bisa berperan sebagai REST API murni sekaligus menyajikan file statis SPA (*Monolith*), membebaskan Anda dari kendala CORS.

**1. Penyesuaian `fullstuck.json`:**
Matikan FST-Agent dan ekspos direktori *build* frontend Anda (misal: `public`).
```jsonc
{
  "agent_js": false,
  "routing": { "public_folders": ["assets", "public"] }
}
```

**2. Struktur Direktori:**
Buat endpoint API Anda di dalam `app/api/` (menggunakan `action.php`), lalu letakkan hasil *build* statis React/Vue/Svelte di `public/`.

**3. Trik Catch-All Routing:**
Untuk mencegah halaman 404 saat pengguna melakukan *hard reload* pada rute React/Vue/Svelte, manfaatkan *error handler* bawaan melalui file `app/404.fst.php`:
```php
<?php
// app/404.fst.php
// Berikan 404 asli jika request ini ditujukan ke API
if (str_starts_with(fst_uri(), '/api/')) fst_json(['error' => 'Not Found'], 404);

// Selain itu, beri status 200 dan serahkan kepada router React/Vue/Svelte
fst_status_code(200);
echo file_get_contents(FST_ROOT_DIR . '/public/index.html');
die();
```

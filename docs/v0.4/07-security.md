## 🔒 7. Keamanan & Session

### Session Fixation Protection
**Wajib** dipanggil setelah login berhasil:
```php
fst_session_regenerate(true);
```

### Keamanan Form (Tanpa CSRF Token)
v0.4 tidak menggunakan CSRF token. Perlindungan form mengandalkan:
- Cookie `SameSite=Lax` (otomatis diset framework)
- Header `X-FST-Request` (disuntikkan FST-Agent)

### File Upload (Secure by Default)
```php
$result = fst_upload('foto', 'uploads/avatar', [
    'max_size' => 1024,                        // KB
    'allowed_types' => ['jpg','png','webp'],   // Whitelist
]);

if ($result['success']) {
    echo $result['path']; // "uploads/avatar/foto-abc123.jpg"
}
```
Ekstensi berbahaya (`.php`, `.exe`, dll) di-*block* secara hardcoded dan tidak bisa di-override.

### XSS Prevention
- **Backend:** Gunakan `{{ $var }}` di template (auto-escape) atau `e($var)` di PHP murni.
- **Frontend:** Gunakan `fst.e(string)` sebelum menyuntikkan ke `innerHTML`.

# 🛡️ Request, Validasi & Keamanan

### Proteksi CSRF (Wajib pada Form)
- **File `.php`**: `<?= fst_csrf_field() ?>`
- **File `.html` (via `fst_template`)**:
  Beri elemen penanda di dalam form HTML Anda, contoh: `<div class="fst-csrf"></div>`
  ```php
  ".fst-csrf" => ["@html" => 'fst_csrf_field()']
  ```

### Validasi Controller
```php
fst_post('/register', function() {
    fst_csrf_check(); // Wajib untuk POST/PUT/DELETE

    $val = fst_validate(fst_request(), [
        'nama'  => 'required|min:3',
        'email' => 'required|email',
        'umur'  => 'required|numeric'
    ]);

    if (!$val['valid']) {
        fst_flash_set('error', implode(', ', array_merge(...array_values($val['errors']))));
        fst_redirect('/register');
    }

    $clean = $val['data']; // Data tersanitasi
});
```

### Pencegahan XSS (Cross-Site Scripting)

1. **Sisi Backend (PHP)**:
   Gunakan helper global `e($str)` atau `fst_escape($str)` saat merender variabel dinamis di dalam file HTML/PHP Anda.
   ```php
   <p>Selamat datang, <?= e($username) ?></p>
   ```

2. **Sisi Frontend (Client-side Router)**:
   Saat menggunakan rute JavaScript dinamis (`fst.set()`), hindari menyuntikkan data mentah ke `.innerHTML`. Gunakan `fst.e()` atau `fst.escape()` untuk mensterilkan variabel.
   ```javascript
   document.querySelector('main').innerHTML = `
       <p>Menampilkan ID: \${fst.e(params.id)}</p>
   `;
   ```

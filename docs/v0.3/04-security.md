# 🛡️ Request, Validasi & Keamanan

### Proteksi CSRF (Wajib pada Form)
- **File `.php`**: `<?= fst_csrf_field() ?>`
- **File `.html` (via `fst_template`)**:
  ```php
  "form" => ["@append" => 'fst_csrf_field()']
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

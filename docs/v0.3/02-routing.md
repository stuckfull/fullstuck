# 📖 Routing & Middleware

**Basic Routing:**
```php
fst_get('/halo', function() { echo "Halo Dunia!"; });
fst_get('/user/{id:i}', function($id) { echo "ID: " . $id; }); // :i (integer)
fst_get('/post/{slug:any}?', function($slug = 'default') { echo "Opsional: " . $slug; });
```

**Middleware (Onion Model):**
```php
function cek_login($next) {
    if (!fst_session_get('user_id')) return fst_redirect('/login');
    return $next(); // Lanjut ke rute tujuan
}

fst_group('/admin', function() {
    fst_get('/dashboard', function() { echo "Admin Area"; });
}, 'cek_login');
```

**Pengambilan Data Request:**
```php
fst_post('/submit', function() {
    // fst_input(key, default) secara otomatis mencari dari $_POST, $_GET, atau JSON body
    $username = fst_input('username');
    $status = fst_input('status', 'active'); // Default fallback
    
    // Semua request data
    $all = fst_request(); 
});
```

> ⚠️ **Peringatan Internal API:** Fungsi `_fst_route()` adalah API internal framework (bersifat *private*) dan DILARANG untuk dipanggil secara langsung. Pengembang (dan Agen AI) WAJIB menggunakan fungsi *wrapper* publik seperti `fst_get()`, `fst_post()`, `fst_put()`, `fst_delete()`, dan `fst_any()`.

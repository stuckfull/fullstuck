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

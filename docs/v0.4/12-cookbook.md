## 🌶️ 12. Cookbook & Tips Lanjutan

### Global Middleware (CORS / Auth)
Buat file di `globals/` — otomatis dimuat sebelum routing:

```php
// globals/cors.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-FST-Request");

if (fst_method() === 'OPTIONS') {
    fst_status_code(200);
    die();
}

function authenticate_api() {
    $auth = getallheaders()['Authorization'] ?? '';
    if (!str_starts_with($auth, 'Bearer ')) {
        fst_json(['error' => 'Unauthorized'], 401);
    }
    return verify_jwt(substr($auth, 7));
}
```

### Request Interceptor (Frontend)
Sisipkan token atau header kustom ke setiap request SPA:
```javascript
fst.setInterceptor((url, fetchOptions) => {
    const token = localStorage.getItem('api_token');
    if (token) fetchOptions.headers['Authorization'] = `Bearer ${token}`;
    return fetchOptions;
});
```

### Database Migration
Buat file skrip terpisah, jalankan via terminal:
```php
// tools/migrate.php
<?php require 'fullstuck.php';

fst_db('EXEC', "CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nama TEXT NOT NULL,
    email TEXT UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

echo "Migration done.\n";
```
```bash
php tools/migrate.php
```

### Custom Error Page
Buat file `app/404.fst.php` atau `app/500.fst.php`. Framework otomatis menggunakannya saat terjadi error. File error juga bisa diletakkan per-section (misal `app/admin/404.fst.php`).

### Folder `globals/` — Apa yang Boleh & Tidak
| ✅ Boleh | ❌ Dilarang |
|---|---|
| Helper functions | File view/template |
| Konstanta | Logika routing |
| Koneksi database | File HTML |
| Middleware global | |

### Konvensi Arsitektur: HTML-over-the-wire (HOTW)
FullStuck menggunakan pola *HTML-over-the-wire* (Single Source of Truth). **Dilarang** merakit HTML (seperti *Card* atau *Modal*) dengan *string literal* di JavaScript karena rentan XSS dan menduplikasi kode.

Untuk memuat komponen dinamis, ambil *render* HTML langsung dari server via `fst.go()`:

```javascript
// Ambil komponen dari server, sisipkan ke #wadah
fst.go('/api/komponen/card', { target: '#wadah', history: false });
```

### Menghindari 404 pada SPA (Isomorphic Routing)
Rute SPA yang didaftarkan via `fst.set('/path', ...)` di `client.js` akan menghasilkan **404 Not Found** jika URL diakses langsung (atau *hard reload*). Hal ini terjadi karena direktori tersebut tidak ada di sisi backend (PHP).

**Solusi:** URL publik **wajib** memiliki direktori pendamping di server. Contoh rute `/dashboard/view/123`:

**1. Pendekatan CSR:**
Backend hanya memuat tata letak (*layout*) kosong. Data diambil terpisah oleh `client.js`.

```php
<!-- app/dashboard/view/[id]/content.fst.php -->
@layout('dashboard')
<div id="detail-container">
    <div class="skeleton-loader">Memuat...</div>
</div>
```

**2. Pendekatan SSR (Direkomendasikan):**
Backend merender tampilan utuh beserta data. Halaman langsung tampil seketika (*first paint*), memaksimalkan performa *Hybrid* FullStuck.

```php
<!-- app/dashboard/view/[id]/content.fst.php -->
<?php
$task = fst_db_row('tasks', ['id' => fst_input('id')]);
if (!$task) fst_abort(404, 'Tugas tidak ditemukan');
?>

@layout('dashboard')
<div id="detail-container">
    <h1>{{ $task['title'] }}</h1>
    <p>{{ $task['description'] }}</p>
</div>
```

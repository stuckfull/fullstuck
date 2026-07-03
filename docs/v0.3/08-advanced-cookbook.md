# 8. 🌶️ Advanced Cookbook (Scale-Up Guide)

FullStuck didesain seringan mungkin untuk mempercepat fase **Zero to One** (Prototyping & MVP). Namun, ketika aplikasi Anda mulai membesar menuju fase **One to Scale** (Production), Anda membutuhkan pendekatan arsitektural tingkat lanjut. 

Bab ini memandu Anda menangani skenario kompleks tanpa perlu mengotori kesederhanaan core framework.

## 1. Menghindari "Array Hell" di `fst_template` (Component Pattern)
Saat UI Anda semakin kompleks (tabel relasi, *nested list*, komponen kondisional), menulis `fst_template` secara berurutan dalam satu file akan menyebabkan "Array Hell" (kode yang sulit dibaca dan dipelihara).

**Solusi:** Gunakan **Component Pattern** dengan memecah UI menjadi fungsi PHP murni.

```php
// file: components/user_card.php
function render_user_card($user) {
    return [
        "div" => [
            "@class" => "card mb-3",
            "div" => [
                "@class" => "card-body",
                "h5" => e($user['name']),
                "p"  => ["@class" => "text-muted", "@text" => e($user['email'])]
            ]
        ]
    ];
}

// file: router.php
fst_get('/users', function() {
    $users = fst_db_select('users');
    
    // Alih-alih merender iterasi langsung di sini, panggil komponen!
    $user_cards = array_map('render_user_card', $users);

    fst_template('BaseLayout', [
        "h2" => "Daftar Pengguna",
        "div" => [
            "@class" => "user-list",
            // Array of nodes dapat langsung disuntikkan ke parent
            "@append" => $user_cards 
        ]
    ]);
});
```
Dengan cara ini, template utama Anda tetap bersih, dan komponen `<UserCard>` dapat digunakan kembali (reusable) di berbagai rute.

---

## 2. Global Middleware (CORS & Auth API)
Untuk mendukung *Mobile Apps* atau aplikasi eksternal, Anda membutuhkan mekanisme **CORS** (Cross-Origin Resource Sharing) dan Autentikasi Stateless (seperti JWT/Bearer Token).

**Solusi:** Manfaatkan fitur `require` di `fullstuck.json` atau gunakan rute `fst_any('*')` sebagai middleware global.

**Cara Terbaik (Pre-booting via `require`):**
Buat file `middleware/api_global.php` dan daftarkan di config `"require": ["middleware/api_global.php"]`.

```php
// middleware/api_global.php

// 1. Injeksi Header CORS Global
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-FST-Request");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    fst_status_code(200);
    die();
}

// 2. Fungsi Helper Verifikasi JWT/Bearer Token
function authenticate_api() {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';
    
    if (empty($auth) || !str_starts_with($auth, 'Bearer ')) {
        fst_json(['error' => 'Unauthorized'], 401);
    }
    
    $token = substr($auth, 7);
    // ... lakukan verifikasi token (misal via Firebase Auth / JWT library)
    return $user_payload;
}
```

Di dalam `router.php`, Anda tinggal memanggil fungsi auth di rute API yang diamankan:
```php
fst_get('/api/saldo', function() {
    $user = authenticate_api();
    fst_json(['saldo' => 50000, 'user_id' => $user['id']]);
});
```

---

## 3. FST Agent Request Interceptor (Header Injections)
Jika Anda menggunakan FST Agent (Fragment Routing) tapi butuh menyuntikkan *header* tambahan di setiap request (misal untuk mengirim token Bearer atau preferensi bahasa).

**Solusi:** Gunakan metode `fst.setInterceptor()` di client-side.

```javascript
// Letakkan di dalam file js utama (main.js)
fst.setInterceptor((url, fetchOptions) => {
    // Tambahkan header khusus sebelum request AJAX dieksekusi
    const token = localStorage.getItem('api_token');
    if (token) {
        fetchOptions.headers['Authorization'] = `Bearer ${token}`;
    }
    fetchOptions.headers['X-Timezone'] = Intl.DateTimeFormat().resolvedOptions().timeZone;
    
    // Kembalikan konfigurasi yang sudah dimodifikasi
    return fetchOptions; 
});
```
Agen FST otomatis akan mematuhi *interceptor* ini untuk setiap klik link bersyarat SPA maupun *submit form*!

---

## 4. Observabilitas dengan Global Exception Handler
Saat masuk *production* (`production: true`), `fullstuck.php` hanya menampilkan layar putih/error statis jika terjadi HTTP 500. Jika Anda ingin mengirim notifikasi spesifik (seperti Webhook Telegram/Slack) setiap kali terjadi *Fatal Error* atau masalah PDO, gunakan `fst_error_handler`.

**Solusi:**
```php
// Letakkan di ujung awal router.php atau via fitur "require" di config
fst_error_handler(function(Throwable $e) {
    // 1. Ekstrak info
    $msg = $e->getMessage();
    $file = $e->getFile();
    
    // 2. Kirim notifikasi darurat (contoh via Telegram)
    // send_telegram_alert("🚨 ERROR: $msg di $file");
    
    // Exception tidak dihentikan di sini, ia akan tetap merender statis HTTP 500
    // atau 'error_handlers' config bawaan FullStuck setelah callback ini selesai.
});
```

---

## 5. Mengelola Skema Database (Migrations)
Micro-framework ini tidak dilengkapi *migration engine* yang *bloated*. Jangan mengeksekusi DDL secara manual berulang-ulang di server *production*!

**Solusi:**
Buatlah file skrip khusus (misal `tools/migrate.php`) yang dieksekusi hanya lewat Terminal untuk meng-*upgrade* versi *schema* secara prosedural.

```php
// tools/migrate.php
require 'fullstuck.php'; // Load environment db

echo "Migrating DB...\n";

// Versi 1
fst_db('EXEC', "CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT)");
// Versi 2
fst_db('EXEC', "ALTER TABLE users ADD COLUMN email TEXT");

echo "Done.\n";
```
*Tip: Untuk skala Enterprise yang butuh migrasi up/down kompleks, pertimbangkan menggunakan library standar seperti [Phinx](https://phinx.org/) tanpa perlu menanamnya ke dalam core FullStuck.*

---

## 6. Strategi E2E / Feature Testing
Sulit melakukan pengujian murni (*Unit Testing*) fungsi-fungsi internal FullStuck karena ia beroperasi menggunakan ruang global monolitik.

**Solusi:** Lakukan pendekatan **Feature Testing** (menembak langsung ke Endpoint). Gunakan **Pest PHP** atau **Playwright** untuk mensimulasikan permintaan klien nyata.

Contoh Pengujian HTTP dengan ekstensi PHP sederhana:
```php
// test.php
$response = file_get_contents("http://localhost:8000/api/users");
if (!str_contains($response, "Budi")) {
    die("Test Failed: Budi not found in response.");
}
echo "Test Passed!";
```
Metode "*Outside-in*" ini jauh lebih efektif dan menjamin fungsionalitas keseluruhan aplikasi tanpa terganggu masalah manipulasi memori internal framework.

---

## 7. Quick Recipe: CRUD Sederhana (End-to-End)
Untuk melihat bagaimana semua konsep (Router, Validasi, Database, CSRF, Flash Message, dan DOM Templating) bekerja sama, ini adalah contoh alur utuh sebuah aplikasi "Tugas":

```php
// 1. Skema Database (Misal di tools/migrate.php)
fst_db('EXEC', "CREATE TABLE tasks (id INTEGER PRIMARY KEY, title TEXT, is_done INTEGER DEFAULT 0)");

// 2. Daftar Rute (di router.php)

// Tampilkan Daftar & Form
fst_get('/tasks', function() {
    $tasks = fst_db_select('tasks', [], ['order_by' => 'id DESC']);
    
    // Pesan Flash jika ada
    $alert = '';
    if (fst_flash_has('msg')) {
        $alert = ["div" => ["@class" => "alert success", "@text" => fst_flash_get('msg')]];
    }

    fst_template('BaseLayout', [
        "h1" => "Daftar Tugas",
        ".flash-container" => ["@html" => $alert],
        
        // Render List Tugas
        "ul.task-list" => ["@append" => array_map(function($t) {
            return ["li" => [
                "span" => e($t['title']),
                "form" => [
                    "@action" => "/tasks/delete/".$t['id'], "@method" => "POST",
                    "@style" => "display:inline; margin-left: 10px;",
                    "@append" => [ fst_csrf_field(), ["button" => "Hapus"] ]
                ]
            ]];
        }, $tasks)],
        
        // Render Form Tambah (Akan disuntik ke tag main atau penampung yang ada di BaseLayout)
        "form.add-task" => [
            "@action" => "/tasks", "@method" => "POST",
            "@append" => [
                fst_csrf_field(),
                ["input" => ["@type" => "text", "@name" => "title", "@placeholder" => "Tugas baru", "@required" => true]],
                ["button" => "Simpan"]
            ]
        ]
    ]);
});

// Proses Simpan Data
fst_post('/tasks', function() {
    fst_csrf_check(); // Validasi token keamanan
    
    // Validasi input
    $input = fst_validate($_POST, [ 'title' => 'required|min:3' ]);
    
    if (!$input['valid']) {
        // Jika gagal, kembalikan dengan error 400
        fst_abort(400, "Input tidak valid: " . $input['errors']['title'][0]);
    }
    
    // Simpan ke database
    fst_db_insert('tasks', ['title' => $input['data']['title']]);
    
    // Set Flash Message
    fst_flash_set('msg', 'Tugas berhasil ditambahkan!');
    
    // Redirect kembali
    fst_redirect('/tasks');
});

// Proses Hapus Data
fst_post('/tasks/delete/:id', function($params) {
    fst_csrf_check();
    fst_db_delete('tasks', ['id' => $params['id']]);
    fst_flash_set('msg', 'Tugas dihapus!');
    fst_redirect('/tasks');
});
```
*Dengan skrip ringkas ini, Anda sudah mendapatkan SSR CRUD super cepat tanpa load halaman jika digabungkan dengan agen SPA (`agent_js: true`)!*

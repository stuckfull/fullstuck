# 📚 API Reference

Dokumen ini menyediakan referensi lengkap untuk semua fungsi publik (API) yang tersedia di framework FullStuck, baik untuk backend (PHP) maupun frontend (`agent.js`).

---

## 🐘 PHP Backend API

Berikut adalah fungsi-fungsi pembantu (*helper*) PHP yang disediakan oleh core FullStuck. Sebagian besar fungsi diawali dengan prefiks `fst_`, kecuali fungsi `e()`.

### 1. Core & Konfigurasi

- **`fst_app($key = null, $value = null)`**
  Mengambil atau menyimpan state/variabel global aplikasi yang hidup selama satu siklus request.
- **`fst_config($key = null, $default = null)`**
  Mengambil nilai konfigurasi dari file `fullstuck.json`. Mendukung notasi titik (dot notation) misal: `fst_config('database.default')`.
- **`fst_is_dev()`**
  Mengembalikan `true` jika aplikasi sedang berjalan di environment lokal/development (berdasarkan ada tidaknya file `.fst_dev_mode`).
- **`fst_is_safe_to_debug()`**
  Mengembalikan `true` jika aplikasi berada di environment development, atau nilai konfigurasi `"production"` diset ke `false`.
- **`fst_log($level, $message, $context = [])`**
  Menulis pesan ke file log internal `.fst.log`. Level bisa berupa string seperti `'INFO'`, `'WARNING'`, `'ERROR'`.
- **`fst_error_handler(callable $callback)`**
  Mendaftarkan penangan error (error handler) kustom yang akan dieksekusi jika terjadi *Exception* atau *Fatal Error*.

### 2. Routing & Middleware

- **`fst_get($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP GET.
- **`fst_post($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP POST.
- **`fst_put($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP PUT.
- **`fst_patch($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP PATCH.
- **`fst_delete($path, $callback, $middleware = [])`**
  Mendaftarkan rute untuk HTTP DELETE.
- **`fst_any($path, $callback, $middleware = [])`**
  Mendaftarkan rute yang menerima metode HTTP apapun (Universal Route).
- **`fst_group($prefix, $callback, $middleware = [])`**
  Mengelompokkan rute dengan prefiks URL dan middleware bersama.
- **`fst_abort($code, $message = '')`**
  Menghentikan eksekusi dan menampilkan halaman error HTTP sesuai `$code` (mendukung integrasi dengan `error_handlers` di config).

### 3. HTTP Request & Response

- **`fst_uri()`**
  Mengembalikan URI dari request saat ini (tanpa menyertakan query string).
- **`fst_method()`**
  Mengembalikan metode HTTP dari request (misal: `GET`, `POST`, `PUT`, `DELETE`).
- **`fst_input($key, $default = null)`**
  Mendapatkan nilai parameter request (dari `$_POST`, `$_GET`, atau payload JSON) berdasarkan `$key`.
- **`fst_request()`**
  Mengembalikan seluruh payload data request dalam bentuk array asosiatif.
- **`fst_file($key)`**
  Mendapatkan array informasi file hasil unggahan (dari `$_FILES`) yang berhasil di-upload.
- **`fst_redirect($url, $code = 302, $allow_external = false)`**
  Mengalihkan (redirect) pengguna ke URL yang diberikan.
- **`fst_json($data, $status = 200)`**
  Mengembalikan respon HTTP berformat JSON dan segera menghentikan eksekusi script.
- **`fst_text($string, $status = 200)`**
  Mengembalikan respon HTTP berformat plain-text dan segera menghentikan eksekusi script.
- **`fst_status_code($code)`**
  Mengatur kode status HTTP untuk respons (misal 200, 404, 500).

### 4. Database (Query Builder)

- **`fst_db($mode, $sql, $params = [], $connection = null)`**
  Mengeksekusi *raw query* SQL. `$mode` dapat bernilai: `'ALL'` (banyak baris), `'ROW'` (1 baris), `'SCALAR'` (1 nilai tunggal), `'EXEC'` (menjalankan command, mengembalikan affected rows/last insert id).
- **`fst_db_select($table, $conditions = [], $options = [])`**
  Mengambil banyak baris dari `$table` sesuai array asosiatif `$conditions` (mendukung operator otomatis). Opsi `$options` bisa berisi `['order_by' => '...', 'limit' => 10]`.
- **`fst_db_row($table, $conditions = [], $options = [])`**
  Sama seperti select, namun hanya mengembalikan baris pertama (satu array asosiatif).
- **`fst_db_exists($table, $conditions = [], $options = [])`**
  Mengembalikan nilai `true` jika minimal ada satu baris yang memenuhi `$conditions`.
- **`fst_db_insert($table, $data, $options = [])`**
  Menyisipkan sebuah baris baru dengan pasangan key-value pada `$data`.
- **`fst_db_update($table, $data, $conditions = [], $options = [])`**
  Memperbarui baris-baris pada tabel yang memenuhi `$conditions` menggunakan nilai-nilai di `$data`.
- **`fst_db_delete($table, $conditions, $options = [])`**
  Menghapus baris-baris yang memenuhi `$conditions`.
- **`fst_db_begin($connection = null)`**, **`fst_db_commit($connection = null)`**, **`fst_db_rollback($connection = null)`**
  Membuka, mengonfirmasi (commit), atau membatalkan (rollback) *Database Transaction*.
- **`fst_db_quote_ident($name, $connection = null)`**
  Melindungi/meng-quote nama tabel atau kolom secara aman sesuai driver DB (misal mem-backtick ``` `table` ``` di MySQL).

### 5. Templating & View

- **`fst_view($path, $data = [])`**
  Melakukan *require* ke file PHP biasa (`$path`) dan mengekstrak array `$data` agar menjadi variabel lokal. (Hanya untuk Legacy Template).
- **`fst_partial($path, $data = [])`**
  Sama seperti `fst_view()`, berguna sebagai penanda (semantic alias) untuk file potongan tampilan.
- **`fst_view_share($key, $value = null)`**
  Berbagi variabel global agar dapat diakses oleh semua file view/template.
- **`fst_template(string $templatePath, array $data, array $rules, ?string $cacheDir = null, bool $forceRebuild = false): void`**
  Merender file HTML Murni ke browser menggunakan FullStuck Template Compiler berbasis Array Ruleset (Cara Modern).
- **`fst_template_render(string $templatePath, array $data, array $rules, ?string $cacheDir = null, bool $forceRebuild = false): string`**
  Sama seperti `fst_template`, tetapi mengembalikan kode HTML hasil render dalam bentuk tipe data String (sangat berguna untuk menyuntikkan template ke dalam sub-komponen layout parent).
- **`fst_serve_static_file($file_path)`**
  Menyajikan isi sebuah file statis ke klien langsung.

### 6. Session & Flash Message

- **`fst_session_set($key, $value)`**, **`fst_session_get($key, $default = null)`**, **`fst_session_forget($key)`**
  Pengelolaan nilai sesi (`$_SESSION`) user saat ini.
- **`fst_flash_set($key, $message)`**, **`fst_flash_get($key, $default = null)`**, **`fst_flash_has($key)`**
  Pengelolaan data `Flash Message` yang hanya hidup 1 kali. Jika di-get, data akan otomatis terhapus untuk request selanjutnya (berguna untuk notifikasi "Berhasil Disimpan").

### 7. Security, Upload & Utility

- **`fst_escape($str)`** / **`e($str)`**
  Sterilisasi string mencegah Cross-Site Scripting (XSS). Disarankan setiap kali mencetak output dari user.
- **`fst_csrf_token()`**
  Men-generate (jika belum ada) dan mengembalikan string raw token CSRF pengguna saat ini.
- **`fst_csrf_field()`**
  Mengembalikan tag elemen `<input type="hidden" name="_token" value="...">` yang berisi Token CSRF.
- **`fst_csrf_check()`**
  Memvalidasi kecocokan token dari request (via POST/_token atau Header) dengan sesi. Langsung abort `403` jika gagal (Wajib digunakan di semua handler rute perubah data).
- **`fst_upload($key, $folder, $options = [])`**
  Fungsi utilitas pintar untuk memindahkan dan memvalidasi file unggahan.
- **`fst_validate($data, $rules)`**
  Memvalidasi associative array input berdasar format tertentu (seperti `'nama' => 'required|min:3'`). Mengembalikan `['valid' => bool, 'errors' => array, 'data' => array]`.
- **`fst_dump(...$vars)`** / **`fst_dd(...$vars)`**
  Mencetak output terstruktur (menggunakan `var_dump`) ke layar untuk keperluan debugging. `fst_dd()` langsung memanggil `die()` sesudahnya.

### 8. Fragment Routing (Khusus Backend)

- **`fst_is_fragment_request(): bool`**
  Menandakan apakah request HTTP masuk via Javascript FST Agent (bukan Full Page Reload).
- **`fst_fragment_target(): string`**
  Mengembalikan *CSS Selector Target* (misal `#app` atau `body`) yang dicari oleh FST Agent.
- **`fst_extract_html_fragment($html, $selector = 'body')`**
  Fungsi utilitas untuk memotong HTML besar dan hanya mengambil bagian dalam/inner HTML dari selector yang ditentukan.

---

## ⚡ FST Agent API (Client-Side / `agent.js`)

Jika FST Agent dinyalakan (`"agent_js": true`), objek utama dapat diakses di sisi klien browser melalui **`window.fst`**.

### 1. Navigasi & Fragment Fetch

- **`fst.go(url, options = {})`**
  Fungsi serbaguna untuk memaksa navigasi fragment secara programmatik (tanpa klik `<a>`).
  Opsi `options` mendukung *property*:
  - `target` (string selector DOM)
  - `history` (boolean - simpan ke browser history?)
  - `scroll` (string/boolean - `'smooth'`, `'instant'`, atau `false`)
  - `indicator` (string class untuk ditambahkan ke target ketika dimuat)
  - `cache` (boolean - paksa simpan/jangan simpan cache fragment)

- **`fst.fetchFragment(url, targetSelector, pushHistory, triggerElement = null, isPopstate = false)`**
  API internal tingkat bawah untuk sekadar mengambil HTML dari server ke *Selector* spesifik. Berguna jika butuh logic asinkron (Lebih disarankan menggunakan `fst.go`).

### 2. Client-Side Routing Configuration

- **`fst.set(pattern, callback)`**
  Menentukan fungsi rute statis/dinamis yang berjalan eksklusif di Frontend JS.
  - Parameter URL akan diekstrak otomatis: `fst.set('/user/:id', (params) => { ... })`.
- **`fst.group(prefix, callback)`**
  Mengelompokkan deklarasi `fst.set()` agar menempel di belakang rute awalan (*prefix*).
- **`fst.matchRoute(path)`**
  Menyocokkan *path* URL dengan kumpulan rute yang telah diregistrasi.

### 3. Hooks & Interceptors (Mencegat Request)

- **`fst.setInterceptor(callback)`**
  Menyisipkan logika *Fetch API*. Sangat bermanfaat untuk menyelipkan Custom Headers, Authorization Token (JWT), dll. Callback menerima argumen `(url, fetchOptions)` dan **harus** mengembalikan obejct `fetchOptions` utuh, atau tidak mengembalikan apapun (falsy) jika modifikasi dilakukan langsung via referensi.
  ```javascript
  fst.setInterceptor(async (url, options) => {
      options.headers['X-Timezone'] = Intl.DateTimeFormat().resolvedOptions().timeZone;
      return options;
  });
  ```
- **`fst.setBefore(callback)`**
  Hook pre-navigasi. Callback menerima target `path`. Jika mengembalikan `false`, FST Agent akan menghentikan navigasi SPA (bisa digunakan untuk *Confirm Dialog/Unsaved Changes warning*).
- **`fst.setAfter(callback)`**
  Hook yang dipanggil seketika *setelah* route callback lokal (`fst.set`) dieksekusi.
- **`fst.setNotFound(callback)`**
  Meng-override perlakuan default jika URL dipanggil namun tidak ada rute frontend yang cocok (Bawaannya, dia akan fallback *fetching* fragmen dari server).

### 4. Utility Security

- **`fst.escape(str)`** / **`fst.e(str)`**
  Membersihkan/men-sanitize nilai string dari karakter-karakter spesial HTML (`&`, `<`, `>`, `"`, `'`) sebelum di-inject ke properti `innerHTML` (Menghindari kerentanan DOM-based XSS).

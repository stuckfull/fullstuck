## 🛠️ 6. Action & Headless API

File `action.php` menangani logika mutasi data. Fokus ke PHP murni:

```php
<?php
// app/users/action.php
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $val = fst_validate(fst_request(), [
        'nama' => 'required|min:3',
        'email' => 'required|email'
    ]);
    if (!$val['valid']) {
        fst_json(['errors' => $val['errors']], 422);
    }
    $id = fst_db_insert('users', $val['data']);
    fst_redirect('/admin/users');
}

if ($method === 'DELETE') {
    $id = fst_input('id');
    fst_db_delete('users', ['id' => $id]);
    fst_json(['success' => true]);
}
```

### Method Spoofing (Form HTML)
Karena elemen `<form>` HTML standar hanya mendukung metode `GET` dan `POST`, Anda dapat melakukan *spoofing* untuk metode lain (seperti `PUT` atau `DELETE`) dengan menyisipkan input tersembunyi bernama `_method`:
```html
<form method="POST" action="/admin/users">
    <input type="hidden" name="_method" value="DELETE">
    <input type="hidden" name="id" value="5">
    <button type="submit">Hapus</button>
</form>
```
Fungsi `fst_method()` di sisi server akan otomatis membaca nilai `_method` ini sebagai metode *request* aktual.

### Error Handling
```php
fst_abort(404, "User tidak ditemukan");
```
- Request ke `/api/*` atau dengan header `Accept: application/json` → response JSON otomatis.
- Request biasa → framework mencari file `404.fst.php` dari folder error naik ke `app/`.

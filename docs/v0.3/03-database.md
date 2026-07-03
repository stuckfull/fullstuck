# 🗄️ Database & Query Builder

Gunakan key `connection` untuk pindah database sesuai konfigurasi di `fullstuck.json`.

> **Advanced Operators:** Query Builder telah diinjeksi dengan parser khusus yang mendukung penambahan operator langsung pada *key* kondisi (seperti `=`, `!=`, `<`, `>`, `<=`, `>=`, `LIKE`, `NOT LIKE`, `IS`, dan `IS NOT`).

### Select, Insert, Update, Delete
```php
$users = fst_db_select('users', ['status' => 'active'], ['order_by' => 'id DESC']);

// Menggunakan operator lanjutan (Advanced Operators)
$products = fst_db_select('products', [
    'status' => 'active',
    'price >' => 50000,
    'name LIKE' => '%baju%'
]);

fst_db_insert('users', ['nama' => 'Budi', 'email' => 'budi@a.com']);
fst_db_update('users', ['status' => 'inactive'], ['id' => 5]);
fst_db_delete('users', ['id' => 5]);
```

### Raw Query (`fst_db`)
Mendukung 4 mode kembalian: `'ALL'` (Array of arrays), `'ROW'` (Single associative array), `'SCALAR'` (Primitive value), `'EXEC'` (Affected rows & Last insert ID).

```php
$posts = fst_db('ALL', "SELECT p.*, u.nama FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = ?", ['published']);
$user = fst_db('ROW', "SELECT id, nama FROM users WHERE email = ? LIMIT 1", ['budi@a.com']);
$total = fst_db('SCALAR', "SELECT COUNT(*) FROM users WHERE status = ?", ['active']);
fst_db('EXEC', "UPDATE users SET last_login = NOW() WHERE id = ?", [1]);
```

### Transactions
```php
try {
    fst_db_begin();
    fst_db_insert('users', ['nama' => 'A']);
    fst_db_update('saldo', ['jumlah' => 0], ['id' => 1]);
    fst_db_commit();
} catch (Exception $e) {
    fst_db_rollback();
}
```

# 🗄️ Database & Query Builder

Gunakan key `connection` untuk pindah database sesuai konfigurasi di `fullstuck.json`.

> **Batasan Query Builder:** `fst_db_select()`, `fst_db_update()`, dll hanya mendukung kondisi `AND` dengan operator `=`. Untuk query lebih kompleks (seperti `OR`, `LIKE`, `>`, `IN`), gunakan **Raw Query** (`fst_db()`).

### Select, Insert, Update, Delete
```php
$users = fst_db_select('users', ['status' => 'active'], ['order_by' => 'id DESC']);
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

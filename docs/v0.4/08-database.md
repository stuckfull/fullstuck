## 🗄️ 8. Database

Fungsi `fst_db_*` adalah wrapper tipis di atas PDO. **Bukan ORM.** Mendukung SQLite, MySQL, dan PostgreSQL.

### CRUD Dasar
```php
// Select
$users = fst_db_select('users', ['status' => 'active'], [
    'order_by' => 'id DESC',
    'limit' => 10
]);

// Select dengan operator
$products = fst_db_select('products', [
    'price >' => 50000,
    'name LIKE' => '%baju%'
]);

// Satu baris
$user = fst_db_row('users', ['id' => 1]);

// Cek keberadaan
$exists = fst_db_exists('users', ['email' => 'budi@mail.com']);

// Insert (return last_id)
$id = fst_db_insert('users', ['nama' => 'Budi', 'email' => 'budi@mail.com']);

// Update (return affected_rows)
fst_db_update('users', ['status' => 'inactive'], ['id' => 5]);

// Delete (return affected_rows)
fst_db_delete('users', ['id' => 5]);
```

### Raw Query
Untuk query kompleks (JOIN, COUNT, GROUP BY), gunakan `fst_db()`:
```php
// Mode: 'ALL' (banyak baris), 'ROW' (1 baris), 'SCALAR' (1 nilai), 'EXEC' (eksekusi)
$posts = fst_db('ALL', 
    'SELECT p.*, u.nama FROM posts p JOIN users u ON p.user_id = u.id WHERE p.status = ?', 
    ['published']
);
$total = fst_db('SCALAR', 'SELECT COUNT(*) FROM users WHERE status = ?', ['active']);
```

### Transactions
```php
try {
    fst_db_begin();
    fst_db_insert('orders', ['total' => 100000]);
    fst_db_update('stock', ['qty' => 0], ['id' => 1]);
    fst_db_commit();
} catch (Exception $e) {
    fst_db_rollback();
}
```

### Multi-Connection
Semua fungsi database mendukung parameter `$connection` atau opsi `['connection' => 'nama']`:
```php
$remote = fst_db('ALL', 'SELECT * FROM logs', [], 'mysql_remote');
$users = fst_db_select('users', [], ['connection' => 'mysql_remote']);
```

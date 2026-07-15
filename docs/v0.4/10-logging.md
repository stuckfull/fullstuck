## 🧾 10. Logging & Error Handling

### Logging
```php
fst_log('INFO', 'Pembayaran berhasil.', ['invoice' => 'INV-001']);
fst_log('ERROR', 'Koneksi gagal.', ['endpoint' => $url]);
```
Log ditulis ke file `.fst.log` dalam format JSON per baris.

### Error Handler Kustom
Daftarkan callback untuk mengirim notifikasi (Telegram, Slack, dll) saat error terjadi:
```php
// Letakkan di globals/error_handler.php
fst_error_handler(function(Throwable $e) {
    $msg = $e->getMessage();
    // send_telegram_alert("🚨 ERROR: $msg");
});
```

### Mode Production vs Development
| Aspek | Development | Production |
|---|---|---|
| Error display | Stack trace visual di browser | Pesan generik, detail di `.fst.log` |
| Cache view | Re-compile jika file berubah | Langsung dimuat tanpa cek |
| Cache router | Re-scan jika folder `app/` berubah | Terkunci, tidak pernah re-scan |

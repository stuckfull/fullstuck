# 📝 Logging & Error Handling

FullStuck v0.3.0 memusatkan seluruh *output error* dan *logging* pada file di dalam root direktori: `.fst.log`. 

- Jika `"production": true` dalam file `fullstuck.json`, pesan *Exception* PHP (*stack trace* yang rawan mengekspos letak path server) akan di-*mute* di layar pengguna (menjadi pesan error 500 generik) dan detail lengkapnya akan dicatat di `.fst.log`.
- Format `.fst.log` ditulis baris demi baris dalam sintaks **JSON**, mempermudah pencarian/filtrasi log bagi developer.

### Manual Logging API
Anda dapat menggunakan fungsi `fst_log` secara global:
```php
fst_log('INFO', 'Pembayaran berhasil dikonfirmasi.', ['invoice' => 'INV-001']);
fst_log('ERROR', 'Koneksi ke pihak ketiga gagal.', ['endpoint' => $api_url]);
```

# 🚀 Welcome to FullStuck.php

**Micro-framework PHP satu-file untuk membangun Web App dan REST API secepat kilat.**

FullStuck.php menggabungkan kesederhanaan PHP klasik dengan kenyamanan pengembangan modern. Dirancang khusus untuk skenario *Fast Prototyping*, *Shared Hosting*, dan kolaborasi dengan **AI Coding Assistant**.

---

## 💡 Filosofi Utama

### 📦 Distribusi Satu File
Lupakan folder `vendor/` yang berat atau konfigurasi server yang rumit. FullStuck membungkus routing, database, view, dan auth ke dalam satu file `fullstuck.php`.

### ⚡ Zero-Config SPA
Nikmati pengalaman aplikasi Single Page Application (SPA) yang mulus hanya dengan menambahkan atribut HTML, tanpa perlu framework JavaScript tambahan seperti React atau Vue.

### 🤖 Dioptimalkan untuk AI (Vibe Coding)
Kami percaya masa depan *coding* adalah kolaborasi dengan AI. Dokumentasi FullStuck sengaja dibuat dalam format satu-file utuh agar AI dapat memahami seluruh kemampuan framework tanpa harus melakukan *crawling* ke banyak halaman.

---

## 🛠️ Contoh Kode (DOM Templating v0.2.0)

```php
// router.php
fst_get('/', function() {
    $data = ['name' => 'World'];
    $rules = ['h1 span' => '$name'];
    return fst_template('views/welcome.html', $data, $rules);
});
```

```html
<!-- views/welcome.html -->
<h1>Hello, <span>Placeholder</span>!</h1>
<p>Dijalankan dengan FullStuck v0.2.0</p>
```

---

## 📖 Arsip Dokumentasi

Silakan pilih versi dokumentasi yang sesuai dengan proyek Anda:

| Versi | Status | Link Dokumentasi |
|---|---|---|
| **v0.2.0** | **Stable (Latest)** | 📖 **[Lihat Dokumentasi v0.2.0](v0.2.0.md)** |
| **v0.1.0** | **Legacy** | 📖 **[Lihat Dokumentasi v0.1.0](v0.1.0.md)** |

---

## 📅 Riwayat Perubahan (Changelog)

Untuk melihat daftar lengkap fitur baru, perbaikan bug, dan perubahan versi, silakan baca file **[CHANGELOG.md](https://github.com/milio48/fullstuck/blob/main/CHANGELOG.md)** yang berada di *root* repository.

---

## 🏗️ Referensi Lainnya

- 🏗️ **[Arsitektur Framework](ARCHITECTURE.md)**: Memahami konsep modularitas dan proses kompilasi.
- 🔌 **[Panduan Pembuatan Plugin](PLUGIN_DEVELOPMENT.md)**: Cara memperluas framework dengan plugin.
- 🤝 **[Panduan Kontribusi](../CONTRIBUTING.md)**: Cara membantu pengembangan core FullStuck.

---
[Kembali ke GitHub Repository](https://github.com/milio48/fullstuck)

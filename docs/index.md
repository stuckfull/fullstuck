---
layout: home

hero:
  name: "FullStuck.php"
  text: "Zero-Config PHP Framework"
  tagline: "Micro-framework satu-file untuk membangun Web App & REST API secepat kilat, ramah AI, dan built-in SPA."
  actions:
    - theme: brand
      text: Mulai Cepat v0.2.0
      link: /v0.2.0
    - theme: alt
      text: Panduan AI Setup
      link: /ai-setup
    - theme: alt
      text: GitHub Repo
      link: https://github.com/stuckfull/fullstuck

features:
  - icon: 📦
    title: Distribusi Satu File
    details: Cukup satu file fullstuck.php. Bebas dari folder vendor/ yang berat, Composer, atau konfigurasi server rumit.
  - icon: ⚡
    title: Zero-Config SPA
    details: Navigasi instan dengan Fragment Rendering dan History Caching tanpa menulis JavaScript tambahan.
  - icon: 🎨
    title: DOM-Based Templating
    details: File HTML Anda 100% murni tanpa tag PHP. Logika injeksi dikelola secara aman dan terpusat di PHP.
  - icon: 🤖
    title: AI-Agent Friendly
    details: Dokumentasi terkonsolidasi satu-file dirancang khusus untuk meminimalkan token context asisten AI Anda.
  - icon: 🎛️
    title: Built-in Admin Dashboard
    details: Panel admin modern di /stuck untuk edit konfigurasi, memantau integritas file, dan satu-klik instal plugin.
  - icon: 🔒
    title: Hardened Security
    details: Proteksi CSRF otomatis, sanitasi path traversal, session fixation protection, dan whitelist IP bawaan.
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

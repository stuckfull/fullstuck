---
layout: home

hero:
  name: "FullStuck.php"
  text: "Path-Based Colocation in a Single File"
  tagline: "Pengalaman Next.js App Router, kesederhanaan HTMX, sintaks Blade — semuanya dalam micro-framework PHP 1 file."
  actions:
    - theme: brand
      text: Mulai Cepat v0.4.0
      link: /v0.4/01-getting-started
    - theme: alt
      text: Panduan AI Setup
      link: /ai-setup
    - theme: alt
      text: GitHub Repo
      link: https://github.com/stuckfull/fullstuck

features:
  - icon: 📁
    title: Path-Based Colocation
    details: Struktur URL Anda = Struktur folder Anda. Tulis UI dan Logika berdampingan tanpa kerumitan router.php.
  - icon: 📦
    title: Distribusi Satu File
    details: Cukup satu file fullstuck.php. Bebas dari folder vendor/ yang berat, Composer, atau konfigurasi server rumit.
  - icon: ⚡
    title: Zero-Config SPA (FST-Agent)
    details: Navigasi perpindahan halaman secepat kilat tanpa full-page reload. HTML-over-the-wire bawaan.
  - icon: 🎨
    title: Blade-like Templating
    details: Tulis view Anda menggunakan file .fst.php dengan sintaks intuitif <code v-pre>{{ $var }}</code>, @foreach, dan @component.
  - icon: 🤖
    title: Zero-Grep AI Architecture
    details: Dokumentasi dan struktur folder dirancang khusus untuk meminimalkan token context asisten AI (Vibe Coding).
  - icon: 🔒
    title: Matryoshka Security
    details: Keamanan berlapis hierarkis. Lindungi sebuah folder dengan _guard.php, dan seluruh isinya otomatis aman.
---

## 💡 Filosofi Utama

### 📁 Zero-Grep Architecture (Colocation)
Lupakan membuang waktu mencari file *Controller*, *View*, dan *Router* yang tersebar di mana-mana. Di FullStuck v0.4, **File Logika** (`action.php`) dan **File UI** (`content.fst.php`) selalu hidup di folder yang sama. AI Anda tidak akan pernah kebingungan mencari konteks!

### ⚡ SPA Tanpa Javascript
Nikmati pengalaman aplikasi *Single Page Application* (SPA) yang mulus berkat **FST-Agent**. Framework secara otomatis menangkap perpindahan link dan *form submission* untuk melakukan injeksi DOM dengan halus, lengkap dengan fitur history API.

### 🚫 Anti-OOP (Procedural First)
Framework ini didesain 100% menggunakan fungsi prosedural (`fst_*`). Kode menjadi lebih mudah dibaca, dilacak, dan di-*debug* secara linear, tanpa abstraksi *Object-Oriented Programming* yang menyembunyikan *state*.

---

## 🛠️ Contoh Kode (v0.4.0)

Semuanya berbasis folder. Tidak ada file `router.php`!

```text
app/
└── blog/
    ├── action.php        → Logika HTTP GET & POST
    └── content.fst.php   → File View HTML
```

**Logika: `app/blog/action.php`**
```php
<?php
// Tangani request berdasarkan HTTP method
if (fst_is_get()) {
    $posts = fst_db()->table('posts')->get();
    return ['posts' => $posts]; // Data dikirim ke View
}

if (fst_is_post()) {
    $title = fst_input('title', 'required');
    fst_db()->table('posts')->insert(['title' => $title]);
    return fst_redirect('/blog');
}
```

**Tampilan: `app/blog/content.fst.php`**
```html
@layout('app') <!-- Memanggil app/_layout.fst.php -->

<h1>Daftar Blog</h1>

<!-- Data $posts otomatis tersedia dari action.php -->
<ul>
    @foreach($posts as $post)
        <li>{{ $post['title'] }}</li>
    @endforeach
</ul>

<form method="POST">
    <input type="text" name="title" required>
    <button type="submit">Simpan</button>
</form>
```

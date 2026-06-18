# Fullstuck DOM Templating Engine

Sebuah mesin templating DOM ultra-minimalis, prosedural, dan tanpa dependensi tambahan (100% Vanilla PHP 8) untuk merender HTML statis menjadi dinamis. Engine ini memisahkan logika backend dan tampilan frontend secara mutlak tanpa menggunakan tag spesial bawaan (`{{ }}`) di sisi HTML.

## ⚙️ Behaviour & Cara Kerja

Mesin ini menggunakan arsitektur **JIT (Just-In-Time) Caching** yang berfokus pada kecepatan dan keamanan:
1. **Pemeriksaan Cache**: Saat `render_template()` dipanggil, sistem akan mengecek keberadaan file `.php` hasil kompilasi di folder `build-template/`. 
2. **Validasi Waktu**: Jika file cache sudah ada, sistem membandingkan `filemtime` (waktu modifikasi) file sumber `.html` dengan file cache. Kompilasi ulang (rebuild) **hanya** terjadi apabila file HTML lebih baru dari modifikasi cache terakhir.
3. **Parsing DOM Cerdas**: HTML di-load secara aman via native `DOMDocument` dengan *fallback* standar UTF-8 (`<?xml encoding="utf-8" ?>`).
4. **Manipulasi Marker**: Berdasarkan instruksi dari *Ruleset DSL*, compiler tidak merusak struktur DOM aslinya secara gegabah. Alih-alih mengeksekusi PHP secara langsung, script akan meletakkan *marker unik* (misal: `@@__FST_MARKER_1__@@`), men-dump struktur DOM final menjadi format string teks (via `saveHTML()`), lalu menukar semua *marker* tersebut dengan skrip asli PHP seperti `<?= htmlspecialchars(...) ?>`. Hal ini menjamin file yang dihasilkan tidak memicu *parser error* ketika ada tag aneh yang di-inject.
5. **Konverter CSS Ketat (Strict CSS2XPath)**: Secara *native*, engine mengonversi sintaks CSS (id, class, child, sibling, attribute) ke dalam ekuivalensi `XPath`. Untuk menjaga sistem tidak error dari regex yang berat, sistem dilengkapi algoritma *whitelist* CSS. Pseudo-class seperti (`:hover`, `:nth-child()`) dan sibling complex selectors secara aman akan dihapus/diterjemahkan sebagai *XPath* buntu (blacklist).

### ⚡ Performa JIT (Just-In-Time) Caching
Mesin templating pada umumnya selalu mem-parsing sintaks setiap kali halaman diakses (CPU intensive). Engine ini memecahkan masalah tersebut dengan bertumpu pada *file statis PHP*:
- **Pemeriksaan 0-Overhead**: Engine mengandalkan perintah native `filemtime($templatePath) > filemtime($cacheFile)`. Selama file sumber HTML belum diubah secara fisik, blok logika parser DOM dan XPath **tidak akan pernah** dieksekusi.
- **Eksekusi Sekilat Native PHP**: Jika file HTML tidak berubah, sistem hanya me-*require* file `.php` hasil kompilasinya saja. Performa eksekusi rendering Anda akan dijamin 100% sama dengan mengeksekusi script native PHP prosedural biasa!
- **Kustomisasi Direktori Cache**: Secara default, *engine* akan mengumpulkan *cache* ke dalam folder `build-template/`. Anda dapat bebas mengatur lokasi folder ini dengan menyisipkan parameter opsional `$cacheDir` pada argumen keempat pemanggilan fungsi `render_template()`.

### 🛡️ Keamanan (Security)
Keamanan adalah nyawa bagi sistem templating yang merender data secara dinamis. Engine ini sudah memblokir celah serangan fatal:
- **Proteksi XSS (Cross-Site Scripting)**: Tidak peduli apakah itu isi teks tag atau properti *attribute*, engine akan selalu membungkus hasil kompilasi ke dalam perintah aman `<?= htmlspecialchars($var ?? '', ENT_QUOTES, 'UTF-8') ?>`. Tag HTML liar (seperti `<script>`) akan ter-encode secara sempurna dan aman di browser.
- **Pencegahan LFI (Local File Inclusion)**: Proses *rendering* (require/extract) dilakukan di *scope* lokal function. Nama dari file *cache* yang dieksekusi telah melewati tahapan filter parsial menggunakan `basename($templatePath)`. Sehingga, percobaan serangan Path Traversal via parameter file eksternal (seperti merender `../../../etc/passwd`) akan dipotong dan ditolak mentah-mentah.

---

## 📋 Cheatsheet Sintaks

### 1. Daftar Logic Directive
| Directive | Penjelasan | Contoh Penggunaan |
|---|---|---|
| `"selector" => '...'` | **Shorthand Text**: Menimpa *nodeValue* elemen langsung. Aman dari XSS. | `"h1" => '$title'` |
| `"@text" => '...'` | **Safe Text**: Menimpa isi teks secara aman (sama dengan shorthand, namun dipakai bila ada properti/atribut lain di blok yang sama). | `"@text" => '$desc'` |
| `"@html" => '...'` | **Raw HTML**: Merender teks langsung sebagai HTML asli. **Peringatan: Bypasses XSS Protection**. Cocok untuk WYSIWYG. | `"@html" => '$content'` |
| `"@foreach" => '...'` | **Looper**: Mengulangi elemen DOM beserta *child*-nya untuk data *Array*. Menghapus elemen duplikat otomatis. | `"@foreach" => '$items as $i'` |
| `"[attr]" => '...'` | **Attribute**: Menimpa nilai *attribute* HTML (contoh `href`, `src`, `class`, `data-*`). | `"[href]" => '$url'` |

### 2. Daftar Selektor CSS
Secara bawaan (*native*), *compiler* mengubah selektor CSS menjadi XPath. Untuk menjaga efisiensi dan mencegah *crash*, sistem *whitelist* ketat diberlakukan.

✅ **Didukung Penuh (Whitelist):**
- Tag Selector: `h1`, `div`, `article`
- Class & ID: `.class`, `#id`
- Compound Selector: `article.post-item`, `div#container`
- Descendant (Spasi): `.container h2`
- Direct Child (`>`): `ul > li`
- Attribute Bracket: `input[name="username"]`, `a[href]`, `[data-id="5"]`
- *Escape Hatch* XPath Murni: `//div`, `.//span` (jika selektor berawalan `//` atau `.//`)

❌ **Ditolak / Diabaikan (Blacklist):**
- Pseudo-classes: `:hover`, `:focus`, `:nth-child()`, `:not()`
- Pseudo-elements: `::before`, `::after`, `::placeholder`
- Sibling Combinators: `+` (Adjacent Sibling), `~` (General Sibling)
*(Catatan: Jika compiler mendeteksi karakter blacklist, pencarian akan secara aman diredam/dihentikan pada scope tersebut tanpa menyebabkan error parsial)*.

### 3. Logika Perbandingan (Vanilla JavaScript)
Untuk mempermudah konseptualisasi, penulisan *ruleset* pada dasarnya ekuivalen dengan operasi DOM pada Vanilla JavaScript:

```php
$rules = [
    // Menimpa isi teks (menggunakan String Shorthand)
    "title" => 'Isi Title Baru', 
    // Sama dengan: document.querySelector("title").innerText = "Isi Title Baru";

    // Menimpa isi teks (menggunakan Logic Directive spesifik)
    "h3" => ["@text" => 'Subjudul Halaman'],
    // Sama dengan: document.querySelector("h3").innerText = "Subjudul Halaman";

    // Menyuntikkan Raw HTML (Bypass XSS protection)
    "span" => ["@html" => '<strong>Teks Tebal</strong>'],
    // Sama dengan: document.querySelector("span").innerHTML = "<strong>Teks Tebal</strong>";

    // Menimpa teks pada elemen ber-atribut (BUKAN mengubah atributnya)
    "a[href]" => 'Teks Link Baru',
    // Sama dengan: document.querySelector("a[href]").innerText = "Teks Link Baru";

    // Menimpa/menambah nilai pada atribut
    "a" => ["[href]" => 'https://example.com'],
    // Sama dengan: document.querySelector("a").setAttribute("href", "https://example.com");

    // Menambah atribut spesifik/custom
    "a" => ["[data-custom]" => '123']
    // Sama dengan: document.querySelector("a").setAttribute("data-custom", "123");
];
```

---

## 🚀 Cara Penggunaan

Panggil `render_template()` dengan mencantumkan path dokumen HTML sumber, susunan *data dinamis*, dan *ruleset* pemetaannya.

### 1. Struktur File HTML (`blog-list.html`)
HTML Anda harus bersih, 100% statis tanpa sisipan tag PHP apa pun.

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Judul Placeholder</title>
</head>
<body>
    <div id="blog-container">
        <!-- Item di bawah ini akan bertindak sebagai template iterasi -->
        <article class="post-item">
            <h2>Judul Sementara</h2>
            <p>Ringkasan Sementara</p>
            <a href="#" title="placeholder">Baca selengkapnya</a>
        </article>
        
        <!-- Item dummy di bawah ini otomatis akan di-hapus dan dibersihkan oleh @foreach -->
        <article class="post-item">
            <h2>Judul 2</h2>
            <p>Ringkasan 2</p>
            <a href="#">Baca</a>
        </article>
    </div>
</body>
</html>
```

### 2. Implementasi Backend (`index.php`)

```php
<?php
require 'compiler.php';

// 1. Siapkan Data yang akan dirender (Bisa bersumber dari pemanggilan DB)
$data = [
    'pageTitle' => 'Eksperimen DOM Templating Deklaratif',
    'blogs' => [
        [
            'title' => 'Vibe Coding', 
            'summary' => 'Sangat menyenangkan...',
            'url' => 'https://example.com/vibe'
        ]
    ]
];

// 2. Tentukan Aturan Injeksi CSS (DSL)
$rules = [
    "title" => '$pageTitle',
    "article.post-item" => [
        "@foreach" => '$blogs as $blog',
        "h2" => '$blog["title"]',
        "p"  => '$blog["summary"]',
        "a"  => [
            "[href]"  => '$blog["url"]',
            "[title]" => '$blog["title"]'
        ]
    ]
];

// 3. Render
render_template(__DIR__ . '/blog-list.html', $data, $rules);
```

---

## 📐 Ruleset Syntax (DSL)

API engine ini menggunakan konsep **Declarative Nested Scope**, di mana Anda memetakan elemen parent sebagai _kunci_ (key), dan daftar instruksinya di dalam parameter sub-array.

Sintaks yang didukung adalah sebagai berikut:

### 1. Text Injection (Shorthand)
Digunakan untuk menimpa konten teks (_inner text_) dari elemen yang dipilih. Nilai dari instruksinya harus berupa **String** yang merepresentasikan pemanggilan variabel/array di PHP.
Semua string akan otomatis dilindungi menggunakan mekanisme XSS Escape (`htmlspecialchars(..., ENT_QUOTES)`).

```php
"h1.title" => '$titleVar'
```

### 2. Attribute Directive `[attr]`
Berfungsi untuk memanipulasi atribut HTML pada blok lingkup/elemen yang sedang aktif. Penulisan wajib dibungkus menggunakan tanda kurung siku `[...]`.
```php
"img.thumbnail" => [
    "[src]" => '$imageSource',
    "[alt]" => '$imageDescription'
]
```

### 3. Logic Directive `@foreach`
Digunakan untuk mereplikasi/me-loop elemen HTML ke dalam format iterasi. Aturan ini selalu dieksekusi **di dalam ruang scope parent**.
- Compiler otomatis akan mencuplik elemen HTML berselector sama yang berada di urutan *pertama* di DOM sebagai "Cetakan".
- Elemen pertama akan dibungkus oleh blok perintah tag pembuka penutup *foreach*.
- Segala elemen duplikat tambahan (dummy nodes) di HTML berselector sama otomatis disapu bersih.
```php
"ul.nav > li" => [
    "@foreach" => '$menus as $menu',
    "a" => [
        "[href]" => '$menu["link"]',
        "@text"  => '$menu["label"]' // Kombinasi modifier teks dan atribut
    ]
]
```
*(Catatan: Aturan `@text` ini menggantikan peran string shorthand jika elemen tersebut membutuhkan atribut directive di saat yang bersamaan).*

### 4. Logic Directive `@html`
Digunakan khusus untuk merender tag HTML mentah (Raw HTML) secara langsung dari string ke dalam DOM tanpa dilakukan proses *escaping* XSS. Fitur ini sangat berguna untuk menampilkan hasil output dari *WYSIWYG Editor*.
```php
"div.content" => [
    "@html" => '$blog["wysiwyg_content"]'
]
```

### 5. Recursive/Nested Selectors
Anda bisa menelusuri elemen yang lebih menjorok ke dalam (children) menggunakan standar penulisan Selector CSS biasa di sub-array. Selector yang bersarang (nested) jangkauan XPath pencariannya akan dilakukan sebatas pada internal elemen pembungkus (relatif) saja. Ini membuat parsing 10x lipat lebih akurat dan minim bentrokan di memori global.
```php
"#profile" => [
    ".avatar" => [
        "[src]" => '$user["avatar"]'
    ],
    ".details" => [
        "h3" => '$user["name"]',
        "p.bio" => '$user["bio"]'
    ]
]
```

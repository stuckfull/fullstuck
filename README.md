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
| `"@if" => '...'` | **Conditional**: Membungkus elemen dengan blok `if` PHP. | `"@if" => '$isActive'` |
| `"[attr]" => '...'` | **Attribute**: Menimpa nilai *attribute* HTML (contoh `href`, `src`, `class`, `data-*`). | `"[href]" => '$url'` |
| `"@remove"` (value) | **Cleanup**: Menghapus elemen atau atribut secara permanen pada saat kompilasi. | `"div.dummy" => "@remove"` |

### 2. Daftar Selektor CSS
Secara bawaan (*native*), *compiler* mengubah selektor CSS menjadi XPath. Untuk menjaga efisiensi dan mencegah *crash*, sistem *whitelist* ketat diberlakukan.

✅ **Didukung Penuh (Whitelist):**
- Tag Selector: `h1`, `div`, `article`
- Class & ID: `.class`, `#id`
- Compound Selector: `article.post-item`, `div#container`
- Descendant (Spasi): `.container h2`
- Direct Child (`>`): `ul > li`
- Attribute Bracket: `input[name="username"]`, `a[href]`, `[data-id="5"]`
- Targeting Tunggal: `^div.alert` (Berikan prefix `^` untuk membatasi pencarian hanya pada node pertama).
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
    // ==========================================
    // 1. MANIPULASI TEKS & HTML (Mass Execution)
    // Berlaku untuk SEMUA elemen yang cocok (querySelectorAll)
    // ==========================================

    // Shorthand Text (Aman dari XSS)
    "title" => '$pageTitle', 
    // JS Murni: document.querySelectorAll("title").forEach(el => el.innerText = pageTitle);

    // Explicit Text (Dipakai jika ada manipulasi atribut di blok yang sama)
    "h3" => ["@text" => '$subJudul'],
    // JS Murni: document.querySelectorAll("h3").forEach(el => el.innerText = subJudul);

    // Raw HTML (Bypass XSS - Khusus untuk output WYSIWYG)
    "span.content" => ["@html" => '$htmlContent'],
    // JS Murni: document.querySelectorAll("span.content").forEach(el => el.innerHTML = htmlContent);


    // ==========================================
    // 2. MANIPULASI ATRIBUT
    // ==========================================

    // Mengganti/Menambah atribut (Teks di dalam elemen tetap utuh)
    "a.external" => ["[href]" => '$linkUrl', "[target]" => '"_blank"'],
    // JS Murni: document.querySelectorAll("a.external").forEach(el => { el.setAttribute("href", linkUrl); el.setAttribute("target", "_blank"); });

    // Mengubah isi teks PADA elemen yang memiliki atribut spesifik
    "a[data-type='link']" => '"Teks Link Baru"',
    // JS Murni: document.querySelectorAll("a[data-type='link']").forEach(el => el.innerText = "Teks Link Baru");


    // ==========================================
    // 3. TARGETING TUNGGAL (Single Node Selection)
    // Gunakan prefix `^` untuk menghentikan pencarian di elemen pertama
    // ==========================================
    
    "^div.alert" => '"Ini alert pertama saja"',
    // JS Murni: document.querySelector("div.alert").innerText = "Ini alert pertama saja";


    // ==========================================
    // 4. COMPILE-TIME CLEANUP (Pembersihan DOM sebelum masuk Cache)
    // Menggunakan string mutlak "@remove"
    // ==========================================

    // Memusnahkan elemen secara utuh beserta anak-anaknya
    "div.debug-panel" => "@remove",
    // JS Murni: document.querySelectorAll("div.debug-panel").forEach(el => el.remove());

    // Membuang atribut sampah/dummy dari FE
    "img.thumbnail" => [
        "[style]" => "@remove",       // Hapus inline style
        "[data-dummy]" => "@remove",  // Hapus atribut dummy
        "[src]" => '$realImageUrl'    // Set src asli
    ],
    // JS Murni: document.querySelectorAll("img.thumbnail").forEach(el => el.removeAttribute("style"));


    // ==========================================
    // 5. RUN-TIME LOGIC (Kondisional & Iterasi di eksekusi PHP)
    // ==========================================

    // IF Murni (Menyembunyikan/Menampilkan Elemen)
    "div.banner-promo" => [
        "@if" => '$isPromoActive'
    ],

    // IF & ELSE (Gunakan Inverse Logic ! pada class berbeda)
    "a.btn-dashboard" => [
        "@if" => '$isLoggedIn'
    ],
    "a.btn-login" => [
        "@if" => '!$isLoggedIn'
    ],

    // TERNARY (Logika sebaris untuk elemen yang sama)
    "button.btn-auth" => [
        "@text"   => '$isLoggedIn ? "Logout" : "Login"',
        "[href]"  => '$isLoggedIn ? "/logout" : "/login"',
        "[class]" => '$isLoggedIn ? "btn-danger" : "btn-primary"'
    ],

    // FOREACH (Looping Data Array)
    // Otomatis mengambil node pertama sebagai cetakan, dan menghapus node duplikat (dummy) lainnya.
    "ul.nav > li" => [
        "@foreach" => '$menus as $menu',
        "a" => [
            "[href]" => '$menu["url"]',
            "@text"  => '$menu["label"]'
        ]
    ]
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
// Parameter keempat bersifat opsional, namun disarankan untuk mengatur direktori output cache secara eksplisit
render_template(__DIR__ . '/blog-list.html', $data, $rules, __DIR__ . "/build-template");
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
Fitur ini juga sangat kuat untuk kebutuhan injeksi (Hotfix) statis, seperti menyelipkan blok `<style>` atau `<script>` darurat di dalam HTML:
```php
// Contoh 1: Hotfix CSS Responsif
"head" => [
    "@html" => '"<style>@media (max-width: 768px) { .sidebar { display: none !important; } }</style>"'
];

// Contoh 2: Injeksi Notifikasi/Maintenance via Vanilla JS
"#js-hotfix" => [
    "@html" => '"<script>console.log(\"Hotfix aktif!\"); alert(\"Server maintenance 10 menit lagi!\");</script>"'
];
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

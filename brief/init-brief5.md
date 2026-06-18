The current compiler architecture is extremely solid, but we need to add 3 crucial features to make it production-ready and allow the Backend team to handle emergency DOM patches independently.

### TASK 1: Single Element Targeting (The `^` Prefix)
Currently, all CSS selectors act as `querySelectorAll`. We need a `querySelector` equivalent.
If a selector key starts with `^` (e.g., `^div.alert`), the compiler must ONLY process the FIRST matched node and ignore the rest.
- Check if `$key` starts with `^`.
- If so, remove the `^` character before passing it to `$css2xpath`.
- After getting the `$nodes`, process ONLY `$nodes->item(0)` instead of looping through all nodes.

### TASK 2: Conditional Rendering (`@if` Directive)
Inside the `$applyRules` closure (where `$value` is an array), add support for the `@if` directive to wrap nodes in PHP if-statements at compile time.
If `isset($value['@if'])`:
- Generate `$startMarker` and `$endMarker`.
- Assign to `$replacements`:
  `$replacements[$startMarker] = "<?php if ({$value['@if']}): ?>";`
  `$replacements[$endMarker] = "<?php endif; ?>";`
- Use `$node->parentNode->insertBefore()` to place `$startMarker` before the matched node, and `$endMarker` immediately after it.
- Do this for ALL matched `$nodes` in the current selector.
- `unset($value['@if']);` so it doesn't execute as a nested rule.

### TASK 3: Compile-Time Cleanup (`@remove` Value)
We need a way to completely strip dummy elements or attributes from the DOM before saving the cache. The trigger is the exact string value `"@remove"`.

A. Node Removal (When $value is a string):
Modify the Text Manipulation block: `if (is_string($value))`.
If `$value === '@remove'`, completely delete the matched nodes using `$node->parentNode->removeChild($node)` and `continue`. (Do not create a marker).

B. Attribute Removal (Inside the Attribute Manipulation block `[...]`):
If the value assigned to an attribute is exactly `'@remove'` (e.g., `"[style]" => "@remove"`), call `$context->removeAttribute($attrName)` and `continue`. (Do not create a marker).

Integrate all 3 tasks seamlessly into `compiler.php` and return the updated code.


```php
<?php

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
    "a[data-type='link']" => 'Teks Link Baru',
    // JS Murni: document.querySelectorAll("a[data-type='link']").forEach(el => el.innerText = "Teks Link Baru");


    // ==========================================
    // 3. TARGETING TUNGGAL (Single Node Selection)
    // Gunakan prefix `^` untuk menghentikan pencarian di elemen pertama
    // ==========================================
    
    "^div.alert" => 'Ini alert pertama saja',
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
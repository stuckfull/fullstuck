# 🌻 Procedural DOM Templating (`fst_template`)

Pisahkan murni file HTML statis (tanpa tag PHP) dan definisikan logikanya menggunakan Array (Ruleset) dari controller. Templating ini secara otomatis me-*escape* variabel (mencegah XSS).

### Signature API
```php
// Merender langsung ke browser
fst_template(string $html_path, array $data, array $rules);

// Mengembalikan string HTML (Berguna untuk Layouting/Nested Template)
$html_string = fst_template_render(string $html_path, array $data, array $rules);
```

### Ruleset (DSL)
```php
$rules = [
    // --- TEXT & HTML ---
    "title"        => '$pageTitle',                    // Men-set innerText (XSS-safe)
    "h3"           => ["@text" => '$heading'],         // Sama seperti di atas
    "span.content" => ["@html" => '$htmlContent'],     // Raw innerHTML (hanya untuk trusted content)
    "head"         => ["@append"  => '"<style>...</style>"'],  // insertAdjacentHTML (beforeend)
    "body"         => ["@prepend" => '"<div>Notif</div>"'],    // insertAdjacentHTML (afterbegin)

    // --- ATTRIBUTES ---
    "a.external"   => ["[href]" => '$linkUrl', "[target]" => '"_blank"'],
    "[data-fst=\"my-form\"]" => ["[action]" => '"/submit"'],

    // --- BOOLEAN / RAW DYNAMIC ATTRIBUTES ---
    // Jangan gunakan `[disabled] => '$cond ? "disabled" : ""'` karena string kosong HTML5 tetap menghitungnya ada.
    // Gunakan `@attrs` untuk merender string raw langsung ke dalam tag.
    "button.submit" => ["@attrs" => '$isDisabled ? "disabled" : ""'],

    // --- SINGLE TARGETING (Optimasi Performa) ---
    // Gunakan prefix `^` untuk memicu querySelector() secara native (bukan querySelectorAll).
    // Sangat berguna jika Anda yakin elemen hanya ada 1 di halaman.
    "^div.alert"   => '"Ini alert pertama saja"',

    // --- COMPILE-TIME REMOVAL ---
    "div.debug-panel" => "@remove",                   // Hapus elemen selamanya dari cache

    // --- CONDITIONALS & LOOPS ---
    "div.promo"       => ["@if" => '$isPromoActive'], // Jika false, elemen dihapus permanen dari DOM output (bukan display: none!)

    // Ternary 
    "button.auth"     => [
        "@text"   => '$isLoggedIn ? "Logout" : "Login"',
        "[href]"  => '$isLoggedIn ? "/logout" : "/login"',
    ],

    // @foreach — elemen child pertama menjadi template loop
    "ul.nav > li"     => [
        "@foreach" => '$menus as $menu',
        "a"        => ["[href]" => '$menu["url"]', "@text" => '$menu["label"]']
    ],
];
```

> ⚠️ **Peringatan DX:** Array `$rules` **tidak mendukung** *Closure* (`fn() => ...`). Semua logika HARUS ditulis dalam bentuk *String Literal Ekspresi PHP* agar dapat di-*serialize* untuk *caching*. Jika butuh variabel kompleks, suntikkan melalui array `$data`.

### Contoh Layouting Bersarang
```php
fst_get('/tentang', function() {  
    // Render anak menjadi string
    $content = fst_template_render('views/tentang.html', [], []);  
    
    // Suntikkan string ke layout utama
    fst_template('views/_layout.html', ['content' => $content], [
        'main' => ['@html' => '$content']
    ]);  
});
```

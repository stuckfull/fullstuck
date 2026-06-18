### 1. Injeksi "State Hydration" (Melempar JSON dari BE ke FE)

Tim *frontend* sering kali butuh data *initial state* dari *backend* agar JavaScript mereka (misalnya Alpine.js atau Vanilla JS) tidak perlu melakukan *request* AJAX tambahan saat halaman pertama kali dimuat.

Daripada membuat *endpoint* API terpisah, kamu bisa langsung menyuntikkan JSON mentah ke dalam halaman HTML.

**Di HTML (Buatan FE):**

```html
<script id="initial-state" type="application/json"></script>

```

**Di Ruleset PHP:**

```php
$rules = [
    "#initial-state" => [
        "@html" => 'json_encode($blogs)' // Menggunakan @html agar tanda kutip JSON tidak rusak
    ]
];

```

*Frontend* sekarang tinggal melakukan `JSON.parse(document.getElementById('initial-state').innerHTML)` dan data *backend* sudah ada di tangan mereka secara instan.

### 2. Manipulasi Meta Tag SEO Dinamis (Open Graph)

Ketika seseorang membagikan *link* artikel ke WhatsApp atau Twitter, *platform* tersebut membaca `<meta property="og:image">` untuk menampilkan gambar *thumbnail*. File statis HTML pasti hanya punya gambar *dummy*.

*Compiler* ini bisa menimpanya dengan sangat elegan:

```php
$rules = [
    "meta[property='og:title']"       => ["[content]" => '$blog["title"]'],
    "meta[property='og:description']" => ["[content]" => '$blog["summary"]'],
    "meta[property='og:image']"       => ["[content]" => '$blog["thumbnail_url"]']
];

```

### 3. Injeksi Token Keamanan (CSRF Siluman)

Jika tim FE membuat form login murni statis, mereka tidak tahu apa token CSRF dari *server*. Memaksa FE mengurus ini via API kadang ribet. Dengan *compiler*-mu, kamu tinggal menyuruh FE menyediakan satu input kosong:

**Di HTML:**

```html
<form action="/login" method="POST">
    <input type="hidden" name="csrf_token" value="dummy">
    </form>

```

**Di Ruleset PHP:**

```php
$rules = [
    "input[name='csrf_token']" => [
        "[value]" => '$_SESSION["csrf"]'
    ]
];

```

Form yang tadinya buta keamanan, sekarang otomatis memiliki token valid yang disuntikkan secara presisi oleh *backend* sebelum sampai ke *browser user*.

### 4. Pencegahan FOUC (Flash of Unstyled Content) untuk Dark Mode

Jika preferensi *Dark Mode user* disimpan di *database* atau *session* PHP, menunggunya dieksekusi oleh JavaScript di sisi *client* sering membuat layar berkedip putih sepersekian detik (FOUC).

Kamu bisa memaksanya langsung dari *root* dokumen saat kompilasi!

```php
$rules = [
    "^html" => [
        "[class]" => '$user["theme"] === "dark" ? "dark-theme" : "light-theme"'
    ]
];

```

*Browser* akan menerima dokumen HTML yang sudah memiliki *class* yang tepat, sehingga halaman langsung *render* dengan warna gelap yang sempurna.
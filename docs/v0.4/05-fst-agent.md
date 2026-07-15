## 🔌 5. FST-Agent (SPA Engine)

Jika `agent_js` aktif di `fullstuck.json`, setiap klik link `<a>` dan submit `<form>` otomatis dimuat via AJAX tanpa full reload.

> **💡 Catatan Ruang Lingkup (Scope):**
> File `client.js` otomatis dibungkus dengan metode *IIFE (Immediately Invoked Function Expression)* untuk mencegah kebocoran status global (*global state*). Oleh karena itu, fungsi atau variabel yang Anda deklarasikan di dalamnya **tidak bisa** dipanggil lewat atribut HTML *inline* seperti `<button onclick="sapa()">`. Jika Anda harus membuat fungsi global, daftarkan secara eksplisit ke dalam objek *window*: `window.sapa = () => { ... }`.

### Event Listener (WAJIB pakai `fst.on`)
Di dalam `client.js`, **jangan** pakai `document.addEventListener` secara langsung. Gunakan `fst.on()` agar listener otomatis dibersihkan saat navigasi halaman:

```javascript
// ✅ BENAR: Otomatis dibersihkan saat pindah halaman
fst.on('click', '#btn-hapus', (e, el) => {
    // ...
});

// ❌ SALAH: Akan menumpuk di memory setiap buka halaman
document.getElementById('btn-hapus').addEventListener('click', ...);
```

### Lifecycle Hook: `fst.onMount`
Callback dijamin berjalan setelah DOM selesai dirender. Jika mengembalikan fungsi, fungsi tersebut dieksekusi saat halaman ditinggalkan (teardown):

```javascript
fst.onMount(() => {
    const chart = new Chart(document.getElementById('myChart'), { ... });
    
    // Teardown: bersihkan saat pindah halaman
    return () => chart.destroy();
});
```

### Event Bus (Komunikasi Antar-Modul)
```javascript
// Pengirim
fst.emit('cart_updated', { total: 50000 });

// Penerima
fst.on('cart_updated', (detail) => {
    console.log('Total:', detail.total);
});
```

### Global Listener
Listener yang **tidak** boleh dibersihkan saat navigasi (misal: theme toggle):
```javascript
fst.on('click', '#btn-theme', (e, el) => { ... }, { global: true });
```

### Navigasi Programatik
```javascript
fst.go('/dashboard');
fst.go('/users', { fragment: '#content', history: false, scroll: 'smooth' });
```

### HTML Data Attributes
| Atribut | Fungsi |
|---|---|
| `data-fst-fragment="#id"` | Target elemen untuk injeksi HTML |
| `data-fst-normal-load` | Bypass SPA, lakukan full page reload |
| `data-fst-history="false"` | Jangan catat di browser history |
| `data-fst-scroll="false"` | Matikan auto scroll-to-top (dukung nilai "smooth") |
| `data-fst-indicator="class"` | CSS class loading kustom |
| `data-fst-ignore` | Script hanya dieksekusi 1 kali |

### Script Deduplication
Script eksternal (dengan `src`) yang sudah dimuat **tidak** akan dimuat ulang saat navigasi SPA. Library seperti Chart.js atau Swiper aman dari eksekusi ganda.

### ⚠️ Batasan `innerHTML` & Aturan "Wadah Bisu" (Dumb Wrapper)
FST-Agent memotong dan menyuntikkan HTML ke klien menggunakan metode **`innerHTML`**. Artinya, hanya *isi konten* dari elemen target yang akan diganti. **Class, *inline style*, atau atribut yang melekat pada tag pembungkus target itu sendiri TIDAK akan diperbarui saat terjadi navigasi SPA.**

**Praktik Terbaik:** Jangan tempatkan class/styling yang bisa berubah antar-halaman langsung di elemen yang menjadi target `X-FST-Fragment` (misalnya `#app` atau `#page-content`). Jadikan elemen target tersebut sebagai "Wadah Bisu" yang statis.

```html
<!-- ✅ BENAR: Wadah statis, class desain ditaruh DI DALAM konten -->
<div id="page-content"> 
    <div class="bg-hitam text-putih">
        <h1>Tentang Kami</h1>
    </div>
</div>

<!-- ❌ SALAH: Class 'bg-putih' tidak akan terganti (hilang konteks) saat FST-Agent me-load halaman baru -->
<div id="page-content" class="bg-putih text-hitam">
    <h1>Beranda</h1>
</div>
```

### 🎯 Dukungan Selector X-FST-Fragment
FST-Agent menggunakan konversi parser HTML kustom di *backend*, sehingga hanya mendukung *CSS selector* standar:

**✅ DIDUKUNG:**
- **ID:** `#app` *(Paling cepat & direkomendasikan)*
- **Class:** `.container`
- **Tag:** `main`, `body`
- **Atribut:** `[data-active]`, `[data-role="admin"]` *(Hanya nilai alfanumerik, spasi, `/`, `-`, `.`)*
- **Hierarki/Kombinasi:** `#app .container`, `#app > .sidebar`, `#app, .cart`

**❌ TIDAK DIDUKUNG (Otomatis Diblokir):**
- **Pseudo-classes/elements:** `:hover`, `:nth-child()`, `::before`
- **Sibling:** `~`, `+`
- **Regex atribut:** `[attr^=val]`, `[attr$=val]`, `[attr*=val]`

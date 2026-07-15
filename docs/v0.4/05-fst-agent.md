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
fst.go('/users', { target: '#content', history: false, scroll: 'smooth' });
```

### HTML Data Attributes
| Atribut | Fungsi |
|---|---|
| `data-fst-fragment="#id"` | Target elemen untuk injeksi HTML |
| `data-fst-normal-load` | Bypass SPA, lakukan full page reload |
| `data-fst-no-history` | Jangan catat di browser history |
| `data-fst-no-scroll` | Matikan auto scroll-to-top |
| `data-fst-indicator="class"` | CSS class loading kustom |
| `data-fst-ignore` | Script hanya dieksekusi 1 kali |

### Script Deduplication
Script eksternal (dengan `src`) yang sudah dimuat **tidak** akan dimuat ulang saat navigasi SPA. Library seperti Chart.js atau Swiper aman dari eksekusi ganda.

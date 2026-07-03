# ⚡ FST Agent & Fragment Routing (Client-Side)

Jika `"agent_js": true` dihidupkan, navigasi Anda secara otomatis bekerja bagaikan *Single Page Application* tanpa *Full Page Reload*. FST Agent akan mencegat setiap klik tag `<a>` dan pengiriman `<form>`, kemudian mengambil *Fragment HTML* dari server.

Anda juga bisa mengatur **Rute Client-Side Murni** melalui object `fst` secara global!

### JavaScript API (`window.fst`)
Definisikan ini di file `.js` eksternal atau tag `<script>` bawaan HTML.
```javascript
// Rute Javascript Murni (Tidak hit server PHP)
fst.set('/editor', (params) => {
    document.querySelector('#app').innerHTML = `<h1>Canvas Editor</h1>`;
});

// Rute Dinamis dengan Regex Extractor
fst.set('/user/:id', (params) => {
    console.log("ID User: " + params.id);
    console.log("Query Params: ", params.query);
});

// Grouping Rute (Sangat berguna untuk struktur aplikasi dalam)
fst.group('/dashboard', () => {
    fst.set('/settings', () => { /* Rute /dashboard/settings */ });
});

// Pemanggilan Programmatik
fst.go('/sebagian', { target: '#widget', history: false, scroll: 'smooth' });
```

### HTML Data Attributes
| Atribut HTML | Penjelasan |
| --- | --- |
| `data-fst-fragment="#id"` | Menentukan di dalam elemen mana hasil HTML disuntikkan (Default: dari config). |
| `data-fst-normal-load` | *Bypass* FST Agent. Tag A / Form akan melakukan *full page reload* biasa. |
| `data-fst-no-history` | Mencegah navigasi untuk dicatat dalam *URL Bar* (browser history). Sangat cocok untuk action form DELETE / POST. |
| `data-fst-no-scroll` | Mematikan efek `scroll-to-top` otomatis setelah perpindahan halaman. |
| `data-fst-indicator="class"`| Menimpa *CSS Class* loading untuk elemen spesifik ini saat di-fetch. |
| `data-fst-ignore` | Ditaruh di dalam `<script>`, menandakan script ini hanya di-eksekusi 1 kali seumur hidup. |

> ⚠️ **Peringatan SPA Script Re-Execution:** Saat FST Agent melakukan transisi *Fragment Routing*, ia akan mengeksekusi ulang seluruh tag `<script>` baru di dalam DOM target agar integrasi *library* lain tetap bekerja. Oleh karena itu:
> 1. Pastikan script bawaan FST Agent selalu memiliki atribut `id="fst-agent"` (`<script src="/fst-agent.js" id="fst-agent"></script>`) agar tidak dieksekusi berulang kali. (Jika menggunakan `--agent_js=yes`, ini di-*handle* otomatis oleh *framework*).
> 2. Untuk tag `<script>` khusus Anda sendiri (misalnya pendaftaran rute SPA `fst.set()`), **WAJIB** menambahkan atribut `data-fst-ignore` agar tidak memicu eksekusi/pendaftaran fungsi ganda ketika berpindah halaman!

*Untuk referensi Javascript API dan Event Hooks lebih lengkap, lihat [FULL.md](./FULL.md).*

### Javascript Event Hooks
Anda dapat memberikan reaksi saat halaman sedang memuat atau selesai memuat (Sangat membantu untuk menghancurkan & me-load ulang library jQuery / pihak ke-3):
```javascript
// Sebelum Fetch
document.addEventListener('fst:loading', (e) => { 
    // e.detail: { url, targetSelector, triggerElement }
    // Memungkinkan e.preventDefault()
});

// HTML Lama dihapus
document.addEventListener('fst:unload', () => { /* destroy plugins */ });

// HTML Baru masuk & dirender
document.addEventListener('fst:load', () => { /* init plugins */ });
```

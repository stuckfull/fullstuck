## 🧩 4. Components

Komponen adalah potongan UI reusable di folder `components/`. Dipanggil on-demand via `@component`.

**Membuat:** `components/alert.fst.php`
```html
<div class="alert alert-{{ $type ?? 'info' }}">
    <p>{{ $message }}</p>
</div>
```

**Menggunakan:**
```html
@component('alert', ['type' => 'danger', 'message' => 'Login gagal!'])
```

**Subfolder:**
```text
components/admin/sidebar.fst.php  → @component('admin/sidebar')
```

Setiap komponen berjalan dalam **Scoped Closure** — variabel di dalamnya tidak bocor ke parent.

> **⚠️ Peringatan JavaScript pada Komponen:**
> Jangan meletakkan tag `<script>` statis (terutama deklarasi `const` atau `let`) di dalam komponen, terlebih jika komponen tersebut dipanggil di dalam perulangan `@foreach`. Script tersebut akan tercetak berulang kali dan memicu *Error: Identifier has already been declared*. Sebagai solusinya, gunakan **Event Delegation (`fst.on`)** secara terpusat di `client.js` untuk memberikan interaktivitas pada komponen.

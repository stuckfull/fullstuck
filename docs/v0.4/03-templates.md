## 🎨 3. Syntax Templating

File `.fst.php` dikompilasi otomatis menjadi PHP murni dan di-cache. Syntax mirip Laravel Blade:

### Output & Escaping
```html
<!-- Otomatis di-escape (aman dari XSS) -->
{{ $user['name'] }}

<!-- Output mentah (hanya untuk HTML terpercaya) -->
{!! $html_content !!}

<!-- Escape kurung kurawal untuk JS/JSON literal -->
@{{ variableVueJs }}
```

### Percabangan
```html
@if($user['role'] === 'admin')
    <button>Hapus</button>
@elseif($user['role'] === 'editor')
    <button>Edit</button>
@else
    <span>Hanya lihat</span>
@endif
```

### Perulangan
```html
<ul>
@foreach($items as $item)
    <li>{{ $item['name'] }}</li>
@endforeach
</ul>
```

### Layout: Yield & Section
**Di `app/_layout.fst.php`:**
```html
<html>
<head><title>@yield('title', 'Default Title')</title></head>
<body>
    <nav>...</nav>
    <main>@yield('content')</main>
</body>
</html>
```

**Di `app/dashboard/content.fst.php`:**
```html
@section('title')
Dashboard
@endsection

<!-- Konten tanpa @section otomatis masuk ke @yield('content') -->
<h1>Selamat Datang</h1>
<p>Ini adalah dashboard.</p>
```

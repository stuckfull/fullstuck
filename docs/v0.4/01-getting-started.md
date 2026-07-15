## 📦 1. Instalasi & Quick Start

### 🤖 Install via AI Coding Agent
```text
Install fullstuck.php. Panduan: https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/docs/ai-setup.md
```

### Download Manual
```bash
curl -O https://raw.githubusercontent.com/stuckfull/fullstuck/refs/heads/main/fullstuck.php
```

### CLI (Rekomendasi)
```bash
php fullstuck.php init --db=sqlite --agent_js=yes --scaffold=yes --htaccess=yes
```

| Flag | Pilihan | Default |
|---|---|---|
| `--db` | `sqlite`, `mysql`, `pgsql`, `none` | `sqlite` |
| `--agent_js` | `yes`, `no` | `yes` |
| `--scaffold` | `yes`, `no` | `yes` |
| `--htaccess` | `yes`, `no` | `yes` |

### Menjalankan Server Lokal
```bash
php -S localhost:8000 fullstuck.php
```

### Web Server Deployment

**Apache / LiteSpeed** — `.htaccess` sudah di-generate otomatis oleh `init`.

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /fullstuck.php?$query_string;
}
```

**FrankenPHP / Caddy:**
```caddyfile
:8000 {
    root * .
    php_server { index fullstuck.php }
}
```

### Membaca Dokumentasi via CLI
```bash
php fullstuck.php docs       # Daftar isi
php fullstuck.php docs:1     # Bab tertentu
php fullstuck.php docs:9     # API Reference
php fullstuck.php docs:full  # Seluruh dokumen
```

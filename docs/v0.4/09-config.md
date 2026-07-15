## ⚙️ 9. Konfigurasi `fullstuck.json`

```jsonc
{
  // Wajib. true = produksi (cache terkunci, error tersembunyi).
  "production": false,

  // Konfigurasi routing.
  "routing": {
    "base_path": "/",                        // Untuk deploy di subfolder
    "public_folders": ["assets", "uploads"]   // Folder yang boleh diakses publik
  },

  // Auto-load file PHP sebelum routing. Default: ["globals"].
  "require": ["globals"],

  // Aktifkan SPA engine.
  "agent_js": true,

  // Opsi fragment SPA.
  "fragment": {
    "history_cache": false   // Cache navigasi di sisi klien
  },

  // Database (opsional). Mendukung multi-koneksi.
  "database": {
    "default": "main",
    "connections": {
      "main": {
        "driver": "sqlite",
        "database_path": "database.sqlite"
      },
      "mysql_remote": {
        "driver": "mysql",
        "host": "${DB_HOST}",
        "port": 3306,
        "dbname": "${DB_NAME}",
        "username": "${DB_USER}",
        "password": "${DB_PASS}"
      }
    }
  }
}
```

Semua nilai string mendukung interpolasi variabel lingkungan: `${NAMA_ENV}`.

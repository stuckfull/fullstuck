## 📚 11. API Reference

### Core & Konfigurasi
| Fungsi | Keterangan |
|---|---|
| `fst_app($key, $value)` | Get/set state runtime aplikasi |
| `fst_config($key, $default)` | Baca nilai dari `fullstuck.json` (dot notation: `database.default`) |
| `fst_is_dev()` | Cek apakah mode development |
| `fst_log($level, $message, $context)` | Tulis log ke `.fst.log` |
| `fst_error_handler(callable)` | Daftarkan callback error kustom |

### HTTP Request & Response
| Fungsi | Keterangan |
|---|---|
| `fst_uri()` | URI request saat ini (tanpa query string) |
| `fst_input($key, $default)` | Ambil data dari GET/POST/JSON body |
| `fst_request()` | Seluruh data request sebagai array |
| `fst_method()` | Metode HTTP (menangani spoofing dari _method) |
| `fst_file($key)` | Data file upload dari `$_FILES` |
| `fst_redirect($url, $code, $allow_external)` | Redirect aman (cegah open redirect) |
| `fst_json($data, $status)` | Response JSON lalu `die()` |
| `fst_text($string, $status)` | Response plain text lalu `die()` |
| `fst_status_code($code)` | Set HTTP status code |
| `fst_abort($code, $message)` | Hentikan eksekusi + tampilkan error page/JSON |

### Session
| Fungsi | Keterangan |
|---|---|
| `fst_session_set($key, $value)` | Simpan ke session |
| `fst_session_get($key, $default)` | Baca dari session |
| `fst_session_forget($key)` | Hapus dari session |
| `fst_session_regenerate($delete_old)` | Regenerasi ID session (**wajib** setelah login) |

### Database
| Fungsi | Keterangan |
|---|---|
| `fst_db($mode, $sql, $params, $conn)` | Raw query. Mode: `ALL`, `ROW`, `SCALAR`, `EXEC` |
| `fst_db_select($table, $cond, $opts)` | Select banyak baris |
| `fst_db_row($table, $cond, $opts)` | Select satu baris |
| `fst_db_exists($table, $cond, $opts)` | Cek keberadaan data (boolean) |
| `fst_db_insert($table, $data, $opts)` | Insert. Return: `last_id` |
| `fst_db_update($table, $data, $cond, $opts)` | Update. Return: `affected_rows` |
| `fst_db_delete($table, $cond, $opts)` | Delete. Return: `affected_rows` |
| `fst_db_begin/commit/rollback($conn)` | Transaction control |

### Keamanan & Utilitas
| Fungsi | Keterangan |
|---|---|
| `e($str)` / `fst_escape($str)` | Escape string untuk mencegah XSS |
| `fst_upload($key, $folder, $opts)` | Upload file secure (whitelist + blacklist) |
| `fst_validate($data, $rules)` | Validasi input (`required`, `email`, `min`, `max`, `numeric`, `in`, `min_value`, `max_value`) |
| `fst_dump(...$vars)` | Debug output (hanya di mode dev) |
| `fst_dd(...$vars)` | Debug output lalu `die()` |

### Templating & View
| Fungsi | Keterangan |
|---|---|
| `fst_view($path, $data)` | Render file PHP biasa dengan data |
| `fst_partial($path, $data)` | Alias semantik untuk `fst_view` |
| `fst_view_share($key, $value)` | Bagikan variabel ke semua view |

### Fragment (Backend)
| Fungsi | Keterangan |
|---|---|
| `fst_is_fragment_request()` | Apakah request dari FST-Agent (SPA) |
| `fst_fragment_target()` | CSS selector target fragment |

### FST-Agent API (JavaScript)
| Method | Penjelasan & Parameter Ekstra |
|---|---|
| `fst.on(event, sel, cb, opts)` | Event delegation aman. `opts` menerima konfigurasi seperti `{ global: true }` agar event tidak dihapus otomatis saat pindah halaman. |
| `fst.onMount(cb)` | Hook lifecycle yang berjalan saat halaman/fragment selesai dirender. Return sebuah function di dalamnya untuk melakukan *cleanup* (mirip `useEffect`). |
| `fst.emit(event, detail)` | Mengirim custom event ke window (otomatis menggunakan prefix `fst:` jika perlu). Parameter `detail` bisa diisi data object. |
| `fst.go(url, options)` | Navigasi programatik SPA. <br>**Opsi:**<br>• `target`: string CSS selector (default: 'body')<br>• `history`: boolean (default: true)<br>• `scroll`: boolean \| 'smooth' \| 'instant'<br>• `indicator`: string (class loading kustom) |
| `fst.set(pattern, cb)` | Mendaftarkan *client-side route*. Callback menerima `(match, triggerElement)`. |
| `fst.group(prefix, cb)` | Mengelompokkan pendaftaran route client dengan awalan path yang sama. |
| `fst.setInterceptor(cb)` | Menyisipkan logika sebelum request `fetch()`. Callback menerima `(url, fetchOptions)` dan bisa me-return objek `fetchOptions` baru (berguna untuk menyisipkan header token/auth). |
| `fst.setBefore(cb)` | Hook navigasi. Menerima `(url)`. Jika fungsi ini me-return `false`, navigasi dibatalkan. |
| `fst.setAfter(cb)` | Hook navigasi. Menerima `(url, triggerElement)`. Dipanggil setelah route SPA berhasil diproses. |
| `fst.e(str)` / `fst.escape(str)`| Utility untuk *HTML escape* string (mencegah XSS) sebelum dimasukkan ke dalam DOM (e.g. `innerHTML`). |

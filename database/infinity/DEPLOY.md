# Deploy Absensi Hub ke InfinityFree

## Data MySQL (vPanel)

| Item | Nilai |
|------|--------|
| Host | `sql303.infinityfree.com` |
| Database | `if0_42012557_testabsen` |
| Username | `if0_42012557` |
| Password | *(password MySQL dari vPanel — biasanya sama dengan yang Anda set saat buat DB)* |

## Langkah 1 — Buat database di vPanel

1. Login [InfinityFree vPanel](https://dash.infinityfree.com/)
2. **MySQL Databases** → buat database `testabsen` (nama penuh: `if0_42012557_testabsen`)
3. Catat **MySQL Password** (bukan password akun InfinityFree, kecuali Anda memang set sama)

## Langkah 2 — Import tabel

1. vPanel → **phpMyAdmin** → pilih database `if0_42012557_testabsen`
2. Tab **Import** → pilih file `database/infinity/schema.sql` dari project ini
3. Klik **Go** / **Kirim**

Harus muncul 9 tabel: `users`, `absensi`, `settings`, `leave_requests`, `admin_logs`, `holidays`, `quota_blocks`, `password_resets`, `notifications`.

## Langkah 3 — Upload file aplikasi

Upload seluruh isi project ke `htdocs` (atau folder situs Anda). Struktur penting:

```
htdocs/
  .env                 ← salin dari .env.infinity.example
  config/
  includes/
  api/
  views/
  public/              ← document root harus mengarah ke folder ini
    index.php
    .htaccess
```

Di InfinityFree: **Domains** → domain Anda → set **Document Root** ke folder `public` (mis. `htdocs/public`).

## Localhost + InfinityFree (satu file `.env`)

File `.env` boleh berisi kredensial InfinityFree. Saat dibuka di **localhost**, aplikasi otomatis memakai MySQL XAMPP (`127.0.0.1` / `absensi_umkm`). Saat dibuka di domain **InfinityFree**, memakai `DB_HOST` / `DB_PASS` dari `.env`.

Pastikan database lokal `absensi_umkm` sudah di-import dari `database/schema.sql` untuk development.

## Langkah 4 — File `.env` (WAJIB)

Buat / upload file **`.env`** di **root project** — **bukan** di dalam folder `public/`:

```
htdocs/
  .env          ← DI SINI (sejajar config/ dan public/)
  config/
  public/
  includes/
  ...
```

Isi file (salin dari `.env.infinity.example`):

```env
DB_HOST=sql303.infinityfree.com
DB_PORT=3306
DB_NAME=if0_42012557_testabsen
DB_USER=if0_42012557
DB_PASS=Purefresh123
```

`DB_PASS` = **MySQL password** dari vPanel (bukan password login InfinityFree, kecuali memang sama).

**Error "set DB_PASS di file .env"** = file `.env` belum di-upload atau `DB_PASS` masih kosong.

**Tips InfinityFree:** isi `.env` tanpa spasi di awal baris. Contoh benar:
```
DB_PASS=Purefresh123
```
Bukan `DB_PASS = Purefresh123` (tetap jalan, hindari spasi saja). Upload ulang `config/database.php` terbaru jika `.env` sudah benar tapi tetap error.

## Langkah 5 — Tes koneksi

Buka di browser (sekali saja):

`https://NAMADOMAIN.infinityfreeapp.com/database/infinity/test-connection.php`

Jika **Koneksi OK** dan daftar tabel muncul, hapus file `test-connection.php` dari hosting.

## Login awal

| Role | No. HP | Password |
|------|--------|----------|
| Admin | `081200000000` | `123456` |

**Segera ganti password admin** setelah login pertama.

## Folder upload

Pastikan folder ini ada dan bisa ditulis (chmod 755):

- `public/uploads/`
- `public/uploads/backups/`

## Troubleshooting

- **Database belum siap: set DB_PASS** → file `.env` belum ada atau salah path
- **Access denied** → password MySQL di `.env` tidak sama dengan vPanel
- **Table doesn't exist** → ulang import `schema.sql`
- **404 / halaman kosong** → document root harus `public`, bukan root project

## Keamanan

- Jangan commit file `.env` ke Git
- Hapus `database/infinity/test-connection.php` setelah deploy
- Jangan upload `database/migrate_infinity.php` ke production

# Dokumentasi Lengkap Proyek: Absensi Hub (Web Absensi & Payroll UMKM)

Dokumentasi ini menjelaskan secara menyeluruh tentang arsitektur, spesifikasi teknis, fitur, struktur database, serta alur logika bisnis yang diimplementasikan pada proyek **Absensi Hub**.

---

## 1. Deskripsi Umum Proyek

**Absensi Hub** adalah platform aplikasi web pencatatan kehadiran (absensi) harian dan penggajian (**payroll**) otomatis yang dirancang khusus untuk memenuhi kebutuhan operasional pemilik usaha mikro, kecil, dan menengah (**UMKM**). 

### Karakteristik & Tujuan Utama:
*   **Ramah Seluler (Mobile-First Design):** UI dirancang menyerupai aplikasi mobile (menggunakan layout shell telepon pintar/phone shell) sehingga karyawan harian dapat melakukan absen dengan mudah via smartphone masing-masing di lapangan.
*   **Keamanan Verifikasi Kehadiran:** Karyawan wajib menyertakan foto selfie (real-time webcam capture) dan posisi lokasi GPS (Geofencing) untuk mencegah kecurangan.
*   **Kendali Biaya Operasional:** Pemilik usaha dapat membatasi kuota kehadiran harian karyawan guna mengontrol anggaran pengeluaran harian UMKM.
*   **Penggajian Otomatis (Payroll):** Gaji pokok, lembur, dan denda terlambat dihitung secara langsung berdasarkan jam check-in/check-out dan skema kerja yang fleksibel.
*   **Mudah Dihosting (Zero-Dependency Backend):** Dibangun dengan PHP Native murni agar dapat dijalankan dengan lancar di server dengan spesifikasi minimum maupun hosting gratis seperti InfinityFree.

---

## 2. Stack Teknologi & Bahasa Pemrograman

Proyek ini dibangun menggunakan teknologi web standar tanpa framework backend berat untuk menjamin efisiensi resource:

### A. Backend & Database
1.  **PHP 8.x (Native):** Menggunakan gaya penulisan prosedural terstruktur yang bersih. Semua interaksi database memakai ekstensi **PDO (PHP Data Objects)** untuk melindungi aplikasi dari kerentanan SQL Injection melalui *Prepared Statements*.
2.  **MySQL / MariaDB:** Digunakan sebagai sistem manajemen database relasional (RDBMS) untuk menyimpan data karyawan, absensi, logs, perizinan, dan konfigurasi aplikasi.

### B. Frontend
1.  **HTML5 & CSS3:** Struktur halaman web modern.
2.  **Tailwind CSS (via CDN):** Digunakan untuk mempercepat proses styling tampilan antarmuka (UI) agar terlihat sangat premium, responsif, dan dinamis.
3.  **Alpine.js (via CDN):** Framework JavaScript deklaratif yang sangat ringan (micro-framework) untuk mengelola logika interaktif di frontend (seperti pengelolaan state modal, navigasi mobile, tombol toggle, dan state kamera).
4.  **Lucide Icons:** Pustaka ikon berbasis SVG yang bersih dan minimalis untuk memperkaya visualisasi UI.
5.  **Chart.js (via CDN):** Pustaka grafik interaktif untuk visualisasi tren kehadiran karyawan dan grafik pengeluaran upah harian pada dasbor admin.

### C. Web & Browser APIs
1.  **HTML5 Geolocation API:** Digunakan untuk menangkap titik koordinat Latitude dan Longitude secara real-time dari perangkat karyawan saat menekan tombol absen.
2.  **HTML5 MediaDevices & Canvas API:** Mengakses kamera depan perangkat untuk mengambil foto selfie instan (tanpa file upload manual) dan mengubahnya ke string data Base64 sebelum dikirim ke server.
3.  **Window Print API:** Digunakan untuk ekspor dokumen (slip gaji) menjadi file PDF menggunakan parser pencetakan bawaan peramban (browser) yang dihiasi CSS khusus media cetak (`@media print`).

---

## 3. Struktur Direktori Proyek

Proyek ini diorganisasikan ke dalam struktur direktori yang modular dan mudah dipahami:

```txt
app/
├── .env                        # File konfigurasi environment (kredensial database prod)
├── .env.example                # Templat konfigurasi environment
├── index.php                   # Entry point root (mengarahkan request ke public/index.php)
├── config/
│   ├── app.php                 # Konfigurasi nama aplikasi, timezone, dan direktori upload
│   └── database.php            # Logika koneksi PDO pintar (auto-detect localhost vs production)
├── database/
│   ├── schema.sql              # Skema database MySQL lokal
│   ├── seed-demo-1bulan.sql    # Data dummy demo absensi selama 1 bulan
│   ├── seed-demo-bulan.php     # PHP script untuk men-seed database demo
│   ├── migrate_infinity.php    # Script migrasi kolom tambahan pada server online
│   ├── debug_infinity.php      # Script pembantu untuk men-debug environment database
│   └── infinity/               # Folder khusus konfigurasi deploy hosting gratis
│       ├── DEPLOY.md           # Petunjuk deploy ke InfinityFree
│       ├── schema.sql          # Skema database khusus hosting InfinityFree
│       └── test-connection.php # Script uji coba koneksi database di hosting
├── includes/
│   ├── auth.php                # Penanganan sesi user, login check, role checking
│   ├── helpers.php             # Kumpulan fungsi utilitas (rupiah, CSRF, upload foto, base_url)
│   ├── geofence.php            # Penghitungan jarak koordinat bumi (rumus Haversine)
│   ├── wage-calculator.php     # Mesin penghitung upah pokok, lembur, dan potongan
│   ├── quota.php               # Logika penghitungan kuota absen harian karyawan
│   ├── leaves.php              # Pengecekan status izin/cuti karyawan
│   ├── holidays.php            # Verifikasi hari libur nasional/toko
│   ├── early-checkout.php      # Validasi pengajuan alasan keluar lebih awal (early checkout)
│   ├── security.php            # Logika pembatasan laju login (login rate limiting)
│   ├── pagination.php          # Logika navigasi halaman (pagination)
│   └── attendance.php          # Kompatibilitas file absensi
├── public/
│   ├── index.php               # Front controller utama yang menangani routing URL
│   ├── .htaccess               # Konfigurasi server Apache untuk pengamanan folder
│   ├── assets/
│   │   ├── css/                # Kustomisasi stylesheet global tambahan
│   │   └── js/                 # File JavaScript pembantu (misal penanganan kamera)
│   └── uploads/                # Direktori penyimpanan foto selfie absensi & bukti izin
│       └── backups/            # Folder penyimpanan file ekspor rekap XLS otomatis
├── api/                        # Endpoint backend untuk transaksi data AJAX/POST
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   ├── absen-masuk.php
│   ├── absen-keluar.php
│   ├── employee-status.php     # Approve / suspend status karyawan
│   ├── leave-request.php       # Karyawan mengajukan izin baru
│   ├── leave-action.php        # Admin menyetujui / menolak izin
│   ├── holiday-save.php        # Admin menyimpan tanggal hari libur
│   ├── holiday-delete.php      # Admin menghapus tanggal hari libur
│   ├── update-payment.php      # Admin mengubah status bayar upah
│   ├── update-profile.php      # Karyawan/Admin memperbarui data diri
│   ├── forgot-password.php     # Request token reset password
│   ├── reset-password.php      # Reset password menggunakan token
│   ├── generate-backup.php     # Ekspor database menjadi file SQL backup
│   ├── delete-backup.php       # Hapus file backup database
│   ├── export-excel.php        # Ekspor laporan rekap absensi & payroll ke file Excel (.xls)
│   ├── export-slip.php         # Tampilan cetak slip upah harian individu (HTML/PDF)
│   └── export-slips-batch.php  # Cetak slip gaji massal sekaligus dalam satu bulan
├── scripts/
│   └── backup-cron.php         # Script CLI Cron untuk otomatisasi backup data bulanan
└── views/                      # Template halaman antarmuka pengguna
    ├── landing.php             # Halaman utama landing page
    ├── login.php               # Form halaman login user
    ├── register.php            # Form pendaftaran akun karyawan baru
    ├── 404.php                 # Halaman penanganan error rute tidak ditemukan
    ├── partials/               # Potongan template UI yang digunakan berulang kali
    │   ├── header.php          # Tag head HTML, link CDN, inisialisasi asset
    │   ├── footer.php          # Tag penutup HTML
    │   ├── admin_sidebar.php   # Sidebar navigasi panel administrator
    │   ├── employee_nav.php    # Bottom navigation bar khusus karyawan (mobile look)
    │   └── flash.php           # Penanganan notifikasi toast/alert berbasis session
    ├── karyawan/               # Halaman antarmuka peran karyawan
    │   ├── home.php            # Beranda karyawan (status absen, estimasi gaji hari ini, profil dompet)
    │   ├── checkin.php         # Antarmuka absen masuk (kamera + lokasi)
    │   ├── checkout.php        # Antarmuka absen keluar (+ form alasan keluar awal)
    │   ├── history.php         # Daftar riwayat absen & download slip gaji mandiri
    │   ├── leaves.php          # Riwayat & form pengajuan izin/cuti/sakit
    │   └── profile.php         # Pengaturan profil dan detail rekening bank
    └── admin/                  # Halaman antarmuka peran administrator
        ├── dashboard.php       # Panel statistik utama (KPI metrics, chart tren, log aktivitas)
        ├── employees.php       # Daftar karyawan, pencarian, tombol persetujuan akun
        ├── employee-detail.php # Detail profil karyawan beserta riwayat absensi individunya
        ├── attendance.php      # Rekap absensi harian seluruh karyawan
        ├── leaves.php          # Persetujuan permohonan izin/cuti
        ├── holidays.php        # Pengaturan hari libur toko/kantor
        ├── wage.php            # Laporan rekapitulasi gaji karyawan (bulanan)
        ├── payment.php         # Pembayaran upah karyawan (ubah status belum bayar -> lunas)
        ├── settings.php        # Konfigurasi Geofence, jam kerja default, kuota, backup & restore
        └── logs.php            # Riwayat log aktivitas admin (audit log)
```

---

## 4. Perancangan Database (MySQL Schema)

Sistem menggunakan database bernama `absensi_umkm` yang terdiri dari 9 tabel yang saling berelasi:

### A. Tabel `users`
Menyimpan data identitas pengguna (Administrator & Karyawan).
*   `id` (INT, Primary Key, Auto Increment)
*   `nama` (VARCHAR(120), Nama lengkap)
*   `no_hp` (VARCHAR(30), Unik, digunakan sebagai nomor ID login utama)
*   `password` (VARCHAR(255), Hash password enkripsi bcrypt)
*   `foto_profil` (VARCHAR(255), Path file foto profil)
*   `bank_name` (VARCHAR(50), Nama bank untuk transfer upah)
*   `norek` (VARCHAR(50), Nomor rekening bank)
*   `role` (ENUM('admin', 'karyawan'), Hak akses pengguna)
*   `status` (ENUM('aktif', 'menunggu_approval', 'nonaktif'), Status keaktifan akun. Akun pendaftar baru wajib melalui status `menunggu_approval` sebelum diaktifkan oleh admin)
*   `created_at` (TIMESTAMP)

### B. Tabel `absensi`
Menyimpan rekaman transaksi kehadiran harian karyawan beserta estimasi upah dan status pembayarannya.
*   `id` (INT, Primary Key, Auto Increment)
*   `user_id` (INT, Foreign Key ke `users.id` dengan opsi ON DELETE CASCADE)
*   `tanggal` (DATE, Tanggal hari absen dilakukan. Kombinasi `user_id` dan `tanggal` bersifat Unik)
*   `jam_masuk` (TIME, Jam check-in)
*   `jam_keluar` (TIME, Jam check-out)
*   `foto_masuk` (VARCHAR(255), Path foto selfie saat check-in)
*   `foto_keluar` (VARCHAR(255), Path foto selfie saat check-out)
*   `lat_masuk` / `lng_masuk` (DECIMAL(10,7), Koordinat GPS check-in)
*   `lat_keluar` / `lng_keluar` (DECIMAL(10,7), Koordinat GPS check-out)
*   `skema` (VARCHAR(10), Skema kerja yang dipilih saat check-in)
*   `upah_final` (INT, Total upah bersih yang diterima hari itu setelah ditambah lembur & dikurangi denda)
*   `potongan` (INT, Jumlah denda keterlambatan yang dikenakan)
*   `lembur` (INT, Nominal bonus lembur yang diterima)
*   `keterangan_keluar_awal` (TEXT, Alasan tertulis jika check-out sebelum jam batas minimum)
*   `bukti_keluar_awal` (VARCHAR(255), Path foto surat sakit atau dokumen pendukung keluar awal)
*   `status` (ENUM('belum_absen', 'bekerja', 'selesai', 'terlambat', 'alpa'))
*   `status_bayar` (ENUM('belum_dibayar', 'sudah_dibayar'))
*   `created_at` (TIMESTAMP)
*   `updated_at` (TIMESTAMP)

### C. Tabel `settings`
Menyimpan konfigurasi parameter sistem operasional UMKM (hanya ada 1 baris data utama).
*   `id` (INT, Primary Key)
*   `app_name` (VARCHAR(100), Nama aplikasi kustomisasi pemilik)
*   `default_kuota` (INT, Jumlah jam kerja standar harian, default: 8 jam)
*   `geofence_lat` / `geofence_lng` (DECIMAL(10,7), Titik koordinat pusat lokasi toko/kantor)
*   `geofence_radius` (INT, Batas radius lingkaran toleransi lokasi dalam meter, default: 100m)
*   `upah_per_jam` (INT, Nilai upah dasar per jam kerja)
*   `multiplier_lembur` (DECIMAL(3,2), Pengali tarif lembur per jam, default: 1.5x)
*   `potongan_terlambat` (INT, Denda flat nominal jika terlambat masuk kerja)
*   `jam_masuk_default` (TIME, Jam masuk kerja standar harian)
*   `menit_toleransi_terlambat` (INT, Batas toleransi terlambat dalam menit)
*   `kuota_karyawan_harian` (INT, Jumlah kuota slot karyawan harian unik yang boleh absen masuk)
*   `jam_skema_b` (TIME, Batas jam kerja untuk pembagian Skema B)
*   `jam_lembur_flat` (TIME, Batas jam pulang malam untuk memicu upah lembur flat penuh)
*   `jam_batas_keluar_awal` (TIME, Jam pulang kerja standar sebelum batas keluar awal dibatasi)

### D. Tabel `leave_requests`
Menyimpan data pengajuan izin/cuti/sakit karyawan.
*   `id` (INT, Primary Key, Auto Increment)
*   `user_id` (INT, Foreign Key ke `users.id`)
*   `jenis` (ENUM('izin', 'sakit', 'cuti'))
*   `tanggal_mulai` / `tanggal_selesai` (DATE, Rentang tanggal pengajuan izin)
*   `alasan` (TEXT, Alasan permohonan)
*   `status` (ENUM('menunggu', 'disetujui', 'ditolak'))
*   `catatan_admin` (VARCHAR(255), Umpan balik dari admin)
*   `reviewed_by` (INT, Foreign Key ke `users.id` sebagai ID Admin pemeriksa)
*   `reviewed_at` (TIMESTAMP)

### E. Tabel `admin_logs`
Menyimpan data audit aktivitas log tindakan admin (demi akuntabilitas sistem).
*   `id` (INT, Primary Key, Auto Increment)
*   `admin_id` (INT, Foreign Key ke `users.id`)
*   `action` (VARCHAR(100), Tindakan yang dilakukan, mis. 'approve_user')
*   `target_type` (VARCHAR(50), Tipe target yang diubah, mis. 'users', 'leave_request')
*   `target_id` (INT, ID baris dari target)
*   `detail` (TEXT, Deskripsi detail perubahan)
*   `created_at` (TIMESTAMP)

### F. Tabel `holidays`
Menyimpan tanggal hari libur yang ditetapkan oleh pemilik UMKM.
*   `id` (INT, Primary key)
*   `tanggal` (DATE, Unik)
*   `keterangan` (VARCHAR(120), Deskripsi libur)

### G. Tabel `quota_blocks`
Mencatat kegagalan absen masuk karyawan yang diblokir akibat kuota harian toko telah penuh pada hari tersebut.
*   `id` (INT, Primary Key)
*   `user_id` (INT, Foreign Key ke `users.id`)
*   `tanggal` (DATE)
*   `alasan` (VARCHAR(50), Default: 'kuota_penuh')

### H. Tabel `password_resets`
Menyimpan data token untuk sistem lupa password/reset password.
*   `id` (INT, Primary Key)
*   `user_id` (INT, Foreign Key)
*   `token` (VARCHAR(64), Unik)
*   `expires_at` (DATETIME)
*   `used_at` (DATETIME, NULL jika belum terpakai)

### I. Tabel `notifications`
Sistem notifikasi internal untuk memberikan info status izin/cuti atau pengumuman dari admin kepada karyawan secara real-time.
*   `id` (INT, Primary Key)
*   `user_id` (INT, Foreign Key)
*   `title` (VARCHAR(120))
*   `message` (VARCHAR(255))
*   `type` (ENUM('info', 'success', 'warning', 'danger'))
*   `link` (VARCHAR(255), Rujukan halaman tujuan klik)
*   `is_read` (TINYINT(1), Status dibaca)

---

## 5. Alur Logika & Algoritma Khusus

Berikut adalah beberapa implementasi logika pemrograman inti yang ada di dalam platform:

### A. Algoritma Geofencing (Rumus Haversine)
Aplikasi memverifikasi apakah koordinat GPS karyawan (`lat_user`, `lng_user`) berada di dalam radius toleransi kantor/toko (`lat_setting`, `lng_setting`). Penghitungan jarak di bumi melengkung diselesaikan menggunakan rumus **Haversine** di file `includes/geofence.php`:

$$\Delta \text{lat} = \text{rad}(\text{lat}_2 - \text{lat}_1)$$
$$\Delta \text{lng} = \text{rad}(\text{lng}_2 - \text{lng}_1)$$
$$a = \sin^2\left(\frac{\Delta \text{lat}}{2}\right) + \cos(\text{rad}(\text{lat}_1)) \cdot \cos(\text{rad}(\text{lat}_2)) \cdot \sin^2\left(\frac{\Delta \text{lng}}{2}\right)$$
$$c = 2 \cdot \text{atan2}\left(\sqrt{a}, \sqrt{1 - a}\right)$$
$$\text{Jarak (meter)} = 6.371.000 \cdot c$$

Jika nilai $\text{Jarak} > \text{geofence\_radius}$, sistem akan langsung menolak proses absensi.

---

### B. Perhitungan Gaji & Lembur Otomatis
Perhitungan gaji karyawan diselesaikan oleh fungsi `calculate_wage()` di file `includes/wage-calculator.php` dengan aturan sebagai berikut:

#### 1. Pembagian Skema Kerja
*   **Skema A (Masuk $\le$ Jam Skema B / Default: 13:00):** 
    *   Mendapatkan upah pokok dasar $\text{base} = 7 \times \text{upah\_per\_jam}$.
    *   Contoh upah per jam Rp 10.000 $\rightarrow$ Gaji pokok Rp 70.000.
*   **Skema B (Masuk > Jam Skema B / Default: 13:00):**
    *   Mendapatkan upah pokok dasar $\text{base} = 6 \times \text{upah\_per\_jam}$.
    *   Contoh upah per jam Rp 10.000 $\rightarrow$ Gaji pokok Rp 60.000.

#### 2. Logika Lembur (Overtime)
*   **Lembur Flat Malam:** Jika karyawan pulang (check-out) melewati `jam_lembur_flat` (Default: 21:30 WIB), mereka langsung mendapatkan upah lembur flat penuh (Skema A mendapat upah setara $13 \times \text{upah\_per\_jam}$, Skema B mendapat upah setara $12 \times \text{upah\_per\_jam}$).
*   **Lembur Per Jam Standar:** Jika pulang sebelum batas malam tetapi durasi kerja melebihi kuota standar (misal 8 jam), maka jam lembur dihitung dengan cara:
    $$\text{Durasi Lembur (Jam)} = \frac{\text{Jam Keluar} - (\text{Jam Masuk} + \text{Kuota Standar})}{60}$$
    $$\text{Nominal Lembur} = \lfloor\text{Durasi Lembur} \times (\text{upah\_per\_jam} \times \text{multiplier\_lembur})\rfloor$$

#### 3. Potongan Denda Terlambat
Jika karyawan melakukan check-in melewati batas jam masuk ditambah menit toleransi terlambat:
$$\text{Status} = \text{"Terlambat"}$$
$$\text{Potongan Denda} = \text{potongan\_terlambat (mis. Rp 5.000)}$$
Gaji bersih harian dihitung sebagai:
$$\text{Upah Akhir} = \text{Gaji Pokok} + \text{Nominal Lembur} - \text{Potongan Denda}$$
*(Upah Akhir tidak bisa kurang dari Rp 0)*

---

### C. Pembatasan Kuota Karyawan Harian (Daily Quota Blocking)
Fitur penting bagi UMKM untuk menghindari pembengkakan biaya tenaga kerja di hari-hari sepi:
1.  Admin mengeset batas kuota karyawan harian di menu pengaturan (misal: 4 orang per hari).
2.  Setiap kali ada karyawan baru yang mencoba absen masuk:
    *   Sistem menghitung total jumlah karyawan unik yang sudah check-in pada tanggal tersebut (`jam_masuk IS NOT NULL`).
    *   Jika jumlah $\ge$ batas kuota, pendaftaran check-in ditolak dengan pesan peringatan kuota penuh.
    *   Kejadian pemblokiran dicatat secara otomatis dalam tabel `quota_blocks` agar admin tahu siapa saja karyawan yang tidak kebagian slot kerja.

---

### D. Penanganan Keluar Awal (Early Checkout)
Untuk mencegah karyawan pulang sebelum jam kerja usai tanpa izin resmi:
1.  Sistem mendeteksi jika check-out terjadi sebelum `jam_batas_keluar_awal` (Default: 17:00 WIB).
2.  Jika ya, karyawan wajib mengisi kolom alasan secara tertulis (maksimal 500 karakter) dan mengunggah foto surat dokter/bukti kedinasan melalui antarmuka kamera atau file uploader.
3.  Tanpa bukti pendukung tersebut, tombol check-out akan menolak proses penyimpanan absensi.

---

### E. Sistem Pengamanan Aplikasi (Security Engine)
Aplikasi dienkripsi dengan standar keamanan modern:
1.  **Cross-Site Request Forgery (CSRF) Protection:** Menggunakan token unik acak di setiap sesi (`csrf_token()`) yang diinjeksikan pada form. Setiap kiriman POST divalidasi keamanannya untuk memastikan request tidak dimanipulasi oleh situs pihak ketiga.
2.  **Brute Force Rate Limiter:** Jika ada upaya login gagal berulang kali pada nomor HP tertentu, sistem akan mencatat total kegagalan. Jika gagal mencapai 5 kali dalam rentang waktu 15 menit, sistem memblokir upaya login pada nomor tersebut selama 15 menit berikutnya (mengembalikan pesan sisa waktu tunggu dalam detik).
3.  **XSS Protection:** Penggunaan fungsi pembantu `e()` secara global untuk melakukan sanitasi HTML entities terhadap semua input data pengguna sebelum dirender ke layar browser.

---

### F. Manajemen Data & Backup Pintar
*   **Export Excel Pintar:** Laporan rekap absensi dan penggajian dibuat dengan menghasilkan file dokumen spreadsheet Microsoft Excel (.xls) secara instan. Metode ini dilakukan dengan merender struktur tabel HTML murni dengan penandaan stylesheet khusus, lalu mengirimkannya dengan header `Content-Type: application/vnd.ms-excel` ke browser pengguna. Ini mengeliminasi kebutuhan pustaka eksternal yang lambat.
*   **Backup & Restore Database:** Admin dapat mencadangkan seluruh data tabel (struktur + isi data) menjadi file skrip `.sql` yang langsung terunduh secara otomatis. Terdapat juga panel manajemen file backup untuk mengembalikan (restore) data database ke kondisi cadangan sebelumnya atau menghapus berkas cadangan lama.

---

## 6. Pembagian Hak Akses Pengguna (Roles & Features)

Aplikasi memiliki pembagian otorisasi akses yang jelas dan ketat diatur di file routing `public/index.php`:

| Fitur | Karyawan | Administrator |
| :--- | :---: | :---: |
| Melakukan Absen Masuk & Keluar (Selfie + GPS) | Ya | Tidak |
| Melihat Estimasi Gaji Live Hari Ini | Ya | Tidak |
| Mengajukan Cuti / Sakit / Izin Kerja | Ya | Tidak |
| Edit Profil Pribadi & Nomor Rekening | Ya | Ya (Profil Sendiri) |
| Melihat Dasbor KPI, Grafik Tren & Riwayat Log | Tidak | Ya |
| Verifikasi Akun Karyawan Baru (Approve/Suspend) | Tidak | Ya |
| Menyetujui / Menolak Pengajuan Izin Cuti | Tidak | Ya |
| Mengelola Hari Libur Nasional & Toko | Tidak | Ya |
| Menandai Status Pembayaran Gaji (Lunas/Pending) | Tidak | Ya |
| Ekspor Laporan Excel Bulanan & Slip Gaji Batch | Tidak | Ya |
| Mengubah Parameter Jam Kerja, Geofence, & Tarif Upah | Tidak | Ya |
| Melakukan Backup & Restore Database | Tidak | Ya |

---

## 7. Cara Menjalankan Project Secara Lokal (XAMPP)

1.  **Persiapkan Folder:**
    Salin folder proyek ini ke dalam folder server web lokal Anda, misalnya di `C:\xampp\htdocs\app`.
2.  **Konfigurasi Database lokal:**
    *   Jalankan panel control XAMPP dan aktifkan layanan Apache dan MySQL.
    *   Buka phpMyAdmin (`http://localhost/phpmyadmin`).
    *   Buat database baru bernama `absensi_umkm`.
    *   Pilih database `absensi_umkm`, masuk ke tab **Import**, pilih berkas `database/schema.sql` lalu klik **Go/Kirim**.
    *   *(Opsional)* Import berkas `database/seed-demo-1bulan.sql` jika ingin menguji dasbor admin dengan data rekap absensi dummy 1 bulan penuh.
3.  **Buat File Environment (.env):**
    Salin berkas `.env.example` di direktori utama menjadi berkas baru bernama `.env`. Berkas `.env` berisi informasi kredensial MySQL hosting produksi. (Tenang saja, saat dijalankan di `localhost`, aplikasi otomatis mengenali alamat lokal dan akan langsung menggunakan database XAMPP default tanpa sandi).
4.  **Jalankan Aplikasi:**
    *   Buka terminal di folder proyek (`C:\xampp\htdocs\app`), jalankan server bawaan PHP:
        ```bash
        php -S 127.0.0.1:8000 -t public
        ```
    *   Atau buka lewat browser melalui URL localhost XAMPP Anda:
        ```txt
        http://localhost/app/
        ```

---

## 8. Cara Deploy Ke Hosting Produksi (InfinityFree)

1.  **Buat Database di vPanel:**
    *   Masuk ke vPanel InfinityFree milik Anda.
    *   Masuk ke menu **MySQL Databases** dan buat database baru (contoh nama lengkap: `if0_42012557_testabsen`).
2.  **Import Tabel Database:**
    *   Masuk ke phpMyAdmin melalui vPanel.
    *   Pilih database produksi Anda, klik tab **Import**, lalu pilih berkas `database/infinity/schema.sql` dan klik **Go**.
3.  **Salin File & Atur Folder public:**
    *   Upload seluruh isi proyek ke dalam direktori `htdocs` hosting.
    *   Pada konfigurasi hosting InfinityFree, pastikan **Document Root** domain Anda diarahkan ke folder `public/` (yaitu `htdocs/public`).
4.  **Buat File `.env` Produksi:**
    *   Buat berkas `.env` di dalam folder root produksi (`htdocs/`, sejajar dengan folder `config` dan `public`).
    *   Isi berkas tersebut dengan kredensial database InfinityFree Anda (salin dari `.env.infinity.example`):
        ```env
        DB_HOST=sql303.infinityfree.com
        DB_PORT=3306
        DB_NAME=if0_42012557_testabsen
        DB_USER=if0_42012557
        DB_PASS=SandiMySQLvPanelAnda
        ```
5.  **Uji Coba & Bersihkan:**
    *   Buka peramban dan jalankan script tes koneksi:
        `https://domain-anda.infinityfreeapp.com/database/infinity/test-connection.php`
    *   Setelah koneksi dipastikan sukses ("Koneksi OK"), hapus berkas `test-connection.php` dari hosting demi keamanan.
6.  **Akun Login Default Pertama:**
    *   **No. HP:** `081200000000`
    *   **Password:** `123456`
    *   *(Segera ubah kata sandi admin utama di menu profil/pengaturan setelah berhasil masuk pertama kali)*.

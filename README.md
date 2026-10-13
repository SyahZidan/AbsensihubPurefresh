# Absensi Hub - Pure Fresh

Sistem pencatatan kehadiran (absensi) dan kalkulasi upah harian berbasis web untuk UMKM **Pure Fresh**. Proyek ini dikembangkan sebagai implementasi tugas kuliah mata kuliah Manajemen Proyek Teknologi Informasi (MPTI), mengacu pada proposal awal kebutuhan operasional UMKM.

Repository resmi: [https://github.com/SyahZidan/AbsensihubPurefresh.git](https://github.com/SyahZidan/AbsensihubPurefresh.git)

---

## Progres & Fitur Inti (Current Scope)

Sesuai tahapan logbook saat ini, fokus pengerjaan diarahkan pada penentuan framework/stack utama serta implementasi **fitur inti**:

1. **Autentikasi & Manajemen Akun**
   - Login karyawan & administrator menggunakan nomor HP (ramah seluler untuk pekerja harian).
   - Registrasi mandiri karyawan dengan alur persetujuan (*approval*) oleh admin.
2. **Presensi Harian Berbasis Validasi (Check-in & Check-out)**
   - **GPS Geofencing:** Memvalidasi radius jarak fisik karyawan terhadap titik koordinat toko Pure Fresh secara real-time.
   - **Face Capture:** Pengambilan foto wajah selfie instan via kamera peramban untuk verifikasi kehadiran.
3. **Rekapitulasi Kehadiran**
   - **Karyawan:** Halaman riwayat kehadiran harian mandiri beserta jam masuk dan jam pulang.
   - **Administrator:** Monitoring absensi harian seluruh staf, foto selfie, koordinat lokasi GPS, dan status kehadiran.
4. **Perhitungan Upah Dasar**
   - Penghitungan estimasi upah harian otomatis berdasarkan durasi kerja dan toleransi keterlambatan.
   - Rekapitulasi total upah dasar staf per periode.
5. **Konfigurasi Toko & Geofence**
   - Pengaturan titik koordinat Latitude/Longitude toko, radius toleransi (meter), serta jadwal jam kerja standar.

*(Fitur lanjutan seperti pengajuan izin/cuti berjenjang, laporan kuota, dan modul disbursement kasir disimpan di kode dan disiapkan untuk milestone berikutnya).*

---

## Stack Teknologi

- **Backend:** PHP Native 8.x (arsitektur prosedural bersih + PDO Prepared Statements)
- **Database:** MySQL / MariaDB
- **Frontend & UI:** HTML5, Tailwind CSS (via CDN), Alpine.js (reactive micro-framework), Lucide Icons
- **Browser APIs:** HTML5 Geolocation API, MediaDevices & Canvas API (Webcam Face Capture)

---

## Struktur Direktori

```txt
├── config/             Konfigurasi aplikasi dan koneksi database
├── includes/           Helper, auth, geofence, absensi, kalkulator upah dasar
├── public/             Entry point aplikasi publik, assets (CSS/JS), folder upload
├── views/              Halaman antarmuka admin, karyawan, login, dan register
│   ├── admin/          Dasbor, daftar karyawan, monitoring absensi, rekap upah, pengaturan
│   ├── karyawan/       Beranda mobile, check-in GPS/kamera, check-out, riwayat, profil
│   └── partials/       Komponen UI header, sidebar, navigation bar, dan flash message
├── api/                Endpoint backend pemrosesan aksi & form
├── database/           Skema SQL dan data demo
├── index.php           Root front controller (redirect ke public/index.php)
└── README.md
```

---

## Cara Menjalankan Secara Lokal

1. **Import Database:**
   ```sql
   SOURCE database/schema.sql;
   ```
   *(Opsional: jalankan `SOURCE database/seed-demo-1bulan.sql;` untuk mengisi data dummy pengujian).*

2. **Konfigurasi Environment:**
   Salin templat konfigurasi `.env.example` menjadi `.env`, lalu sesuaikan kredensial MySQL lokal Anda:
   ```env
   DB_HOST_LOCAL=127.0.0.1
   DB_NAME_LOCAL=absensi_umkm
   DB_USER_LOCAL=root
   DB_PASS_LOCAL=
   ```

3. **Jalankan Web Server:**
   Buka terminal di direktori proyek dan jalankan PHP built-in server:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

4. **Akses Aplikasi:**
   Buka peramban pada alamat:
   ```txt
   http://127.0.0.1:8000
   ```
   Aplikasi akan langsung mengarahkan Anda ke halaman login portal.

---

## Akun Demo Pengujian

| Peran | Nomor HP | Password Default |
|---|---|---|
| **Administrator** | `081200000000` | `admin123` |
| **Karyawan 1** | `081234567890` | `123456` |
| **Karyawan 2** | `081234567891` | `123456` |

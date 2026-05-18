---
name: Tech Stack Upgrade Plan
about: Perencanaan major upgrade untuk PHP, Laravel, Filament, dan dependencies terkait
title: 'Tech Debt: Major Stack Upgrade (PHP, Laravel, Filament)'
labels: enhancement, tech-debt, major-upgrade
assignees: ''
---

## 🎯 Tujuan Utama (Objective)

Melakukan *Major Upgrade* pada *Tech Stack* (teknologi dasar) aplikasi E-Gudang untuk memastikan keamanan, peningkatan performa, dan kompatibilitas jangka panjang. Implementasi ini akan berfokus pada pembaruan dari sisi *Server Environment* hingga *Framework Dependencies*.

## 📋 Daftar Tugas (Task Checklist)

Tahapan di bawah ini harus dieksekusi secara berurutan (*Sequential*):

### 1. Upgrade PHP Environment (Laragon)
- [ ] Unduh versi PHP terbaru (Disarankan minimal **PHP 8.2** atau **PHP 8.3**) versi *Thread Safe (TS)* dari repositori resmi PHP untuk Windows.
- [ ] Ekstrak dan masukkan *folder* tersebut ke dalam direktori instalasi Laragon (`C:\laragon\bin\php\`).
- [ ] Ubah versi PHP aktif melalui antarmuka Laragon (*Right-click Laragon Tray* -> *Menu* -> *PHP* -> *Version*).
- [ ] Pastikan semua *PHP Extensions* krusial (seperti `pdo_pgsql`, `gd`, `zip`, `mbstring`, `intl`) telah di-*uncomment* dan diaktifkan di dalam file `php.ini` versi baru tersebut.
- [ ] Lakukan verifikasi di *Terminal* dengan menjalankan perintah `php -v` dan pastikan versi yang tampil sudah yang terbaru.

### 2. Upgrade Laravel Framework
- [ ] Buka file `composer.json` dan perbarui *Version Constraint* untuk *package* `laravel/framework` ke versi mayor terbaru (misalnya dari `^10.0` menuju `^11.0` atau `^12.0`).
- [ ] Perbarui juga *Version Constraint* untuk dependensi bawaan dan pihak ketiga (seperti `laravel/sanctum`, `laravel/tinker`, `spatie/laravel-permission`, `barryvdh/laravel-dompdf`, dan `maatwebsite/excel`) agar terjamin *Compatible* dengan versi Laravel yang baru.
- [ ] Baca secara detail **Laravel Upgrade Guide** dari dokumentasi resmi untuk menangani potensi *Breaking Changes* (terutama yang berkaitan dengan struktur *Middleware*, *Routing*, dan *Exception Handling*).
- [ ] Jalankan perintah `composer update -W` (atau `composer update --with-all-dependencies`) untuk menginstal *package* yang baru.

### 3. Upgrade Filament & UI Ecosystem
- [ ] Pastikan *package* `filament/filament` dan sub-komponennya telah diarahkan ke versi stabil terbaru yang mendukung versi Laravel yang baru dipasang.
- [ ] Setelah proses *composer update* selesai, wajib menjalankan perintah `php artisan filament:upgrade` untuk membersihkan dan mempublikasikan *Assets* terbaru dari Filament.
- [ ] Jalankan *Cache Clearing* secara menyeluruh dengan perintah `php artisan optimize:clear` dan `php artisan view:clear` untuk menghindari masalah *Stale Views*.

### 4. Testing & Verification
- [ ] Jalankan aplikasi menggunakan *Local Development Server* (`php artisan serve`). Pastikan tidak terdapat *Fatal Error* atau *Deprecation Warnings* yang *blocking* di layar maupun di *Log* (`storage/logs/laravel.log`).
- [ ] Lakukan *Manual Testing* pada fungsi-fungsi sistem esensial:
  - *Authentication Flow* (Login, Logout, RBAC/Roles).
  - Operasi *CRUD* di seluruh modul utama (*Products*, *Categories*, *Stock Transactions*, *Users*).
  - Komponen Generator: Pembuatan dan *Render* Barcode serta QR Code.
  - Modul Ekspor: Pembuatan dokumen PDF dan laporan berformat Excel.
- [ ] Jika terjadi *Error* akibat fungsi pihak ketiga yang usang (*Deprecated Methods*), lakukan modifikasi kode pada level *Controller* atau *Service* untuk menyesuaikan standar baru.

## ⚠️ Catatan Penting (Important Notes)

- **Database Backup:** Wajib melakukan pencadangan (*Backup*) *Database* PostgreSQL secara penuh sebelum memulai tahapan *Upgrade* ini.
- **Git Branching:** Lakukan semua pekerjaan ini di dalam *Git Branch* terpisah (misal: `feature/upgrade-stack-2026`) untuk menghindari kerusakan sistem pada *branch* `main`.
- **Dependency Conflicts:** Jika terjadi benturan dependensi pada saat menjalankan Composer, jangan menghapus folder `vendor` secara paksa sebelum mencoba menyelesaikan versi *package* yang bermasalah (*Troubleshoot Conflict*). Jika ada *package* yang sudah mati (*Abandoned*), carilah *Library* alternatif.

## ✅ Progress Update (18 Mei 2026)

- **Filament Upgrade:** Berhasil di-upgrade ke versi `v3.3.50`.
- **Security Patch:** Berhasil menjalankan `composer audit` dan memperbaiki 5 *vulnerabilities* (*CVE*) yang ditemukan pada *package* `phpoffice/phpspreadsheet` dengan melakukan instalasi versi yang aman.
- **PHP 8.5 Fix:** Telah memperbaiki *Deprecation Warning* pada konstanta `PDO::MYSQL_ATTR_SSL_CA` (diubah menjadi `Pdo\Mysql::ATTR_SSL_CA`) pada konfigurasi *database* (`config/database.php` dan *vendor*).
- **Composer Update:** Telah melakukan `composer self-update` untuk menangani *warning* deprecation usang yang berasal dari aplikasi Composer di lingkungan PHP 8.5.

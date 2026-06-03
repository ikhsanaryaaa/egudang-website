# Tutorial Deploy E-Gudang ke Coolify

Panduan lengkap untuk deploy aplikasi E-Gudang (Laravel 11 + Filament 3) menggunakan Docker di Coolify.

---

## Prasyarat

Sebelum memulai, pastikan sudah memiliki:

- **VPS/Server** dengan minimal 2GB RAM dan 20GB disk
- **Coolify** sudah ter-install di server ([panduan install Coolify](https://coolify.io/docs/installation))
- **Domain** yang sudah diarahkan (DNS A Record) ke IP server
- **Repository GitHub** `egudang-website` bisa diakses dari Coolify

---

## Daftar Isi

1. [Setup Database PostgreSQL di Coolify](#1-setup-database-postgresql-di-coolify)
2. [Membuat Project dan Resource Baru](#2-membuat-project-dan-resource-baru)
3. [Konfigurasi Build](#3-konfigurasi-build)
4. [Konfigurasi Environment Variables](#4-konfigurasi-environment-variables)
5. [Konfigurasi Domain dan SSL](#5-konfigurasi-domain-dan-ssl)
6. [Konfigurasi Persistent Storage](#6-konfigurasi-persistent-storage)
7. [Konfigurasi Health Check](#7-konfigurasi-health-check)
8. [Deploy](#8-deploy)
9. [Post-Deployment Verification](#9-post-deployment-verification)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Setup Database PostgreSQL di Coolify

Database harus dibuat terlebih dahulu sebelum deploy aplikasi.

### Langkah:

1. Login ke **Coolify Dashboard** (`https://coolify.domain-anda.com`)
2. Buka project yang akan digunakan (atau buat project baru)
3. Klik **"+ New"** → pilih **"Database"**
4. Pilih **PostgreSQL** → pilih versi **16**
5. Isi konfigurasi database:

   | Field | Value |
   |---|---|
   | Database Name | `e_gudang` |
   | Database User | `egudang` |
   | Database Password | *(generate password yang kuat)* |

6. Klik **"Start"** untuk menjalankan PostgreSQL
7. **Catat informasi koneksi** yang muncul:
   - `DB_HOST` → biasanya berupa nama internal container, contoh: `postgresql-xxxxx`
   - `DB_PORT` → `5432`
   - `DB_DATABASE` → `e_gudang`
   - `DB_USERNAME` → `egudang`
   - `DB_PASSWORD` → password yang di-generate

> **Catatan:** Gunakan **internal hostname** (bukan IP publik) untuk `DB_HOST` agar koneksi tetap di internal Docker network dan lebih cepat.

---

## 2. Membuat Project dan Resource Baru

### Langkah:

1. Di Coolify Dashboard, buka project yang sama dengan database
2. Klik **"+ New"** → pilih **"Application"**
3. Pilih **source**: **GitHub (Public)** atau **GitHub (Private)** sesuai repository
4. Masukkan URL repository:
   ```
   https://github.com/ikhsanaryaaa/egudang-website
   ```
5. Pilih **branch**: `main` (atau branch yang sudah di-merge)
6. Klik **"Continue"**

---

## 3. Konfigurasi Build

Setelah resource dibuat, konfigurasikan build settings:

1. Buka tab **"General"** di resource yang baru dibuat
2. Ubah **Build Pack** ke **"Dockerfile"**
3. Pastikan **Dockerfile Location** terisi: `Dockerfile` (atau `/Dockerfile`)
4. **Port Exposes**: set ke `80`

> **Penting:** Jangan gunakan Docker Compose sebagai build pack di Coolify. File `docker-compose.yml` hanya untuk local testing. Coolify akan menggunakan `Dockerfile` langsung.

---

## 4. Konfigurasi Environment Variables

Buka tab **"Environment Variables"** dan tambahkan variabel berikut:

### Application

```env
APP_NAME="E-Gudang"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://egudang.domain-anda.com
```

> **Catatan:** `APP_KEY` tidak perlu diisi manual. Entrypoint script akan generate otomatis jika kosong. Namun setelah deploy pertama, **copy** APP_KEY dari log lalu simpan sebagai environment variable agar key tetap konsisten antar deployment.

### Database

```env
DB_CONNECTION=pgsql
DB_HOST=<internal-hostname-postgresql>
DB_PORT=5432
DB_DATABASE=e_gudang
DB_USERNAME=egudang
DB_PASSWORD=<password-dari-langkah-1>
```

> Ganti `<internal-hostname-postgresql>` dengan hostname internal PostgreSQL dari Coolify.

### Logging

```env
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Driver

```env
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### Mail (Opsional)

```env
MAIL_MAILER=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<smtp-username>
MAIL_PASSWORD=<smtp-password>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@domain-anda.com
MAIL_FROM_NAME="E-Gudang"
```

---

## 5. Konfigurasi Domain dan SSL

1. Buka tab **"General"** → bagian **"Domains"**
2. Masukkan domain:
   ```
   https://egudang.domain-anda.com
   ```
3. SSL certificate akan di-generate otomatis oleh Coolify (Let's Encrypt)
4. Pastikan DNS A Record sudah mengarah ke IP server Coolify

### Verifikasi DNS

Cek DNS resolution sebelum deploy:
```bash
dig egudang.domain-anda.com
# atau
nslookup egudang.domain-anda.com
```

Pastikan hasilnya mengarah ke IP server Coolify.

---

## 6. Konfigurasi Persistent Storage

Agar file uploads tidak hilang saat re-deploy, tambahkan persistent storage:

1. Buka tab **"Storages"** (atau **"Persistent Storage"**)
2. Klik **"+ Add"**
3. Isi konfigurasi:

   | Field | Value |
   |---|---|
   | Name | `egudang-storage` |
   | Source Path | *(biarkan kosong, Coolify generate otomatis)* |
   | Destination Path | `/var/www/html/storage/app` |

4. Klik **"Save"**

> **Penting:** Volume ini menyimpan semua file upload (surat jalan, invoice, foto produk, dll). Data akan persisten antar deployment.

---

## 7. Konfigurasi Health Check

1. Buka tab **"General"** → bagian **"Health Check"**
2. Isi konfigurasi:

   | Field | Value |
   |---|---|
   | Health Check Path | `/health` |
   | Health Check Port | `80` |
   | Health Check Interval | `30` (detik) |
   | Health Check Timeout | `10` (detik) |
   | Health Check Retries | `3` |
   | Health Check Start Period | `60` (detik) |

> Start period 60 detik memberi waktu entrypoint script untuk menjalankan migration dan cache optimization sebelum health check mulai.

---

## 8. Deploy

### Deploy Pertama

1. Pastikan semua konfigurasi di langkah 1-7 sudah benar
2. Klik tombol **"Deploy"**
3. Pantau log build di tab **"Deployments"**

### Build Process (Otomatis)

Berikut yang terjadi saat build dan startup:

```
1. [Build] Node.js install dependencies (npm ci)
2. [Build] Vite build frontend assets (npm run build)
3. [Build] PHP install system dependencies (Alpine packages)
4. [Build] PHP install extensions (pdo_pgsql, gd, etc.)
5. [Build] Composer install (--no-dev --optimize-autoloader)
6. [Build] Copy built assets, configs, set permissions
7. [Startup] Generate APP_KEY (jika belum ada)
8. [Startup] Cache config, routes, views, icons
9. [Startup] Run database migrations
10. [Startup] Create storage link
11. [Startup] Fix permissions
12. [Startup] Start Supervisor (nginx + php-fpm + queue worker)
```

### Estimasi Waktu Build

| Tahap | Estimasi |
|---|---|
| Node.js build | 1-3 menit |
| PHP extensions | 2-5 menit |
| Composer install | 1-2 menit |
| Total build pertama | 5-10 menit |
| Build selanjutnya (cached) | 1-3 menit |

---

## 9. Post-Deployment Verification

Setelah deployment selesai, verifikasi:

### A. Cek Health Endpoint

```bash
curl https://egudang.domain-anda.com/health
```

Expected response:
```json
{
    "status": "ok",
    "timestamp": "2026-06-03T12:00:00+00:00"
}
```

### B. Cek Halaman Admin

Buka browser dan akses:
```
https://egudang.domain-anda.com/admin
```

Pastikan halaman login Filament muncul.

### C. Cek Deployment Log

Di Coolify Dashboard → tab **"Deployments"** → klik deployment terbaru → cek log untuk:
- ✅ `Generating application key...` (deploy pertama)
- ✅ `Caching configuration...`
- ✅ `Running database migrations...`
- ✅ `Application ready!`

### D. Simpan APP_KEY

**Penting untuk deploy pertama:**

1. Buka log deployment
2. Cari baris yang berisi `APP_KEY=base64:xxxxxxxxxxxx`
3. Copy nilai APP_KEY tersebut
4. Buka tab **"Environment Variables"**
5. Tambahkan:
   ```
   APP_KEY=base64:xxxxxxxxxxxx
   ```
6. Ini memastikan APP_KEY tetap sama di deployment selanjutnya

---

## 10. Troubleshooting

### Build gagal: "npm ci" error

**Penyebab:** `package-lock.json` tidak sinkron dengan `package.json`

**Solusi:**
```bash
# Di local machine
rm -rf node_modules package-lock.json
npm install
git add package-lock.json
git commit -m "fix: regenerate package-lock.json"
git push
```

---

### Build gagal: PHP extension error

**Penyebab:** Dependency system yang kurang

**Solusi:** Pastikan `Dockerfile` sudah include semua package yang dibutuhkan di `apk add`.

---

### Container crash: "permission denied"

**Penyebab:** Directory `storage/` atau `bootstrap/cache/` tidak writable

**Solusi:** Entrypoint script seharusnya handle ini otomatis. Jika masih error, tambahkan di environment variables:
```env
LOG_CHANNEL=stderr
```

---

### Database connection refused

**Penyebab:** `DB_HOST` salah atau PostgreSQL belum running

**Solusi:**
1. Pastikan PostgreSQL service di Coolify dalam status **Running**
2. Gunakan **internal hostname** (bukan `localhost` atau IP publik)
3. Pastikan kedua resource (app dan database) ada di **project yang sama** di Coolify

---

### Migration error: "table already exists"

**Penyebab:** Migration sudah pernah dijalankan sebelumnya

**Solusi:** Ini seharusnya tidak terjadi karena Laravel migration tracking. Jika terjadi, cek tabel `migrations` di database.

---

### File upload hilang setelah re-deploy

**Penyebab:** Persistent storage tidak terkonfigurasi

**Solusi:** Pastikan volume mount sudah dikonfigurasi di Coolify (lihat [langkah 6](#6-konfigurasi-persistent-storage)).

---

### Halaman 502 Bad Gateway

**Penyebab:** PHP-FPM belum ready atau crash

**Solusi:**
1. Cek log container di Coolify
2. Pastikan memory server cukup (minimal 2GB RAM)
3. Cek apakah `supervisord` menjalankan semua process

---

### Queue job tidak diproses

**Penyebab:** `QUEUE_CONNECTION` masih `sync` atau queue worker crash

**Solusi:**
1. Set `QUEUE_CONNECTION=database` di environment variables
2. Pastikan tabel `jobs` dan `failed_jobs` sudah ada (jalankan migration)
3. Cek log supervisor untuk queue worker

---

## Update / Re-deploy

Untuk deploy ulang setelah ada perubahan kode:

1. Push perubahan ke branch `main` di GitHub
2. Di Coolify Dashboard, klik **"Deploy"** (atau aktifkan auto-deploy via webhook)
3. Coolify akan rebuild image dan restart container
4. Migration otomatis dijalankan oleh entrypoint script

### Mengaktifkan Auto-Deploy (Webhook)

1. Buka tab **"General"** → aktifkan **"Auto Deploy"**
2. Copy webhook URL yang diberikan Coolify
3. Di GitHub repository → **Settings** → **Webhooks** → **Add webhook**
4. Paste webhook URL
5. Pilih event: **"Just the push event"**
6. Klik **"Add webhook"**

Sekarang setiap push ke `main` akan otomatis trigger deployment.

---

## Struktur File Docker

```
egudang-website/
├── Dockerfile                          # Multi-stage build config
├── docker-compose.yml                  # Local testing only
├── .dockerignore                       # Build context exclusions
└── docker/
    ├── entrypoint.sh                   # Container startup script
    ├── nginx/
    │   └── default.conf                # Nginx virtual host config
    ├── php/
    │   └── opcache.ini                 # PHP OPcache production config
    └── supervisor/
        └── supervisord.conf            # Process manager config
```

---

## Catatan Penting

- **Jangan** edit environment variables langsung di `.env` file dalam container. Selalu gunakan Coolify UI.
- **Jangan** gunakan `docker-compose.yml` untuk deploy di Coolify. File ini hanya untuk local testing.
- **Backup database** secara berkala. Gunakan fitur backup di Coolify atau setup `pg_dump` cron job.
- **Monitor disk usage** karena file uploads dan database akan terus bertambah.
- SSL certificate di-handle sepenuhnya oleh Coolify (auto-renew Let's Encrypt).

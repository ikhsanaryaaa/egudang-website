# E-Gudang

Aplikasi **inventory management** berbasis web untuk mengelola produk, kategori, transaksi stok (barang masuk/keluar), dokumen inventory, serta reporting gudang. Dibangun dengan Laravel dan Filament Admin Panel, dengan sistem role & permission yang fleksibel.

🔗 **Production**: [https://e-gudang.my.id](https://e-gudang.my.id)

---

## Fitur Utama

- **Authentication & RBAC**: Login/logout dengan role & permission berbasis [spatie/laravel-permission](https://spatie.be/docs/laravel-permission).
- **User Management**: CRUD user, assign role, dan reset password.
- **Category Management**: CRUD kategori dengan kode unik per kategori.
- **Product Management**: CRUD produk, upload gambar, search & filter, serta **SKU generator** otomatis (format `KODE-BRAND-NAMA-UNIT-000`).
- **Stock Transaction**: Pencatatan barang masuk, barang keluar, dan stock adjustment dengan histori transaksi. Stok tidak boleh negatif.
- **File Upload**: Upload multiple dokumen (PDF, DOC/DOCX, XLS/XLSX, JPG/PNG) sebagai lampiran produk (Surat Jalan, Invoice, PO, dll).
- **Dashboard**: Ringkasan total produk, low stock, stok masuk/keluar hari ini, dan recent activity.
- **Reporting**: Export laporan stok ke **Excel** dan **PDF**, termasuk laporan low stock.
- **Audit Log**: Pencatatan otomatis aktivitas Create, Update, dan Delete pada modul utama.

---

## Tech Stack

| Komponen | Teknologi |
|---|---|
| Backend Framework | Laravel 11 (PHP 8.2+) |
| Admin Panel | Filament 3.2 |
| Database | MySQL / MariaDB |
| Authorization | spatie/laravel-permission |
| Excel Export | maatwebsite/excel |
| PDF Export | barryvdh/laravel-dompdf |
| Frontend Build | Vite + TailwindCSS + Alpine.js |
| Deployment | Docker + Coolify (Nginx + PHP-FPM) |

---

## Role & Permission

| Module | Super Admin | Manager | Kepala Gudang | Operator Gudang |
|---|---|---|---|---|
| Dashboard | Read | Read | Read | Read |
| Reports | Read/Export | Read/Export | Read/Export | No Access |
| Stock Transactions | Read/Create | Read | CRUD Permission | Read/Create |
| Categories | CRUD | Read | CRUD | Read |
| Products | CRUD | No Access | CRUD | Read |
| EOQ Calculations | CRUD | No Access | CRUD | CRUD |
| User Management | CRUD | No Access | No Access | No Access |
| Roles & Permissions | CRUD | No Access | No Access | No Access |
| Audit Log | Read | No Access | Read | No Access |

Stock transactions are immutable in the current UI, so edit/delete actions are not exposed even when a role owns the corresponding permissions.

Super Admin can create custom roles and attach any combination of the available permissions. Custom roles can enter the admin panel and only see pages and actions allowed by their permissions. Role management and user role assignment remain restricted to Super Admin, and the `Super Admin` role itself cannot be edited or deleted.

---

## Requirements

- PHP >= 8.2 (production menggunakan PHP 8.4)
- Composer 2.x
- Node.js >= 18 & npm
- MySQL 8.x / MariaDB 10.x
- Ekstensi PHP: `gd`, `pdo_mysql`, `mbstring`, `zip`, `bcmath`

---

## Instalasi Lokal

```bash
# 1. Clone repository
git clone https://github.com/ikhsanaryaaa/egudang-website.git
cd egudang-website

# 2. Install dependency PHP
composer install

# 3. Install dependency frontend
npm install

# 4. Setup environment
cp .env.example .env
php artisan key:generate

# 5. Konfigurasi database di .env, lalu jalankan migration + seeder
php artisan migrate --seed

# 6. Symlink storage (agar file upload bisa diakses publik)
php artisan storage:link

# 7. Build assets & jalankan server
npm run dev
php artisan serve
```

Aplikasi dapat diakses di `http://localhost:8000` dan admin panel di `http://localhost:8000/admin`.

### Default Seeder

Seeder `RoleAndPermissionSeeder` akan membuat role & permission dasar. Sesuaikan kredensial admin awal pada seeder sebelum menjalankan `php artisan db:seed` di environment baru.

---

## Konfigurasi Environment

Variabel penting pada `.env`:

```env
APP_NAME=E-Gudang
APP_ENV=production
APP_URL=https://e-gudang.my.id

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=egudang
DB_USERNAME=...
DB_PASSWORD=...

FILESYSTEM_DISK=public
```

> [!IMPORTANT]
> Di production, pastikan `APP_URL` di-set ke `https://e-gudang.my.id` agar Filament & Livewire meng-generate URL asset dengan skema HTTPS yang benar di belakang reverse proxy Coolify.

---

## Deployment dengan Coolify

Aplikasi ini di-deploy menggunakan **Coolify** dengan build berbasis `Dockerfile` (Nginx + PHP-FPM) dan domain **e-gudang.my.id**.

### 1. Konfigurasi Aplikasi di Coolify

- **Source**: Git repository `ikhsanaryaaa/egudang-website`, branch `main`.
- **Build Pack**: Dockerfile.
- **Port**: `80` (Nginx di dalam container).
- **Domain**: `https://e-gudang.my.id` (aktifkan SSL/Let's Encrypt otomatis dari Coolify).

### 2. Environment Variables

Set seluruh variabel `.env` (lihat bagian di atas) pada tab **Environment Variables** di Coolify. Pastikan:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://e-gudang.my.id`
- Kredensial database mengarah ke database service di Coolify.

### 3. Persistent Storage

> [!WARNING]
> Tanpa persistent volume, semua file upload (gambar produk & dokumen) akan **hilang** setiap kali redeploy.

Tambahkan **Persistent Storage** di Coolify yang me-mount direktori berikut:

```text
/var/www/html/storage/app/public
```

### 4. Migration & Setup

Migration dan `storage:link` dijalankan otomatis melalui `docker/entrypoint.sh` saat container start. Jika perlu menjalankan manual, gunakan **Execute Command** di Coolify:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
```

### 5. Catatan Reverse Proxy

Coolify menggunakan reverse proxy (Traefik) yang meneruskan request via HTTP ke container. Middleware `TrustProxies` dan `APP_URL` HTTPS sudah dikonfigurasi agar:

- Login POST request tidak ter-redirect ke HTTP GET.
- Asset Livewire (`livewire.js`) ter-load dengan benar tanpa error 404.

---

## Struktur Direktori Penting

```text
app/
├── Filament/Resources/    # Resource admin panel (Product, Category, User, dll)
├── Models/                # Eloquent models
├── Services/              # Business logic (ProductService untuk SKU, AttachmentService, dll)
├── Traits/HasAuditLog.php # Auto audit logging untuk Create/Update/Delete
└── Exports/               # Excel export classes

database/
├── migrations/            # Skema database
└── seeders/               # RoleAndPermissionSeeder, dll

docker/                    # Konfigurasi Docker (entrypoint, nginx, php-fpm)
```

---

## Testing

```bash
php artisan test
```

---

## Lisensi

Aplikasi ini dibangun di atas framework Laravel yang berlisensi [MIT](https://opensource.org/licenses/MIT).

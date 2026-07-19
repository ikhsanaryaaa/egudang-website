# Implement Dynamic Custom Roles and Permission Based Access Control

## Summary

Sistem Role-Based Access Control perlu mendukung custom role yang dapat dibuat dan dikelola oleh Super Admin. Akses modul tidak boleh bergantung pada nama role bawaan seperti `Manager`, `Kepala Gudang`, atau `Operator Gudang`. Setiap akses harus ditentukan oleh permission yang dipasangkan ke role.

Super Admin harus dapat membuat role baru, memilih permission, mengubah permission, memasangkan role kepada user, dan menghapus custom role. Role `Super Admin` tetap mendapatkan akses penuh dan harus dilindungi dari perubahan atau penghapusan.

## Scope

Perubahan mencakup:

- Mengizinkan user dengan custom role masuk ke Filament Admin Panel.
- Mengubah pemeriksaan akses modul dari nama role menjadi permission.
- Menambahkan permission untuk Dashboard, Reports, Report Export, EOQ Calculation, dan Audit Log.
- Menambahkan policy untuk EOQ Calculation.
- Membatasi pengelolaan role dan permission hanya untuk Super Admin.
- Membatasi pemasangan role kepada user hanya untuk Super Admin.
- Melindungi role `Super Admin` dari edit dan delete.
- Mempertahankan permission bawaan untuk Manager, Kepala Gudang, dan Operator Gudang.
- Menambahkan pengujian untuk role bawaan dan custom role.

## Tahapan Implementasi (Steps)

### 1. Define Complete Permissions

Tambahkan permission berikut ke `RoleAndPermissionSeeder`:

- `view dashboard`
- `view users`
- `manage users`
- `view products`
- `create products`
- `edit products`
- `delete products`
- `view categories`
- `create categories`
- `edit categories`
- `delete categories`
- `view stock movements`
- `create stock movements`
- `edit stock movements`
- `delete stock movements`
- `view reports`
- `export reports`
- `view eoq calculations`
- `create eoq calculations`
- `edit eoq calculations`
- `delete eoq calculations`
- `view audit logs`

Gunakan `syncPermissions()` untuk role bawaan agar permission lama yang tidak sesuai dapat dicabut ketika seeder dijalankan ulang.

### 2. Allow Custom Roles to Access the Panel

- Implementasikan kontrak `FilamentUser` pada model `User`.
- Izinkan akses panel untuk user yang memiliki minimal satu role.
- Hapus daftar nama role hard-coded dari middleware panel.
- Tetap gunakan policy dan permission pada setiap modul untuk membatasi akses aktual.
- Arahkan user setelah login ke modul pertama yang diizinkan jika Dashboard tidak tersedia.

### 3. Convert Pages to Permission Based Access

- Dashboard menggunakan permission `view dashboard`.
- Reports dan EOQ Report menggunakan permission `view reports`.
- Tombol dan proses export menggunakan permission `export reports`.
- Tambahkan pemeriksaan server-side pada proses export agar tidak dapat dipanggil tanpa permission.

### 4. Add EOQ Calculation Policy

- Buat `EoqCalculationPolicy`.
- Hubungkan policy melalui `AuthServiceProvider`.
- Gunakan permission view, create, edit, dan delete EOQ.
- Hapus pemeriksaan nama role dari `EoqCalculationResource`.

### 5. Convert Audit Log Access

- Gunakan permission `view audit logs` untuk membuka daftar dan detail Audit Log.
- Pertahankan Audit Log sebagai data read-only.
- Jangan menyediakan create, update, atau delete untuk Audit Log.

### 6. Protect Super Admin Management

- Role Management hanya dapat diakses oleh Super Admin.
- Role `Super Admin` tidak dapat diedit atau dihapus.
- Role `Super Admin` tidak dapat dipilih dalam bulk delete.
- Form pemasangan role pada User Management hanya ditampilkan dan diproses untuk Super Admin.
- User non-Super Admin tidak dapat mengubah atau menghapus akun yang memiliki role `Super Admin`.

### 7. Preserve Default Role Matrix

Konfigurasi role bawaan:

| Module | Super Admin | Manager | Kepala Gudang | Operator Gudang |
|---|---|---|---|---|
| Dashboard | View | View | View | View |
| Reports | View and Export | View and Export | View and Export | No Access |
| Products | CRUD | No Access | CRUD | View |
| Categories | CRUD | View | CRUD | View |
| Stock Transactions | All Permissions | View | All Permissions | View and Create |
| EOQ Calculations | CRUD | No Access | CRUD | CRUD |
| User Management | CRUD | No Access | No Access | No Access |
| Role and Permission Management | Full Access | No Access | No Access | No Access |
| Audit Log | View | No Access | View | No Access |

### 8. Add Automated Tests

- Pastikan Manager hanya memperoleh permission monitoring yang telah ditentukan.
- Pastikan Super Admin memperoleh seluruh permission.
- Pastikan custom role dapat masuk ke panel.
- Pastikan custom role hanya dapat membuka modul yang memiliki permission.
- Pastikan perubahan permission pada custom role langsung mengubah akses user.
- Pastikan role `Super Admin` tidak dapat diedit atau dihapus.

### 9. Apply Database Changes

Jalankan perintah berikut:

```bash
php artisan optimize:clear
php artisan db:seed --class=RoleAndPermissionSeeder --force
php artisan test --filter=RoleAccessTest
```

## Expected Result

- Super Admin dapat mengakses seluruh modul dan action yang tersedia.
- Super Admin dapat membuat custom role dengan kombinasi permission apa pun.
- Super Admin dapat menambah atau mengurangi permission custom role.
- Super Admin dapat memasangkan custom role kepada user.
- User dengan custom role dapat login ke panel.
- User dengan custom role diarahkan ke modul pertama yang dapat diakses.
- Menu dan URL modul hanya dapat diakses jika role user memiliki permission yang sesuai.
- Perubahan permission langsung memengaruhi akses user.
- Manager tetap terbatas pada Dashboard, Reports, Categories read-only, dan Stock Transactions read-only.
- Role `Super Admin` tidak dapat diedit atau dihapus.
- Audit Log tetap read-only.

## Features

- Dynamic custom role creation.
- Permission assignment and removal.
- Permission based navigation.
- Permission based direct URL protection.
- Dynamic panel access for custom roles.
- Protected Super Admin role.
- Restricted user role assignment.
- Report export authorization.
- Automated RBAC testing.

## Related Modules

- Authentication
- Dashboard
- User Management
- Role Management
- Permission Management
- Product Management
- Category Management
- Stock Transactions
- EOQ Calculations
- Inventory Reports
- EOQ Reports
- Audit Log

## Related Files

- `database/seeders/RoleAndPermissionSeeder.php`
- `app/Models/User.php`
- `app/Http/Responses/LoginResponse.php`
- `app/Providers/AppServiceProvider.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Filament/Pages/Dashboard.php`
- `app/Filament/Pages/Reports.php`
- `app/Filament/Pages/EoqReport.php`
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/RoleResource.php`
- `app/Filament/Resources/EoqCalculationResource.php`
- `app/Policies/UserPolicy.php`
- `app/Policies/ProductPolicy.php`
- `app/Policies/CategoryPolicy.php`
- `app/Policies/StockTransactionPolicy.php`
- `app/Policies/EoqCalculationPolicy.php`
- `app/Policies/AuditLogPolicy.php`
- `tests/Feature/RoleAccessTest.php`
- `README.md`

## Notes

- Permission mengontrol akses modul, bukan nama custom role.
- Custom role tidak boleh diberi perlakuan khusus berdasarkan namanya.
- Seeder hanya menyinkronkan role bawaan dan tidak menghapus custom role yang dibuat melalui panel.
- Super Admin memperoleh semua permission yang tersedia setiap kali seeder dijalankan.
- Audit Log tetap immutable untuk menjaga integritas histori aktivitas.
- Stock Transaction tetap tidak menyediakan edit dan delete di UI untuk menjaga konsistensi histori stok.
- PHP minimal yang digunakan oleh dependensi proyek saat ini adalah PHP 8.4.

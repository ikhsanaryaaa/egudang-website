# Penghapusan Fitur Barcode dan QR Code pada Modul Product

## Summary

Saat ini setiap Product secara otomatis meng-generate **QR Code** (berisi SKU) dan **Barcode** (CODE_128 berdasarkan input field `barcode`) ketika data dibuat atau di-update. Gambar hasil generate disimpan ke storage (`qrcodes/` dan `barcodes/`) dan ditampilkan pada form Edit serta tabel list Product di admin panel.

Fitur ini akan **dihapus sepenuhnya** karena dianggap tidak lagi diperlukan. Penghapusan mencakup service generator, model lifecycle hooks, kolom database, elemen UI Filament, package dependency, serta file gambar yang sudah ter-generate di storage.

## Scope

Perubahan ini menghapus seluruh logika dan artefak yang berkaitan dengan barcode dan QR code:

| Area | Yang Dihapus |
|---|---|
| Services | `QrService.php`, `BarcodeService.php` |
| Model | Lifecycle hooks (`created`, `updated`, `deleting`) untuk QR/barcode, fillable `barcode`, `qr_code_path`, `barcode_image_path` |
| Filament UI | Input field `barcode`, section preview "QR Code & Barcode", kolom tabel `qr_code_path` & `barcode_image_path` |
| Database | Kolom `barcode`, `qr_code_path`, `barcode_image_path` pada tabel `products` |
| Dependency | `endroid/qr-code`, `picqer/php-barcode-generator` |
| Storage | Folder `storage/app/public/qrcodes/` dan `storage/app/public/barcodes/` |

> Fitur **SKU** (`ProductService::generateSku`) **TIDAK** dihapus karena merupakan identitas produk yang berdiri sendiri dan tidak bergantung pada QR/barcode.

## Tahapan Implementasi (Steps)

### 1. Hapus Service Generator

- Hapus file `app/Services/QrService.php`
- Hapus file `app/Services/BarcodeService.php`

### 2. Bersihkan Model `Product.php`

- Hapus `use App\Services\QrService;` dan `use App\Services\BarcodeService;`
- Hapus entry `'barcode'`, `'qr_code_path'`, `'barcode_image_path'` dari array `$fillable`
- Pada method `boot()`:
  - Hapus blok generate QR & barcode di dalam `static::created()`
  - Hapus blok regenerate/delete QR & barcode di dalam `static::updated()`
  - Hapus blok delete QR & barcode di dalam `static::deleting()`
- Jika setelah penghapusan method `boot()` menjadi kosong, hapus method tersebut sepenuhnya.

### 3. Bersihkan `ProductResource.php` (Filament)

- Hapus `use App\Services\QrService;` (pastikan tidak dipakai di tempat lain pada file ini)
- Hapus `Forms\Components\TextInput::make('barcode')` pada section Product Information
- Hapus seluruh `Forms\Components\Section::make('QR Code & Barcode')` beserta isinya (placeholder `qr_preview` dan `barcode_preview`)
- Hapus kolom tabel `Tables\Columns\ImageColumn::make('qr_code_path')`
- Hapus kolom tabel `Tables\Columns\ImageColumn::make('barcode_image_path')`

### 4. Buat Migration untuk Drop Kolom

- Buat migration baru, contoh: `drop_barcode_and_qr_columns_from_products_table`
- Pada method `up()`: drop kolom `barcode`, `qr_code_path`, `barcode_image_path`
- Pada method `down()`: tambahkan kembali ketiga kolom agar migration tetap reversible

```php
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn(['barcode', 'qr_code_path', 'barcode_image_path']);
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->string('barcode')->nullable()->after('sku');
        $table->string('qr_code_path')->nullable()->after('image_path');
        $table->string('barcode_image_path')->nullable()->after('qr_code_path');
    });
}
```

> Catatan: migration lama (`add_qr_code_path...`, `add_barcode_image_path...`, dan kolom `barcode` pada `create_products_table`) **dibiarkan apa adanya** untuk menjaga history migration. Penghapusan kolom cukup lewat migration baru.

### 5. Hapus Package Dependency

- Jalankan: `composer remove endroid/qr-code picqer/php-barcode-generator`
- Pastikan tidak ada referensi lain ke kedua package tersebut sebelum dihapus.

### 6. Bersihkan File Storage

- Hapus folder `storage/app/public/qrcodes/` dan `storage/app/public/barcodes/` beserta isinya (gambar yang sudah ter-generate)
- Di production (Coolify/VPS), hapus juga folder yang sama pada persistent volume.

### 7. Jalankan Migration & Clear Cache

- `php artisan migrate`
- `php artisan optimize:clear` (clear config, route, view, cache)

### 8. Testing Manual

- Login ke admin panel (`/admin/login`)
- Buka List Products → pastikan kolom QR dan Barcode sudah hilang
- Buat Product baru → pastikan tidak ada input `barcode` dan tidak ada error saat save
- Edit Product → pastikan section "QR Code & Barcode" sudah tidak muncul
- Hapus Product → pastikan proses delete berjalan normal tanpa error
- Pastikan generate SKU tetap berfungsi normal

## Expected Result

- Form Create/Edit Product tidak lagi menampilkan input `barcode` maupun preview QR/Barcode
- Tabel list Product tidak lagi menampilkan kolom QR dan Barcode
- Tabel `products` tidak lagi memiliki kolom `barcode`, `qr_code_path`, `barcode_image_path`
- Tidak ada lagi proses generate gambar saat create/update Product
- Package `endroid/qr-code` dan `picqer/php-barcode-generator` terhapus dari `composer.json` dan `composer.lock`
- Fitur SKU tetap berjalan normal tanpa terpengaruh
- Tidak ada error/exception di seluruh flow Create, Edit, dan Delete Product

## Features

- Penyederhanaan modul Product dengan menghilangkan dependency generator gambar
- Pengurangan ukuran storage karena tidak ada lagi file QR/barcode yang ter-generate
- Skema database lebih ramping (3 kolom dihapus)
- Berkurangnya dependency eksternal sehingga maintenance lebih ringan

## Related Modules

- Filament Admin Panel (v3.x) — `ProductResource`
- Eloquent Model lifecycle (`App\Models\Product`)
- Laravel Storage (disk `public`)
- Database Migration (tabel `products`)

## Related Files

| File | Aksi |
|---|---|
| `app/Services/QrService.php` | Hapus file |
| `app/Services/BarcodeService.php` | Hapus file |
| `app/Models/Product.php` | Hapus import, fillable, dan lifecycle hooks QR/barcode |
| `app/Filament/Resources/ProductResource.php` | Hapus input field, section preview, dan kolom tabel |
| `database/migrations/xxxx_drop_barcode_and_qr_columns_from_products_table.php` | File migration baru |
| `composer.json` | Hapus dependency `endroid/qr-code` & `picqer/php-barcode-generator` |
| `storage/app/public/qrcodes/` | Hapus folder beserta isinya |
| `storage/app/public/barcodes/` | Hapus folder beserta isinya |

## Notes

- **Backup database** sebelum menjalankan migration drop kolom, karena penghapusan kolom bersifat destruktif dan akan menghilangkan data `barcode` yang tersimpan.
- Verifikasi ulang tidak ada referensi `qr_code_path`, `barcode_image_path`, `barcode`, `QrService`, atau `BarcodeService` yang tersisa di codebase (gunakan global search) sebelum commit.
- Cek juga modul export (`ProductStockExport`, `LowStockExport`) untuk memastikan tidak ada kolom barcode/QR yang direferensikan — berdasarkan pengecekan saat ini tidak ditemukan, namun tetap konfirmasi setelah perubahan.
- Setelah deploy ke production, jalankan `php artisan migrate` dan bersihkan persistent volume storage untuk folder `qrcodes/` dan `barcodes/`.
- Estimasi waktu pengerjaan: **~30-45 menit** (termasuk testing).

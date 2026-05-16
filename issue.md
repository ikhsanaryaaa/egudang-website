# E-Gudang Inventory Features Stabilization: SKU Formatting, Barcode Integration, PDF Export, and UI Improvements

## Deskripsi & Objective
Issue ini bertujuan untuk melakukan stabilisasi menyeluruh pada sistem inventory E-Gudang. Fokus utama adalah pada standarisasi format data (SKU), perbaikan media generation (QR Code), integrasi fitur generate Barcode otomatis, penambahan fungsionalitas export dokumen transaksi (PDF), penyajian file upload saat edit mode, serta rebranding visual halaman Dashboard.

---

## Spesifikasi Teknis & Dependencies
* **PHP GD Library**: Harus aktif di PHP local server (karena library QR Code dan Barcode akan memanfaatkan GD engine).
* **Package QR Code**: `endroid/qr-code` (menggantikan `simplesoftwareio/simple-qrcode` untuk bypass extension Imagick).
* **Package Barcode**: `picqer/php-barcode-generator` (untuk generator standar CODE-128).
* **Package PDF**: `barryvdh/laravel-dompdf` (untuk render export PDF).

---

## Panduan Langkah Demi Langkah (Step-by-Step Implementation Guide)
Panduan ini dirancang agar mudah diikuti oleh Junior Programmer maupun AI Model untuk mereplikasi atau mengembangkan perbaikan ini:

### Langkah 1: Instalasi Dependency Packages
Jalankan command composer berikut di terminal root project untuk menginstall library yang diperlukan:
```bash
composer require endroid/qr-code
composer require picqer/php-barcode-generator
```

### Langkah 2: Skema Database & Model Product
1. Buat file migration untuk menambahkan kolom barcode image path:
   ```bash
   php artisan make:migration add_barcode_image_path_to_products_table --table=products
   ```
2. Isi file migration tersebut dengan menambahkan kolom nullable:
   ```php
   $table->string('barcode_image_path')->nullable()->after('qr_code_path');
   ```
3. Daftarkan field `'barcode_image_path'` ke dalam property `$fillable` di model `app/Models/Product.php`.

### Langkah 3: Konfigurasi Service Layer & Auto-Generator
1. **Perbaikan QR Code Service (`app/Services/QrService.php`)**:
   * Ubah direktori simpan dari `public/qrcodes` menjadi `qrcodes`.
   * Ganti call storage bawaan menjadi eksplisit menggunakan `Storage::disk('public')` (misal: `Storage::disk('public')->put($path, ...)`), guna menghindari double folder path `public/public/qrcodes`.
2. **Pembuatan Barcode Service (`app/Services/BarcodeService.php`)**:
   * Buat class baru `BarcodeService`.
   * Gunakan `Picqer\Barcode\BarcodeGeneratorPNG` untuk mengenerate string barcode ke raw PNG stream.
   * Gunakan PHP GD function (`imagecreatefromstring`, `imagecreatetruecolor`, `imagecopy`) untuk menaruh gambar barcode di atas canvas latar belakang putih solid dengan padding seragam `15px`.
3. **Model Boot Listener Event (`app/Models/Product.php`)**:
   * Di dalam method `boot()`, daftarkan listener `created` untuk memicu method `generateForProduct` dari `QrService` dan `BarcodeService` secara otomatis sesaat setelah product disimpan.
   * Daftarkan listener `updated` dengan pengecekan `isDirty('sku')` atau `isDirty('barcode')` untuk meregenerasi/update file image QR dan Barcode jika nilainya berubah.
   * Daftarkan listener `deleting` untuk menghapus file image lama dari disk storage agar tidak menyisakan file sampah.

### Langkah 4: Kustomisasi UI Filament (Product Resource)
Ubah file resource `app/Filament/Resources/ProductResource.php`:
1. **Form Schema**:
   * Sembunyikan atau hapus `helperText` pada input SKU.
   * Aktifkan method `preserveFilenames()` pada component `FileUpload` image dan dokumen.
   * Tambahkan Placeholder component untuk menampilkan list link unduhan file yang sudah tersimpan di table `attachments` saat product berada dalam edit mode.
   * Di bagian bawah, tambahkan layout section dua kolom untuk preview real-time gambar QR Code dan gambar Barcode (hanya visible saat product record sudah ter-create/bukan halaman create baru).
2. **Table Schema**:
   * Hapus `TextColumn` SKU (agar visual table list lebih clean sesuai request).
   * Hapus action download QR `downloadQr` dari list action table.
   * Tambahkan `ImageColumn` untuk menampilkan gambar Barcode di samping kolom gambar QR code menggunakan disk `'public'`.

### Langkah 5: Penambahan Tombol Download PDF pada Transaksi Stok
1. Buat file template blade baru di `resources/views/pdf/stock-transaction.blade.php`. Desain layout formal laporan dengan data header transaksi, detail pencipta, beserta tabel item mutasi stok.
2. Edit file detail page di `app/Filament/Resources/StockTransactionResource/Pages/ViewStockTransaction.php`:
   * Tambahkan method `getHeaderActions()` untuk merender tombol action **"Download PDF"**.
   * Integrasikan dengan `Barryvdh\DomPDF\Facade\Pdf::loadView()` untuk me-render PDF stream secara instant dari database record saat tombol diklik.

### Langkah 6: Rebranding Halaman Dashboard
1. Buat file class custom page baru di `app/Filament/Pages/Dashboard.php` yang meng-extend `\Filament\Pages\Dashboard`.
2. Di dalam custom class tersebut, override static property:
   ```php
   protected static ?string $title = 'Dashboard';
   protected static ?string $navigationLabel = 'Dashboard';
   protected static ?string $slug = 'dashboard';
   ```
3. Daftarkan class custom dashboard tersebut ke dalam array `pages` pada file configuration `app/Providers/Filament/AdminPanelProvider.php` menggantikan bawaan `Pages\Dashboard::class`.

---

## Langkah Pengujian & Validasi (Testing Checklist)
* [ ] Jalankan `php artisan optimize:clear` untuk memastikan cache routes, config, dan views ter-reset dengan bersih.
* [ ] Masuk ke form edit product lama, isi field barcode, simpan, lalu verifikasi gambar barcode dengan background putih solid dan padding 15px muncul di table list dan form edit.
* [ ] Verifikasi menu navigasi samping berubah menjadi "Dashboard" dan tautan URL mengarah ke `/admin/dashboard`.
* [ ] Buka halaman view detail mutasi stok (`/admin/stock-transactions/{id}`), klik tombol "Download PDF", dan pastikan file PDF laporan sukses terunduh dengan layout yang rapi.
* [ ] Coba upload file di menu Product & Stock Movement, pastikan nama file document yang di-upload tetap utuh (tidak berubah menjadi random string hash).

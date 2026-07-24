# Use Manual Period Description for EOQ Custom Period Basis

## Summary

Modul EOQ Calculation sebelumnya membuat `Period Label` custom secara otomatis dari `Period Start` dan `Period End` (format `01 Jan 2026 - 01 Mar 2026`), dan menyembunyikan input manual `Period` ketika basis `Custom` dipilih. Basis `Monthly` dan `Yearly` sudah menggunakan input manual `Period` (misalnya `January 2026` atau `2026`).

Perubahan ini menjadikan `Custom` konsisten dengan `Monthly` dan `Yearly`: user mengisi sendiri deskripsi `Period` secara manual pada semua basis. `Period Start` dan `Period End` tetap dipertahankan untuk custom period karena keduanya menjadi dasar perhitungan prorating pada `EoqService::periodFactor()`, tetapi tidak lagi menghasilkan label otomatis.

## Scope

Perubahan mencakup:

- Menampilkan input `Period` (manual description) pada semua period basis, termasuk `Custom`.
- Menjadikan `Period` required pada seluruh basis.
- Menghentikan auto-generate `Period Label` dari date range.
- Mempertahankan `Period Start` dan `Period End` sebagai input perhitungan custom period.
- Membersihkan field `Period` ketika basis diganti agar tidak menyisakan nilai lama.
- Menghapus dead code helper label otomatis.
- Mempertahankan behavior `Monthly` dan `Yearly` yang sudah ada.
- Mempertahankan formula EOQ dan prorating custom period tanpa perubahan.

## Tahapan Implementasi (Steps)

### 1. Tampilkan Input Period pada Semua Basis

- Hapus `->hidden(...)` pada `TextInput` `period_label`.
- Jadikan `->required()` tanpa kondisi (berlaku untuk semua basis).
- Pertahankan placeholder `e.g. January 2026 or 2026`.

### 2. Hentikan Auto-Generate Label

- Hapus pemanggilan `syncCustomPeriodLabel()` pada `afterStateUpdated` untuk `period_type`, `period_start`, dan `period_end`.
- Pertahankan pemanggilan `recalculate()` dan reset date pada basis non-custom.
- Pertahankan reset `period_label` menjadi `null` saat basis diganti agar tidak menyisakan deskripsi lama.

### 3. Normalisasi Data Sebelum Save

- Pertahankan penghapusan `period_start` dan `period_end` untuk basis non-custom.
- Hentikan penulisan ulang `period_label` pada `normalizePeriodData()` sehingga deskripsi ketikan user tersimpan verbatim.

### 4. Hapus Dead Code

- Hapus method `syncCustomPeriodLabel()`.
- Hapus method `formatCustomPeriodLabel()`.
- Hapus import `Carbon\CarbonImmutable` yang tidak lagi dipakai.

### 5. Validate Implementation

```bash
php artisan test --filter=EoqServiceTest
php artisan test
vendor/bin/pint --test
```

## Expected Result

- Input `Period` tampil dan required pada basis `Monthly`, `Yearly`, dan `Custom`.
- `Period Start` dan `Period End` tetap tampil hanya untuk custom period dan tetap menggerakkan live recalculation.
- Deskripsi `Period` yang diketik user tersimpan verbatim, tidak ditimpa oleh date range.
- Mengganti basis membersihkan field `Period` agar tidak menyisakan nilai lama.
- Formula EOQ, ROP, Order Frequency, Total Cost, dan prorating custom period tidak berubah.
- List table, chart label, serta export Excel dan PDF menampilkan deskripsi manual `period_label`.
- Existing records tetap valid tanpa migration.

## Features

- Manual period description pada semua period basis.
- Konsistensi input antara `Monthly`, `Yearly`, dan `Custom`.
- Custom date range tetap sebagai dasar perhitungan prorating.
- Reset field pada pergantian basis.

## Related Modules

- EOQ Calculation
- EOQ Calculation History
- EOQ Report
- EOQ Chart

## Related Files

- `app/Filament/Resources/EoqCalculationResource.php`

## Notes

- Tidak ada perubahan skema database; kolom `period_label`, `period_start`, dan `period_end` sudah ada.
- `Period Start` dan `Period End` tetap hanya digunakan oleh custom period calculation.
- Formula prorating custom period pada `EoqService` tidak diubah.
- `EoqServiceTest` tetap lulus (17 tests) karena logika perhitungan tidak disentuh.
- UI check manual disarankan melalui panel Filament untuk memastikan visibilitas field per basis.

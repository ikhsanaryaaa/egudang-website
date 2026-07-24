# Add Custom Date Range for EOQ Calculation Period

## Summary

Modul EOQ Calculation perlu mendukung custom calculation period agar user dapat menentukan rentang tanggal secara fleksibel, misalnya 1 Januari sampai 1 Maret. Sistem sebelumnya hanya menyediakan `Monthly` dan `Yearly` sebagai `Period Basis`, sedangkan `Calculation Date` hanya berfungsi sebagai tanggal pencatatan kalkulasi.

Perubahan ini menambahkan opsi `Custom` dengan `Period Start` dan `Period End`. Demand dan holding cost tetap menggunakan annual value, kemudian sistem melakukan prorating berdasarkan jumlah hari kalender dalam rentang custom secara inclusive.

## Scope

Perubahan mencakup:

- Menambahkan opsi `Custom` pada `Period Basis`.
- Menampilkan input `Period Start` dan `Period End` hanya ketika basis `Custom` dipilih.
- Memvalidasi kedua tanggal sebagai required field untuk custom period.
- Memastikan `Period End` sama dengan atau setelah `Period Start`.
- Menyimpan custom date range pada EOQ calculation history.
- Membuat `Period Label` custom secara otomatis.
- Menghitung annual period factor berdasarkan jumlah hari kalender inclusive.
- Mendukung leap year dan cross-year date range.
- Menjalankan live recalculation ketika custom date range berubah.
- Menambahkan filter `Custom` pada EOQ calculation list dan EOQ chart.
- Mempertahankan behavior `Monthly` dan `Yearly` yang sudah ada.
- Menambahkan automated unit tests untuk custom period calculation.

## Tahapan Implementasi (Steps)

### 1. Add Custom Period Columns

- Buat migration baru untuk tabel `eoq_calculations`.
- Tambahkan kolom `period_start` dengan tipe `date` dan nullable.
- Tambahkan kolom `period_end` dengan tipe `date` dan nullable.
- Pertahankan nilai nullable agar existing monthly dan yearly records tidak membutuhkan data backfill.
- Tambahkan rollback untuk menghapus kedua kolom tersebut.

### 2. Update EOQ Calculation Model

- Tambahkan `period_start` dan `period_end` ke `$fillable`.
- Tambahkan date casting untuk kedua field.
- Pertahankan existing relationship dan numeric casting.

### 3. Implement Annual Period Factor

- Pertahankan existing divisor untuk `Monthly` dan `Yearly`.
- Tambahkan `periodFactor()` pada `EoqService`.
- Gunakan factor `1 / 12` untuk monthly period.
- Gunakan factor `1` untuk yearly period.
- Hitung custom factor berdasarkan jumlah hari inclusive dalam date range.
- Gunakan denominator 365 atau 366 sesuai calendar year.
- Pisahkan cross-year range per calendar year, kemudian jumlahkan setiap annual fraction.
- Return factor `0` untuk missing date, invalid date, atau reversed date range selama live preview.

Formula custom period:

```text
custom_period_factor = sum(inclusive_days_per_year / days_in_calendar_year)
```

Contoh:

```text
Period Start = 2026-01-01
Period End = 2026-03-01
Inclusive Days = 60
Period Factor = 60 / 365
```

### 4. Apply Custom Factor to EOQ Inputs

- Hitung demand per period menggunakan annual demand dikali period factor.
- Hitung holding cost per period menggunakan annual holding cost dikali period factor.
- Teruskan `period_start` dan `period_end` melalui `calculateAll()`.
- Pertahankan existing EOQ, ROP, Order Frequency, dan Total Cost formula.

Formula input per period:

```text
Demand Per Period = Annual Demand * Period Factor
Holding Cost Per Period = Annual Holding Cost * Period Factor
```

### 5. Update Filament EOQ Form

- Tambahkan `Custom` ke options pada `Period Basis`.
- Tampilkan `Period Start` dan `Period End` hanya untuk custom period.
- Jadikan kedua field required secara conditional.
- Tambahkan validation `after_or_equal` pada `Period End`.
- Jalankan live recalculation ketika salah satu tanggal berubah.
- Perbarui Demand helper text untuk menjelaskan bahwa input menggunakan annual demand.
- Sembunyikan manual `Period Label` ketika custom period dipilih.
- Buat custom label otomatis dari start date dan end date.

Format label:

```text
01 Jan 2026 - 01 Mar 2026
```

### 6. Normalize Period Data Before Save

- Jalankan period data normalization sebelum create dan update.
- Untuk custom period, generate ulang `Period Label` pada server-side.
- Untuk monthly dan yearly period, set `period_start` dan `period_end` menjadi `null`.
- Jalankan EOQ calculation ulang pada server-side sebelum data disimpan.
- Jangan mempercayai calculated values dari client-side form state.

### 7. Update EOQ List and Chart

- Tambahkan `Custom` pada period basis filter di EOQ calculation list.
- Gunakan badge color khusus untuk custom period.
- Tambahkan `Custom` pada EOQ chart filter.
- Gunakan generated `Period Label` sebagai chart label dan report period display.

### 8. Update Documentation

- Dokumentasikan tiga period basis: `Monthly`, `Yearly`, dan `Custom`.
- Jelaskan bahwa `Calculation Date` merupakan recording date, bukan period boundary.
- Dokumentasikan inclusive date range.
- Dokumentasikan annual demand dan annual holding cost input.
- Dokumentasikan leap year dan cross-year calculation.

### 9. Add Automated Tests

- Pastikan monthly divisor dan yearly divisor tetap sama.
- Pastikan custom date range menghitung kedua boundary secara inclusive.
- Pastikan single-day range menghasilkan factor satu hari.
- Pastikan leap year menggunakan 366 hari.
- Pastikan cross-year range menggunakan denominator masing-masing tahun.
- Pastikan missing, invalid, dan reversed range menghasilkan factor `0`.
- Pastikan annual demand dan holding cost diprorata sesuai custom factor.
- Pastikan `calculateAll()` menghasilkan EOQ result yang konsisten untuk custom period.

### 10. Validate Implementation

Jalankan perintah berikut:

```bash
php artisan migrate --pretend --no-interaction
php artisan test tests/Unit/EoqServiceTest.php
php artisan test
vendor/bin/pint --test
```

Setelah validation berhasil, jalankan migration:

```bash
php artisan migrate
```

## Expected Result

- User dapat memilih `Custom` pada `Period Basis`.
- Form menampilkan `Period Start` dan `Period End` hanya untuk custom period.
- Form menolak custom period tanpa date range lengkap.
- Form menolak `Period End` yang lebih awal dari `Period Start`.
- Sistem menghitung range secara inclusive.
- Sistem menggunakan 365 atau 366 hari sesuai calendar year.
- Cross-year range dihitung secara akurat.
- EOQ result diperbarui secara live ketika date range berubah.
- Custom period tersimpan bersama start date, end date, dan generated label.
- Monthly dan yearly calculation tetap backward compatible.
- Existing EOQ records tetap valid tanpa migration backfill.
- Custom records tersedia pada list filter, report display, export display, dan chart filter.
- Seluruh automated tests dan code style validation berhasil.

## Features

- Custom EOQ calculation period.
- Conditional date range inputs.
- Inclusive date range calculation.
- Annual value prorating.
- Leap year support.
- Cross-year range support.
- Automatic period label generation.
- Live EOQ recalculation.
- Server-side period normalization.
- Custom period list filtering.
- Custom period chart filtering.
- Backward compatibility untuk existing EOQ records.
- Automated custom period calculation tests.

## Related Modules

- EOQ Calculation
- EOQ Calculation History
- EOQ Report
- EOQ Chart
- Product EOQ Parameters
- Database Migration
- Automated Testing

## Related Files

- `database/migrations/2026_07_24_175702_add_period_range_to_eoq_calculations_table.php`
- `app/Models/EoqCalculation.php`
- `app/Services/EoqService.php`
- `app/Filament/Resources/EoqCalculationResource.php`
- `app/Filament/Resources/EoqCalculationResource/Pages/CreateEoqCalculation.php`
- `app/Filament/Resources/EoqCalculationResource/Pages/EditEoqCalculation.php`
- `app/Filament/Widgets/EoqChartWidget.php`
- `tests/Unit/EoqServiceTest.php`
- `PRD.md`

## Notes

- `Calculation Date` tetap digunakan sebagai tanggal pencatatan dan filtering report.
- `Period Start` dan `Period End` hanya digunakan oleh custom period calculation.
- Demand dan holding cost menggunakan annual input value.
- Custom date range menggunakan inclusive boundaries.
- Existing monthly dan yearly behavior tidak diubah.
- Existing records tidak membutuhkan data backfill karena custom period columns nullable.
- Report dan export tetap menggunakan generated `Period Label`, sehingga tidak membutuhkan perubahan struktur output.
- Automatic demand dari stock transaction tidak termasuk dalam scope.
- Future date restriction dan maximum range restriction tidak termasuk dalam scope.
- Full test suite berhasil dengan 45 tests dan 136 assertions.

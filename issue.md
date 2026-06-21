# Revisi Sistem EOQ: Modul Perhitungan EOQ Transaksional + Graph Dashboard

## Summary

Implementasi EOQ saat ini masih **menempel pada modul Product** (kolom parameter EOQ, preview hasil, dan kolom tabel ada di `ProductResource`, dengan `EoqService` menghitung langsung dari satu produk). Pendekatan ini akan **direvisi** agar EOQ menjadi **modul perhitungan transaksional yang berdiri sendiri**, mengikuti pola aplikasi referensi (Sistem Pengendalian Persediaan Barang dengan Metode EOQ).

Pada model baru, setiap perhitungan EOQ adalah **satu record transaksi** yang mencatat: produk (Barang), periode (Bulan), permintaan (Permintaan/Demand), serta snapshot biaya pemesanan & penyimpanan, lalu menyimpan hasil **EOQ**, **ROP**, dan **Total Biaya (TIC)**. Produk hanya menyimpan **nilai default** (biaya pesan, biaya simpan, lead time) yang otomatis mengisi form perhitungan namun tetap bisa diubah per transaksi.

Revisi ini juga menambahkan:
- **Modul Perhitungan EOQ** (list + entri data) di admin panel.
- **Laporan EOQ** dengan filter rentang tanggal (Tanggal Awal & Tanggal Akhir) beserta export.
- **Graph EOQ** pada Dashboard yang memvisualisasikan hasil perhitungan.

## Scope

Mengubah EOQ dari atribut produk menjadi entitas transaksi tersendiri, plus pelaporan dan visualisasi.

| Area | Perubahan |
|---|---|
| Database | Tabel baru `eoq_calculations`. Kolom pada `products` disesuaikan menjadi **default value** (`ordering_cost`, `holding_cost`, `lead_time_days`), kolom `safety_stock_days` dihapus |
| Model | Model baru `EoqCalculation`; relasi `Product hasMany EoqCalculation` |
| Service | `EoqService` direfactor: menghitung dari input transaksi (demand, cost, lead time, basis periode bulanan/tahunan), bukan dari objek `Product` |
| Filament Resource | Resource baru `EoqCalculationResource` (list + create/entri data + view) |
| Filament Page | Page baru `EoqReport` (Laporan EOQ) dengan filter tanggal + export Excel/PDF |
| Dashboard | Widget chart baru `EoqChartWidget` |
| Cleanup | Hapus section EOQ + kolom EOQ/ROP dari `ProductResource`; hapus / ganti `ReorderRecommendationWidget` |

> Logika transaksi stok (`StockService`) dan batch costing (LIFO) **tetap tidak diubah**.

## Tahapan Implementasi (Steps)

### 1. Migration: Tabel `eoq_calculations`

Buat migration `create_eoq_calculations_table` dengan kolom:

- `id`
- `product_id` — foreign key ke `products`
- `calculation_date` (`date`) — Tanggal
- `period_label` (`string`) — label periode (mis. "Januari 2026" untuk bulanan, atau "2026" untuk tahunan)
- `period_type` (`enum`/`string`: `bulanan`|`tahunan`, default `bulanan`) — basis periode yang dipilih untuk perhitungan ini
- `demand` (`integer`) — Permintaan total dalam basis periode yang dipilih

> **Basis periode**: `bulanan` memakai divisor 12 (konversi nilai tahunan ke bulanan), `tahunan` memakai divisor 1 (dipakai apa adanya). Basis dipilih per record sehingga histori dapat memuat campuran perhitungan bulanan dan tahunan.
- `ordering_cost` (`decimal(15,2)`) — snapshot biaya per pemesanan
- `holding_cost` (`decimal(15,2)`) — snapshot biaya simpan per unit per periode
- `lead_time_days` (`integer`) — snapshot lead time
- `eoq` (`decimal(15,2)`) — hasil EOQ
- `rop` (`decimal(15,2)`) — hasil Reorder Point
- `order_frequency` (`decimal(10,2)`) — frekuensi pemesanan (D / EOQ)
- `total_cost` (`decimal(18,2)`) — Total Biaya / Total Inventory Cost (TIC)
- `created_by` — foreign key ke `users`
- `timestamps`

### 2. Migration: Sesuaikan Kolom `products`

- Buat migration `adjust_eoq_default_columns_on_products_table`.
- Drop kolom `safety_stock_days` (tidak dipakai di model referensi).
- Pertahankan `ordering_cost`, `holding_cost`, `lead_time_days` sebagai **default value** produk (auto-fill form perhitungan).
- Update `$fillable`/`$casts` pada `Product` sesuai.

### 3. Model `EoqCalculation`

- Buat `app/Models/EoqCalculation.php` dengan `$fillable`, `$casts`, relasi `product()` dan `creator()`.
- Tambahkan relasi `eoqCalculations()` (hasMany) pada `Product`.

### 4. Refactor `EoqService`

Ubah service agar berbasis input transaksi (pure calculation, tanpa ketergantungan objek `Product`):

- `periodDivisor(string $periodType): int` — `bulanan` => `12`, `tahunan` => `1`.
- `computeDemandPerPeriod(int $demand, string $periodType): float` — `demand / periodDivisor`
- `computeHoldingPerPeriod(float $holdingCost, string $periodType): float` — `holdingCost / periodDivisor`
- `computeEoq(float $demandPerPeriod, float $orderingCost, float $holdingPerPeriod): float` — `sqrt((2 * Dp * S) / Hp)`
- `computeRop(float $demandPerPeriod, int $leadTimeDays): float` — `Dp * leadTime`
- `computeOrderFrequency(int $demand, float $eoq): float` — `demand / eoq`
- `computeTotalCost(int $demand, float $eoq, float $orderingCost, float $holdingPerPeriod): float` — `(D/EOQ)*S + (EOQ/2)*Hp`
- `calculateAll(array $input): array` — gabungan seluruh hasil untuk disimpan ke record.

Pertahankan guard pembagian nol (return `0` bila input tidak valid).

### 5. Resource `EoqCalculationResource` (Filament)

- Buat resource baru, navigation group **"Perhitungan"**, label **"Perhitungan EOQ"**.
- **Form (Entri Data)**:
  - `Select` Barang (`product_id`) — `live()`, saat dipilih auto-fill `ordering_cost`, `holding_cost`, `lead_time_days` dari default produk.
  - `DatePicker` Tanggal (`calculation_date`).
  - `Select` Basis Periode (`period_type`) — pilihan **Bulanan** / **Tahunan**, `live()`.
  - `TextInput`/`Select` Periode (`period_label`) — label menyesuaikan basis (bulan+tahun untuk bulanan, tahun untuk tahunan).
  - `TextInput` Permintaan (`demand`) — `live()`.
  - `TextInput` Biaya Pemesanan, Biaya Penyimpanan, Lead Time (editable, default dari produk).
  - `Placeholder` hasil real-time: EOQ, ROP, Order Frequency, Total Biaya (dihitung via `EoqService` saat input berubah).
  - Simpan hasil ke kolom record saat create (gunakan `mutateFormDataBeforeCreate`).
- **Table (Data Perhitungan EOQ)**: kolom No, Tanggal, Bulan, Barang, Permintaan, EOQ, ROP, Total Biaya, plus action View/Edit/Delete.

### 6. Page `EoqReport` (Laporan EOQ)

- Buat `app/Filament/Pages/EoqReport.php`, navigation group **"Laporan"**, label **"Laporan EOQ"**.
- Form filter: `DatePicker` Tanggal Awal & Tanggal Akhir + tombol Tampilkan.
- Tampilkan tabel hasil perhitungan dalam rentang tanggal.
- Sediakan export **Excel** & **PDF** (mengikuti pola modul reporting existing: `maatwebsite/excel`, `barryvdh/laravel-dompdf`).

### 7. Widget `EoqChartWidget` (Dashboard)

- Buat `app/Filament/Widgets/EoqChartWidget.php` (extends `ChartWidget`).
- Visualisasikan hasil perhitungan EOQ, contoh: bar/line chart EOQ & ROP per Bulan, atau EOQ per Barang.
- Sediakan filter periode bila relevan.

### 8. Cleanup Implementasi Lama

- Hapus `Section::make('EOQ Parameters')` dan `Placeholder` hasil EOQ dari form `ProductResource`.
- Hapus kolom tabel `eoq` & `reorder_point` dari `ProductResource`.
- Hapus `ReorderRecommendationWidget` (atau ganti dengan widget berbasis `eoq_calculations`).

### 9. Testing

- Update/replace `EoqServiceTest` agar menguji method pure calculation yang baru (Dp, Hp, EOQ, ROP, frequency, total cost) + guard pembagian nol.
- Jalankan `php artisan test`.

### 10. Migration & Verifikasi

- `php artisan migrate`
- `php artisan optimize:clear`
- Testing manual: entri data perhitungan, cek hasil EOQ/ROP/Total Biaya, cek Laporan EOQ + export, cek graph dashboard.

## Expected Result

- EOQ **tidak lagi muncul** di form maupun tabel Product.
- Menu **Perhitungan EOQ** tersedia: bisa entri data (pilih barang, bulan, permintaan) dan menampilkan tabel hasil (No, Tanggal, Bulan, Barang, Permintaan, EOQ, ROP, Total Biaya, Aksi).
- Hasil EOQ, ROP, Order Frequency, dan Total Biaya dihitung otomatis dan tersimpan per record.
- Default biaya pesan/simpan/lead time produk otomatis mengisi form namun tetap bisa diubah per perhitungan.
- Menu **Laporan EOQ** dengan filter Tanggal Awal/Akhir + export Excel & PDF berfungsi.
- **Dashboard** menampilkan graph EOQ.
- `php artisan test` hijau; tidak ada error di seluruh flow.

## Features

- Modul **Perhitungan EOQ** transaksional yang berdiri sendiri (tidak menempel pada produk).
- Perhitungan otomatis EOQ, ROP, frekuensi pemesanan, dan Total Inventory Cost per transaksi.
- Histori perhitungan EOQ per barang dan per periode.
- **Laporan EOQ** dengan filter rentang tanggal + export Excel/PDF.
- **Graph EOQ** pada dashboard untuk visualisasi hasil.
- Produk berperan sebagai master default biaya yang mempercepat input.

## Related Modules

- Filament Admin Panel (v3.x) — Resource, Page, Widget
- Eloquent Model (`App\Models\EoqCalculation`, `App\Models\Product`)
- Service Layer (`App\Services\EoqService`)
- Reporting (`maatwebsite/excel`, `barryvdh/laravel-dompdf`)
- Database Migration (tabel `eoq_calculations`, `products`)

## Related Files

| File | Aksi |
|---|---|
| `database/migrations/xxxx_create_eoq_calculations_table.php` | File migration baru |
| `database/migrations/xxxx_adjust_eoq_default_columns_on_products_table.php` | File migration baru (drop `safety_stock_days`) |
| `app/Models/EoqCalculation.php` | Model baru |
| `app/Models/Product.php` | Sesuaikan fillable/casts + relasi `eoqCalculations` |
| `app/Services/EoqService.php` | Refactor ke perhitungan berbasis transaksi |
| `app/Filament/Resources/EoqCalculationResource.php` | Resource baru (list + entri data) |
| `app/Filament/Pages/EoqReport.php` | Page Laporan EOQ baru |
| `app/Filament/Widgets/EoqChartWidget.php` | Widget graph dashboard baru |
| `app/Filament/Resources/ProductResource.php` | Hapus section & kolom EOQ |
| `app/Filament/Widgets/ReorderRecommendationWidget.php` | Hapus / ganti |
| `tests/Unit/EoqServiceTest.php` | Update unit test formula baru |

## Notes

- **Basis periode (bulanan & tahunan)**: dipilih per perhitungan via `period_type`. `bulanan` memakai divisor 12 (nilai tahunan dikonversi ke bulanan), `tahunan` memakai divisor 1. Default `bulanan` agar konsisten dengan aplikasi referensi.
- **Formula acuan** (dari referensi): `Dp = Demand / Period`, `Hp = HoldingCost / Period`, `EOQ = sqrt(2·Dp·S / Hp)`, `ROP = Dp · LeadTime`, `Total Biaya = (D/EOQ)·S + (EOQ/2)·Hp`. Verifikasi ulang rumus Total Biaya terhadap referensi sebelum finalisasi.
- **Snapshot biaya**: nilai biaya pesan/simpan/lead time disimpan per record (snapshot) agar histori perhitungan tidak berubah ketika default produk diperbarui.
- **Migration produk**: drop `safety_stock_days` bersifat destruktif — backup database terlebih dahulu. Kolom ini ditambahkan pada pekerjaan EOQ sebelumnya (PR #85) dan tidak dipakai pada model referensi.
- **Permission**: tambahkan permission/role access untuk modul Perhitungan EOQ & Laporan EOQ sesuai matrix RBAC existing.
- **Chart**: gunakan Filament `ChartWidget` (Chart.js bawaan) agar konsisten dengan stack, tanpa dependency tambahan.
- Estimasi waktu pengerjaan: **~4-6 jam** (model, service, resource, report, widget, cleanup, test).

---

# Translate EOQ Module UI to English and Relocate EOQ Chart to Report Page

## Summary

Mengubah seluruh UI pada modul EOQ dari Bahasa Indonesia menjadi Bahasa Inggris
agar konsisten dengan label modul lain yang sudah berbahasa Inggris (Products,
Inventory, dsb). Selain itu, memindahkan `EoqChartWidget` dari halaman Dashboard
ke halaman **EOQ Report** supaya grafik EOQ dan ROP berada satu konteks dengan
laporan dan filter periode, sehingga Dashboard lebih ringkas dan fokus pada
ringkasan inventory.

## Scope

- Translation seluruh string UI modul EOQ (Filament Resource, Page, Widget,
  Export, dan Blade views) ke Bahasa Inggris.
- Relokasi `EoqChartWidget` dari Dashboard menjadi header widget pada halaman
  EOQ Report.
- Tidak mengubah logika perhitungan EOQ/ROP, skema database, maupun nilai
  enum `period_type` (`bulanan`/`tahunan`) yang tersimpan di database. Yang
  diubah hanya display label.
- Tidak menyentuh modul lain (Audit Log, Stock Movement, Product) selain
  memastikan tidak ada regresi.

## Tahapan Implementasi (Steps)

1. **Inventarisasi string Indonesia** pada modul EOQ menggunakan pencarian
   pattern (`grep`) di direktori `app/` dan `resources/views/`.
2. **Translate EoqCalculationResource** — navigation group/label, model label,
   form section, field label, options, placeholder, helper text, table column,
   dan filter.
3. **Translate EoqReport page** — navigation group/label, title, section filter,
   dan label tanggal.
4. **Translate EoqChartWidget** — heading dan filter options (`Monthly`/`Yearly`).
5. **Translate EoqCalculationExport** — heading kolom Excel dan judul sheet.
6. **Translate Blade views** — `eoq-report.blade.php` (tabel di halaman) dan
   `reports/eoq.blade.php` (template PDF export).
7. **Relokasi grafik EOQ:**
   - Override `getWidgets()` pada `Dashboard` untuk mengeluarkan
     `EoqChartWidget` dan hanya menampilkan widget inventory.
   - Tambahkan `getHeaderWidgets()` pada `EoqReport` agar grafik tampil di
     bagian atas halaman laporan.
8. **Clear cache** dengan `php artisan optimize:clear` agar perubahan label dan
   widget langsung terlihat.

## Expected Result

- Sidebar menampilkan grup **Reports → EOQ Report** dan
  **Calculation → EOQ Calculation** (sebelumnya Laporan/Perhitungan).
- Seluruh form, tabel, filter, export Excel, dan PDF pada modul EOQ
  berbahasa Inggris.
- Grafik EOQ (`EOQ Chart`) tidak lagi muncul di Dashboard.
- Grafik EOQ muncul di bagian atas halaman EOQ Report, lengkap dengan filter
  `Monthly`/`Yearly`.
- Perhitungan EOQ/ROP tetap berfungsi normal tanpa perubahan hasil.

## Features

- Konsistensi bahasa UI (full English) pada modul EOQ.
- Kontekstualisasi grafik EOQ/ROP bersama laporan dan filter periode.
- Dashboard yang lebih ringkas dengan fokus pada ringkasan inventory.

## Related Modules

- EOQ Calculation (Filament Resource)
- EOQ Report (Filament Page + export Excel/PDF)
- EOQ Chart (Filament Widget)
- Dashboard (Filament Page)

## Related Files

- `app/Filament/Resources/EoqCalculationResource.php`
- `app/Filament/Pages/EoqReport.php`
- `app/Filament/Pages/Dashboard.php`
- `app/Filament/Widgets/EoqChartWidget.php`
- `app/Exports/EoqCalculationExport.php`
- `resources/views/filament/pages/eoq-report.blade.php`
- `resources/views/reports/eoq.blade.php`

## Notes

- Nilai enum `period_type` di database tetap `bulanan`/`tahunan`; hanya display
  label yang menjadi `Monthly`/`Yearly`. Tidak diperlukan data migration.
- String pada `AuditLogResource.php` seperti `'Barang Masuk' => 'Stock In'`
  adalah mapping dari nilai aksi Indonesia yang tersimpan di database menuju
  label tampilan Inggris, sehingga sengaja tidak diubah agar lookup tidak rusak.
- `EoqChartWidget` didaftarkan sebagai header widget pada EOQ Report dan
  dirender otomatis oleh komponen `<x-filament-panels::page>`, sehingga tidak
  perlu mengubah Blade view halaman tersebut.

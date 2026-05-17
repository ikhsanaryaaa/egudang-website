---
name: Bug Fix Report
about: Dokumentasi perbaikan bug pada Bulk Actions dan Livewire State
title: 'Fix: Bulk Actions Appearance and Selection Bug in Filament Resources'
labels: bug, enhancement, filament
assignees: ''
---

## 🐛 Deskripsi Masalah (Issue Description)

Terdapat beberapa *bug* terkait *state management* pada antarmuka tabel Filament (khususnya pada `ProductResource` dan `CategoryResource`):
1. **Bulk Action Bar Appearance Bug:** *Bulk Action Bar* (termasuk *Active Filters* dan *Select All Button*) secara tidak sengaja muncul saat melakukan *Search* atau *Filter* data, padahal tidak ada baris yang dipilih.
2. **Broken Selection Toggle:** Fungsionalitas *Select All* dan *Deselect All* pada *Table* tidak berfungsi dengan semestinya.
3. **UX Improvement:** Pengguna menginginkan *Bulk Action Button* untuk "Delete selected" tampil langsung sebagai *flat button* tanpa harus diklik melalui *Dropdown* `Bulk actions`.

## 🛠️ Langkah Perbaikan (Steps Taken to Fix)

Perbaikan telah dilakukan dengan pendekatan *version upgrade* dan penyesuaian UI *components*:

1. **Menghilangkan Dropdown Bulk Actions (UI Tweak):**
   - Menghapus *wrapper* `Tables\Actions\BulkActionGroup` pada `ProductResource.php` dan `CategoryResource.php`.
   - Menempatkan `Tables\Actions\DeleteBulkAction::make()` secara langsung di dalam *method* `bulkActions()`. Hal ini membuat *Button* **"Delete selected"** tampil secara *flat* (rata) dan meminimalisir interaksi *Dropdown*.

2. **Memperbarui Versi Filament (Package Update):**
   - Mengubah *version constraint* pada `composer.json` untuk *package* `filament/filament` dari *exact version* `"3.2"` menjadi `"^3.2"`.
   - Menjalankan perintah `composer update filament/filament --with-all-dependencies` untuk melakukan *upgrade package* Filament beserta seluruh *sub-packages* bawaannya dari versi `3.2.0` ke versi **`3.3.50`**. Pembaruan ini secara bawaan mengandung perbaikan internal (terutama pada *Livewire State Management*) yang menyelesaikan *bug* *Bulk Action Bar* yang *trigger* saat *Search/Filter* serta memperbaiki fungsi *Checkbox* untuk *Select All/Deselect All*.

3. **Pembersihan Cache dan Stale Views (Cleanup):**
   - Terjadi *error* `trim(): Argument #1 ($string) must be of type string, array given` sesaat setelah proses *update*.
   - Penyebab diidentifikasi: Terdapat *stale published Filament views* dari versi sebelumnya (3.2) yang tidak *compatible* dengan *core view* 3.3.
   - Solusi: Menghapus direktori `resources/views/vendor/filament` secara paksa agar aplikasi *fallback* menggunakan *Blade Views* bawaan langsung dari *package* Filament v3.3.50.
   - Menjalankan `php artisan view:clear` dan `php artisan filament:upgrade` untuk membersihkan *compiled views* dan *re-publish assets*.

## ✅ Verifikasi (Verification)

- [x] *Button* "Delete selected" tampil *flat* (tanpa *Dropdown*).
- [x] *Bulk Action Bar* tidak lagi muncul (*trigger*) saat melakukan *Search* atau menerapkan *Filter*.
- [x] Fungsionalitas *Checkbox* per baris, *Select All*, dan *Deselect All* berfungsi normal.
- [x] *Error* `TypeError: trim()` terkait proses *render component* Filament sudah teratasi sepenuhnya.

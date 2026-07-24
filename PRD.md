# PRD.md

# E-Gudang

## Product Requirement Document

---

# Product Overview

E-Gudang adalah aplikasi inventory gudang berbasis web yang digunakan untuk:
- mengelola produk dan kategori
- mencatat barang masuk dan keluar
- monitoring stok gudang
- mengelola user dan hak akses
- upload dokumen inventory
- reporting inventory
- perhitungan EOQ (Economic Order Quantity) untuk optimasi pemesanan

Target pengguna:
- perusahaan kecil hingga menengah
- operasional gudang internal
- admin warehouse
- operator gudang

---

# Objectives

## Main Objectives
- mempermudah pengelolaan inventory
- mengurangi human error
- menyediakan histori stok
- meningkatkan monitoring gudang
- mempermudah reporting inventory
- membantu penentuan jumlah dan waktu pemesanan optimal lewat perhitungan EOQ

---

# User Roles

| Role | Description |
|---|---|
| Manager | Monitoring dan user management |
| Kepala Gudang | Pengelolaan gudang |
| Operator Gudang | Operasional inventory harian |

---

# Permission Matrix

| Module | Manager | Kepala Gudang | Operator Gudang |
|---|---|---|---|
| Tracking Barang | Read | Read, Update | CRUD |
| Manage User | CRUD | CRUD | No Access |
| Manage Category | Read | CRUD | CRUD |
| Manage Product | Read | Read | CRUD |
| EOQ Calculation | Read | CRUD | CRUD |
| EOQ Report | Read | Read | Read |

---

# Main Features

## Authentication & RBAC

### Features
- Login
- Logout
- Roles & Permissions
- Middleware Authorization
- Policy Authorization

---

## User Management

### Features
- Create User
- Read User
- Update User
- Delete User
- Assign Role
- Reset Password

---

## Category Management

### Features
- Create Category
- Read Category
- Update Category
- Delete Category
- Search Category

---

## Product Management

### Features
- Create Product
- Read Product
- Update Product
- Delete Product
- Product Image Upload
- Product Search
- Product Filter
- SKU Generator
- EOQ Default Parameters (ordering cost, holding cost, lead time)

### EOQ Default Parameters
Produk menyimpan nilai default yang dipakai sebagai auto-fill saat membuat perhitungan EOQ:
- `ordering_cost`: biaya pemesanan default
- `holding_cost`: biaya penyimpanan per unit (basis tahunan)
- `lead_time_days`: waktu tunggu pemesanan (hari)

---

## QR & Barcode System

### Features
- QR Code Generation
- Barcode Support
- QR Preview
- QR Download

### Usage
- Product labeling
- Inventory lookup
- Product scanning

---

## Stock Transaction System

### Features
- Barang Masuk
- Barang Keluar
- Stock Adjustment
- Multi Product Transaction
- Stock History
- Notes
- Reference Number

### Stock Rules

#### Barang Masuk
```text
stock + qty
```

#### Barang Keluar
```text
stock - qty
```

#### Validation
```text
stock cannot be negative
```

---

## EOQ Calculation Module

Module perhitungan EOQ (Economic Order Quantity) berdiri sendiri (standalone) untuk menentukan jumlah pemesanan ekonomis, titik pemesanan ulang, frekuensi pemesanan, dan total biaya persediaan per produk.

### Features
- Create EOQ Calculation
- Read EOQ Calculation
- Update EOQ Calculation
- Delete EOQ Calculation
- Auto-fill parameter biaya dari default produk
- Live recalculation saat input berubah
- Filter per produk dan per basis periode
- Simpan histori perhitungan per periode

### Input Parameters
- Product (produk yang dihitung)
- Calculation Date (tanggal pencatatan perhitungan, bukan batas periode)
- Period Basis (`bulanan` / `tahunan` / `custom`)
- Period Label (label periode, contoh: `Januari 2026` atau `2026`; dibuat otomatis untuk `custom`)
- Period Start dan Period End (wajib untuk `custom`, kedua batas inklusif)
- Demand (total permintaan basis tahunan)
- Ordering Cost (biaya pemesanan)
- Holding Cost (biaya penyimpanan per unit, basis tahunan)
- Lead Time (hari)

### Calculation Output
- EOQ (Economic Order Quantity)
- ROP (Reorder Point)
- Order Frequency (frekuensi pemesanan)
- Total Cost (Total Inventory Cost)

### Period Basis Rules
Nilai tahunan dikonversi ke basis periode terpilih sebelum dihitung:
```text
bulanan => faktor 1 / 12
tahunan => faktor 1
custom => faktor = jumlah pecahan tahun dalam rentang inklusif
```

Untuk `custom`, setiap bagian tahun memakai denominator kalender tahun tersebut: 365 hari atau 366 hari pada leap year. Rentang lintas tahun menjumlahkan faktor setiap bagian tahun.

### Formulas

#### Demand per Period
```text
Dp = Demand tahunan * period_factor
```

#### Holding Cost per Period
```text
Hp = Holding Cost tahunan * period_factor
```

#### EOQ
```text
EOQ = sqrt((2 * Dp * S) / Hp)
```

#### Reorder Point
```text
ROP = Dp * Lead Time (hari)
```

#### Order Frequency
```text
F = Demand / EOQ
```

#### Total Inventory Cost
```text
TIC = (Demand / EOQ) * S + (EOQ / 2) * Hp
```

#### Validation
```text
EOQ = 0 jika Dp <= 0 atau S <= 0 atau Hp <= 0 (guard pembagian nol)
```

---

## EOQ Report Module

### Features
- Filter berdasarkan rentang tanggal (Start Date, End Date)
- EOQ Chart (perbandingan EOQ vs ROP)
- Export Excel
- Export PDF

### Chart
- Menampilkan 12 perhitungan terbaru pada basis periode terpilih
- Dataset: EOQ dan Reorder Point (ROP)
- Filter chart: Monthly / Yearly / Custom

---

## File Upload Module

### Supported Files
- PDF
- DOC / DOCX
- XLS / XLSX
- JPG / PNG

### Features
- Multiple Upload
- File Preview
- Download File
- Attachment History

### Use Cases
- Surat Jalan
- Invoice
- Purchase Order
- Adjustment Document

---

## Dashboard Module

### Features
- Total Product
- Low Stock
- Stock In Today
- Stock Out Today
- Recent Activity

---

## Reporting Module

### Features
- Export Excel
- Export PDF
- Stock Report
- Movement Report
- Low Stock Report
- EOQ Report

---

## Audit Log Module

### Features
- Login Activity
- CRUD Activity
- Upload Activity
- Audit History

---

# Functional Requirements

## Authentication
- User dapat login/logout
- Unauthorized access harus diblok

---

## Inventory
- Semua perubahan stok wajib tercatat
- Stock tidak boleh negatif
- Transaction history wajib tersedia

---

## EOQ Calculation
- Perhitungan wajib divalidasi terhadap pembagian nol
- Parameter biaya default diambil dari produk dan dapat diubah per perhitungan
- Nilai tahunan dikonversi sesuai basis periode (bulanan/tahunan/custom)
- Hasil perhitungan wajib tersimpan sebagai histori per periode
- Report EOQ dapat difilter berdasarkan rentang tanggal dan diekspor ke Excel/PDF

---

## File Upload
- File wajib divalidasi
- Upload multiple file didukung
- File harus aman disimpan

---

## Reporting
- Report dapat difilter
- Export Excel dan PDF tersedia

---

# Non Functional Requirements

## Performance
- Response time cepat
- Pagination untuk data besar
- Query optimization

---

## Security
- Password hashing
- CSRF protection
- Middleware authorization
- Policy authorization

---

## Scalability
- Support future multi warehouse
- Support future mobile API
- Maintainable architecture

---

# Success Criteria

## System Success
- Inventory tracking berjalan stabil
- Role & permission berjalan benar
- Reporting berjalan baik
- Stock history akurat
- Perhitungan EOQ akurat dan hasilnya tersimpan sebagai histori

---

## User Success
- User mudah menggunakan sistem
- Monitoring stok lebih cepat
- Human error berkurang

---

# Future Features

## Planned Features
- Barcode Scanner
- QR Scanner
- Multi Warehouse
- Supplier Management
- Purchase Order
- Mobile API
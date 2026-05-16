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
# design.md

# E-Gudang

## Overview

E-Gudang adalah sistem inventory gudang berbasis web yang digunakan untuk:
- mengelola produk
- mengelola kategori
- mencatat barang masuk dan keluar
- mengelola user dan permission
- upload dokumen inventory
- monitoring stok
- reporting inventory

Sistem dibangun menggunakan Laravel ecosystem dengan fokus:
- maintainability
- scalability
- clean architecture
- practical enterprise workflow

---

# Technology Stack

| Layer | Technology |
|---|---|
| Backend | Laravel |
| Admin Panel | FilamentPHP |
| Database | PostgreSQL |
| Frontend Styling | TailwindCSS |
| Authentication | Laravel Breeze / Sanctum |
| Permission | Spatie Permission |
| File Upload | Spatie Media Library |

---

# Architecture

## Architecture Style
- Modular Monolith
- Service Layer Pattern
- Repository Pattern

---

## Folder Structure

```text
app/
├── Actions/
├── Enums/
├── Filament/
├── Helpers/
├── Http/
├── Models/
├── Policies/
├── Repositories/
├── Services/
└── Traits/
```

---

# User Roles

| Role | Access |
|---|---|
| Manager | Monitoring & User Management |
| Kepala Gudang | Warehouse Management |
| Operator Gudang | Inventory Operation |

---

# Permission Matrix

| Module | Manager | Kepala Gudang | Operator Gudang |
|---|---|---|---|
| Tracking Barang | Read | Read, Update | CRUD |
| Manage User | CRUD | CRUD | No Access |
| Manage Category | Read | CRUD | CRUD |
| Manage Product | Read | Read | CRUD |

---

# Main Modules

## Authentication & RBAC

### Features
- Login
- Logout
- Roles & Permissions
- Middleware Authorization
- Laravel Policies
- Filament Protection

---

## User Management

### Features
- User CRUD
- Assign Role
- Reset Password

---

## Category Management

### Features
- Category CRUD
- Search & Filter
- Soft Delete

---

## Product Management

### Features
- Product CRUD
- SKU Generator
- Product Image Upload
- Search & Filter

### Product Fields

| Field | Type |
|---|---|
| category_id | foreign key |
| sku | string |
| barcode | string |
| name | string |
| description | text |
| stock | integer |
| minimum_stock | integer |
| unit | string |

---

## QR & Barcode System

### Features
- Auto Generate QR
- Barcode Support
- QR Preview
- QR Download

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

---

### Transaction Tables

#### stock_transactions

```text
id
transaction_number
type
notes
created_by
created_at
```

---

#### stock_transaction_items

```text
id
stock_transaction_id
product_id
qty
before_stock
after_stock
created_at
```

---

### Stock Logic

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

# Database Tables

```text
users
roles
permissions
categories
products
stock_transactions
stock_transaction_items
media
audit_logs
```

---

# Database Relationships

```text
Category
 └── hasMany(Product)

Product
 └── belongsTo(Category)

StockTransaction
 └── hasMany(StockTransactionItems)

StockTransactionItem
 └── belongsTo(Product)

User
 └── hasMany(StockTransactions)
```

---

# UI/UX Design

## Design Style
- Clean
- Minimalist
- Responsive
- Enterprise Dashboard

---

## Components
- Sidebar Navigation
- Dashboard Cards
- Data Tables
- Search & Filter
- Modal Form
- Notifications
- Status Badges

---

# Security

## Features
- Password Hashing
- CSRF Protection
- Middleware Authorization
- Policy Authorization
- File Validation
- SQL Injection Protection

---

# Optimization

## Strategy
- Eager Loading
- Pagination
- Database Indexing
- Queue Jobs

---

# Production Setup

## Deployment
- VPS
- Laravel Forge
- PostgreSQL
- S3 / Cloudflare R2

---

# Future Features

## Planned Features
- Barcode Scanner
- QR Scanner
- Multi Warehouse
- Supplier Module
- Purchase Order
- Mobile API
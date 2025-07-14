# Laravel CRUD Generator

A Laravel package to generate full CRUD operations with UI, relationships, soft deletes, and advanced field types — all through a web interface.

---

## ✨ Features

- Web UI for CRUD generation (no Artisan commands needed)
- Support for all common MySQL field types (including `enum`, `bigint`, `longText`, etc.)
- Add Laravel relationships (e.g., `belongsTo`, `hasMany`, `morphOne`, etc.)
- Generate:
  - Model with fillables, relationships, and soft deletes
  - Controller
  - Migration
  - Views (`index.blade.php`, `create.blade.php`)

---

## 🚀 Installation

> ✅ Requires Laravel 8+ and PHP 8.0+

```bash
composer require kuldeep/crud-generator

## Usage
1. Visit:
  /crud-generator

2. Fill in:

  Module Name

  Fields (name, type, nullable, enum options)

  Relations (type, model name)

3. Submit to generate full CRUD files automatically.

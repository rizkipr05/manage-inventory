# Sistem Manajemen Barang Medis

Sistem web-based untuk manajemen inventori barang medis. Repository ini berisi dua modul yang aktif di kode:

- **Modul Utama (pages/)**: Fitur lengkap manajemen barang, transaksi, laporan, supplier, dan user admin.
- **Modul Sederhana (auth/ + suppliers/ + partials/)**: Dashboard ringkas dan kelola supplier.

## Fitur Modul Utama (pages/)

### 1. Login & Logout Admin
- Autentikasi admin
- Password di-hash SHA2
- Session management

### 2. Dashboard
- Total barang, total supplier, total stok
- Stok minimum (alert)
- Transaksi hari ini (masuk/keluar)
- Barang hampir kadaluarsa (≤ 30 hari)
- Top 10 barang dengan stok terbesar

### 3. Manajemen Barang
- CRUD barang medis
- Tracking stok masuk/keluar
- Stok minimum dan indikator status
- Tanggal kadaluarsa + peringatan expired
- Pencarian barang (nama/kode)

### 4. Manajemen Supplier
- CRUD supplier
- Info kontak, alamat, kota, provinsi

### 5. Manajemen Transaksi
- Barang masuk/keluar
- Validasi stok untuk transaksi keluar
- Filter transaksi per tanggal

### 6. Laporan
- Ringkasan total masuk/keluar
- Laporan transaksi per periode + filter tipe
- Laporan stok
- Laporan penggunaan (keluar)
- Export CSV dan mode print

### 7. Manajemen User Admin
- CRUD user admin
- Update password (opsional saat edit)
- Proteksi agar tidak menghapus akun sendiri

## Fitur Modul Sederhana (auth/ + suppliers/ + partials/)

- Login admin (session berbasis `users` table)
- Dashboard ringkas: total stok, hampir habis, total masuk/keluar, pergerakan stok terbaru
- Kelola supplier sederhana (nama, telepon, alamat)

Catatan: Modul sederhana memakai konfigurasi `config/db.php` dan database `manage_inventory`. Skema database untuk modul ini belum disertakan di repo.

## Teknologi

- **Backend**: PHP Native (MySQLi + PDO)
- **Database**: MySQL
- **Frontend**: Bootstrap 5
- **Icons**: FontAwesome 6 (modul utama), Bootstrap Icons (modul sederhana)

## Instalasi Modul Utama (disarankan)

### Persyaratan
- XAMPP/LAMPP dengan PHP 7.4+
- MySQL Server
- Browser modern

### Langkah Instalasi

1. **Tempatkan proyek**
   ```
   /opt/lampp/htdocs/manage-medical
   ```

2. **Setup database**
   - Buka PhpMyAdmin: http://localhost/phpmyadmin
   - Import file `database/schema.sql`
   - Database yang dibuat: `manage_medical_db`

3. **Konfigurasi**
   - Cek `includes/config.php`
   - Default:
     - `DB_HOST`: localhost
     - `DB_USER`: root
     - `DB_PASS`: (kosong)
     - `DB_NAME`: manage_medical_db

4. **Akses aplikasi**
   - Buka: `http://localhost/manage-medical/login.php`
   - Default login:
     - Username: `admin`
     - Password: `admin123`

## Instalasi Modul Sederhana (opsional)

1. Cek `config/db.php` untuk konfigurasi database (`manage_inventory`).
2. Siapkan tabel minimal: `users`, `suppliers`, `items`, `stock_movements`.
3. Akses: `http://localhost/manage-medical/auth/login.php`.

## Struktur Folder

```
manage-medical/
├── assets/               # CSS untuk modul sederhana
├── auth/                 # Login modul sederhana
├── config/               # Config DB modul sederhana (PDO)
├── includes/             # Config + session modul utama (MySQLi)
├── pages/                # Modul utama (dashboard, barang, supplier, transaksi, laporan, users)
├── suppliers/            # Kelola supplier modul sederhana
├── partials/             # Layout modul sederhana
├── database/             # Schema modul utama
├── login.php             # Login modul utama
├── logout.php            # Logout modul utama
└── index.php             # Dashboard modul sederhana
```

## Penggunaan Modul Utama

### Login
1. Masukkan username dan password
2. Klik tombol Login
3. Jika berhasil, diarahkan ke dashboard

### Manajemen Barang
1. Menu "Data Barang"
2. Isi form, pilih supplier, dan set stok minimum
3. Gunakan pencarian untuk filter data

### Manajemen Transaksi
1. Menu "Transaksi"
2. Pilih barang dan tipe (Masuk/Keluar)
3. Filter transaksi berdasarkan tanggal

### Laporan
1. Menu "Laporan"
2. Pilih periode dan tipe transaksi
3. Export CSV atau print sesuai kebutuhan

### Manajemen User
1. Menu "Users"
2. Tambah/edit/hapus user admin

## Catatan Keamanan

Untuk production:
- Ubah password default admin
- Gunakan HTTPS
- Tambahkan CSRF protection
- Validasi input lebih ketat
- Backup database berkala
- Gunakan prepared statements

---
Dibuat untuk manajemen inventori barang medis

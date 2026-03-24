# Panduan Instalasi & Setup Database

## Step 1: Buka PhpMyAdmin

1. Pastikan XAMPP/LAMPP sudah running
2. Buka browser dan akses: `http://localhost/phpmyadmin`
3. Login dengan username `root` (tanpa password jika default)

## Step 2: Import Database Schema

### Metode 1: Import dari File SQL

1. Di halaman PhpMyAdmin, klik tab **"Import"**
2. Klik **"Choose File"** dan pilih file `database/schema.sql` dari proyek
3. Klik tombol **"Go"** atau **"Import"**
4. Tunggu hingga selesai, akan muncul pesan sukses

### Metode 2: Manual (Jika Import Gagal)

1. Di PhpMyAdmin, klik **"New"** untuk membuat database baru
2. Ketik nama database: `manage_medical_db`
3. Klik **"Create"**
4. Klik database yang baru dibuat
5. Buka tab **"SQL"**
6. Copy-paste isi file `database/schema.sql` ke text area
7. Klik **"Go"**

## Step 3: Verifikasi Database

Setelah import, verifikasi dengan:

1. Di PhpMyAdmin, klik database `manage_medical_db`
2. Seharusnya muncul tabel-tabel berikut:
   - `admin` (berisi 1 user default: admin/admin123)
   - `supplier` (berisi 3 supplier sample)
   - `barang` (kosong, siap diisi)
   - `transaksi` (kosong, siap diisi)

## Step 4: Test Koneksi

1. Buka browser dan akses: `http://localhost/manage-medical`
2. Seharusnya diarahkan ke halaman login
3. Login dengan kredensial:
   - Username: `admin`
   - Password: `admin123`

## Troubleshooting

### Error: Koneksi Database Gagal

**Solusi:**
1. Pastikan MySQL Service sudah running
2. Edit file `includes/config.php`
3. Pastikan parameter database benar:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Kosong jika tidak ada password
   define('DB_NAME', 'manage_medical_db');
   ```

### Error: Database tidak ditemukan

**Solusi:**
1. Buka PhpMyAdmin dan cek apakah database `manage_medical_db` sudah ada
2. Jika belum, ikuti Step 2 di atas untuk import/membuat database

### Error: Tabel tidak ditemukan

**Solusi:**
1. Buka PhpMyAdmin → database `manage_medical_db`
2. Klik tab SQL
3. Jalankan ulang import file `schema.sql`

### Login Gagal

**Solusi:**
1. Pastikan database sudah import dengan benar
2. Check di PhpMyAdmin, buka tabel `admin`
3. Seharusnya ada 1 data dengan:
   - username: `admin`
   - password: (hash SHA2)

### Cara Reset Admin Password

1. Di PhpMyAdmin, klik database `manage_medical_db`
2. Klik tabel `admin`
3. Klik tab SQL
4. Copy-paste query berikut:
   ```sql
   UPDATE admin SET password = SHA2('admin123', 256) WHERE username = 'admin';
   ```
5. Klik "Go"

## Database Backup

Untuk backup database, di PhpMyAdmin:

1. Pilih database `manage_medical_db`
2. Klik tab **"Export"**
3. Pilih format **"SQL"**
4. Klik **"Go"** untuk download file backup

## Restore dari Backup

1. Klik tab **"Import"**
2. Pilih file backup yang sudah didownload
3. Klik **"Go"**

---

Untuk informasi lebih lanjut, baca file `README.md` di folder utama proyek.

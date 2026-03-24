# Sistem Manajemen Barang Medis

Sistem web-based untuk manajemen inventori barang medis dengan fitur login, dashboard, dan manajemen supplier.

## Fitur-Fitur

### 1. Login & Logout
- Autentikasi admin/pemilik
- Keamanan dengan hashing password
- Session management

### 2. Dashboard
- Menampilkan ringkasan informasi:
  - Total stok bahan baku
  - Bahan baku yang hampir habis
  - Data bahan masuk dan keluar
- Monitoring secara cepat

### 3. Kelola Data Supplier
- Menambah data supplier
- Mengubah data supplier
- Menghapus data supplier
- Melihat daftar supplier lengkap

### 4. Kelola Data Barang
- CRUD (Create, Read, Update, Delete) barang medis
- Tracking stok masuk dan keluar
- Pencatatan tanggal kadaluarsa
- Sistem peringatan barang expired

### 5. Transaksi
- Pencatatan barang masuk
- Pencatatan barang keluar
- Tracking pergerakan stok real-time

## Teknologi

- **Backend**: PHP Native
- **Database**: MySQL (PhpMyAdmin)
- **Frontend**: Bootstrap 5
- **Icons**: FontAwesome 6

## Instalasi

### Persyaratan
- XAMPP/LAMPP dengan PHP 7.4+
- MySQL Server
- Browser modern

### Langkah Instalasi

1. **Download/Clone Proyek**
   ```
   Tempatkan folder `manage-medical` di folder htdocs (/opt/lampp/htdocs/)
   ```

2. **Setup Database**
   - Buka PhpMyAdmin: http://localhost/phpmyadmin
   - Buat database baru atau import file `database/schema.sql`
   - Jalankan SQL dari file `database/schema.sql`

3. **Konfigurasi**
   - Edit file `includes/config.php` jika diperlukan
   - Default: DB_HOST: localhost, DB_USER: root, DB_PASS: (kosong)

4. **Akses Aplikasi**
   - Buka browser: http://localhost/manage-medical
   - Login dengan kredensial default:
     - Username: `admin`
     - Password: `admin123`

## Struktur Folder

```
manage-medical/
├── assets/              # File-file assets (css, js, images)
├── includes/            # File-file include (config, session, header, footer)
├── pages/               # Halaman-halaman aplikasi
│   ├── dashboard.php    # Halaman dashboard
│   ├── barang.php       # Manajemen data barang
│   ├── supplier.php     # Manajemen supplier
│   └── transaksi.php    # Manajemen transaksi
├── database/            # Database schema
│   └── schema.sql       # SQL untuk membuat database
├── login.php            # Halaman login
├── logout.php           # Proses logout
└── index.php            # File redirect ke login
```

## Penggunaan

### Login
1. Masukkan username dan password
2. Klik tombol Login
3. Jika berhasil, akan diarahkan ke dashboard

### Manajemen Supplier
1. Klik menu "Supplier" di sidebar
2. Isi form untuk tambah supplier baru
3. Lihat daftar supplier yang sudah ada
4. Klik Edit untuk mengubah atau Hapus untuk menghapus

### Manajemen Barang
1. Klik menu "Data Barang" di sidebar
2. Isi form untuk tambah barang baru
3. Pilih supplier, kategori, stok, dan tanggal kadaluarsa
4. Lihat daftar barang dengan status stok
5. Lakukan pencarian dengan fitur search

### Manajemen Transaksi
1. Klik menu "Transaksi" di sidebar
2. Pilih barang dari dropdown
3. Tentukan tipe transaksi (Masuk/Keluar)
4. Masukkan jumlah dan keterangan
5. Lihat history transaksi dengan filter tanggal

### Dashboard
- Melihat statistik total barang, supplier, dan stok
- Alert untuk barang yang hampir kadaluarsa
- Top 10 barang dengan stok terbesar
- Informasi transaksi hari ini

## Catatan Keamanan

⚠️ **Untuk Production:**
- Ubah password default admin
- Gunakan HTTPS
- Implementasikan CSRF protection
- Validasi input lebih ketat
- Backup database secara berkala
- Hide error messages di production
- Gunakan prepared statements untuk query

## Support

Untuk pertanyaan atau bantuan, silakan hubungi administrator sistem.

---
Dibuat dengan ❤️ untuk manajemen inventori barang medis

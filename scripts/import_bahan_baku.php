<?php
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Script ini hanya untuk CLI.\n");
    exit(1);
}

$path = $argv[1] ?? '';
$truncate = in_array('--truncate', $argv, true);
if ($path === '') {
    fwrite(STDERR, "Usage: php scripts/import_bahan_baku.php /path/to/file.xlsx [--truncate]\n");
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';

function excelSerialToDate($serial) {
    if ($serial === '' || $serial === null) {
        return '';
    }
    $serial = (int)floor((float)$serial);
    if ($serial <= 0) {
        return '';
    }
    $base = new DateTime('1899-12-30');
    $base->modify("+{$serial} days");
    return $base->format('Y-m-d');
}

function readXlsxRows($filePath, &$errMsg) {
    $errMsg = '';
    if (!class_exists('ZipArchive')) {
        $errMsg = 'Ekstensi ZipArchive tidak tersedia di server.';
        return [];
    }
    if (!file_exists($filePath)) {
        $errMsg = 'File tidak ditemukan.';
        return [];
    }
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        $errMsg = 'Gagal membuka file Excel.';
        return [];
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = simplexml_load_string($sharedXml);
        if ($shared && isset($shared->si)) {
            $i = 0;
            foreach ($shared->si as $si) {
                $texts = [];
                foreach ($si->t as $t) {
                    $texts[] = (string)$t;
                }
                foreach ($si->r as $r) {
                    if (isset($r->t)) {
                        $texts[] = (string)$r->t;
                    }
                }
                $sharedStrings[$i] = implode('', $texts);
                $i++;
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        $errMsg = 'Sheet1 tidak ditemukan di file Excel.';
        return [];
    }

    $sheet = simplexml_load_string($sheetXml);
    $rows = [];
    if ($sheet && isset($sheet->sheetData->row)) {
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $c) {
                $cellRef = (string)$c['r'];
                $col = preg_replace('/[^A-Z]/', '', $cellRef);
                $type = (string)$c['t'];
                $v = isset($c->v) ? (string)$c->v : '';
                if ($type === 's') {
                    $v = $sharedStrings[(int)$v] ?? '';
                }
                $rowData[$col] = $v;
            }
            $rows[] = $rowData;
        }
    }
    $zip->close();
    return $rows;
}

$import_error = '';
$rows = readXlsxRows($path, $import_error);
if ($import_error) {
    fwrite(STDERR, $import_error . "\n");
    exit(1);
}

$data_rows = [];
foreach ($rows as $idx => $row) {
    if ($idx <= 1) {
        continue;
    }
    if (empty($row['A']) || empty($row['B'])) {
        continue;
    }
    $data_rows[] = $row;
}

if ($truncate) {
    $conn->query("DELETE FROM barang");
}

$imported = 0;
foreach ($data_rows as $row) {
    $kode_barang = $conn->real_escape_string(trim($row['A'] ?? ''));
    $nama_barang = $conn->real_escape_string(trim($row['B'] ?? ''));
    $supplier_nama = trim($row['C'] ?? '');
    $tanggal_masuk = excelSerialToDate($row['D'] ?? '');
    $tanggal_kadaluarsa = excelSerialToDate($row['E'] ?? '');
    $stok_awal = (int)floor((float)($row['F'] ?? 0));
    $stok_akhir = (int)floor((float)($row['G'] ?? 0));
    $satuan = $conn->real_escape_string(trim($row['H'] ?? ''));
    $harga_unit = (float)($row['I'] ?? 0);
    $kategori = 'Bahan Baku';

    // Supplier: ambil atau buat
    $supplier_id = 0;
    if ($supplier_nama !== '') {
        $safe_supplier = $conn->real_escape_string($supplier_nama);
        $sup_res = $conn->query("SELECT id FROM supplier WHERE nama_supplier = '$safe_supplier' LIMIT 1");
        if ($sup_res && $sup_res->num_rows > 0) {
            $supplier_id = (int)$sup_res->fetch_assoc()['id'];
        } else {
            $conn->query("INSERT INTO supplier (nama_supplier) VALUES ('$safe_supplier')");
            $supplier_id = (int)$conn->insert_id;
        }
    }
    if ($supplier_id <= 0) {
        $sup_res = $conn->query("SELECT id FROM supplier ORDER BY id ASC LIMIT 1");
        if ($sup_res && $sup_res->num_rows > 0) {
            $supplier_id = (int)$sup_res->fetch_assoc()['id'];
        } else {
            $conn->query("INSERT INTO supplier (nama_supplier) VALUES ('Supplier Umum')");
            $supplier_id = (int)$conn->insert_id;
        }
    }

    $stok_masuk = 0;
    $stok_keluar = max(0, $stok_awal - $stok_akhir);
    $stok_minimum = 10;

    $cek = $conn->query("SELECT id FROM barang WHERE kode_barang = '$kode_barang' LIMIT 1");
    if ($cek && $cek->num_rows > 0) {
        $existing_id = (int)$cek->fetch_assoc()['id'];
        $query = "UPDATE barang SET
                  nama_barang = '$nama_barang',
                  supplier_id = $supplier_id,
                  kategori = '$kategori',
                  stok_awal = $stok_awal,
                  stok_masuk = $stok_masuk,
                  stok_keluar = $stok_keluar,
                  stok_akhir = $stok_akhir,
                  stok_minimum = $stok_minimum,
                  harga_unit = $harga_unit,
                  tanggal_masuk = '$tanggal_masuk',
                  tanggal_kadaluarsa = '$tanggal_kadaluarsa',
                  satuan = '$satuan'
                  WHERE id = $existing_id";
        $conn->query($query);
        $imported++;
    } else {
        $query = "INSERT INTO barang (nama_barang, kode_barang, supplier_id, kategori, stok_awal, stok_masuk, stok_keluar, stok_akhir, stok_minimum, harga_unit, tanggal_masuk, tanggal_kadaluarsa, satuan)
                  VALUES ('$nama_barang', '$kode_barang', $supplier_id, '$kategori', $stok_awal, $stok_masuk, $stok_keluar, $stok_akhir, $stok_minimum, $harga_unit, '$tanggal_masuk', '$tanggal_kadaluarsa', '$satuan')";
        if ($conn->query($query)) {
            $imported++;
        }
    }
}

fwrite(STDOUT, "Import selesai. Data diproses: {$imported} baris.\n");

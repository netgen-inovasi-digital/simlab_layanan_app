# Dokumentasi Penyesuaian Modul Pelaksanaan

## Tanggal: 2025-01-XX

## Tujuan

Menyesuaikan modul **Pelaksanaan** (Upload LHU oleh Teknisi) dengan struktur database terbaru:

- `t_files_lhu` untuk file LHU (Laporan Hasil Uji)
- `t_files_lhus` untuk file LHUS (Laporan Hasil Uji Sampel)
- `t_layanan_detil` struktur baru tanpa kolom `detil_LHU` dan `detil_LHUS`

## Perubahan yang Dilakukan

### 1. Method `detectLhusFile()` - Deteksi File LHUS

**File**: `appengines/app/Modules/Pelaksanaan/Controllers/Pelaksanaan.php`

**SEBELUM**: Cek dari kolom `simlab_t_layanan_detil.detil_LHUS`

```php
$detils = (new MyModel('simlab_t_layanan_detil'))->getAllDataById(['detLnKode'=>$row->lnKode]);
foreach ($detils as $d) {
    foreach (['detil_LHUS','detLhus','detFileLhus','det_file_lhus'] as $df) {
        // ...
    }
}
```

**SESUDAH**: Cek dari tabel `t_files_lhus` dengan `kode_layanan`

```php
$db = \Config\Database::connect();

$lhusFile = $db->table('t_files_lhus')
    ->select('file_lhus')
    ->where('kode_layanan', $row->lnKode)
    ->whereIn('status', [0, 1]) // 0=terkirim, 1=diterima
    ->orderBy('file_id', 'DESC')
    ->limit(1)
    ->get()->getRow();
```

**Benefit**:

- ✅ Langsung query ke tabel dedicated `t_files_lhus`
- ✅ Filter hanya status yang relevan (terkirim/diterima)
- ✅ Ambil file terbaru jika ada multiple uploads

---

### 2. Method `detectLhuFile()` - Deteksi File LHU

**File**: `appengines/app/Modules/Pelaksanaan/Controllers/Pelaksanaan.php`

**SEBELUM**: Cek dari kolom `simlab_t_layanan_detil.detil_LHU`

```php
$detils = (new MyModel('simlab_t_layanan_detil'))->getAllDataById(['detLnKode'=>$row->lnKode]);
foreach ($detils as $d) {
    foreach (['detil_LHU','detFile','detFilelhu','det_file_lhu'] as $df) {
        // ...
    }
}
```

**SESUDAH**: Cek dari tabel `t_files_lhu`

```php
$db = \Config\Database::connect();

$lhuFile = $db->table('t_files_lhu')
    ->select('file')
    ->where('kode', $row->lnKode)
    ->orderBy('file_id', 'DESC')
    ->limit(1)
    ->get()->getRow();
```

**Benefit**:

- ✅ Konsisten dengan struktur database baru
- ✅ Ambil file LHU terbaru

---

### 3. Method `upload()` - Upload File LHU

**File**: `appengines/app/Modules/Pelaksanaan/Controllers/Pelaksanaan.php`

**SEBELUM**: Update ke `simlab_t_layanan_detil.detil_LHU`

```php
$ok = $db->table('simlab_t_layanan_detil')
    ->where('detKode', $detKode)
    ->update(['detil_LHU' => $filename]);
```

**SESUDAH**: Insert/Update ke tabel `t_files_lhu`

```php
// Cek apakah sudah ada record untuk lnKode ini
$existingFile = $db->table('t_files_lhu')
    ->where('kode', $lnKode)
    ->get()->getRow();

if ($existingFile) {
    // Update record yang sudah ada
    $db->table('t_files_lhu')
        ->where('kode', $lnKode)
        ->update([
            'file' => $filename,
            'upload_by' => $user_id
        ]);

    // Hapus file lama
    if (!empty($existingFile->file) && $existingFile->file !== $filename) {
        $oldFilePath = FCPATH . 'uploads/lhu/' . ltrim($existingFile->file, '/');
        if (is_file($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }
} else {
    // Insert record baru
    $db->table('t_files_lhu')->insert([
        'kode' => $lnKode,
        'file' => $filename,
        'upload_by' => $user_id
    ]);
}
```

**Benefit**:

- ✅ Tidak ada duplikat (UPDATE jika sudah ada)
- ✅ Auto-delete file lama dari server
- ✅ Track uploader dengan `upload_by`

---

### 4. Method `detailList()` - Tampilan Detail Layanan

**File**: `appengines/app/Modules/Pelaksanaan/Controllers/Pelaksanaan.php`

**SEBELUM**: Query dari `simlab_t_layanan_detil` saja

```php
$rows = $db->table('simlab_t_layanan_detil as d')
    ->select('d.detKode, d.detLayanan, d.detil_LHUS, d.detil_LHU, ...')
    ->join('simlab_account up',  'up.user_id = d.detUploadLHUS', 'left')
    ->where('d.detLnKode', $lnKode)
    ->get()->getResult();
```

**SESUDAH**: JOIN dengan `t_files_lhus` dan `t_files_lhu`

```php
$rows = $db->table('t_layanan_detil as d')
    ->select('
        d.kode, d.nama_layanan, d.jumlah,
        lhus.file_lhus,
        up_lhus.username AS upload_lhus_by,
        acc_lhus.username AS acc_lhus_by,
        lhu.file AS file_lhu,
        up_lhu.username AS upload_lhu_by
    ')
    // JOIN LHUS (ambil terbaru)
    ->join(
        '(SELECT lhus1.* FROM t_files_lhus lhus1
          INNER JOIN (
            SELECT kode, MAX(file_id) as max_file_id
            FROM t_files_lhus
            GROUP BY kode
          ) lhus2 ON lhus1.kode = lhus2.kode AND lhus1.file_id = lhus2.max_file_id
        ) lhus',
        'lhus.kode = d.kode',
        'left'
    )
    ->join('simlab_account up_lhus', 'up_lhus.user_id = lhus.upload_by', 'left')
    ->join('simlab_account acc_lhus', 'acc_lhus.user_id = lhus.validasi_by', 'left')
    // JOIN LHU
    ->join('t_files_lhu lhu', 'lhu.kode = d.kode_layanan', 'left')
    ->join('simlab_account_users up_lhu', 'up_lhu.user_id = lhu.upload_by', 'left')
    ->where('d.kode_layanan', $lnKode)
    ->get()->getResult();
```

**Perubahan Mapping Kolom**:
| Lama | Baru |
|------|------|
| `d.detKode` | `d.kode` |
| `d.detLayanan` | `d.nama_layanan` |
| `d.detJumlah` | `d.jumlah` |
| `d.detKeterangan` | `d.catatan_pelanggan` |
| `d.detil_LHUS` | `lhus.file_lhus` (dari JOIN) |
| `d.detil_LHU` | `lhu.file` (dari JOIN) |
| `d.detUploadLHUS` | `lhus.upload_by` → `up_lhus.username` |
| `d.detAccLHUS` | `lhus.validasi_by` → `acc_lhus.username` |

**Benefit**:

- ✅ Tidak ada duplikat (subquery ambil file terbaru)
- ✅ Tampilan username uploader/approver langsung dari JOIN
- ✅ Prioritas file: LHUS > LHU

---

## Struktur Database Terkait

### Tabel `t_files_lhu` (LHU = Laporan Hasil Uji)

```sql
CREATE TABLE t_files_lhu (
    file_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode INT UNSIGNED,  -- FK ke simlab_t_layanan.lnKode
    file VARCHAR(255),
    upload_by INT UNSIGNED,  -- FK ke simlab_account_users.user_id
    FOREIGN KEY (kode) REFERENCES simlab_t_layanan(lnKode),
    FOREIGN KEY (upload_by) REFERENCES simlab_account_users(user_id)
);
```

**Relasi**:

- `kode` → `simlab_t_layanan.lnKode` (1 invoice bisa punya 1 file LHU)
- `upload_by` → `simlab_account_users.user_id` (teknisi yang upload)

### Tabel `t_files_lhus` (LHUS = Laporan Hasil Uji Sampel)

```sql
CREATE TABLE t_files_lhus (
    file_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode INT UNSIGNED,  -- FK ke t_layanan_detil.kode
    kode_layanan INT UNSIGNED,  -- FK ke simlab_t_layanan.lnKode
    file_lhus VARCHAR(255),
    upload_by INT UNSIGNED,  -- FK ke simlab_account_users.user_id
    validasi_by INT UNSIGNED,  -- FK ke simlab_account_users.user_id
    catatan VARCHAR(255),
    status INT,  -- 0=terkirim, 1=diterima, 2=ditolak, 3=uploaded (belum kirim)
    FOREIGN KEY (kode) REFERENCES t_layanan_detil(kode),
    FOREIGN KEY (kode_layanan) REFERENCES simlab_t_layanan(lnKode),
    FOREIGN KEY (upload_by) REFERENCES simlab_account_users(user_id),
    FOREIGN KEY (validasi_by) REFERENCES simlab_account_users(user_id)
);
```

**Relasi**:

- `kode` → `t_layanan_detil.kode` (1 detail layanan = 1 file LHUS)
- `kode_layanan` → `simlab_t_layanan.lnKode` (untuk cek kelengkapan per invoice)

---

## Testing Checklist

### ✅ Upload LHU (Teknisi)

1. Buka modul Pelaksanaan
2. Klik "Upload LHU" pada invoice dengan status ≥ 6
3. Upload file PDF/DOC/IMG
4. **Cek**: File masuk ke tabel `t_files_lhu`
5. **Cek**: Badge status berubah menjadi "LHU terunggah"
6. Upload ulang file baru → file lama terhapus dari server

### ✅ Deteksi File LHUS

1. Pastikan ada LHUS yang sudah dikirim (status=0 atau 1) di `t_files_lhus`
2. Klik "Lihat File" di modul Pelaksanaan
3. **Cek**: Modal menampilkan file LHUS (prioritas) atau LHU
4. **Cek**: Kolom "Upload LHUS" dan "Acc LHUS" menampilkan username

### ✅ View Detail

1. Klik "Lihat File" pada invoice
2. **Cek**: DataTable menampilkan nama layanan, jumlah, keterangan
3. **Cek**: Tombol "Lihat" menampilkan file LHUS (prioritas) atau LHU
4. **Cek**: Kolom "Upload LHUS" dan "Acc LHUS" terisi username dengan benar

### ✅ Proses (Accept LHU)

1. Upload LHU terlebih dahulu
2. Klik tombol "Proses (Setujui LHU)"
3. **Cek**: Status invoice berubah dari 6 → 7
4. **Cek**: Badge berubah menjadi "LHU Disetujui"

---

## SQL Helper Queries

### Cek File LHU Per Invoice

```sql
SELECT
    l.lnKode,
    l.lnNomor,
    lhu.file_id,
    lhu.file AS file_lhu,
    u.username AS uploaded_by
FROM simlab_t_layanan l
LEFT JOIN t_files_lhu lhu ON lhu.kode = l.lnKode
LEFT JOIN simlab_account_users u ON u.user_id = lhu.upload_by
WHERE l.lnStatus >= 6
ORDER BY l.lnKode DESC;
```

### Cek File LHUS yang Sudah Dikirim untuk Invoice

```sql
SELECT
    l.lnKode,
    l.lnNomor,
    d.kode AS detail_kode,
    d.nama_layanan,
    lhus.file_lhus,
    lhus.status,
    CASE lhus.status
        WHEN 0 THEN 'Terkirim (Menunggu Review)'
        WHEN 1 THEN 'Diterima'
        WHEN 2 THEN 'Ditolak'
        WHEN 3 THEN 'Terunggah (Belum Kirim)'
        ELSE 'Unknown'
    END AS status_text
FROM simlab_t_layanan l
INNER JOIN t_layanan_detil d ON d.kode_layanan = l.lnKode
LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
WHERE l.lnKode = 123  -- ganti dengan lnKode yang ingin dicek
ORDER BY d.kode;
```

### Cek Duplikat File LHU (Seharusnya Tidak Ada)

```sql
SELECT kode, COUNT(*) as jumlah
FROM t_files_lhu
GROUP BY kode
HAVING COUNT(*) > 1;
```

---

## Notes

- ✅ Logic modul **TIDAK BERUBAH**, hanya sumber datanya yang disesuaikan
- ✅ LHU tetap 1 file per invoice (stored in `t_files_lhu`)
- ✅ LHUS bisa multiple per invoice (1 per detail layanan, stored in `t_files_lhus`)
- ✅ Upload ulang LHU akan UPDATE record (tidak duplikat) dan hapus file lama
- ✅ Backward compatible: query tetap berjalan meskipun ada data lama

## Status

✅ **SELESAI** - Modul Pelaksanaan berhasil disesuaikan dengan database baru

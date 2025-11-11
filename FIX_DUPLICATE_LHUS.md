# Fix Duplicate LHUS Records

## Tanggal: 2025-01-XX

## Masalah

Saat LHUS ditolak di modul TinjauLHUS dan kemudian penyelia mengupload ulang file baru lalu mengirim lagi, muncul **record duplikat** di halaman TinjauLHUS.

**Contoh**: Layanan "Al (Aluminium) - Tanah, air" muncul 3 kali dengan status "Terkirim (Menunggu Review)"

## Root Cause

Ada 2 masalah:

### 1. Di `HasilPengujian.php` - Method `upload()`

```php
// SEBELUM (SALAH):
$db->table('t_files_lhus')->insert([
    'kode' => $detKode,
    'kode_layanan' => $lnKode,
    'file_lhus' => $filename,
    'upload_by' => $user_id,
    'status' => 3
]);
```

**Problem**: Setiap upload melakukan `INSERT` baru tanpa cek existing record. Jadi jika file sudah ada (misal setelah ditolak), akan terbuat record duplikat di `t_files_lhus`.

### 2. Di `TinjauLHUS.php` - Method `detailList()`

```php
// SEBELUM (SALAH):
$builder->join('t_files_lhus lhus', 'lhus.kode = d.kode', 'left');
```

**Problem**: LEFT JOIN langsung ke `t_files_lhus` tanpa filter. Jika ada 3 record di `t_files_lhus` untuk 1 `kode`, maka JOIN akan menghasilkan 3 baris.

## Solusi Implementasi

### Fix 1: Update Method `upload()` di HasilPengujian.php

**File**: `appengines/app/Modules/HasilPengujian/Controllers/HasilPengujian.php`

**Perubahan**: Cek apakah sudah ada record untuk `kode` tersebut:

- Jika **sudah ada**: `UPDATE` record yang sudah ada + hapus file lama
- Jika **belum ada**: `INSERT` record baru

```php
// SESUDAH (BENAR):
// Cek apakah sudah ada record di t_files_lhus untuk kode ini
$existingFile = $db->table('t_files_lhus')
    ->where('kode', $detKode)
    ->get()->getRow();

if ($existingFile) {
    // Update record yang sudah ada
    $db->table('t_files_lhus')
        ->where('kode', $detKode)
        ->update([
            'file_lhus' => $filename,
            'upload_by' => $user_id,
            'status' => 3,
            'kode_layanan' => $lnKode
        ]);

    // Hapus file lama jika ada
    if (!empty($existingFile->file_lhus) && $existingFile->file_lhus !== $filename) {
        $oldFilePath = FCPATH . 'uploads/lhus/' . $existingFile->file_lhus;
        if (is_file($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }
} else {
    // Insert record baru jika belum ada
    $db->table('t_files_lhus')->insert([
        'kode' => $detKode,
        'kode_layanan' => $lnKode,
        'file_lhus' => $filename,
        'upload_by' => $user_id,
        'status' => 3
    ]);
}
```

**Benefit**:

- ✅ Tidak ada duplikat di database
- ✅ File lama otomatis terhapus dari server
- ✅ Setiap `kode` hanya punya 1 record aktif di `t_files_lhus`

### Fix 2: Update Query `detailList()` di TinjauLHUS.php

**File**: `appengines/app/Modules/TinjauLHUS/Controllers/TinjauLHUS.php`

**Perubahan**: Gunakan subquery untuk ambil hanya 1 file **terbaru** per `kode`

```php
// SESUDAH (BENAR):
// Subquery untuk ambil hanya 1 file terbaru per kode
// Jika ada multiple files untuk 1 kode, ambil yang id paling besar (terakhir diupload)
$builder->join(
    '(SELECT lhus1.* FROM t_files_lhus lhus1
      INNER JOIN (
        SELECT kode, MAX(id) as max_id
        FROM t_files_lhus
        GROUP BY kode
      ) lhus2 ON lhus1.kode = lhus2.kode AND lhus1.id = lhus2.max_id
    ) lhus',
    'lhus.kode = d.kode',
    'left'
);
```

**Benefit**:

- ✅ Hanya 1 baris per layanan (tidak ada duplikat visual)
- ✅ Selalu ambil file yang terakhir diupload
- ✅ Tetap backward compatible jika ada data lama

## Testing Scenario

1. **Normal Upload**: Upload LHUS baru → kirim → cek tidak ada duplikat ✅
2. **Re-upload After Reject**:
   - Manager tolak LHUS (files=2)
   - Kembali ke Hasil Pengujian
   - Upload file baru
   - Kirim lagi
   - **Cek**: Hanya muncul 1 record di TinjauLHUS (bukan duplikat) ✅
3. **Multiple Re-upload**: Tolak → upload → tolak → upload → kirim → cek hanya 1 record ✅

## Database Impact

- **t_files_lhus**: Setiap `kode` maksimal punya 1 record aktif (bukan 3+ seperti sebelumnya)
- **File system**: File lama otomatis dihapus saat upload ulang (hemat storage)

## Notes

- Fix ini **backward compatible** dengan data lama yang mungkin sudah punya duplikat
- Subquery hanya ambil file dengan `id` terbesar (terakhir diupload)
- Jika mau clean up data lama yang duplikat, bisa jalankan:

```sql
-- Query untuk lihat duplikat yang ada
SELECT kode, COUNT(*) as jumlah_duplikat
FROM t_files_lhus
GROUP BY kode
HAVING COUNT(*) > 1;

-- Query untuk hapus duplikat (HATI-HATI! Backup dulu!)
DELETE lhus1 FROM t_files_lhus lhus1
INNER JOIN (
    SELECT kode, MAX(id) as max_id
    FROM t_files_lhus
    GROUP BY kode
) lhus2 ON lhus1.kode = lhus2.kode
WHERE lhus1.id < lhus2.max_id;
```

## Status

✅ **FIXED** - Duplicate prevention implemented in both upload and display logic

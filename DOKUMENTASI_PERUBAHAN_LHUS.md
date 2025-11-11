# Dokumentasi Perubahan Sistem Upload LHUS

## Ringkasan Perubahan

Sistem upload LHUS telah diperbaiki dengan logika yang lebih jelas dan penambahan kolom database untuk mempermudah pengecekan kelengkapan upload per invoice.

## Perubahan Database

### 1. Tabel `t_files_lhus`

Ditambahkan kolom baru:

- **`kode_layanan`** (INT UNSIGNED, NULL)
  - Relasi ke `simlab_t_layanan.lnKode`
  - Foreign Key dengan CASCADE
  - Mempermudah pengecekan apakah semua layanan dalam 1 invoice sudah terupload

### 2. Status di Kolom `status` (t_files_lhus dan t_layanan_detil.files)

Status yang digunakan:

- **0** = Terkirim ke manajer (sudah dikirim dengan tombol "Kirim")
- **1** = Diterima/disetujui oleh manajer
- **2** = Ditolak oleh manajer
- **3** = Terunggah (file sudah diupload tapi belum dikirim)

## Logika Alur Upload & Kirim

### 1. Upload File LHUS

Ketika penyelia mengupload file LHUS:

- Status `files` di `t_layanan_detil` = **3** (terunggah)
- Status di `t_files_lhus.status` = **3** (terunggah)
- Record di `t_files_lhus` mencatat:
  - `kode` = kode detail layanan
  - `kode_layanan` = lnKode invoice
  - `file_lhus` = nama file
  - `upload_by` = user_id penyelia
  - `status` = 3

**Hasil di UI Modal Detail:**

- Status file: "Terunggah (belum dikirim)"
- Badge: `<span class="badge bg-info">Terunggah (belum dikirim)</span>`

### 2. Tekan Tombol "Kirim"

Ketika penyelia menekan tombol "Kirim":

- Status `files` di `t_layanan_detil` berubah dari **3** → **0** (terkirim)
- Status di `t_files_lhus.status` berubah dari **3** → **0** (terkirim)
- Kolom `terima_layanan_by` di `t_layanan_detil` diisi dengan `user_id`

**Pengecekan Kelengkapan Invoice:**
Sistem akan mengecek apakah **SEMUA** layanan detil dalam invoice tersebut sudah terupload:

```sql
SELECT COUNT(*) as total_belum_upload
FROM t_layanan_detil d
LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
WHERE d.kode_layanan = ? -- lnKode invoice
  AND d.status_layanan = 1
  AND (lhus.file_id IS NULL OR lhus.file_lhus IS NULL OR lhus.file_lhus = '')
```

- **Jika `total_belum_upload = 0`** (semua layanan sudah upload):

  - `lnStatus` di `simlab_t_layanan` → **5** (LHUS diverifikasi manajer)
  - Pesan: "Semua LHUS dalam invoice ini sudah lengkap dan terkirim ke manajer teknis!"

- **Jika masih ada yang belum upload:**
  - `lnStatus` tetap **4** (sedang dalam pengujian)
  - Pesan: "LHUS Anda berhasil dikirim ke manajer. Masih ada X layanan lain yang belum terupload."

**Hasil di UI Tabel Utama:**

- Status: "Terkirim ke manajer teknis"
- Badge: `<span class="badge bg-primary">Terkirim ke manajer teknis</span>`

### 3. Status di Tabel Utama (formatStatusForPenyelia)

Prioritas tampilan status untuk penyelia:

1. **LHUS ditolak** (files = 2) → Badge merah
2. **Terunggah (belum dikirim)** (files = 3) → Badge biru info
3. **LHUS diterima** (files = 1, semua) → Badge hijau
4. **Terkirim ke manajer teknis** (files = 0) → Badge biru primary
5. **Fallback** → Status sesuai lnStatus

## Keuntungan Penambahan Kolom `kode_layanan`

### Sebelum (Tanpa `kode_layanan`):

```sql
-- Harus JOIN 2 tabel untuk cek kelengkapan
SELECT COUNT(*)
FROM t_layanan_detil d
LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
WHERE d.kode_layanan = ?
  AND d.status_layanan = 1
  AND (lhus.file_id IS NULL)
```

### Sesudah (Dengan `kode_layanan`):

```sql
-- Bisa langsung query dari t_files_lhus untuk laporan
SELECT COUNT(*) as total_uploaded,
       COUNT(CASE WHEN status = 0 THEN 1 END) as total_sent,
       COUNT(CASE WHEN status = 3 THEN 1 END) as total_pending
FROM t_files_lhus
WHERE kode_layanan = ?
```

**Manfaat:**

- Query lebih cepat untuk laporan per invoice
- Mudah tracking progress upload per invoice
- Bisa langsung lihat statistik tanpa JOIN kompleks

## File yang Diubah

### 1. Migration

- **File:** `2025-11-09-064857_CreateTFilesLhus.php`
- **Perubahan:** Menambahkan kolom `kode_layanan` dan foreign key
- **File Baru:** `2025-11-11-000001_AlterTFilesLhusAddKodeLayanan.php` (untuk database yang sudah ada)

### 2. Controller

- **File:** `HasilPengujian.php`
- **Method yang diubah:**
  - `upload()`: Menambahkan kolom kode_layanan saat insert, status = 3
  - `submit()`: Logika kirim dengan pengecekan kelengkapan invoice
  - `formatStatusForPenyelia()`: Menambahkan status "Terunggah (belum dikirim)"

## Cara Testing

### Test Case 1: Upload File (Belum Kirim)

1. Login sebagai penyelia
2. Pilih layanan yang sedang dalam pengujian
3. Klik "Unggah LHUS"
4. Upload file
5. **Expected Result:**
   - Status di modal detail: "Terunggah (belum dikirim)"
   - Status di tabel utama: "Terunggah (belum dikirim)" (badge biru info)
   - `files` = 3 di `t_layanan_detil`
   - `status` = 3 di `t_files_lhus`

### Test Case 2: Kirim LHUS (Partial Upload)

1. Lanjutkan dari Test Case 1
2. Klik tombol "Kirim" di modal detail
3. **Expected Result:**
   - Status di modal detail: "Terkirim ke manajer teknis"
   - Status di tabel utama: "Terkirim ke manajer teknis" (badge biru primary)
   - `files` = 0 di `t_layanan_detil`
   - `status` = 0 di `t_files_lhus`
   - `lnStatus` tetap = 4 (jika masih ada layanan lain yang belum upload)
   - Pesan: "LHUS Anda berhasil dikirim ke manajer. Masih ada X layanan lain yang belum terupload."

### Test Case 3: Kirim LHUS (Complete Upload)

1. Pastikan SEMUA layanan dalam 1 invoice sudah terupload
2. Klik tombol "Kirim" untuk layanan terakhir
3. **Expected Result:**
   - Status berubah menjadi "Terkirim ke manajer teknis"
   - `lnStatus` berubah ke 5 (LHUS diverifikasi manajer)
   - Pesan: "Semua LHUS dalam invoice ini sudah lengkap dan terkirim ke manajer teknis!"

### Test Case 4: Multiple Users Upload

1. Login sebagai penyelia A, upload LHUS untuk layanan A
2. Kirim LHUS layanan A
3. Login sebagai penyelia B, upload LHUS untuk layanan B
4. Kirim LHUS layanan B
5. **Expected Result:**
   - Setelah penyelia A kirim: lnStatus tetap 4
   - Setelah penyelia B kirim (jika sudah semua): lnStatus berubah ke 5

## Query untuk Monitoring

### Cek Status Upload per Invoice

```sql
SELECT
    ln.lnKode,
    ln.lnStatus,
    COUNT(d.kode) as total_layanan,
    COUNT(lhus.file_id) as total_uploaded,
    COUNT(CASE WHEN lhus.status = 3 THEN 1 END) as total_terunggah,
    COUNT(CASE WHEN lhus.status = 0 THEN 1 END) as total_terkirim,
    COUNT(CASE WHEN lhus.status = 1 THEN 1 END) as total_diterima,
    COUNT(CASE WHEN lhus.status = 2 THEN 1 END) as total_ditolak
FROM simlab_t_layanan ln
LEFT JOIN t_layanan_detil d ON d.kode_layanan = ln.lnKode AND d.status_layanan = 1
LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
WHERE ln.lnStatus >= 4
GROUP BY ln.lnKode
ORDER BY ln.lnTgl DESC;
```

### Cek Layanan yang Belum Upload

```sql
SELECT
    ln.lnKode,
    ln.lnNoTransaksi,
    d.kode,
    d.nama_layanan,
    u.user_name as penyelia
FROM simlab_t_layanan ln
INNER JOIN t_layanan_detil d ON d.kode_layanan = ln.lnKode
LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
LEFT JOIN r_tim rt ON rt.uji_kode = d.uji_kode
LEFT JOIN simlab_account_users u ON u.user_id = rt.user_id
WHERE ln.lnStatus = 4
  AND d.status_layanan = 1
  AND (lhus.file_id IS NULL OR lhus.file_lhus IS NULL OR lhus.file_lhus = '')
ORDER BY ln.lnTgl, d.kode;
```

## Catatan Penting

1. **Status 3 adalah sementara**: Status ini hanya untuk file yang sudah diupload tapi belum ditekan tombol "Kirim"
2. **lnStatus hanya berubah jika semua layanan dalam invoice sudah terupload**: Ini memastikan invoice benar-benar lengkap sebelum dikirim ke manajer
3. **Kolom kode_layanan penting**: Jangan dihapus karena digunakan untuk pengecekan kelengkapan
4. **Migration alternatif tersedia**: Jika database sudah running, gunakan file `2025-11-11-000001_AlterTFilesLhusAddKodeLayanan.php`

## Troubleshooting

### Problem: Status tidak berubah setelah upload

**Solusi:** Cek apakah:

- File berhasil tersimpan di `t_files_lhus`
- Kolom `kode_layanan` terisi dengan benar
- Status di `t_layanan_detil.files` = 3

### Problem: lnStatus tidak berubah ke 5 padahal semua sudah upload

**Solusi:** Jalankan query manual:

```sql
SELECT d.kode, d.nama_layanan, lhus.file_lhus, lhus.status
FROM t_layanan_detil d
LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
WHERE d.kode_layanan = ? AND d.status_layanan = 1;
```

Pastikan semua baris memiliki `file_lhus` yang terisi dan tidak NULL.

### Problem: Migration error "fieldExists not found"

**Solusi:** Ganti `$this->db->fieldExists()` dengan query manual:

```php
$result = $this->db->query("SHOW COLUMNS FROM t_files_lhus LIKE 'kode_layanan'")->getResult();
if (empty($result)) {
    // Kolom belum ada, lakukan ALTER TABLE
}
```

---

**Versi:** 1.0  
**Tanggal:** 11 November 2025  
**Author:** Development Team

# Perubahan Sistem Upload LHUS - Summary

## 🎯 Tujuan Perubahan

Memperbaiki logika upload dan pengiriman LHUS dengan membedakan status:

- **Terunggah** (file sudah diupload tapi belum dikirim)
- **Terkirim** (sudah ditekan tombol kirim)
- **lnStatus berubah ke 5** hanya jika SEMUA layanan dalam 1 invoice sudah terupload

## 📊 Perubahan Database

### Tabel: `t_files_lhus`

**Kolom Baru:** `kode_layanan` (INT UNSIGNED)

- Relasi ke `simlab_t_layanan.lnKode`
- Mempermudah pengecekan kelengkapan upload per invoice

**Status Code:**

- `0` = Terkirim ke manajer
- `1` = Diterima manajer
- `2` = Ditolak manajer
- `3` = Terunggah (belum dikirim) ✨ BARU

## 🔄 Alur Kerja Baru

### 1️⃣ Upload File LHUS

```
User upload file → Status = 3 (terunggah)
└─ Di modal detail: "Terunggah (belum dikirim)"
└─ Di tabel utama: Badge biru info
```

### 2️⃣ Kirim LHUS

```
User klik "Kirim" → Status = 0 (terkirim)
├─ Cek: Apakah semua layanan dalam invoice sudah upload?
├─ ✅ Ya → lnStatus = 5 (Terkirim ke manajer teknis)
└─ ❌ Tidak → lnStatus tetap 4 (Masih dalam pengujian)
```

## 📁 File yang Dimodifikasi

1. **Migration:**

   - `2025-11-09-064857_CreateTFilesLhus.php` - Tambah kolom `kode_layanan`
   - `2025-11-11-000001_AlterTFilesLhusAddKodeLayanan.php` - Alter table (untuk database existing)

2. **Controller:**
   - `HasilPengujian.php`
     - Method `upload()` - Tambahkan `kode_layanan`, status = 3
     - Method `submit()` - Logika kirim dengan cek kelengkapan invoice
     - Method `formatStatusForPenyelia()` - Tambah status "Terunggah"

## 🧪 Testing Checklist

- [ ] Upload file → Status = "Terunggah (belum dikirim)"
- [ ] Kirim (partial) → Status = "Terkirim", lnStatus tetap 4
- [ ] Kirim (complete) → Status = "Terkirim", lnStatus berubah ke 5
- [ ] Multiple users upload → Status sync correctly
- [ ] Modal detail refresh after upload
- [ ] Tabel utama refresh after kirim

## 🚀 Cara Deploy

### Step 1: Backup Database

```sql
-- Backup tabel terkait
CREATE TABLE t_files_lhus_backup AS SELECT * FROM t_files_lhus;
CREATE TABLE t_layanan_detil_backup AS SELECT * FROM t_layanan_detil;
CREATE TABLE simlab_t_layanan_backup AS SELECT * FROM simlab_t_layanan;
```

### Step 2: Run Migration

```bash
# Jika database baru (fresh install)
php spark migrate

# Jika database sudah ada
php spark migrate --group=app # Akan menjalankan AlterTFilesLhusAddKodeLayanan
```

### Step 3: Update Existing Data

```sql
-- Update kode_layanan untuk data yang sudah ada
UPDATE t_files_lhus lhus
INNER JOIN t_layanan_detil d ON d.kode = lhus.kode
SET lhus.kode_layanan = d.kode_layanan
WHERE lhus.kode_layanan IS NULL;
```

### Step 4: Verify

```sql
-- Cek apakah semua data sudah memiliki kode_layanan
SELECT COUNT(*) as total_null
FROM t_files_lhus
WHERE kode_layanan IS NULL;
-- Expected: 0
```

## 📝 Query Helper

Lihat file `SQL_HELPER_LHUS.sql` untuk query-query monitoring:

- Cek status upload per invoice
- Cek layanan yang belum upload
- Cek progress per penyelia
- Dashboard summary

## ⚠️ Known Issues & Solutions

### Issue: Status tidak update setelah upload

**Solusi:** Pastikan kolom `kode_layanan` terisi dengan benar di `t_files_lhus`

### Issue: lnStatus tidak berubah ke 5

**Solusi:** Jalankan query untuk cek kelengkapan:

```sql
SELECT
    d.kode, d.nama_layanan,
    lhus.file_lhus, lhus.status
FROM t_layanan_detil d
LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
WHERE d.kode_layanan = ? AND d.status_layanan = 1;
```

## 📚 Dokumentasi Lengkap

- **Detail Perubahan:** `DOKUMENTASI_PERUBAHAN_LHUS.md`
- **Query Helper:** `SQL_HELPER_LHUS.sql`
- **Test Cases:** Lihat section Testing di dokumentasi

## 👥 Contact

Jika ada pertanyaan atau issue, hubungi Development Team.

---

**Last Updated:** 11 November 2025  
**Version:** 1.0

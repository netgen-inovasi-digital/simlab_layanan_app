# Perubahan Modul TinjauLHUS

## 📋 Ringkasan Perubahan

Modul TinjauLHUS telah disesuaikan dengan struktur database baru yang menggunakan:

- `t_layanan_detil` (menggantikan `simlab_t_layanan_detil`)
- `t_files_lhus` (untuk tracking file upload)
- `r_tim` (untuk relasi user dengan layanan)

**Logika dan alur kerja tetap sama**, hanya struktur database yang disesuaikan.

---

## 🔄 Perubahan Database

### Tabel yang Digunakan

#### **Sebelum:**

- `simlab_t_layanan_detil` (dengan kolom `detLnKode`, `detStatusLHUS`, `detKetLhus`)
- Relasi langsung: `detManajerTeknis`, `detPenyelia`

#### **Sesudah:**

- `t_layanan_detil` (dengan kolom `kode_layanan`, `files`, `status_layanan`)
- `t_files_lhus` (dengan kolom `kode`, `kode_layanan`, `file_lhus`, `status`, `catatan`, `validasi_by`)
- `r_tim` (relasi user dengan `uji_kode`)

### Mapping Status

#### **Files Status di `t_layanan_detil.files`:**

- `0` = Terkirim (menunggu review manajer)
- `1` = Diterima manajer
- `2` = Ditolak manajer
- `3` = Terunggah (belum dikirim ke manajer)

---

## 🎯 Perubahan Method

### 1. **dataList()**

**Yang Berubah:**

- Query menggunakan `t_layanan_detil` dan `r_tim`
- Filter berdasarkan `rt.user_id` (bukan `detManajerTeknis` / `detPenyelia`)
- Status summary menggunakan kolom `files` (bukan `detStatusLHUS`)

**Query Baru:**

```php
$detRows = $db->table('t_layanan_detil as d')
    ->select('DISTINCT d.kode_layanan')
    ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
    ->where('rt.user_id', $user_id)
    ->where('d.status_layanan', 1)
    ->get()->getResult();
```

**Status Badge:**

- `files = 0` → "Terkirim (Menunggu Review)"
- `files = 1` → "LHUS Diterima"
- `files = 2` → "LHUS Ditolak"
- `files = 3` → "Terunggah (Belum Kirim)"

---

### 2. **detailList()**

**Yang Berubah:**

- Query JOIN dengan `t_files_lhus` untuk mendapatkan file dan catatan
- Data keterangan diambil dari `t_files_lhus.catatan` (bukan `detKetLhus`)
- File path diambil dari `t_files_lhus.file_lhus`

**Query Baru:**

```php
$builder = $db->table('t_layanan_detil as d');
$builder->select("
    d.kode,
    d.uji_kode,
    d.kode_layanan,
    d.nama_layanan,
    d.jumlah,
    d.biaya,
    d.catatan_pelanggan,
    d.catatan_manajer,
    d.files,
    d.status_layanan,
    lhus.file_lhus,
    lhus.catatan as ket_lhus,
    lhus.validasi_by
", false);

$builder->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner');
$builder->join('t_files_lhus lhus', 'lhus.kode = d.kode', 'left');
$builder->where('d.kode_layanan', $lnKode);
$builder->where('d.status_layanan', 1);
$builder->where('rt.user_id', $user_id);
```

---

### 3. **saveDetKetLhus()**

**Yang Berubah:**

- Update ke `t_files_lhus.catatan` (bukan `simlab_t_layanan_detil.detKetLhus`)

**Query Baru:**

```php
$db->table('t_files_lhus')
    ->where('kode', $detKode)
    ->update(['catatan' => $ket]);
```

---

### 4. **prosesDetailLhus()**

**Yang Berubah:**

- Update `t_layanan_detil.files` (bukan `detStatusLHUS`)
- Catat `validasi_by` di `t_files_lhus` (bukan `detAccLHUS`)
- Query menggunakan `kode_layanan` dan `status_layanan`

**Update Baru:**

```php
// Update status files di t_layanan_detil
$db->table('t_layanan_detil')
    ->where('kode', $detKode)
    ->update(['files' => $new]);

// Update validasi_by di t_files_lhus
$db->table('t_files_lhus')
    ->where('kode', $detKode)
    ->update([
        'status' => $new,
        'validasi_by' => $accUserId
    ]);
```

**Auto Update lnStatus:**

```php
// Jika semua layanan diterima (files = 1), set lnStatus = 6
$rowG = $db->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN files = 1 THEN 1 ELSE 0 END) AS cnt1
    FROM t_layanan_detil
    WHERE kode_layanan = ? AND status_layanan = 1
", [$lnKode])->getRowArray();

if ($gTotal > 0 && $gCnt1 === $gTotal) {
    $db->table('simlab_t_layanan')
       ->where('lnKode', $lnKode)
       ->update(['lnStatus' => 6]);
}
```

---

### 5. **proses()** (Parent LN)

**Yang Berubah:**

- Query menggunakan `t_layanan_detil.files` dan `r_tim`
- Filter berdasarkan `rt.user_id`

**Query Baru:**

```php
// Ringkasan global
$rowG = $db->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN files = 1 THEN 1 ELSE 0 END) AS cnt1,
        SUM(CASE WHEN files = 0 OR files = 3 THEN 1 ELSE 0 END) AS cnt0
    FROM t_layanan_detil
    WHERE kode_layanan = ? AND status_layanan = 1
", [$lnKode])->getRowArray();

// Ringkasan subset user
$rowU = $db->table('t_layanan_detil as d')
    ->select("
        COUNT(*) AS total,
        SUM(CASE WHEN d.files = 1 THEN 1 ELSE 0 END) AS cnt1,
        SUM(CASE WHEN d.files = 0 OR d.files = 3 THEN 1 ELSE 0 END) AS cnt0
    ", false)
    ->join('r_tim rt', 'rt.uji_kode = d.uji_kode', 'inner')
    ->where('d.kode_layanan', $lnKode)
    ->where('d.status_layanan', 1)
    ->where('rt.user_id', $user_id)
    ->get()->getRowArray();
```

---

## ✅ Alur Kerja (Tidak Berubah)

### 1. Manajer Login

- Melihat daftar invoice dengan status >= 5
- Filter berdasarkan status: Belum ditinjau (5), Disetujui (6), Ditolak (2)

### 2. Klik "Tinjau LHUS"

- Modal muncul dengan daftar layanan detail
- Manajer bisa lihat file LHUS yang sudah diupload
- Manajer bisa input keterangan untuk setiap layanan

### 3. Aksi per Layanan

- **Terima (✓)**: `files` = 1, `validasi_by` = user_id manajer
- **Tolak (✗)**: `files` = 2, `validasi_by` = user_id manajer

### 4. Auto Update lnStatus

- Jika **SEMUA** layanan dalam invoice sudah diterima (`files = 1`)
- Maka `lnStatus` otomatis berubah ke **6** (LHUS Disetujui)

---

## 🧪 Testing Checklist

- [ ] Manajer bisa melihat list invoice dengan status >= 5
- [ ] Filter status berfungsi (Belum ditinjau, Disetujui, Ditolak)
- [ ] Klik "Tinjau LHUS" menampilkan modal dengan detail layanan
- [ ] File LHUS bisa dilihat (tombol "Lihat")
- [ ] Keterangan LHUS bisa disimpan
- [ ] Tombol "Terima" mengubah status ke 1
- [ ] Tombol "Tolak" mengubah status ke 2
- [ ] `validasi_by` tercatat dengan benar
- [ ] `lnStatus` berubah ke 6 jika semua layanan diterima
- [ ] `lnStatus` tetap 5 jika masih ada yang belum diterima/ditolak

---

## 📊 Query Monitoring

### Cek Status Review per Invoice

```sql
SELECT
    ln.lnKode,
    ln.lnNoTransaksi,
    ln.lnStatus,
    COUNT(d.kode) as total_layanan,
    SUM(CASE WHEN d.files = 1 THEN 1 ELSE 0 END) as diterima,
    SUM(CASE WHEN d.files = 2 THEN 1 ELSE 0 END) as ditolak,
    SUM(CASE WHEN d.files = 0 OR d.files = 3 THEN 1 ELSE 0 END) as pending
FROM simlab_t_layanan ln
LEFT JOIN t_layanan_detil d ON d.kode_layanan = ln.lnKode AND d.status_layanan = 1
WHERE ln.lnStatus >= 5
GROUP BY ln.lnKode, ln.lnNoTransaksi, ln.lnStatus
ORDER BY ln.lnTgl DESC;
```

### Cek Layanan yang Belum Ditinjau

```sql
SELECT
    ln.lnKode,
    ln.lnNoTransaksi,
    d.kode,
    d.nama_layanan,
    d.files,
    lhus.file_lhus,
    lhus.catatan,
    u.user_name as validator
FROM simlab_t_layanan ln
INNER JOIN t_layanan_detil d ON d.kode_layanan = ln.lnKode
LEFT JOIN t_files_lhus lhus ON lhus.kode = d.kode
LEFT JOIN simlab_account_users u ON u.user_id = lhus.validasi_by
WHERE ln.lnStatus = 5
  AND d.status_layanan = 1
  AND (d.files = 0 OR d.files = 3)
ORDER BY ln.lnTgl, d.kode;
```

### Cek Siapa yang Menvalidasi

```sql
SELECT
    lhus.kode,
    d.nama_layanan,
    lhus.status,
    u.user_name as validator,
    lhus.catatan
FROM t_files_lhus lhus
INNER JOIN t_layanan_detil d ON d.kode = lhus.kode
LEFT JOIN simlab_account_users u ON u.user_id = lhus.validasi_by
WHERE lhus.kode_layanan = ? -- ganti dengan lnKode
ORDER BY lhus.kode;
```

---

## 📝 Catatan Penting

1. **Kolom `validasi_by`**: Mencatat siapa manajer yang menerima/menolak LHUS
2. **Kolom `catatan`**: Keterangan dari manajer untuk setiap layanan
3. **Status `files = 3`**: Status terunggah (belum dikirim) diperlakukan sama dengan `files = 0` dalam pengecekan "pending"
4. **Relasi `r_tim`**: Digunakan untuk filter layanan yang relevan dengan user yang login
5. **Auto update `lnStatus`**: Hanya berubah ke 6 jika **SEMUA** layanan dalam invoice sudah `files = 1`

---

**Last Updated:** 11 November 2025  
**Version:** 1.0  
**Compatible with:** Struktur database baru (t_layanan_detil + t_files_lhus + r_tim)

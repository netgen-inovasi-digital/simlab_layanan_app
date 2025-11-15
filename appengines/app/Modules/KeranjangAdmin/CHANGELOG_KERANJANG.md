# Perubahan Keranjang Checkout Admin

## Tanggal: 15 November 2025

## Deskripsi Perubahan

Mengupdate keranjang checkout pada FormulirAdmin agar konsepnya sama dengan yang ada di Pelayanan, dengan tetap mempertahankan fitur select pelanggan.

## Perubahan yang Dilakukan pada `Views/v_keranjang.php`:

### 1. **Perubahan Tabel Pilih Layanan (Atas)**

- **Sebelum**: Kolom header → Parameter | Instrumen/Alat/Tempat | Biaya | Jumlah | **Keterangan** | Aksi
- **Sesudah**: Kolom header → Parameter | Instrumen/Alat/Tempat | Biaya | Jumlah | **Metode Uji** | Aksi
- Mengubah kolom "Keterangan" menjadi "Metode Uji" untuk konsistensi dengan Pelayanan

### 2. **Perubahan Tabel Preview Keranjang (Tengah)**

- **Sebelum**: Kolom header → No | Parameter | Instrumen/Alat/Tempat | Diskon | Biaya | Jumlah | **Keterangan** | Aksi
- **Sesudah**: Kolom header → No | Parameter | Instrumen/Alat/Tempat | Diskon | Biaya | Jumlah | **Metode Uji** | Aksi
- Mengubah kolom "Keterangan" menjadi "Metode Uji"
- Menyesuaikan lebar kolom agar lebih proporsional

### 3. **Penambahan Form Detail Identitas Sampel (Bawah)**

Menambahkan section baru di bawah preview keranjang dengan field:

- **Jenis Sampel** (required) - Input text
- **Kemasan Sampel** (required) - Input text
- **Sifat Sampel** (required) - Select dropdown dengan pilihan:
  - Cair
  - Korosif
  - Beracun
  - Mudah menguap
  - Higroskopis
  - Tidak mudah menguap
  - Padat kering
  - Cairan kental
- **Sisa Sampel** (required) - Select dropdown:
  - Tidak diambil
  - Diambil
- **Deskripsi Sampel** (optional) - Textarea
- **Keterangan Khusus** (optional) - Textarea

### 4. **Update JavaScript - Fungsi `updateKeranjangCounter()`**

Menambahkan logika untuk menampilkan/menyembunyikan form identitas sampel:

```javascript
const formIdentitas = document.getElementById("formIdentitasSampel");

if (jumlahItem === 0) {
  // ... kode existing ...
  if (formIdentitas) formIdentitas.style.display = "none";
} else {
  // ... kode existing ...
  if (formIdentitas) formIdentitas.style.display = "block";
}
```

### 5. **Update JavaScript - Fungsi `doCheckout()`**

Menambahkan:

- **Validasi field identitas sampel** sebelum checkout
- **Pengiriman data identitas sampel** ke server via FormData
- **Reset form identitas sampel** setelah checkout berhasil

```javascript
// Validasi form identitas sampel
if (!jenisSampel || !jenisSampel.value.trim()) {
  sayAlert("errorModal", "Gagal", "Harap isi Jenis Sampel!", "warning");
  return;
}
// ... validasi lainnya ...

// Tambahkan data identitas sampel ke FormData
formData.append("jenisSampel", jenisSampel.value.trim());
formData.append("kemasanSampel", kemasanSampel.value.trim());
// ... data lainnya ...

// Reset form setelah sukses
if (jenisSampel) jenisSampel.value = "";
// ... reset field lainnya ...
```

## Fitur yang Dipertahankan

✅ **Select Pelanggan** - Tetap ada dan berfungsi seperti sebelumnya
✅ **Validasi pelanggan** - Masih dicek sebelum bisa menambahkan item
✅ **Filter kategori** - Tetap berfungsi normal
✅ **Semua fungsi existing** - Tidak ada yang dihapus/dirusak

## Struktur Akhir Modal Keranjang

```
┌─────────────────────────────────────────────────┐
│ 1. Daftar Layanan Tersedia                     │
│    - Filter Kategori & Select Pelanggan        │
│    - Tabel Pilih Layanan (+ Metode Uji)       │
├─────────────────────────────────────────────────┤
│ 2. Keranjang Anda (Preview)                    │
│    - Tabel Preview (+ Metode Uji)              │
│    - Grand Total                                │
├─────────────────────────────────────────────────┤
│ 3. Detail Identitas Sampel [BARU]             │
│    - Jenis Sampel *                            │
│    - Kemasan Sampel *                          │
│    - Sifat Sampel *                            │
│    - Sisa Sampel *                             │
│    - Deskripsi Sampel                          │
│    - Keterangan Khusus                         │
└─────────────────────────────────────────────────┘
[Tutup]  [Checkout Sekarang]
```

## Testing yang Perlu Dilakukan

1. ✅ Buka modal keranjang admin
2. ✅ Pilih pelanggan
3. ✅ Tambahkan item ke keranjang
4. ✅ Pastikan form identitas sampel muncul
5. ✅ Coba checkout tanpa mengisi form identitas → harus muncul alert validasi
6. ✅ Isi form identitas sampel lengkap
7. ✅ Checkout → harus berhasil dan form ter-reset
8. ✅ Pastikan data terkirim ke backend dengan benar

## Catatan Penting

⚠️ **Backend Controller**: Pastikan controller `KeranjangAdmin::checkout()` sudah diupdate untuk menerima parameter baru:

- `jenisSampel`
- `kemasanSampel`
- `sifatSampel`
- `sisaSampel`
- `deskripsiSampel` (optional)
- `keteranganKhusus` (optional)

⚠️ **Database**: Pastikan tabel yang relevan memiliki kolom untuk menyimpan data identitas sampel.

## File yang Dimodifikasi

- ✅ `appengines/app/Modules/KeranjangAdmin/Views/v_keranjang.php`

## File yang TIDAK Dimodifikasi

- ❌ Controller (`KeranjangAdmin\Controllers\*.php`) - Perlu update manual
- ❌ Model - Perlu update manual
- ❌ Database schema - Perlu update manual
- ❌ File lain di module KeranjangAdmin

---

**Status**: ✅ View telah diupdate dan siap untuk testing
**Next Steps**: Update backend controller dan database untuk handle data identitas sampel

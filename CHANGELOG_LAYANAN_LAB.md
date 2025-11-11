# Perubahan Sistem Layanan Lab - Tim Kolaborasi

## Ringkasan Perubahan

Sistem layanan lab telah diperbarui untuk mendukung **kolaborasi tim** dengan beberapa penanggung jawab per layanan, menggantikan sistem lama yang hanya memiliki 1 penyelia dan 1 manajer teknis.

## Perubahan Database

### 1. Migration Baru

- **File**: `2025-11-11-000002_AlterRLayananPengujianRemoveOldColumns.php`
- **Fungsi**: Menghapus kolom lama `ujiPenyelia` dan `ujiManajerTeknis` dari tabel `r_layanan_pengujian`
- **Migrasi Otomatis**: Jika tabel masih menggunakan nama lama `simlab_r_layanan_pengujian`, akan otomatis di-rename ke `r_layanan_pengujian`

### 2. Tabel yang Digunakan

- **`r_layanan_pengujian`**: Menyimpan data layanan lab
  - Kolom: `kode`, `nama_layanan`, `kode_alat`, `kode_parameter`, `kode_jenis`, `satuan`, `biaya`, `diskon`
- **`r_tim`**: Menyimpan data tim penanggung jawab (relasi many-to-many)
  - Kolom: `id`, `uji_kode` (FK ke r_layanan_pengujian.kode), `user_id` (FK ke simlab_account.user_id)

### 3. Update Migration File

- **File**: `2025-09-14-055500_SimlabRLayananPengujian.php`
- Foreign key names telah diperbarui untuk menghindari konflik

## Perubahan Kode

### Controller (`Lab.php`)

1. **Property diubah**:

   - `$table = 'r_layanan_pengujian'` (dari `simlab_r_layanan_pengujian`)
   - `$id = 'kode'` (dari `ujiKode`)

2. **Method `edit()`**:

   - Mengambil data tim dari tabel `r_tim`
   - Mengembalikan array `user_id` yang tergabung dalam tim

3. **Method `submit()`**:

   - Menerima array `tim[]` dari form (multiple user_id)
   - Insert/Update data layanan
   - Mengelola anggota tim di tabel `r_tim`
   - Menghapus tim lama dan insert tim baru saat update

4. **Method `getoptions()`**:

   - Mengembalikan list `users` (role_id=6 untuk penyelia, bisa disesuaikan)
   - Tidak lagi memisahkan penyelia dan manajer

5. **Method `getTim($id)` [BARU]**:

   - Mengambil detail tim penanggung jawab untuk layanan tertentu
   - JOIN dengan `simlab_account` dan `roles`
   - Mengembalikan nama, role, dll

6. **Method `dataList()`**:
   - JOIN diubah mengikuti nama kolom baru
   - Menampilkan jumlah anggota tim dengan button "Lihat (n)"
   - Button trigger modal untuk melihat detail tim

### View (`v_lab.php`)

1. **Form Input**:

   - Field name diubah: `kode_jenis`, `kode_alat`, `kode_parameter`, `nama_layanan`, `satuan`, `biaya`, `diskon`
   - **Section baru**: "TIM PENANGGUNG JAWAB" dengan checkbox list untuk memilih multiple users
   - Checkbox list di-generate dari API `getoptions()`

2. **Table Kolom**:

   - Kolom "Penanggung Jawab" sekarang menampilkan button:
     ```html
     <button class="btn btn-sm btn-outline-primary btn-lihat-tim" data-id="...">
       <i class="bi bi-eye"></i> Lihat (n)
     </button>
     ```
   - `n` = jumlah anggota tim

3. **Modal Baru**: `modalTim`

   - Menampilkan daftar anggota tim dalam bentuk tabel
   - Kolom: No, Nama, Role
   - Di-trigger saat button "Lihat" diklik

4. **JavaScript**:
   - Event listener untuk `.btn-lihat-tim`
   - Function `lihatTim(id)`: Fetch data tim via AJAX dan populate modal
   - Function `loadOptions()`: Generate checkbox list untuk tim penanggung jawab

## Cara Menggunakan

### 1. Menjalankan Migration

```bash
php spark migrate
```

### 2. Menambah/Edit Layanan Lab

1. Klik tombol "Tambah" atau "Edit" pada layanan
2. Isi data layanan (Jenis, Alat, Parameter, Nama, Biaya, Satuan, Diskon)
3. **Pilih Tim Penanggung Jawab**: Centang checkbox untuk memilih 1 atau lebih user
4. Klik "Simpan"

### 3. Melihat Tim Penanggung Jawab

1. Pada tabel data layanan, klik button **"Lihat (n)"** di kolom "Penanggung Jawab"
2. Modal akan muncul menampilkan daftar anggota tim beserta role mereka

## Catatan Penting

- **Data Lama**: Jika sudah ada data dengan kolom `ujiPenyelia` dan `ujiManajerTeknis`, data tersebut akan hilang setelah migration. Harap backup terlebih dahulu jika diperlukan migrasi manual ke tabel `r_tim`.
- **Role User**: Saat ini sistem mengambil user dengan `role_id = 6` (Penyelia). Jika ingin menampilkan role lain (misal Manajer Teknis role_id=4), ubah query di method `getoptions()` dan `edit()`:

  ```php
  'users' => $accModel->getWhere(['role_id' => [4, 6]])->getResult()
  ```

  Atau gunakan `whereIn`:

  ```php
  $accModel->builder->whereIn('role_id', [4, 6]);
  $users = $accModel->builder->get()->getResult();
  ```

- **Validasi**: Tidak ada validasi minimum jumlah anggota tim. Bisa 0 atau lebih.

## File yang Diubah/Dibuat

1. ✅ `appengines/app/Database/Migrations/2025-09-14-055500_SimlabRLayananPengujian.php` (diubah)
2. ✅ `appengines/app/Database/Migrations/2025-11-11-000002_AlterRLayananPengujianRemoveOldColumns.php` (baru)
3. ✅ `appengines/app/Modules/Lab/Controllers/Lab.php` (diubah)
4. ✅ `appengines/app/Modules/Lab/Views/v_lab.php` (diubah)

---

**Update Date**: 2025-11-11  
**Developer**: NetGen Inovasi Digital

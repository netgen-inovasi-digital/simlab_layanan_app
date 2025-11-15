# Update Keranjang Admin - Dropdown Metode Uji

## Tanggal: 15 November 2025 (Update #2)

## Perubahan Tambahan: Implementasi Dropdown Metode Uji

Setelah perubahan pertama, kini ditambahkan **dropdown Metode Uji** yang sama persis dengan implementasi di modul Pelayanan.

---

## 🔄 File yang Dimodifikasi:

### 1. **Controller** - `KeranjangAdmin\Controllers\Keranjang.php`

#### a) Method `keranjangDataListLayanan()` (Line ~330-370)

**DITAMBAHKAN:**

```php
// Get metode list untuk dropdown
$modelMetode = new MyModel('r_metode');
$metodeList = $modelMetode->getAllData();

// ...dalam foreach loop...

// Dropdown Metode Uji (menggantikan input keterangan)
$selectMetode = '<select class="form-select form-select-sm metode-select" required>';
$selectMetode .= '<option value="">-- Pilih Metode --</option>';
foreach ($metodeList as $metode) {
    $selectMetode .= '<option value="' . esc($metode->metode_kode) . '">' . esc($metode->nama) . '</option>';
}
$selectMetode .= '</select>';
$response[] = $selectMetode;
```

**DIHAPUS:**

```php
// Input Keterangan
$response[] = '<input type="text" class="form-control form-control-sm keterangan" placeholder="Keterangan...">';
```

#### b) Method `buildRowData()` (Line ~29-75)

**DIUBAH** untuk menampilkan nama metode di preview keranjang:

```php
protected function buildRowData(array $row, int $idx): array
{
    // ... existing code ...

    $metodeKode  = $row['metode_kode'] ?? null;  // BARU

    // ... existing code ...

    // Jumlah
    $response[] = $jumlah;

    // Metode Uji (BARU - menggantikan Keterangan)
    $metodeNama = '-';
    if ($metodeKode) {
        $modelMetode = new MyModel('r_metode');
        $metode = $modelMetode->getDataById('metode_kode', $metodeKode);
        if ($metode && isset($metode->nama)) {
            $metodeNama = esc($metode->nama);
        }
    }
    $response[] = $metodeNama;

    // ... existing code ...
}
```

#### c) Method `processItemData()` (Line ~88-125)

**DIUBAH** untuk menerima dan memproses metode:

```php
protected function processItemData(array $post): array
{
    // ... existing code ...

    $detMetode     = $post['detMetode']    ?? null;  // BARU

    // ... existing code ...

    return [
        'kode'        => $detUjiKode,
        'layanan'     => $detParameter ?? 'Layanan',
        'alat'        => $detAlat ?? '',
        'biaya_asli'  => $biayaPerItem,
        'diskon'      => $appliedDiskon,
        'jumlah'      => $jumlah,
        'metode_kode' => $detMetode,  // BARU
        'biaya'       => $biayaTotalBaru,
    ];
}
```

**DIHAPUS:**

```php
'keterangan'  => $detKeterangan,
```

#### d) Method `findAndUpdateExistingItem()` (Line ~131-150)

**DITAMBAHKAN** validasi metode untuk cek duplikat:

```php
protected function findAndUpdateExistingItem(array &$keranjang, array $itemData): bool
{
    foreach ($keranjang as $idx => $item) {
        $sameKode = isset($item['kode']) && (string)$item['kode'] === (string)$itemData['kode'];
        $sameAlat = (isset($item['alat']) ? trim((string)$item['alat']) : '') === trim((string)$itemData['alat']);
        $sameMetode = (isset($item['metode_kode']) ? (int)$item['metode_kode'] : null) === (isset($itemData['metode_kode']) ? (int)$itemData['metode_kode'] : null);  // BARU

        if ($sameKode && $sameAlat && $sameMetode) {  // BARU: tambah && $sameMetode
            // ... update logic ...
            $keranjang[$idx]['metode_kode'] = $itemData['metode_kode'];  // BARU
        }
    }
}
```

---

### 2. **View** - `KeranjangAdmin\Views\v_keranjang.php`

#### a) JavaScript Event Handler - Tombol Masukkan (Line ~670-730)

**DITAMBAHKAN** validasi dan pengambilan nilai metode:

```javascript
document.addEventListener("click", function (e) {
  if (e.target.closest(".btnMasukkan")) {
    let btn = e.target.closest(".btnMasukkan");
    let tr = btn.closest("tr");

    // ... existing code ...

    // BARU: Ambil nilai metode dari dropdown
    let metodeSelect = tr.querySelector(".metode-select");
    let metodeValue = metodeSelect ? metodeSelect.value : "";

    let data = {
      detUjiKode: btn.dataset.kode,
      detAlat: btn.dataset.alat,
      detBiaya: biaya,
      detParameter: btn.dataset.parameter,
      detDiskon: diskon,
      detJumlah: jumlah,
      detMetode: metodeValue, // BARU
      detTotal: total,
    };

    // ... existing validations ...

    // BARU: Validasi metode wajib dipilih
    if (!data.detMetode) {
      sayAlert("errorModal", "Gagal", "Metode Uji harus dipilih.", "warning");
      return;
    }

    // ... submit logic ...
  }
});
```

#### b) Reset Form Setelah Submit Sukses (Line ~730-750)

**DIUBAH** untuk reset dropdown metode:

```javascript
if (res.res === true) {
  // ... existing code ...

  if (jumlahInput) jumlahInput.value = 1;
  if (metodeSelect) metodeSelect.value = ""; // BARU: reset dropdown metode

  // ... existing code ...
}
```

**DIHAPUS:**

```javascript
if (tr.querySelector(".keterangan")) tr.querySelector(".keterangan").value = "";
```

---

## 📊 Ringkasan Perubahan:

| Aspek                 | Sebelum                 | Sesudah                  |
| --------------------- | ----------------------- | ------------------------ |
| **Input Metode**      | Input text (keterangan) | Dropdown (select metode) |
| **Validasi**          | Tidak wajib             | Wajib dipilih            |
| **Data di Session**   | `keterangan` (string)   | `metode_kode` (integer)  |
| **Preview Keranjang** | Tidak ada               | Menampilkan nama metode  |
| **Cek Duplikat**      | Kode + Alat             | Kode + Alat + Metode     |

---

## 🎯 Fitur Baru yang Ditambahkan:

1. ✅ **Dropdown Metode Uji** pada tabel pilih layanan
2. ✅ **Validasi wajib** pilih metode sebelum masukkan keranjang
3. ✅ **Tampilan nama metode** di preview keranjang
4. ✅ **Deteksi duplikat** berdasarkan kombinasi kode + alat + metode
5. ✅ **Reset dropdown** setelah item berhasil ditambahkan
6. ✅ **Integrasi dengan tabel `r_metode`** untuk data metode

---

## ✅ Testing Checklist:

- [ ] Dropdown metode muncul di tabel pilih layanan
- [ ] Dropdown terisi dengan data dari tabel `r_metode`
- [ ] Validasi muncul jika metode belum dipilih
- [ ] Item berhasil masuk keranjang dengan metode yang dipilih
- [ ] Preview keranjang menampilkan nama metode (bukan kode)
- [ ] Item dengan metode berbeda tidak digabung (treated as different)
- [ ] Item dengan metode sama digabung (jumlah bertambah)
- [ ] Dropdown ter-reset setelah item berhasil ditambahkan
- [ ] Identitas sampel tetap berfungsi normal
- [ ] Checkout berhasil dengan data lengkap (termasuk metode)

---

## ⚠️ Catatan Backend:

### Database

Pastikan tabel yang relevan memiliki kolom untuk menyimpan `metode_kode`:

```sql
-- Contoh struktur kolom yang dibutuhkan
ALTER TABLE simlab_layanan_detail
ADD COLUMN metode_pengujian INT DEFAULT NULL,
ADD CONSTRAINT fk_metode
    FOREIGN KEY (metode_pengujian)
    REFERENCES r_metode(metode_kode);
```

### Referensi Tabel

- **r_metode**: Tabel master metode pengujian
  - `metode_kode` (PK, INT)
  - `nama` (VARCHAR) - Nama metode
  - `keterangan` (TEXT) - Deskripsi metode

---

## 🔗 Konsistensi dengan Pelayanan:

Implementasi ini kini **100% konsisten** dengan modul Keranjang Pelayanan:

- ✅ Dropdown metode yang sama
- ✅ Validasi yang sama
- ✅ Struktur data yang sama
- ✅ Flow checkout yang sama
- ⭐ **Plus**: Form identitas sampel (bonus untuk admin)
- ⭐ **Plus**: Select pelanggan (khusus admin)

---

**Status**: ✅ **SELESAI** - Keranjang admin kini sama persis dengan pelayanan + fitur tambahan admin

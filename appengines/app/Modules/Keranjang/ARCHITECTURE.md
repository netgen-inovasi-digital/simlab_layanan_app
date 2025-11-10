# Keranjang Module - Inheritance Architecture

## 📁 Struktur File

```
app/Modules/Keranjang/
├── Config/
│   └── LayananConfig.php          ← Konfigurasi untuk semua jenis layanan
├── Controllers/
│   ├── KeranjangBase.php          ← Base class (abstract)
│   ├── Keranjang.php              ← Controller untuk PENGUJIAN (extends Base)
│   ├── KeranjangSewa.php          ← Controller untuk SEWA ALAT (extends Base)
│   └── KeranjangKonsultasi.php    ← (Future) Controller untuk KONSULTASI
├── Views/
│   └── v_keranjang.php            ← Modal view (shared)
└── Routes.php                     ← Routing dengan dukungan multi-service type
```

---

## 🏗️ Arsitektur Inheritance

### Hierarki Class

```
BaseController (CI4)
    ↓
KeranjangBase (abstract)
    ↓
    ├── Keranjang (pengujian)
    ├── KeranjangSewa (sewa)
    └── KeranjangKonsultasi (konsultasi)
```

---

## 📋 LayananConfig.php

**Lokasi:** `app/Modules/Keranjang/Config/LayananConfig.php`

**Fungsi:**

-   Central configuration untuk semua jenis layanan
-   Definisi table names, column mappings, joins, validation rules
-   Config-driven approach untuk fleksibilitas

**Jenis Layanan yang Didefinisikan:**

### 1. **pengujian** (Testing Services)

```php
'table_pengujian' => 'r_layanan_pengujian'
'columns' => [
    'kode', 'nama_layanan', 'alat', 'parameter',
    'jenis', 'satuan', 'biaya', 'diskon'
]
'joins' => [
    simlab_r_parameter, simlab_r_alat, simlab_r_jenis
]
```

### 2. **sewa** (Equipment Rental)

```php
'table_pengujian' => 'r_layanan_sewa_alat'
'columns' => [
    'kode', 'nama_alat', 'kategori', 'biaya_per_hari',
    'diskon', 'durasi', 'tanggal_mulai', 'tanggal_selesai',
    'stok_tersedia'
]
'joins' => [
    simlab_r_alat, simlab_r_kategori_alat
]
```

### 3. **konsultasi** (Consultation Services)

```php
'table_pengujian' => 'r_layanan_konsultasi'
'columns' => [
    'kode', 'topik_konsultasi', 'konsultan',
    'biaya_per_jam', 'durasi_jam', 'tanggal_konsultasi'
]
```

---

## 🔧 KeranjangBase.php (Abstract Base Class)

**Lokasi:** `app/Modules/Keranjang/Controllers/KeranjangBase.php`

### Properties

```php
protected $jenisLayanan;      // 'pengujian', 'sewa', 'konsultasi'
protected $config;            // Config dari LayananConfig
protected $encrypter;
protected $sessionKey;        // 'keranjang_pengujian', 'keranjang_sewa', dll

// Table names (dari config)
protected $tableLayanan;
protected $tableLayananDetail;
protected $tablePengujian;
protected $tablePembayaran;
```

### Common Methods (Concrete)

```php
✅ __construct()                      - Load config berdasarkan $jenisLayanan
✅ loadConfig()                       - Load dari LayananConfig::getConfig()
✅ index()                            - Tampilkan modal keranjang
✅ getCategories()                    - Get kategori layanan
✅ checkVerified()                    - Cek status verifikasi user
✅ keranjangDataList()                - Get items dari session keranjang
✅ cleanDuplicates()                  - Hapus duplikat items
✅ keranjangSubmit()                  - Submit item ke keranjang
✅ validateSubmitData()               - Validasi input (bisa di-override)
✅ keranjangCheckout()                - Proses checkout
✅ saveMainLayanan()                  - Simpan data layanan utama
✅ savePembayaran()                   - Simpan data pembayaran
✅ keranjangDelete()                  - Hapus item dari keranjang
✅ kategoriList()                     - Get kategori list
✅ aksiKeranjang()                    - Generate action button HTML
✅ getUserDiscount()                  - Calculate user discount (ULM)
```

### Abstract Methods (Harus di-implement oleh child)

```php
❌ abstract buildRowData(array $row, int $idx): array
   → Build data row untuk display di tabel keranjang

❌ abstract processItemData(array $post): array
   → Process POST data untuk create item

❌ abstract findAndUpdateExistingItem(array &$keranjang, array $itemData): bool
   → Cari dan update item yang sudah ada di keranjang

❌ abstract saveDetailLayanan(int $lnKode, array $keranjang): void
   → Simpan detail layanan ke database (custom per service type)

❌ abstract keranjangDataListLayanan()
   → Get available layanan untuk ditampilkan di modal (custom query)
```

---

## 🎯 Keranjang.php (Pengujian Service)

**Lokasi:** `app/Modules/Keranjang/Controllers/Keranjang.php`

**Extends:** `KeranjangBase`

**Jenis Layanan:** `pengujian`

### Constructor

```php
public function __construct()
{
    $this->jenisLayanan = 'pengujian';
    parent::__construct();
}
```

### Implemented Abstract Methods

#### 1. buildRowData()

Menampilkan kolom:

-   Parameter
-   Instrumen/Alat
-   Diskon
-   Biaya satuan
-   Jumlah
-   Keterangan
-   Aksi (hapus button)

#### 2. processItemData()

Process data:

-   `detUjiKode`, `detParameter`, `detAlat`
-   `detBiaya`, `detDiskon`, `detJumlah`
-   `detKeterangan`
-   Hitung biaya total dengan diskon

#### 3. findAndUpdateExistingItem()

Logic:

-   Match berdasarkan `kode` + `alat`
-   Update `jumlah` (tambah)
-   Update `keterangan` (replace)
-   Recalculate `biaya`

#### 4. saveDetailLayanan()

Insert ke `t_layanan_detil`:

```php
[
    'kode_layanan' => $lnKode,
    'uji_kode' => $item['kode'],
    'biaya' => $item['biaya'],
    'jumlah' => $item['jumlah'],
    'catatan_pelanggan' => $item['keterangan'],
    'nama_layanan' => $item['layanan'],
    'status_layanan' => 0,
    'kode_jenis' => $jenKodeValue
]
```

#### 5. keranjangDataListLayanan()

Query:

-   Table: `r_layanan_pengujian`
-   Joins: `simlab_r_parameter`, `simlab_r_alat`, `simlab_r_jenis`
-   Filter: kategori (`jenKode`), search query
-   Display: Parameter, Alat, Biaya, Input Jumlah, Input Keterangan, Button

---

## 🏢 KeranjangSewa.php (Sewa Alat Service)

**Lokasi:** `app/Modules/Keranjang/Controllers/KeranjangSewa.php`

**Extends:** `KeranjangBase`

**Jenis Layanan:** `sewa`

### Constructor

```php
public function __construct()
{
    $this->jenisLayanan = 'sewa';
    parent::__construct();
}
```

### Implemented Abstract Methods

#### 1. buildRowData()

Menampilkan kolom:

-   Nama Alat
-   Kategori
-   Durasi (hari)
-   Tanggal Mulai
-   Diskon
-   Biaya per hari
-   Jumlah Unit
-   Keterangan
-   Aksi

#### 2. processItemData()

Process data:

-   `kode_alat`, `nama_alat`, `kategori`
-   `biaya_per_hari`, `diskon`, `jumlah`
-   `durasi`, `tanggal_mulai`
-   `keterangan`
-   Hitung: `biaya = (biaya_per_hari * durasi * jumlah) dengan diskon`

#### 3. validateSubmitData() ✨ OVERRIDE

Custom validation:

-   Validasi `durasi` (1-365 hari)
-   Validasi `tanggal_mulai` (required)
-   Validasi `stok_tersedia`

#### 4. findAndUpdateExistingItem()

Logic:

-   Match berdasarkan `kode_alat` + `tanggal_mulai`
-   Update `jumlah` DAN `durasi` (tambah keduanya)
-   Recalculate biaya

#### 5. saveDetailLayanan()

Insert ke `t_sewa_detil`:

```php
[
    'kode_layanan' => $lnKode,
    'kode_alat' => $item['kode_alat'],
    'nama_alat' => $item['nama_alat'],
    'kategori' => $item['kategori'],
    'biaya_per_hari' => $item['biaya_per_hari'],
    'diskon' => $item['diskon'],
    'jumlah' => $item['jumlah'],
    'durasi' => $durasi,
    'tanggal_mulai' => $tanggalMulai,
    'tanggal_selesai' => $tanggalSelesai, // auto-calculated
    'catatan_pelanggan' => $item['keterangan'],
    'biaya' => $item['biaya'],
    'status_layanan' => 0
]
```

#### 6. keranjangDataListLayanan()

Query:

-   Table: `r_layanan_sewa_alat`
-   Joins: `simlab_r_alat`, `simlab_r_kategori_alat`
-   Filter: kategori, search query, `stok_tersedia > 0`
-   Display: Nama Alat, Kategori, Stok, Biaya/hari, Input Jumlah, Input Durasi, Input Tanggal, Keterangan, Button

---

## 🌐 Routes.php

**Lokasi:** `app/Modules/Keranjang/Routes.php`

### Routing Structure

```php
/keranjang                          → Keranjang::index() (pengujian - default)
/keranjang/dataListLayanan          → Keranjang::keranjangDataListLayanan()
/keranjang/submit                   → Keranjang::keranjangSubmit()
/keranjang/checkout                 → Keranjang::keranjangCheckout()
/keranjang/delete/{id}              → Keranjang::keranjangDelete()

/keranjang/sewa                     → KeranjangSewa::index()
/keranjang/sewa/dataListLayanan     → KeranjangSewa::keranjangDataListLayanan()
/keranjang/sewa/submit              → KeranjangSewa::keranjangSubmit()
/keranjang/sewa/checkout            → KeranjangSewa::keranjangCheckout()
/keranjang/sewa/delete/{id}         → KeranjangSewa::keranjangDelete()

/keranjang/konsultasi               → KeranjangKonsultasi::index() (future)
/keranjang/konsultasi/submit        → KeranjangKonsultasi::keranjangSubmit()
```

### Backward Compatibility ✅

Routes tanpa prefix (e.g., `/keranjang/submit`) tetap menggunakan `Keranjang` controller (pengujian), menjaga backward compatibility dengan kode yang sudah ada.

---

## 🔄 Data Flow

### 1. User Memilih Layanan

```
User klik "Tambah ke Keranjang"
    ↓
JavaScript collect data dari form
    ↓
AJAX POST ke /keranjang/{type}/submit
    ↓
KeranjangBase::keranjangSubmit()
    ↓
Child::processItemData() (abstract - custom per service)
    ↓
Session keranjang_{type} updated
    ↓
Response JSON success
```

### 2. User Checkout

```
User klik "Checkout"
    ↓
AJAX POST ke /keranjang/{type}/checkout
    ↓
KeranjangBase::keranjangCheckout()
    ↓
DB Transaction Start
    ↓
KeranjangBase::saveMainLayanan() (common)
    ↓
KeranjangBase::savePembayaran() (common)
    ↓
Child::saveDetailLayanan() (abstract - custom per service)
    ↓
DB Transaction Commit
    ↓
Session keranjang_{type} cleared
    ↓
Response JSON success
```

---

## 🎨 Keuntungan Inheritance Pattern

### ✅ Advantages

1. **Code Reusability**

    - Logic umum (submit, checkout, delete, validation) hanya ditulis 1x di Base
    - Setiap child hanya implement logic yang spesifik

2. **Maintainability**

    - Update logic umum di Base → otomatis apply ke semua children
    - Bug fix di Base → fix untuk semua service types

3. **Extensibility**

    - Tambah service type baru: create new child class + update config
    - Tidak perlu modify existing code (Open/Closed Principle)

4. **Type Safety**

    - Abstract methods force implementation
    - PHP akan error jika child tidak implement required methods

5. **Separation of Concerns**
    - Config di LayananConfig.php
    - Common logic di KeranjangBase
    - Specific logic di child classes

### ❌ Trade-offs

1. **Complexity**

    - Developer perlu memahami inheritance hierarchy
    - Abstract methods harus di-implement dengan benar

2. **Tight Coupling**
    - Child classes tightly coupled dengan Base
    - Changes di Base signature bisa break children

---

## 📦 Session Storage Structure

### Pengujian

```php
Session key: 'keranjang_pengujian'
[
    [
        'kode' => 'UJI001',
        'layanan' => 'Parameter XYZ',
        'alat' => 'Instrumen ABC',
        'biaya_asli' => 100000,
        'diskon' => 10,
        'jumlah' => 2,
        'keterangan' => 'Sample testing',
        'biaya' => 180000 // (100000 * 0.9) * 2
    ],
    ...
]
```

### Sewa

```php
Session key: 'keranjang_sewa'
[
    [
        'kode_alat' => 'ALT001',
        'nama_alat' => 'Mikroskop Digital',
        'kategori' => 'Optik',
        'biaya_per_hari' => 50000,
        'diskon' => 5,
        'jumlah' => 1,
        'durasi' => 7,
        'tanggal_mulai' => '2025-11-15',
        'keterangan' => 'Untuk penelitian',
        'biaya' => 332500 // (50000 * 0.95) * 7 * 1
    ],
    ...
]
```

---

## 🚀 Cara Menambah Service Type Baru

### Example: Menambahkan "Kalibrasi"

#### 1. Update LayananConfig.php

```php
public static function getConfig(string $jenisLayanan): array
{
    $configs = [
        'pengujian' => [...],
        'sewa' => [...],
        'kalibrasi' => [ // ← NEW
            'title' => 'Keranjang Kalibrasi',
            'session_key' => 'keranjang_kalibrasi',
            'table_layanan' => 'simlab_t_layanan',
            'table_detail' => 't_kalibrasi_detil',
            'table_pengujian' => 'r_layanan_kalibrasi',
            'table_pembayaran' => 't_pembayaran',
            'columns' => [
                'kode' => 'kode',
                'nama_alat' => 'nama_alat',
                'biaya' => 'biaya_kalibrasi',
                // ... dst
            ],
            'joins' => [...],
            'modal_fields' => [...],
            'validation' => [...]
        ]
    ];
    // ...
}
```

#### 2. Create KeranjangKalibrasi.php

```php
<?php

namespace Modules\Keranjang\Controllers;

use App\Models\MyModel;

class KeranjangKalibrasi extends KeranjangBase
{
    public function __construct()
    {
        $this->jenisLayanan = 'kalibrasi';
        parent::__construct();
    }

    protected function buildRowData(array $row, int $idx): array
    {
        // Custom display logic untuk kalibrasi
    }

    protected function processItemData(array $post): array
    {
        // Custom processing untuk kalibrasi
    }

    protected function findAndUpdateExistingItem(array &$keranjang, array $itemData): bool
    {
        // Custom update logic
    }

    protected function saveDetailLayanan(int $lnKode, array $keranjang): void
    {
        // Custom save to t_kalibrasi_detil
    }

    public function keranjangDataListLayanan()
    {
        // Custom query untuk available kalibrasi services
    }
}
```

#### 3. Update Routes.php

```php
$subroutes->group('kalibrasi', function ($kalibRoutes) {
    $kalibRoutes->get('/', 'KeranjangKalibrasi::index');
    $kalibRoutes->get('datalist', 'KeranjangKalibrasi::keranjangDataList');
    $kalibRoutes->get('dataListLayanan', 'KeranjangKalibrasi::keranjangDataListLayanan');
    $kalibRoutes->post('submit', 'KeranjangKalibrasi::keranjangSubmit');
    $kalibRoutes->get('delete/(:any)', 'KeranjangKalibrasi::keranjangDelete/$1');
    $kalibRoutes->post('checkout', 'KeranjangKalibrasi::keranjangCheckout');
    $kalibRoutes->get('checkVerified', 'KeranjangKalibrasi::checkVerified');
});
```

#### 4. Done! ✅

Routes tersedia:

-   `/keranjang/kalibrasi`
-   `/keranjang/kalibrasi/submit`
-   `/keranjang/kalibrasi/checkout`

---

## 📝 Testing Checklist

### Unit Tests untuk KeranjangBase

-   [ ] `loadConfig()` loads correct config
-   [ ] `cleanDuplicates()` removes duplicate items
-   [ ] `getUserDiscount()` returns correct discount for ULM users
-   [ ] `getUserDiscount()` returns 0 for non-ULM users
-   [ ] `aksiKeranjang()` generates correct HTML
-   [ ] `checkVerified()` returns correct verification status

### Integration Tests untuk Keranjang (Pengujian)

-   [ ] `keranjangSubmit()` adds new item to cart
-   [ ] `keranjangSubmit()` updates existing item quantity
-   [ ] `keranjangDataList()` returns correct cart items
-   [ ] `keranjangDelete()` removes item from cart
-   [ ] `keranjangCheckout()` creates layanan, pembayaran, and detail records
-   [ ] `keranjangDataListLayanan()` filters by kategori correctly

### Integration Tests untuk KeranjangSewa

-   [ ] `keranjangSubmit()` validates durasi range (1-365)
-   [ ] `keranjangSubmit()` validates tanggal_mulai required
-   [ ] `findAndUpdateExistingItem()` matches kode_alat + tanggal_mulai
-   [ ] `saveDetailLayanan()` calculates tanggal_selesai correctly
-   [ ] `keranjangDataListLayanan()` only shows stok_tersedia > 0

---

## 🔧 Troubleshooting

### Error: "Property $jenisLayanan harus di-set di child class"

**Cause:** Child class tidak set `$this->jenisLayanan` sebelum call `parent::__construct()`

**Fix:**

```php
public function __construct()
{
    $this->jenisLayanan = 'pengujian'; // ← WAJIB
    parent::__construct();
}
```

### Error: "Class must implement abstract methods"

**Cause:** Child class belum implement semua abstract methods dari Base

**Fix:** Implement:

-   `buildRowData()`
-   `processItemData()`
-   `findAndUpdateExistingItem()`
-   `saveDetailLayanan()`
-   `keranjangDataListLayanan()`

### Keranjang kosong setelah checkout

**Cause:** Session key berbeda antara submit dan checkout

**Fix:** Pastikan `$this->sessionKey` di Base sama dengan yang digunakan di child

---

## 📚 References

-   **Design Pattern:** Inheritance-Based Template Method Pattern
-   **Config-Driven:** LayananConfig.php
-   **Framework:** CodeIgniter 4
-   **Database:** MySQL/MariaDB

---

**Last Updated:** November 10, 2025
**Version:** 1.0.0
**Maintainer:** Development Team

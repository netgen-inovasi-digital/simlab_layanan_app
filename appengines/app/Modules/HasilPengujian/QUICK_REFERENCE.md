# QUICK REFERENCE - HasilPengujian Module

## Arsitektur Baru (Setelah Refactoring)

```
Controller (HasilPengujian.php)
    ↓ menggunakan
Model Module (HasilPengujianModel.php) - Business logic spesifik
    ↓ menggunakan
Model Global (MyModel.php) - Generic CRUD operations
```

---

## Cara Menggunakan Model di Controller

### ❌ JANGAN LAKUKAN INI (Anti-pattern):

```php
public function someMethod() {
    $db = \Config\Database::connect();  // ❌ JANGAN!
    $builder = $db->table('t_layanan_detil');
    $result = $builder->where('user_id', $userId)->get()->getResult();
    // ...
}
```

### ✅ LAKUKAN INI (Best practice):

```php
public function someMethod() {
    // Gunakan method dari HasilPengujianModel
    $result = $this->hasilPengujianModel->getLayananKodesByUserId($userId);

    // Atau untuk operasi generic, gunakan MyModel
    $model = new MyModel('nama_tabel');
    $data = $model->getDataById('field', $value);
}
```

---

## Available Methods

### HasilPengujianModel Methods

#### Query Methods

```php
// Get layanan codes for user
$lnKodeList = $this->hasilPengujianModel->getLayananKodesByUserId($userId);

// Get layanan list with filters
$list = $this->hasilPengujianModel->getLayananListForPenyelia(
    $lnKodeList,
    $userId,
    $statusFilter,
    $wantReject,
    $wantUploaded
);

// Check authorization
$isAuthorized = $this->hasilPengujianModel->isUserAuthorizedForLayanan($lnKode, $userId);

// Get detail list
$details = $this->hasilPengujianModel->getDetailListForPenyelia($kode, $userId);

// Get file info
$fileInfo = $this->hasilPengujianModel->getFileLhus($detailKode);

// Get layanan status
$status = $this->hasilPengujianModel->getLayananStatus($lnKode);
```

#### Update Methods

```php
// Update files status
$this->hasilPengujianModel->updateFilesStatusForUser($lnKode, $userId, $fromStatus, $toStatus);

// Update t_files_lhus status
$this->hasilPengujianModel->updateFilesLhusStatusForUser($lnKode, $userId, $fromStatus, $toStatus);

// Update terima_layanan_by
$this->hasilPengujianModel->updateTerimaLayananBy($lnKode, $userId, $acceptedBy);

// Update layanan status
$this->hasilPengujianModel->updateLayananStatus($lnKode, $status);

// Update detail files status
$this->hasilPengujianModel->updateDetailFilesStatus($detailKode, $status);

// Save file LHUS
$this->hasilPengujianModel->saveFileLhus($detailKode, $lnKode, $filename, $userId, $status);
```

#### Check Methods

```php
// Check missing files
$missingData = $this->hasilPengujianModel->checkMissingFilesForUser($lnKode, $userId);
// Returns: ['missingCount' => int, 'missingItems' => array]

// Count missing files
$count = $this->hasilPengujianModel->countMissingFilesForLayanan($lnKode);

// Has rejected LHUS?
$hasRejected = $this->hasilPengujianModel->hasRejectedLhus($lnKode, $userId);

// Has uploaded LHUS?
$hasUploaded = $this->hasilPengujianModel->hasUploadedLhus($lnKode, $userId);

// All LHUS accepted?
$allAccepted = $this->hasilPengujianModel->allUserLhusAccepted($lnKode, $userId);

// Has sent LHUS?
$hasSent = $this->hasilPengujianModel->hasSentLhus($lnKode, $userId);
```

#### Transaction Methods

```php
$this->hasilPengujianModel->transBegin();
// ... database operations
if ($this->hasilPengujianModel->transStatus() === false) {
    $this->hasilPengujianModel->transRollback();
} else {
    $this->hasilPengujianModel->transCommit();
}
```

### MyModel Methods (Generic)

```php
$model = new MyModel('table_name');

// Get single record
$row = $model->getDataById('field_name', $value);

// Get all data
$rows = $model->getAllData('order_field', 'ASC');

// Get with where conditions
$rows = $model->getAllDataById(['field1' => 'value1'], ['order' => 'ASC']);

// Get with join
$rows = $model->getAllDataByJoin(
    ['other_table' => 'table.id = other_table.table_id'],
    ['field' => 'value']
);

// Count records
$count = $model->getCountAll('field', 'value');

// Insert
$lastId = $model->insertData($data, true); // true = return last insert ID

// Update
$model->updateData($data, 'field', 'value');

// Delete
$model->deleteData('field', 'value');
```

---

## Kapan Menggunakan Model Module vs Model Global?

### Gunakan HasilPengujianModel jika:

- ✅ Query melibatkan multiple tables dengan logic kompleks
- ✅ Business logic spesifik untuk HasilPengujian module
- ✅ Query dengan aggregation atau subquery kompleks
- ✅ Logic yang hanya digunakan di module ini

**Contoh:**

- Get layanan list dengan filter dan aggregation status user
- Check authorization dengan r_tim join
- Update dengan complex WHERE conditions

### Gunakan MyModel jika:

- ✅ Simple CRUD operations
- ✅ Query generic yang bisa dipakai di banyak tempat
- ✅ Single table operations
- ✅ Standard operations (get by ID, get all, insert, update, delete)

**Contoh:**

- Get user by ID: `$modelUser->getDataById('user_id', $userId)`
- Get sample data: `$modelSample->getDataById('kode_layanan', $lnKode)`
- Insert/Update simple records

---

## Pattern untuk Menambah Fitur Baru

### 1. Jika perlu query database baru:

**Step 1:** Tambahkan method di HasilPengujianModel

```php
// HasilPengujianModel.php
public function getNewData($param): array
{
    return $this->db->table('table_name')
        ->where('field', $param)
        ->get()
        ->getResult();
}
```

**Step 2:** Gunakan di Controller

```php
// HasilPengujian.php
public function newMethod()
{
    $data = $this->hasilPengujianModel->getNewData($param);
    // process data...
}
```

### 2. Jika logic kompleks, buat helper method di controller:

```php
// HasilPengujian.php
private function processComplexLogic($data): array
{
    // Complex processing here
    return $result;
}

public function mainMethod()
{
    $data = $this->hasilPengujianModel->getData();
    $result = $this->processComplexLogic($data);
    return $this->response->setJSON($result);
}
```

### 3. Jika perlu transaction:

```php
public function saveWithTransaction()
{
    try {
        $this->hasilPengujianModel->transBegin();

        // Operation 1
        $this->hasilPengujianModel->updateSomething($data1);

        // Operation 2
        $this->hasilPengujianModel->updateSomethingElse($data2);

        if ($this->hasilPengujianModel->transStatus() === false) {
            $this->hasilPengujianModel->transRollback();
            return $this->jsonResponse('error', 'Transaction failed');
        }

        $this->hasilPengujianModel->transCommit();
        return $this->jsonResponse(true, 'Success');

    } catch (\Throwable $e) {
        $this->hasilPengujianModel->transRollback();
        return $this->jsonResponse('error', $e->getMessage());
    }
}
```

---

## Naming Convention

### Method Names

- `get*()` - Untuk mengambil data (SELECT)
- `update*()` - Untuk update data (UPDATE)
- `save*()` - Untuk insert/update data (INSERT/UPDATE)
- `delete*()` - Untuk hapus data (DELETE)
- `check*()` - Untuk pengecekan boolean
- `has*()` - Untuk pengecekan existence (return bool)
- `count*()` - Untuk menghitung records (return int)
- `format*()` - Untuk formatting data (return string/array)
- `process*()` - Untuk business logic processing

### Variable Names

- `$userId` - User ID (camelCase)
- `$lnKode` - Layanan code
- `$detKode` - Detail code
- `$model` - Instance of model
- `$data` - Array of data
- `$row` - Single database row (object)
- `$rows` - Multiple database rows (array)

---

## Testing Checklist untuk Developer

Setelah membuat perubahan, pastikan:

- [ ] Tidak ada `$db = \Config\Database::connect()` di controller
- [ ] Semua query ada di model
- [ ] Method names jelas dan descriptive
- [ ] Ada PHPDoc comment di setiap method
- [ ] Error handling proper (try-catch)
- [ ] Transaction rollback jika ada error
- [ ] Return type declaration ada jika perlu
- [ ] Parameter type hints ada
- [ ] Method tidak lebih dari 50 baris (ideally)

---

## Troubleshooting

### Error: "Call to undefined method"

**Penyebab:** Method belum dibuat di model
**Solusi:** Tambahkan method di HasilPengujianModel atau gunakan MyModel

### Error: "Too few arguments to function"

**Penyebab:** Parameter kurang saat memanggil method
**Solusi:** Cek signature method dan pastikan semua required parameter dikirim

### Error: "Cannot redeclare"

**Penyebab:** Method name sudah ada
**Solusi:** Gunakan nama method yang berbeda atau refactor yang sudah ada

### Data tidak muncul

**Penyebab:** Query di model salah atau authorization failed
**Solusi:**

1. Check method return value
2. Check query SQL di model
3. Check authorization logic

---

## Contact & Support

Jika ada pertanyaan atau butuh bantuan:

1. Check dokumentasi ini dulu
2. Check REFACTORING_SUMMARY.md untuk detail lengkap
3. Review code di HasilPengujianModel.php untuk contoh implementasi
4. Tanyakan ke team lead

---

**Last Updated:** 23 November 2025
**Version:** 2.0 (After Refactoring)

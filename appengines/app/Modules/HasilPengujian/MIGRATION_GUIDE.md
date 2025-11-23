# MIGRATION GUIDE - HasilPengujian Module Refactoring

## Overview

Dokumen ini menjelaskan cara melakukan rollback atau forward migration untuk refactoring HasilPengujian module.

---

## File Locations

```
appengines/app/Modules/HasilPengujian/
├── Controllers/
│   ├── HasilPengujian.php          ← NEW (Refactored version)
│   └── HasilPengujian.php.backup   ← OLD (Original version)
├── Models/
│   └── HasilPengujianModel.php     ← UPDATED (Minor changes)
├── Views/
│   └── v_hasilPengujian.php        ← NO CHANGES
├── Routes.php                       ← NO CHANGES
├── REFACTORING_SUMMARY.md          ← Documentation
├── QUICK_REFERENCE.md              ← Developer guide
└── MIGRATION_GUIDE.md              ← This file
```

---

## Forward Migration (Apply Refactoring)

Refactoring sudah diterapkan! File yang aktif sekarang adalah versi yang sudah di-refactor.

### Verification Steps

1. **Check no direct DB connections in controller:**

   ```powershell
   Select-String -Path "appengines\app\Modules\HasilPengujian\Controllers\HasilPengujian.php" -Pattern "Database::connect"
   ```

   **Expected:** No matches found ✅

2. **Check PHP syntax:**

   ```powershell
   php -l appengines\app\Modules\HasilPengujian\Controllers\HasilPengujian.php
   ```

   **Expected:** No syntax errors ✅

3. **Check model syntax:**
   ```powershell
   php -l appengines\app\Modules\HasilPengujian\Models\HasilPengujianModel.php
   ```
   **Expected:** No syntax errors ✅

---

## Rollback Migration (Revert to Original)

Jika ada masalah dan perlu rollback ke versi original:

### Option 1: Using PowerShell

```powershell
# Masuk ke directory project
cd c:\laragon\www\netx

# Backup current (refactored) version
Copy-Item "appengines\app\Modules\HasilPengujian\Controllers\HasilPengujian.php" `
          "appengines\app\Modules\HasilPengujian\Controllers\HasilPengujian.php.refactored"

# Restore original version
Copy-Item "appengines\app\Modules\HasilPengujian\Controllers\HasilPengujian.php.backup" `
          "appengines\app\Modules\HasilPengujian\Controllers\HasilPengujian.php" -Force

# Verify
Write-Host "Rollback completed. Check the file."
```

### Option 2: Manual

1. Rename file saat ini:

   - `HasilPengujian.php` → `HasilPengujian.php.refactored`

2. Copy backup file:

   - `HasilPengujian.php.backup` → `HasilPengujian.php`

3. Restart server jika perlu

### After Rollback

⚠️ **PENTING:** Jika rollback, Model juga perlu di-rollback!

HasilPengujianModel.php mengalami perubahan kecil pada method `updateFilesStatusForUser()`.
Jika rollback controller, pastikan method signature di model sesuai:

**Original signature (before):**

```php
public function updateFilesStatusForUser($lnKode, int $userId, int $fromStatus, int $toStatus): bool
```

**Refactored signature (after):**

```php
public function updateFilesStatusForUser($lnKode, int $userId, ?int $fromStatus, int $toStatus): bool
```

Jika rollback controller, ubah kembali `?int $fromStatus` menjadi `int $fromStatus`.

---

## Testing After Migration

### 1. Functional Testing

Test semua endpoint:

```bash
# Test halaman index
curl http://localhost/netx/hasilpengujian

# Test dataList (dengan berbagai filter)
curl http://localhost/netx/hasilpengujian/dataList
curl http://localhost/netx/hasilpengujian/dataList?lnStatus=4
curl http://localhost/netx/hasilpengujian/dataList?lnStatus=tolak

# Test detailList
curl http://localhost/netx/hasilpengujian/detailList/[encrypted_id]

# Test upload (gunakan Postman atau form)
# POST http://localhost/netx/hasilpengujian/upload

# Test submit (gunakan Postman atau form)
# POST http://localhost/netx/hasilpengujian/submit
```

### 2. Database Testing

Check database operations:

```sql
-- Check t_layanan_detil
SELECT * FROM t_layanan_detil WHERE kode_layanan = 'LN001' LIMIT 5;

-- Check t_files_lhus
SELECT * FROM t_files_lhus WHERE kode_layanan = 'LN001' LIMIT 5;

-- Check simlab_t_layanan
SELECT * FROM simlab_t_layanan WHERE lnKode = 'LN001';

-- Check r_tim
SELECT * FROM r_tim WHERE user_id = 1 LIMIT 5;
```

### 3. Authorization Testing

Test authorization dengan different users:

1. Login sebagai user A
2. Check data list → harus hanya melihat data mereka
3. Try to access detail dari layanan user lain → harus ditolak
4. Upload LHUS → harus berhasil
5. Submit LHUS → harus berhasil

### 4. Transaction Testing

Test transaction rollback:

1. Modify code untuk force error di tengah transaction
2. Submit LHUS
3. Check database → data harus tidak berubah (rolled back)
4. Remove forced error
5. Submit lagi → harus berhasil

---

## Common Issues & Solutions

### Issue 1: "Call to undefined method"

**Symptom:**

```
Fatal error: Call to undefined method HasilPengujianModel::someMethod()
```

**Solution:**
Method belum ada di model. Check:

1. Spelling method name
2. Method exists di HasilPengujianModel.php
3. Method signature sesuai

### Issue 2: "Too few arguments to function"

**Symptom:**

```
ArgumentCountError: Too few arguments to function HasilPengujianModel::updateFilesStatusForUser()
```

**Solution:**
Check parameter yang dikirim:

```php
// Wrong
$this->hasilPengujianModel->updateFilesStatusForUser($lnKode, $userId);

// Correct
$this->hasilPengujianModel->updateFilesStatusForUser($lnKode, $userId, 3, 0);
```

### Issue 3: Empty data list

**Symptom:**
DataList tidak menampilkan data

**Possible causes:**

1. Authorization failed → check `getLayananKodesByUserId()`
2. Filter terlalu strict → check status filter logic
3. Database connection issue → check database config

**Debug steps:**

```php
// Add temporary debug di controller
$lnKodeList = $this->hasilPengujianModel->getLayananKodesByUserId($user_id);
log_message('debug', 'LnKodeList: ' . print_r($lnKodeList, true));
```

### Issue 4: Transaction doesn't rollback

**Symptom:**
Data berubah meskipun ada error

**Solution:**
Check transaction handling:

```php
try {
    $this->hasilPengujianModel->transBegin();  // ← Must be BEGIN, not START

    // operations...

    if ($this->hasilPengujianModel->transStatus() === false) {
        $this->hasilPengujianModel->transRollback();
        return $this->jsonResponse('error', 'Failed');
    }

    $this->hasilPengujianModel->transCommit();

} catch (\Throwable $e) {
    $this->hasilPengujianModel->transRollback();  // ← Important!
    return $this->jsonResponse('error', $e->getMessage());
}
```

---

## Performance Considerations

### Database Queries

**Before refactoring:**

- Multiple direct queries di controller
- Possible N+1 query issues
- Hard to optimize

**After refactoring:**

- Queries centralized di model
- Easier to add caching
- Easier to optimize with indexes

### Recommended Indexes

```sql
-- Add indexes untuk improve performance
CREATE INDEX idx_layanan_detil_user ON t_layanan_detil(kode_layanan, status_layanan);
CREATE INDEX idx_rtim_user ON r_tim(user_id, uji_kode);
CREATE INDEX idx_files_lhus_kode ON t_files_lhus(kode, kode_layanan);
CREATE INDEX idx_layanan_status ON simlab_t_layanan(lnStatus, lnKode);
```

---

## Maintenance

### Adding New Feature

1. **Analyze requirements**

   - Apakah perlu query baru?
   - Apakah logic kompleks?
   - Apakah generic atau specific?

2. **Add to appropriate layer**

   - Generic CRUD → MyModel
   - Specific business logic → HasilPengujianModel
   - Request handling → Controller
   - Display logic → View

3. **Follow naming convention**

   - get*, update*, save*, delete*, check*, has*, count*, format*, process\*

4. **Add documentation**
   - PHPDoc comments
   - Update QUICK_REFERENCE.md if needed

### Code Review Checklist

- [ ] No direct DB connection in controller
- [ ] All queries in model
- [ ] Proper error handling
- [ ] Transaction rollback on error
- [ ] Method names clear and descriptive
- [ ] PHPDoc comments present
- [ ] Type hints for parameters
- [ ] No code duplication
- [ ] Method length < 50 lines (ideally)
- [ ] Tested manually

---

## Support & Documentation

### Documentation Files

1. **REFACTORING_SUMMARY.md** - Detailed changes dan metrics
2. **QUICK_REFERENCE.md** - Developer quick guide
3. **MIGRATION_GUIDE.md** - This file

### Getting Help

1. Read documentation files
2. Check code examples in HasilPengujianModel.php
3. Review original code in HasilPengujian.php.backup
4. Ask team lead

---

## Version History

| Version | Date       | Changes                              | Author         |
| ------- | ---------- | ------------------------------------ | -------------- |
| 1.0     | 2025-11-23 | Initial version (before refactoring) | -              |
| 2.0     | 2025-11-23 | Refactored version                   | GitHub Copilot |

---

## Approval & Sign-off

- [ ] Code reviewed by: ********\_********
- [ ] Testing completed by: ********\_********
- [ ] Approved for production by: ********\_********
- [ ] Date: ********\_********

---

**End of Migration Guide**

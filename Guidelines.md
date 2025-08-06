## 🚀 Panduan Instalasi & Setup

1. **Tidak perlu membuat file `composer.json` baru.**  
   Semua dependensi sudah tersedia di repository.

2. **Nyalakan server dan MySQL.**  
   - Jalankan Apache/Nginx dan MySQL sesuai konfigurasi lokal Anda (misal menggunakan XAMPP).

3. **Atur file `.env`.**  
   - Buka file `.env` pada root project.
   - Ubah konfigurasi berikut sesuai database Anda:
     ```
     database.default.database = [nama_database]
     database.default.username = [username_database]
     database.default.password = [password_database]
     ```
   - Simpan perubahan.

4. **Arahkan server ke base URL project.**  
   - Contoh: `http://localhost/netx/`

---

## 🧾 Format Commit

Gunakan format commit message standar sebagai berikut:

```bash
[type]: [deskripsi singkat]

feat: menambahkan fitur autentikasi login
fix: memperbaiki bug tampilan pada halaman dashboard
docs: update dokumentasi layout engine
```

Jenis type yang digunakan:
- **feat** → penambahan fitur baru
- **fix** → perbaikan bug
- **docs** → perubahan dokumentasi
- **refactor** → perombakan struktur kode tanpa mengubah perilaku

---

## 🌱 Aturan Branching

Gunakan skema nama branch berikut:

| Branch Awal  | Tujuan                        | Contoh                  |
|--------------|------------------------------|-------------------------|
| main         | Branch stabil untuk rilis     | -                       |
| dev          | Branch utama pengembangan     | -                       |
| feature/*    | Untuk fitur baru              | feature/auth-system     |
| fix/*        | Untuk perbaikan bug           | fix/navbar-overlap      |
| docs/*       | Untuk dokumentasi             | docs/update-readme      |
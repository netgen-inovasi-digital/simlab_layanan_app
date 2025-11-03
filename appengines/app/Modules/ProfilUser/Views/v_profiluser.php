<div class="row">   
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <label class="card-title mb-0"><?= esc($title) ?></label>
            </div>
            <div class="card-body">

                <?= form_open_multipart(site_url('profiluser/submit'), ['id'=>'myform','novalidate'=>'']) ?>
                <?= csrf_field() ?>

                <div class="row">

                    <!-- Kiri: Identitas, Instansi/Bukti, Nomor Telepon -->
                    <div class="col-md-6 p-4 border-end">

                        <h5 class="mb-4">Lengkapi Identitas</h5>

                        <!-- Status Identitas -->
                        <div class="mb-3">
                            <label class="form-label">Status Identitas</label>
                            <select name="user_identity" class="form-select" id="identitySelect">
                                <option value="">-- Pilih Identitas --</option>
                                <option value="ULM" <?= $get->user_identity === 'ULM' ? 'selected' : '' ?>>ULM</option>
                                <option value="NON ULM" <?= $get->user_identity === 'NON ULM' ? 'selected' : '' ?>>NON ULM</option>
                            </select>
                        </div>

                        <!-- Asal Instansi (NON ULM) -->
                        <div class="mb-3" id="instansiField" style="display: <?= $get->showInstansi ? 'block' : 'none' ?>;">
                            <label class="form-label">Alamat Instansi</label>
                            <input type="text" class="form-control" name="user_instansi" 
                                   value="<?= esc($get->user_instansi ?? '') ?>" placeholder="Masukkan Alamat Instansi">
                        </div>

                        <!-- Upload Bukti (ULM) -->
                        <div class="mb-3" id="buktiWrapper" style="display: <?= $get->showBukti ? 'block' : 'none' ?>;">
                            <label class="form-label">Upload Bukti</label>
                            <input type="file" class="form-control" name="bukti_file">
                            <div id="buktiInfo" class="mt-2">
                                <?php if (!empty($get->bukti ?? '')): ?>
                                    <a href="<?= $get->bukti_url ?>" target="_blank" class="btn btn-info btn-sm">
                                        <i class="bi bi-eye"></i> Lihat Bukti
                                    </a>
                                    <p class="text-muted small mt-1">Anda bisa unggah file baru untuk mengganti.</p>
                                <?php else: ?>
                                    <span class="text-danger">Belum ada bukti, silakan upload file.</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" name="user_telpon" 
                                   value="<?= esc($get->user_telpon ?? '') ?>" placeholder="Masukkan Nomor Telepon">
                        </div>

                        <!-- Status Verifikasi -->
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <span class="badge <?= $get->statusVerifikasi['class'] ?>">
                                <?= !empty($get->statusVerifikasi['icon']) ? '<i class="'.$get->statusVerifikasi['icon'].'"></i> ' : '' ?>
                                <?= $get->statusVerifikasi['text'] ?>
                            </span>
                        </div>

                    </div>

                    <!-- Kanan: Email + Password -->
                    <div class="col-md-6 p-4">
                        <h5 class="mb-4">Perbarui Akun</h5>

                        <div class="mb-3">
                        <label class="form-label mb-0">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                name="email"
                                id="emailInput"
                                value="<?= esc($get->user_email ?? '') ?>"
                                data-original="<?= esc(strtolower($get->user_email ?? '')) ?>"
                                placeholder="nama@domain.tld"
                                autocomplete="email"
                            >
                            <div class="form-text">Jika mengubah email, wajib isi Password Lama.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="old_password" id="oldPassword" placeholder="Masukkan Password Lama">
                                <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('oldPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="new_password" id="newPassword" placeholder="Masukkan Password Baru">
                                <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('newPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ulangi Password Baru</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password" id="confirmPassword" placeholder="Ulangi Password Baru">
                                <span class="input-group-text" style="cursor:pointer;" onclick="togglePassword('confirmPassword', this)">
                                    <i class="bi bi-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-check2-circle"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>

                </div>

                <?= form_close() ?>

            </div>
        </div>
    </div>
</div>

<script>
// pakai yang sudah ada kalau sebelumnya pernah dideklarasikan
if (typeof identitySelect === 'undefined') 
    { window.identitySelect = document.getElementById('identitySelect'); }
if (typeof instansiField  === 'undefined') 
    { window.instansiField  = document.getElementById('instansiField'); }
if (typeof buktiWrapper   === 'undefined') 
    { window.buktiWrapper   = document.getElementById('buktiWrapper'); }


function toggleFields() {
    const value = identitySelect.value;
    if (value === 'ULM') {
        instansiField.style.display = 'none';
        buktiWrapper.style.display  = 'block';
    } else if (value === 'NON ULM') {
        instansiField.style.display = 'block';
        buktiWrapper.style.display  = 'none';
    } else {
        instansiField.style.display = 'none';
        buktiWrapper.style.display  = 'none';
    }
}

identitySelect.addEventListener('change', toggleFields);
document.addEventListener('DOMContentLoaded', toggleFields);

function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

$('#myform').submit(function(e){
  e.preventDefault();
  const form = this;
  const url  = form.getAttribute('action');

  // === email changed guard ===
  const emailInput = form.querySelector('#emailInput');
  const oldPwd     = form.querySelector('#oldPassword');
  if (emailInput) {
    const original = (emailInput.dataset.original || '').trim().toLowerCase();
    const current  = (emailInput.value || '').trim().toLowerCase();

    // optional: format sederhana
    const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (current && !emailRe.test(current)) {
      if (typeof sayAlert === 'function')
        sayAlert('errorModal','Format Email','Format email tidak valid.','warning');
      else alert('Format email tidak valid.');
      return;
    }

    // jika email berubah → wajib isi password lama
    if (current && current !== original && (!oldPwd || !oldPwd.value)) {
      if (typeof sayAlert === 'function')
        sayAlert('errorModal','Perlu Password','Masukkan password lama untuk mengubah email.','warning');
      else alert('Masukkan password lama untuk mengubah email.');
      return;
    }
  }

  showLoading();

  const formData = new FormData(form);
  fetch(url, {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin'
  })
  .then(async res => {
    const ctype = res.headers.get('content-type') || '';
    if (!res.ok) {
      const text = await res.text();
      throw new Error(`HTTP ${res.status}: ${text.slice(0,300)}`);
    }
    if (!ctype.includes('application/json')) {
      const text = await res.text();
      throw new Error(`Unexpected response (not JSON): ${text.slice(0,300)}`);
    }
    return res.json();
  })
  .then(data => {
    // update CSRF bila ada
    if (data.xname && data.xhash) {
      const csrfInput = form.querySelector(`input[name="${data.xname}"]`)
                      || form.querySelector('input[name="<?= csrf_token() ?>"]');
      if (csrfInput) { csrfInput.setAttribute('name', data.xname); csrfInput.value = data.xhash; }
    }

    if (data.res === 'refresh') {
      window.location.href = data.link || '<?= site_url('profiluser') ?>';
      return;
    }
    if (data.res === true || data.res === 'true') {
      sayAlert('successModal','Success', data.msg || 'Data berhasil disimpan','success');
      setTimeout(() => window.location.reload(), 350);
      return;
    }
    if (data.res === 'redirect' && data.link) {
      window.location.href = data.link;
      return;
    }

    sayAlert('errorModal','Error', data.msg || 'Data gagal disimpan','warning');
  })
  .catch(err => {
    console.error(err);
    sayAlert('errorModal','Error','Terjadi kesalahan sistem atau respon tidak valid','warning');
  })
  .finally(() => hideLoading());
});

</script>

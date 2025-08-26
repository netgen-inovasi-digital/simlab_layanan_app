<?= $this->extend('auth/auth_layout') ?>

<?= $this->section('title') ?>
Lupa Password
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-0">
    <div class="col-md-6 bg-white p-5 d-flex flex-column justify-content-center">
        <h5 class="fw-bold mb-4 text-center">Lupa Password</h5>
        
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success small text-center" role="alert">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger small text-center" role="alert">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <p class="text-muted small mb-4 text-center">Masukkan email Anda yang terdaftar. Kami akan mengirimkan link untuk mereset password.</p>
        
        <?= form_open('forgot/auth', ['id' => 'forgot-form']) ?>
          <div class="mb-3">
            <input name="email" type="email" class="form-control rounded-pill mx-auto bg-light-gray" placeholder="Masukkan email Anda" value="<?= old('email') ?>" required />
          </div>
          <div class="d-grid">
            <button type="submit" class="btn btn-primary rounded-pill mx-auto">SUBMIT</button>
          </div>
        </form>
        <div class="mt-4 text-muted small">
            <p class="text-center">Kembali untuk Login? Klik <a href="<?= base_url('login') ?>" class="text-decoration-none">Disini</a>.</p>
        </div>
    </div>

    <div class="col-md-6 bg-light-gray d-flex flex-column justify-content-center align-items-center p-5">
        <img src="https://placehold.co/250x100?text=Logo+Template" alt="Logo Template" style="width: 250px;" class="img-fluid" />
    </div>
</div>
<?= $this->endSection() ?>
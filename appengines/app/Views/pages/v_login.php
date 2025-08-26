<?= $this->extend('auth/auth_layout') ?>

<?= $this->section('title') ?>
Login
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-0">
    <div class="col-md-6 bg-light-gray d-flex flex-column justify-content-center align-items-center p-5">
        <img src="https://placehold.co/250x100?text=Logo+Template" alt="Logo Template" style="width: 250px;" class="img-fluid" />
    </div>

    <div class="col-md-6 bg-white p-5 d-flex flex-column justify-content-center">
        <h5 class="fw-bold mb-4 text-center">Silakan Masuk</h5>
        
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success small text-center" role="alert">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('login_error')): ?>
            <div class="alert alert-danger small text-center" role="alert">
                <?= session()->getFlashdata('login_error') ?>
            </div>
        <?php endif; ?>

        <?= form_open('login/auth', ['id' => 'login-form']) ?>
            <div class="mb-3">
                <input name="usr" type="text" class="form-control rounded-pill mx-auto bg-light-gray" placeholder="Username" value="<?= old('usr') ?>" required />
            </div>
            <div class="mb-3">
                <input name="pwd" type="password" class="form-control rounded-pill mx-auto bg-light-gray" placeholder="Password" required />
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary rounded-pill mx-auto">MASUK</button>
            </div>
        </form>
        <div class="mt-4 text-muted small">
            <p class="text-center">Lupa Sandi? Klik <a href="<?= base_url('forgot') ?>" class="text-decoration-none">Disini</a>.</p>
            <p class="text-center">Belum Punya Akun? Daftar <a href="<?= base_url('register') ?>" class="text-decoration-none">Disini</a>.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
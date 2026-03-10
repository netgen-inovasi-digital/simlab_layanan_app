<style>
    .questionnaire-shell {
        width: 100%;
        margin: 0;
    }

    .questionnaire-shell .card {
        border-radius: 10px;
        box-shadow: 0 15px 10px rgba(12, 48, 66, 0.08);
    }

    .questionnaire-shell .card-header {
        border-bottom: 1px solid #eef2f6;
    }

    .questionnaire-shell .info-banner {
        border-radius: 14px;
        background: linear-gradient(135deg, #f6f9ff, #eef5ff);
        border: 1px solid #d9e6ff;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }

    .questionnaire-shell .question-block {
        border: 1px solid #e4ebf3;
        border-radius: 12px;
        padding: 1.25rem;
        background-color: #fff;
        margin-bottom: 1.25rem;
    }

    .questionnaire-shell .question-title {
        font-weight: 600;
        font-size: 1rem;
        color: #1f2d3d;
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .questionnaire-shell .question-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        font-weight: 600;
        background: #eff5ff;
        color: #246bfd;
    }

    .questionnaire-shell .rating-stars .form-check {
        padding-left: 0;
        margin-bottom: 0.35rem;
    }

    .questionnaire-shell .form-check-input:checked+.form-check-label {
        font-weight: 600;
        color: #1a5ad4;
    }

    .questionnaire-shell .form-check,
    .questionnaire-shell .form-check-inline {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding-left: 0;
    }

    .questionnaire-shell .form-check-input {
        margin-top: 0;
        margin-left: 5px;
        margin-right: 0.15rem;
        float: none;
        cursor: pointer;
    }

    .questionnaire-shell .form-check-label {
        margin-bottom: 0;
        line-height: 1.2;
        cursor: pointer;
    }

    .questionnaire-shell .action-bar {
        border-top: 1px solid #eef2f6;
        padding-top: 1.25rem;
        margin-top: 1.5rem;
        gap: 0.75rem;
    }
</style>

<div class="row">
    <div class="col-12 questionnaire-shell">
        <div class="card">
            <div class="card-header flex-column flex-md-row d-flex justify-content-between align-items-md-center gap-3">
                <label class="card-title mb-0 d-flex align-items-center gap-2">
                    <span style="cursor:pointer" onclick="loadContent('pelayanan')">
                        <i class="bi bi-arrow-left-circle fs-5 text-secondary"></i>
                    </span>
                    <div><?= esc($title ?? 'Kuesioner') ?></div>
                </label>
                <small class="text-muted">Isi kuisioner untuk membantu kami meningkatkan layanan.</small>
            </div>
            <div class="card-body">
                <div class="info-banner d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                        style="width:42px;height:42px;">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Petunjuk</div>
                        <small class="text-muted">Pertanyaan bertanda <span class="text-danger">*</span> wajib diisi.
                            Pastikan seluruh jawaban sudah benar sebelum mengirim.</small>
                    </div>
                </div>

                <?php echo form_open('pelayanan/submit_kuesioner', ['id' => 'myform', 'novalidate' => '', 'onsubmit' => 'return false;']); ?>
                <input type="hidden" name="idenc" value="<?= esc($idenc ?? '') ?>">

                <div class="mt-4">
                    <?php
                    $no = 1;
                    if (!empty($pertanyaan)):
                        foreach ($pertanyaan as $p):
                            $is_wajib = (int) ($p->pertanyaan_wajib ?? 0) === 1;
                            ?>
                            <div class="question-block">
                                <div class="question-title mb-2">
                                    <span class="question-badge"><?= $no++ ?></span>
                                    <span><?= esc($p->pertanyaan_teks) ?><?php if ($is_wajib): ?><span
                                                class="text-danger ms-1">*</span><?php endif; ?></span>
                                </div>

                                <?php if ($p->pertanyaan_tipe === 'rating'): ?>
                                    <small class="text-muted d-block mb-3">Skala 1 = Sangat Buruk, 5 = Sangat Baik</small>
                                <?php endif; ?>

                                <?php if ($p->pertanyaan_tipe === 'isian'): ?>
                                    <textarea name="jawaban[<?= $p->kuesioner_id ?>]" class="form-control" rows="3"
                                        placeholder="Tulis jawaban Anda..." <?= $is_wajib ? 'required' : '' ?>></textarea>

                                <?php elseif ($p->pertanyaan_tipe === 'rating'): ?>
                                    <div class="d-flex justify-content-start rating-stars flex-wrap">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="form-check form-check-inline me-3 mb-1">
                                                <input class="form-check-input" type="radio" name="jawaban[<?= $p->kuesioner_id ?>]"
                                                    id="rating-<?= $p->kuesioner_id . '-' . $i ?>" value="<?= $i ?>" <?= $is_wajib ? 'required' : '' ?>>
                                                <label class="form-check-label"
                                                    for="rating-<?= $p->kuesioner_id . '-' . $i ?>"><?= $i ?></label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>

                                <?php elseif ($p->pertanyaan_tipe === 'pilihan'): ?>
                                    <div class="d-flex flex-column gap-2">
                                        <?php
                                        $opsi_array = array_filter(array_map('trim', explode("\n", $p->pertanyaan_opsi ?? '')));
                                        foreach ($opsi_array as $idx => $opsi_val): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="jawaban[<?= $p->kuesioner_id ?>]"
                                                    id="opsi-<?= $p->kuesioner_id . '-' . $idx ?>" value="<?= esc($opsi_val) ?>"
                                                    <?= $is_wajib ? 'required' : '' ?>>
                                                <label class="form-check-label"
                                                    for="opsi-<?= $p->kuesioner_id . '-' . $idx ?>"><?= esc($opsi_val) ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <p class="text-center text-muted">Tidak ada pertanyaan kuesioner yang tersedia saat ini.</p>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center action-bar">
                    <button type="button" class="btn btn-light" onclick="loadContent('pelayanan')">
                        <i class="bi bi-x-circle"></i> Keluar
                    </button>
                    <button class="btn btn-primary" id="btnSimpan" type="button" <?= empty($pertanyaan) ? 'disabled' : '' ?>>
                        <i class="bi bi-check2-circle"></i> Kirim Jawaban
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const btnSimpan = document.querySelector('#btnSimpan');
    if (btnSimpan) {
        btnSimpan.addEventListener('click', function (e) {
            e.preventDefault();
            const form = document.querySelector('#myform');
            const actionUrl = form.getAttribute('action');

            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;

            requiredFields.forEach(field => {
                let isFieldValid = false;
                if (field.type === 'radio' || field.type === 'checkbox') {
                    const groupName = field.name;
                    if (form.querySelector(`input[name="${groupName}"]:checked`)) {
                        isFieldValid = true;
                    }
                } else {
                    if (field.value.trim() !== '') {
                        isFieldValid = true;
                    }
                }

                if (!isFieldValid) {
                    isValid = false;
                    if (firstInvalidField === null) {
                        firstInvalidField = field;
                    }
                }
            });

            if (!isValid) {
                sayAlert('errorModal', 'Gagal', 'Harap mengisi semua pertanyaan yang ditandai bintang (*).', 'warning');
                if (firstInvalidField) {
                    const label = firstInvalidField.closest('.mb-4')?.querySelector('label');
                    if (label) label.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    else firstInvalidField.focus();
                }
                return;
            }

            const formData = new FormData(form);
            // Refresh CSRF in formData from the form's own hidden input (scoped, not doc-wide)
            const csrfNameField = form.querySelector('input[type="hidden"][name]');
            if (csrfNameField) {
                formData.set(csrfNameField.name, csrfNameField.value);
            }
            saveData({
                url: actionUrl,
                formData: formData,
                onSuccess: function (data) {
                    if (data.res === false && data.msg) {
                        sayAlert('errorModal', 'Gagal', data.msg, 'warning');
                    }
                },
                onError: function (err) {
                    sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem. ' + err.message, 'warning');
                    console.error("SaveData error:", err);
                }
            });
        });
    }

    function saveData({
        url,
        formData,
        onSuccess,
        onError
    }) {
        showLoading();

        fetch(url, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.xname && data.xhash) {
                    const currentCsrf = document.querySelector('#myform input[name="' + data.xname + '"]');
                    if (currentCsrf) currentCsrf.value = data.xhash;
                    document.querySelectorAll('input[name="' + data.xname + '"]').forEach(input => {
                        if (input !== currentCsrf) input.value = data.xhash;
                    });
                }
                if (data.res === 'refresh' || data.res === 'redirect') {
                    sayAlert('successModal', 'Berhasil', 'Terima kasih, jawaban Anda telah disimpan.', 'success');
                    if (typeof loadContent === 'function') {
                        setTimeout(() => {
                            loadContent('pelayanan');
                        }, 1500);
                    } else {
                        setTimeout(() => {
                            window.location.href = data.link;
                        }, 1500);
                    }
                    return;
                }
                try {
                    if (typeof onSuccess === 'function') {
                        onSuccess(data);
                        return;
                    }
                } catch (e) {
                    console.error("Error inside onSuccess callback:", e);
                    if (typeof onError === 'function') {
                        onError(e);
                    }
                }

                console.warn("Unexpected response from server:", data);
                sayAlert('errorModal', 'Error', data.msg || 'Terjadi kesalahan.', 'warning');

            })
            .catch(error => {
                console.error("Fetch error:", error);
                try {
                    if (typeof onError === 'function') {
                        onError(error);
                    } else {
                        sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
                    }
                } catch (e) {
                    console.error("Error inside onError callback:", e);
                }
            })
            .finally(() => {
                hideLoading();
            });
    }
</script>
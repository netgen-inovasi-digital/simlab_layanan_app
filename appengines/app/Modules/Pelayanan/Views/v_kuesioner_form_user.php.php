<!-- <div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0 d-flex align-items-center">
                    <span style="cursor:pointer" class="me-2" onclick="loadContent('pelayanan')">
                        <i class="bi bi-arrow-left-circle fs-5 text-secondary"></i>
                    </span>
                    <div><?php echo $title ?></div>
                </label>
            </div>
            <div class="card-body">
                <?php echo form_open('pelayanan/submit_kuesioner', array('id' => 'myform', 'novalidate' => '')) ?>
                <input type="hidden" name="idenc" value="<?= esc($idenc ?? '') ?>">
                <div class="mt-4">
                    <?php
                    $no = 1;
                    if (isset($pertanyaan) && !empty($pertanyaan)):
                        foreach ($pertanyaan as $p) :
                            $is_wajib = $p->pertanyaan_wajib == 1;
                    ?>
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <?= $no++ . ". " . esc($p->pertanyaan_teks) ?>
                                    <?php if ($is_wajib) : ?><span class="text-danger">*</span><?php endif; ?>
                                </label>

                                <?php if ($p->pertanyaan_tipe == 'rating') : ?>
                                    <small class="form-text text-muted d-block mb-2">
                                        (Skala 1 = Sangat Buruk, 5 = Sangat Baik)
                                    </small>
                                <?php endif; ?>

                                <?php
                                if ($p->pertanyaan_tipe == 'isian') :
                                ?>
                                    <textarea name="jawaban[<?= $p->kuesioner_id ?>]" class="form-control" rows="3" <?= $is_wajib ? 'required' : '' ?>></textarea>

                                <?php
                                elseif ($p->pertanyaan_tipe == 'rating') :
                                ?>
                                    <div class="d-flex justify-content-start rating-stars flex-wrap">
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <div class="form-check form-check-inline me-3 mb-1">
                                                <input class="form-check-input" type="radio" name="jawaban[<?= $p->kuesioner_id ?>]" id="rating-<?= $p->kuesioner_id . '-' . $i ?>" value="<?= $i ?>" <?= $is_wajib ? 'required' : '' ?>>
                                                <label class="form-check-label" for="rating-<?= $p->kuesioner_id . '-' . $i ?>"><?= $i ?></label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>

                                    <?php
                                elseif ($p->pertanyaan_tipe == 'pilihan') :
                                    $opsi_array = explode("\n", $p->pertanyaan_opsi ?? '');
                                    foreach ($opsi_array as $idx => $opsi) :
                                        $opsi_val = trim($opsi);
                                        if (empty($opsi_val)) continue;
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="jawaban[<?= $p->kuesioner_id ?>]" id="opsi-<?= $p->kuesioner_id . '-' . $idx ?>" value="<?= esc($opsi_val) ?>" <?= $is_wajib ? 'required' : '' ?>>
                                            <label class="form-check-label" for="opsi-<?= $p->kuesioner_id . '-' . $idx ?>">
                                                <?= esc($opsi_val) ?>
                                            </label>
                                        </div>
                                <?php
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <p class="text-center text-muted">Tidak ada pertanyaan kuesioner yang tersedia saat ini.</p>
                    <?php endif; ?>
                </div>
                <div class="text-end border-top pt-3">
                    <button class="btn btn-primary" id="btnSimpan" type="submit" <?= (!isset($pertanyaan) || empty($pertanyaan)) ? 'disabled' : '' ?>>
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
        btnSimpan.addEventListener('click', function(e) {
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
            saveData({
                url: actionUrl,
                formData: formData,
                onSuccess: function(data) {
                    if (data.res === false && data.msg) {
                        sayAlert('errorModal', 'Gagal', data.msg, 'warning');
                    }
                },
                onError: function(err) {
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
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
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
</script> -->
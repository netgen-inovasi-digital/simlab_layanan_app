<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <button id="add" class="btn btn-primary">
                    <i class="bi bi-plus-circle-dotted"></i> Tambah
                </button>
            </div>
            <div class="card-body">

                <!-- === Tambahan: Dropdown kode layanan + form diskon civitas ULM === -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="kode_layanan" class="form-label">Kode Layanan</label>
                        <select id="kode_layanan" name="kode_layanan" class="form-control">
                            <option value="">-- Pilih Kode Layanan --</option>
                            <option value="L001">L001 - Pengujian A</option>
                            <option value="L002">L002 - Pengujian B</option>
                            <option value="L003">L003 - Pengujian C</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <form id="formDiskonULM" action="<?= base_url('lab/update_diskon') ?>" method="post" class="d-flex">
                            <?= csrf_field() ?>
                            <div class="flex-grow-1">
                                <label for="diskon_ulm" class="form-label">Diskon Civitas ULM (%)</label>
                                <input type="number" name="diskon" id="diskon_ulm"
                                    class="form-control"
                                    value="<?= isset($diskon_ulm) ? $diskon_ulm : '' ?>"
                                    min="0" max="100">
                            </div>
                            <div class="ms-2 align-self-end">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- === End tambahan === -->

                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="8%">Kategori Layanan</th>
                            <th show>Nama Layanan</th>
                            <th show>Biaya</th>
                            <th show class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    table = createTable({
        apiUrl: '<?php echo site_url("lab/datalist") ?>',
    });
    addAction();

    // === AJAX submit untuk form diskon ULM ===
    document.querySelector('#formDiskonULM').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action');

        showLoading();
        const csrfInput = form.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        fetch(actionUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }
            if (data.res === true) {
                sayAlert('successModal', 'Berhasil', 'Diskon berhasil diupdate.', 'success');
            } else {
                sayAlert('errorModal', 'Gagal', 'Diskon gagal diupdate.', 'warning');
            }
        })
        .catch(err => {
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem.', 'warning');
        })
        .finally(() => hideLoading());
    });

    // event btnSimpan untuk modal layanan lab (tetap sama)
    document.querySelector('#btnSimpan').addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.querySelector('#myform');
        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action');

        saveData({ url: actionUrl, formData: formData, onSuccess: function(data) {
            if (data.res === true) {
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
            }
        }});
    });

    function saveData({ url, formData, onSuccess, onError }) {
        showLoading();

        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
            .then(response => response.json())
            .then(data => {
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                        input.value = data.xhash;
                    });
                }

                if (typeof onSuccess === 'function') {
                    onSuccess(data);
                    return;
                }

                if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');

                if (data.res === true) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                    sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
                } else if (data.res === 'reload') {
                    sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
                } else if (data.res === 'refresh') {
                    loadContent(data.link);
                    sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
                } else if (data.res === 'redirect') {
                    window.location.href = data.link;
                } else if (data.res === 'check') {
                    sayAlert('errorModal', 'Error', data.link, 'warning');
                } else if (data.res === 'refresh-print') {
                    loadContent(data.link);
                    window.open(data.print, "_blank");
                } else {
                    sayAlert('errorModal', 'Error', 'Data gagal disimpan.', 'warning');
                }
            })
            .catch(error => {
                if (typeof onError === 'function') {
                    onError(error);
                } else {
                    sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
                }
            })
            .finally(() => hideLoading());
    }

    // ====== Tambahan untuk isi dropdown dari getOptions ======
    function loadOptions(selected = {}) {
        fetch('<?php echo site_url("lab/getoptions") ?>')
            .then(res => res.json())
            .then(data => {
                let jenis = document.querySelector('[name="ujiJenKode"]');
                let alat  = document.querySelector('[name="ujiAlatKode"]');
                let para  = document.querySelector('[name="ujiParaKode"]');

                jenis.innerHTML = '<option value="">-- Pilih Jenis --</option>';
                alat.innerHTML  = '<option value="">-- Pilih Alat --</option>';
                para.innerHTML  = '<option value="">-- Pilih Parameter --</option>';

                data.jenis.forEach(j => {
                    jenis.innerHTML += `<option value="${j.jenKode}" ${selected.jenis==j.jenKode?"selected":""}>${j.jenNama}</option>`;
                });
                data.alat.forEach(a => {
                    alat.innerHTML += `<option value="${a.alatKode}" ${selected.alat==a.alatKode?"selected":""}>${a.alatNama}</option>`;
                });
                data.parameter.forEach(p => {
                    para.innerHTML += `<option value="${p.paraKode}" ${selected.para==p.paraKode?"selected":""}>${p.paraNama}</option>`;
                });
            });
    }

    // buka modal tambah
    document.querySelector('#add').addEventListener('click', function() {
        document.querySelector('#myform').reset();
        document.querySelector('[name="id"]').value = "";
        loadOptions();
        $('#modalForm').modal('show');
    });

    // ====== Tambahan fungsi editItem ======
    function editItem(e) {
        const id = e.target.closest('div').id;

        fetch('<?php echo site_url("lab/edit/") ?>' + id)
            .then(res => res.json())
            .then(data => {
                document.querySelector('[name="id"]').value = data.id;
                document.querySelector('[name="ujiLayanan"]').value = data.ujiLayanan;
                document.querySelector('[name="ujiSatuan"]').value = data.ujiSatuan;
                document.querySelector('[name="ujiBiaya"]').value = data.ujiBiaya;

                loadOptions({
                    jenis: data.ujiJenKode,
                    alat: data.ujiAlatKode,
                    para: data.ujiParaKode
                });

                $('#modalForm').modal('show');
            });
    }
</script>

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Layanan Lab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <?php echo form_open('lab/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body">
                <input type="hidden" value="" name="id" />
                <div class="row mb-2">
                    <div class="col">
                        <label class="col-form-label">Jenis Layanan</label>
                        <select name="ujiJenKode" class="form-control" required>
                            <option value="">-- Pilih Jenis --</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="col-form-label">Alat</label>
                        <select name="ujiAlatKode" class="form-control" required>
                            <option value="">-- Pilih Alat --</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col">
                        <label class="col-form-label">Parameter</label>
                        <select name="ujiParaKode" class="form-control" required>
                            <option value="">-- Pilih Parameter --</option>
                        </select>
                    </div>
                    <div class="col">
                        <label class="col-form-label">Nama Layanan</label>
                        <input name="ujiLayanan" type="text" class="form-control" required placeholder="Masukkan nama layanan">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col">
                        <label class="col-form-label">Satuan</label>
                        <input name="ujiSatuan" type="text" class="form-control" required placeholder="Masukkan satuan (contoh: Sampel)">
                    </div>
                    <div class="col">
                        <label class="col-form-label">Biaya</label>
                        <input name="ujiBiaya" type="text" class="form-control" required placeholder="Masukkan biaya (angka)">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                <button class="btn btn-success" id="btnSimpan" type="submit"><i class="bi bi-check2-circle"></i> Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>

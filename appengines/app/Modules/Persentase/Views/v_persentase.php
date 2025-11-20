<div class="row">
    <div class="col-md-12">
        <div class="card">
            
            <div class="card-header border-bottom pb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="card-title mb-0 fs-5 fw-bold"><?php echo $title ?></label>
                    <button id="add" class="btn btn-primary d-flex align-items-center">
                        <i class="bi bi-plus-circle-dotted me-1"></i> Tambah
                    </button>
                </div>

                <div class="d-flex align-items-center">
                    <label for="filterJenis" class="me-2">Filter:</label>
                    <select id="filterJenis" class="form-select form-select-sm" style="width: auto;">
                        <option value="">-- Semua Layanan --</option>
                        <?php if (isset($jenis) && !empty($jenis)) : ?>
                            <?php foreach ($jenis as $j) : ?>
                                <option value="<?php echo $j->jenKode; ?>">
                                    <?php echo $j->jenKode . ' - ' . $j->jenNama; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="6%">No.</th>
                            <th show width="35%">Kode Layanan</th>
                            <th show width="35%">Jenis Biaya</th>
                            <th show width="8%">Persentase</th>
                            <th show class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    var apiUrl = '<?php echo site_url("persentase/datalist") ?>';

    var currentPage = 1;
    var currentLimit = 10;

    function loadTable(url) {
        return createTable({
            tableId: 'data-table',
            apiUrl: url,
            dataSrc: 'items',
            columns: [{
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                { data: 'kodeLayanan' },
                { data: 'jenisBiaya' },
                { data: 'persentase' },
                { data: 'aksi' }
            ]
        });
    }

    table = loadTable(apiUrl + "?page=" + currentPage + "&limit=" + currentLimit);
    addAction();

    document.querySelector('#filterJenis').addEventListener('change', function() {
        let kode = this.value;
        let newUrl = apiUrl + "?page=" + currentPage + "&limit=" + currentLimit;
        if (kode !== "") {
            newUrl = apiUrl + "?kdJenKode=" + encodeURIComponent(kode) + "&page=" + currentPage + "&limit=" + currentLimit;
        }
        table = loadTable(newUrl);
        addAction();
    });

    document.querySelector('#btnSimpan').addEventListener('click', function(e) {
        e.preventDefault();

        const form = document.querySelector('#myform');
        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action');

        saveData({
            url: actionUrl,
            formData: formData,
            onSuccess: function(data) {
                if (data.res === true) {
                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
                    sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                    if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
                }
            }
        });
    });

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
                    if (typeof table !== 'undefined') table.fetchData({
                        reload: true
                    });
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
            .finally(() => {
                hideLoading();
            });
    }
</script>

<?php echo view('Modules\Persentase\Views\v_tambahPersentase'); ?>
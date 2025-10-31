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
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="8%">No.</th>
                            <th show>Judul Pengumuman</th>
                            <th>File</th>
                            <th>Status</th>
                            <th show class="action text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    table = createTable({
        apiUrl: '<?php echo site_url("pengumuman/datalist") ?>',
    });
    addAction();

    var modal = document.getElementById('modalForm');
    modal.addEventListener('shown.bs.modal', function (e) {
        const id = document.querySelector('[name="id"]').value;
        const fileInput = document.querySelector('[name="file"]');
        fileInput.removeAttribute('required');
        if (id == "") fileInput.setAttribute('required', true);
    });
    async function editItem(e) {
    const id = e.target.closest('div').id;
    const csrfName = document.querySelector('[name="<?php echo csrf_token() ?>"]').getAttribute('name');
    const csrfHash = document.querySelector('[name="<?php echo csrf_token() ?>"]').value;

    const formData = new URLSearchParams();
    formData.append('id', id);
    formData.append(csrfName, csrfHash);

    const response = await fetch("<?php echo site_url('pengumuman/edit') ?>", {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    });

    if (!response.ok) {
        alert("Gagal menghubungi server. (" + response.status + ")");
        return;
    }

    const data = await response.json();

    document.querySelector('[name="<?php echo csrf_token() ?>"]').value = data["<?php echo csrf_token() ?>"];

    if (data.res === 'error') {
        alert(data.msg);
        return;
    }

    const form = document.getElementById('myform');
    form.reset();

    document.querySelector('[name="id"]').value = data.id;
    document.querySelector('[name="judul"]').value = data.judul ?? '';
    document.querySelector('[name="status"]').checked = data.status === 'tampil';

    const modal = new bootstrap.Modal(document.getElementById('modalForm'));
    modal.show();
}
</script>

<!-- Modal Form -->
<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Data Pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <?php echo form_open_multipart('pengumuman/submit', array('id' => 'myform', 'novalidate' => '')) ?>
                <div class="modal-body">
                    <input type="hidden" name="id" value="">

                    <!-- Judul -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Judul Pengumuman</label>
                        <div class="col">
                            <input type="text" class="form-control" name="judul" required>
                        </div>
                    </div>

                    <!-- File -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">File Pengumuman</label>
                        <div class="col">
                            <input type="file" class="form-control" name="file" accept=".png,.jpg,.jpeg">
                            <small class="text-muted">Kosongkan jika tidak mengganti file.</small>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Status</label>
                        <div class="col mt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" id="status" checked>
                                <label class="form-check-label" for="status">Tampilkan di halaman depan</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button class="btn btn-success" type="submit">
                        <i class="bi bi-check2-circle"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

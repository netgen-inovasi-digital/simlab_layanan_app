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
                        <th show>Username<i class="fa-solid fa-sort sort-icon"></i></th>
                        <th>Email</th>
                        <th>Status Identitas</th>
                        <th>Status</th>
                        <th show class="action text-end">Aksi<i class="fa-solid fa-forward-step sort-icon"></i></th>
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
    apiUrl: '<?php echo site_url("akun/datalist") ?>',
});
addAction();
var modal = document.getElementById('modalForm');
modal.addEventListener('shown.bs.modal', function (e) {
    const pwd = document.querySelector('[name="user_password"]');
    pwd.value = "";
    const id = document.querySelector('[name="id"]').value;
    if(id=="") pwd.setAttribute('required', true);
    else pwd.removeAttribute('required');
});
</script>

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Akun Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <?php echo form_open('akun/submit', array('id'=>'myform', 'novalidate'=>'')) ?>
                <div class="modal-body">
                    <input type="hidden" value="" name="id"/>

                    <!-- Username -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Username</label>
                        <div class="col">
                            <input name="user_name" type="text" class="form-control" required>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Email</label>
                        <div class="col">
                            <input name="user_email" type="email" class="form-control">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Password</label>
                        <div class="col">
                            <input name="user_password" type="password" class="form-control">
                        </div>
                    </div>

                    <!-- Status Identitas -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Status Identitas</label>
                        <div class="col">
                            <select name="user_identity" class="form-select" required>
                                <option value="">-- pilih identitas --</option>
                                <option value="ULM">ULM</option>
                                <option value="NON ULM">NON ULM</option>
                            </select>
                        </div>
                    </div>

                    <!-- Status User -->
                    <div class="row mb-2">
                        <label class="col-4 col-form-label">Status</label>
                        <div class="col">
                            <div class="form-check mt-2 form-check-inline">
                                <input class="form-check-input" type="radio" name="status_user" id="status1" value="1" checked>
                                <label class="form-check-label" for="status1">Aktif</label>
                            </div>
                            <div class="form-check mt-2 form-check-inline">
                                <input class="form-check-input" type="radio" name="status_user" id="status0" value="0">
                                <label class="form-check-label text-danger" for="status0">Tidak Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Footer -->
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

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
                        <th>Kontak</th>
                        <th>Status Identitas</th>
                        <th>Asal Instansi</th>
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

// === Modal form logic ===
var modal = document.getElementById('modalForm');
modal.addEventListener('shown.bs.modal', function (e) {
    const pwd = document.querySelector('[name="user_password"]');
    pwd.value = "";
    const id = document.querySelector('[name="id"]').value;
    if (id == "") pwd.setAttribute('required', true);
    else pwd.removeAttribute('required');

    // === Tambahan: tampilkan input instansi jika NON ULM ===
    const identitySelect = modal.querySelector('[name="user_identity"]');
    const instansiField = modal.querySelector('#instansiField');
    const instansiInput = instansiField.querySelector('input');


    function toggleInstansi() {
        if (identitySelect.value === "NON ULM") {
            instansiField.style.display = "flex"; 
            instansiInput.setAttribute("required", true);
        } else {
            instansiField.style.display = "none"; 
            instansiInput.removeAttribute("required");
            instansiInput.value = ""; 
        }
    }

    identitySelect.addEventListener("change", toggleInstansi);
    toggleInstansi();
});

// === Edit Data: isi form dengan response dari controller ===
function editItem(event) {
    const id = event.target.closest("div").id;
    fetch("<?php echo site_url('akun/edit/') ?>" + id)
        .then(res => res.json())
        .then(data => {
            document.querySelector('[name="id"]').value = data.id;
            document.querySelector('[name="user_name"]').value = data.user_name;
            document.querySelector('[name="user_email"]').value = data.user_email;
            document.querySelector('[name="user_telpon"]').value = data.user_telpon; 

            // status user (radio)
            if (data.status_user == "1") {
                document.getElementById("status1").checked = true;
            } else {
                document.getElementById("status0").checked = true;
            }

            // identitas
            document.querySelector('[name="user_identity"]').value = data.user_identity;

            // instansi
            if (data.user_identity === "NON ULM") {
                document.querySelector('#instansiField').style.display = "flex";
                document.querySelector('[name="user_instansi"]').value = data.user_instansi ?? "";
            } else {
                document.querySelector('#instansiField').style.display = "none";
                document.querySelector('[name="user_instansi"]').value = "";
            }

            // === Tambahan: bukti file ===
            const buktiWrapper = document.getElementById("buktiWrapper");
            const buktiInfo = document.getElementById("buktiInfo");
            if (data.bukti_file) {
                buktiInfo.innerHTML = `<a href="<?= base_url('uploads/bukti/') ?>${data.bukti_file}" target="_blank" class="btn btn-info btn-sm">Lihat Bukti</a>`;
            } else {
                buktiInfo.innerHTML = `<span class="text-danger">Silakan upload file</span>`;
            }

            // === Tambahan: verifikasi ===
            document.querySelector('[name="verifikasi"]').checked = (data.verifikasi == 1);

            // tampilkan modal
            var myModal = new bootstrap.Modal(document.getElementById('modalForm'));
            myModal.show();
        });
}

// === Hapus Data: langsung inline tanpa function deleteItem ===
document.addEventListener("click", function(e) {
    if (e.target.classList.contains("btn-delete")) {
        const id = e.target.closest("div").id;
        if (!id) return;

        if (confirm("Yakin ingin menghapus data ini?")) {
            showLoading();

            fetch("<?php echo site_url('akun/delete/') ?>" + id, {
                method: "DELETE",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(res => res.json())
            .then(data => {
                // update csrf
                if (data.xname && data.xhash) {
                    document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                        input.value = data.xhash;
                    });
                }

                if (data.res === true) {
                    if (typeof table !== 'undefined') table.fetchData({ reload: true });
                    sayAlert('successModal', 'Berhasil', 'Data berhasil dihapus.', 'success');
                } else {
                    sayAlert('errorModal', 'Gagal', 'Data gagal dihapus.', 'warning');
                }
            })
            .catch(error => {
                sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
            })
            .finally(() => {
                hideLoading();
            });
        }
    }
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
            <?php echo form_open_multipart('akun/submit', array('id'=>'myform', 'novalidate'=>'')) ?>
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
                            <input name="user_email" type="email" class="form-control" required>
                        </div>
                    </div>

                    <!-- Nomor Telepon -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Nomor Telepon</label>
                        <div class="col">
                            <input name="user_telpon" type="text" class="form-control">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Ubah Password</label>
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

                    <!-- Asal Instansi -->
                    <div class="row mb-2" id="instansiField" style="display: none;">
                        <label class="col-md-4 col-form-label">Asal Instansi</label>
                        <div class="col">
                            <input name="user_instansi" type="text" class="form-control">
                        </div>
                    </div>

                    <!-- Upload Bukti -->
                    <div class="row mb-2" id="buktiWrapper">
                        <label class="col-md-4 col-form-label">Bukti</label>
                        <div class="col">
                            <input type="file" name="bukti_file" class="form-control">
                            <div id="buktiInfo" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Verifikasi -->
                    <div class="row mb-2">
                        <label class="col-md-4 col-form-label">Terverifikasi</label>
                        <div class="col mt-2">
                            <input type="checkbox" name="verifikasi" value="1"> Sudah Diverifikasi
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

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header border-bottom pb-3">
                <div class="d-flex justify-content-between align-items-center mb-0">
                    <label class="card-title mb-0 fs-5 fw-bold"><?php echo $title ?></label>
                    <button id="add" class="btn btn-primary">
                        <i class="bi bi-plus-circle-dotted"></i> Tambah Pertanyaan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                         <tr>
                            <th show width="3%">No.</th> 
                            <th show width="60%">Pertanyaan</th>
                            <th show width="15%">Tipe</th>
                            <th show width="10%">Wajib</th>
                            <th show width="10%" class="action text-end">Aksi<i class="bi bi-code sort-icon"></i></th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Form Master Kuesioner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <?php echo form_open('kuesioner/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body p-4">
                
                <input type="hidden" value="" name="id" />

                <div class="mb-3">
                    <label class="form-label">Teks Pertanyaan</label>
                    <textarea name="pertanyaan_teks" class="form-control" rows="3" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tipe Pertanyaan</label>
                            <select name="pertanyaan_tipe" class="form-select" required>
                                <option value="isian">Isian Teks</option>
                                <option value="pilihan">Pilihan Ganda</option>
                                <option value="rating">Rating (Bintang)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                         <div class="mb-3">
                            <label class="form-label">Wajib Diisi</label>
                            <select name="pertanyaan_wajib" class="form-select" required>
                                <option value="1">Ya</option>
                                <option value="0">Tidak</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="wrapperOpsi" style="display: none;">
                    <label class="form-label">Opsi Jawaban (A-E)</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text" style="width: 40px;">A</span>
                        <input type="text" name="opsi_a" class="form-control" placeholder="Teks Opsi A">
                    </div>
                     <div class="input-group mb-2">
                        <span class="input-group-text" style="width: 40px;">B</span>
                        <input type="text" name="opsi_b" class="form-control" placeholder="Teks Opsi B">
                    </div>
                     <div class="input-group mb-2">
                        <span class="input-group-text" style="width: 40px;">C</span>
                        <input type="text" name="opsi_c" class="form-control" placeholder="Teks Opsi C">
                    </div>
                     <div class="input-group mb-2">
                        <span class="input-group-text" style="width: 40px;">D</span>
                        <input type="text" name="opsi_d" class="form-control" placeholder="Teks Opsi D">
                    </div>
                     <div class="input-group mb-3">
                        <span class="input-group-text" style="width: 40px;">E</span>
                        <input type="text" name="opsi_e" class="form-control" placeholder="Teks Opsi E">
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                <button class="btn btn-primary" id="btnSimpan" type="submit"><i class="bi bi-check2-circle"></i> Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>

<script>
    var apiUrl = '<?php echo site_url("kuesioner/datalist") ?>';
    var currentPage = 1;
    var currentLimit = 10;
    var kuesionerTable;

    function loadTable(url) {
        if (typeof createTable === 'function') {
            return createTable({
                tableId: 'data-table',
                apiUrl: url
            });
        }
        console.error('Fungsi createTable() tidak ditemukan.');
        return null;
    }

    // 1. Inisiasi tabel
    kuesionerTable = loadTable(apiUrl + "?page=" + currentPage + "&limit=" + currentLimit);

    // 2. Listener tombol Simpan
    const btnSimpan = document.querySelector('#btnSimpan');
    if (btnSimpan) {
        btnSimpan.addEventListener('click', function(e) {
            e.preventDefault();
            const form = document.querySelector('#myform');
            const actionUrl = form.getAttribute('action');

            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            requiredFields.forEach(field => {
                if (field.value.trim() === '') {
                    isValid = false;
                }
            });

            if (!isValid) {
                sayAlert('errorModal', 'Gagal', 'Teks Pertanyaan wajib diisi.', 'warning');
                return;
            }
            
            const tipe = form.querySelector('[name="pertanyaan_tipe"]').value;
            if (tipe === 'pilihan') {
                const opsiA = form.querySelector('[name="opsi_a"]').value.trim();
                const opsiB = form.querySelector('[name="opsi_b"]').value.trim();

                if (opsiA === '' || opsiB === '') {
                    sayAlert('errorModal', 'Gagal', 'Untuk Tipe Pilihan Ganda, minimal Opsi A dan Opsi B wajib diisi.', 'warning');
                    return; 
                }
            }

            const formData = new FormData(form);
            saveData({
                url: actionUrl,
                formData: formData,
                onSuccess: function(data) { 
                    if (data.res === true) {
                        if (typeof kuesionerTable !== 'undefined' && kuesionerTable) {
                            kuesionerTable.fetchData({ reload: true });
                        }
                        sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                        if ($('#modalForm').hasClass('show')) {
                            $('#modalForm').modal('hide');
                        }
                    } else if (data.res === false && data.msg) {
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

    // 3. Fungsi saveData 
    function saveData({ url, formData, onSuccess, onError }) {
        showLoading();
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(response => {
            if (!response.ok) { throw new Error('Network response was not ok: ' + response.statusText); }
            return response.json();
        })
        .then(data => {
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => { input.value = data.xhash; });
            }
            try {
                if (typeof onSuccess === 'function') { 
                    onSuccess(data);
                    return; 
                }
            } catch (e) {
                console.error("Error inside onSuccess callback:", e);
                if (typeof onError === 'function') { onError(e); }
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            try {
                if (typeof onError === 'function') { onError(error); }
            } catch (e) { console.error("Error inside onError callback:", e); }
        })
        .finally(() => {
            hideLoading();
        });
    }

    // 4. Fungsi Delete 
    function deleteItem(e) {
        const id = e.target.closest('div').id;
        
        sayConfirm('Konfirmasi Hapus', 'Apakah Anda yakin ingin menghapus data ini?', () => {
            showLoading();
            const deleteUrl = '<?php echo site_url("kuesioner/delete/") ?>' + id;
            
            fetch(deleteUrl)
                .then(res => res.json())
                .then(data => {
                    if (data.xname && data.xhash) {
                        document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                            input.value = data.xhash;
                        });
                    }
                    if (data.res === true) {
                        sayAlert('successModal', 'Berhasil', 'Data berhasil dihapus.', 'success');
                        if (typeof kuesionerTable !== 'undefined' && kuesionerTable) {
                            kuesionerTable.fetchData({ reload: true });
                        }
                    } else {
                        sayAlert('errorModal', 'Gagal', 'Data gagal dihapus.', 'warning');
                    }
                })
                .catch(err => {
                    sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem.', 'warning');
                    console.error("Delete fetch error:", err);
                })
                .finally(() => {
                    hideLoading();
                });
        });
    }

    // 5. Fungsi Edit
    function editItem(e) {
        const id = e.target.closest('div').id;
        fetch('<?php echo site_url("kuesioner/edit/") ?>' + id)
            .then(res => res.json())
            .then(data => {
                document.querySelector('[name="id"]').value = data.id;
                document.querySelector('[name="pertanyaan_teks"]').value = data.pertanyaan_teks;
                document.querySelector('[name="pertanyaan_wajib"]').value = data.pertanyaan_wajib;
                document.querySelector('[name="pertanyaan_tipe"]').value = data.pertanyaan_tipe;
                document.querySelector('[name="opsi_a"]').value = data.opsi_a;
                document.querySelector('[name="opsi_b"]').value = data.opsi_b;
                document.querySelector('[name="opsi_c"]').value = data.opsi_c;
                document.querySelector('[name="opsi_d"]').value = data.opsi_d;
                document.querySelector('[name="opsi_e"]').value = data.opsi_e;
                toggleOpsiWrapper(data.pertanyaan_tipe);
                $('#modalForm').modal('show');
            })
            .catch(err => {
                 sayAlert('errorModal', 'Error', 'Gagal memuat data untuk edit.', 'warning');
                 console.error("Edit fetch error:", err);
            });
    }

    // --- LOGIKA FORM (Variabel Unik) ---
    const kuesionerTipeSelect = document.querySelector('[name="pertanyaan_tipe"]'); 
    const kuesionerWrapperOpsi = document.getElementById('wrapperOpsi'); 

    function toggleOpsiWrapper(tipe) {
        if (kuesionerWrapperOpsi) {
            if (tipe === 'pilihan') {
                kuesionerWrapperOpsi.style.display = 'block';
            } else {
                kuesionerWrapperOpsi.style.display = 'none';
            }
        }
    }

    if (kuesionerTipeSelect) {
        kuesionerTipeSelect.addEventListener('change', function() {
            toggleOpsiWrapper(this.value);
        });
    }

    const addButton = document.querySelector('#add');
    if (addButton) {
        addButton.addEventListener('click', function() {
            document.querySelector('#myform').reset();
            document.querySelector('[name="id"]').value = "";
            if (kuesionerTipeSelect) {
                kuesionerTipeSelect.value = 'isian'; 
            }
            toggleOpsiWrapper('isian');
            $('#modalForm').modal('show');
        });
    }

    $('#modalForm').on('hidden.bs.modal', function () {
        toggleOpsiWrapper('isian');
    });

</script>
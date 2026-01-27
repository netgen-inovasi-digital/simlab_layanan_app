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

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
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
                                <option value="rating">Rating</option>
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
                    <label class="form-label">Opsi Jawaban</label>

                    <div id="dynamicOpsiContainer">
                    </div>

                    <div class="mt-2 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddOpsi">
                            <i class="bi bi-plus-circle"></i> Tambah Opsi Jawaban
                        </button>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i>
                    Batal</button>
                <button class="btn btn-primary" id="btnSimpan" type="submit"><i class="bi bi-check2-circle"></i>
                    Simpan</button>
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
    kuesionerTable = loadTable(apiUrl + "?init=1&page=" + currentPage + "&limit=" + currentLimit);

    // 2. Listener tombol Simpan
    var btnSimpan = document.querySelector('#btnSimpan');
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
                var allOpsiInputs = form.querySelectorAll('input[name="opsi[]"]');
                var filledOpsi = 0;
                allOpsiInputs.forEach(input => {
                    if (input.value.trim() !== '') {
                        filledOpsi++;
                    }
                });

                if (filledOpsi < 2) {
                    sayAlert('errorModal', 'Gagal', 'Untuk Tipe Pilihan Ganda, minimal 2 opsi jawaban wajib diisi.', 'warning');
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

                if (dynamicOpsiContainer) {
                    dynamicOpsiContainer.innerHTML = ''; 
                }

                if (data.pertanyaan_tipe === 'pilihan' && data.opsi_list && data.opsi_list.length > 0) {
                    let hasValidOpsi = false;
                    data.opsi_list.forEach(opsiValue => {
                        if (opsiValue.trim() !== '') {
                            tambahInputOpsi(opsiValue);
                            hasValidOpsi = true;
                        }
                    });
                    
                    if (!hasValidOpsi || dynamicOpsiContainer.children.length < 2) {
                        while(dynamicOpsiContainer.children.length < 2) {
                            tambahInputOpsi();
                        }
                    }
                } else {
                    resetOpsiContainer();
                }

                toggleOpsiWrapper(data.pertanyaan_tipe);
                $('#modalForm').modal('show');
            })
            .catch(err => {
                 sayAlert('errorModal', 'Error', 'Gagal memuat data untuk edit.', 'warning');
                 console.error("Edit fetch error:", err);
            });
    }

    var kuesionerTipeSelect = document.querySelector('[name="pertanyaan_tipe"]'); 
    var kuesionerWrapperOpsi = document.getElementById('wrapperOpsi'); 
    var dynamicOpsiContainer = document.getElementById('dynamicOpsiContainer');

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

    var addButton = document.querySelector('#add');
    if (addButton) {
        addButton.addEventListener('click', function() {
            document.querySelector('#myform').reset();
            document.querySelector('[name="id"]').value = "";
            if (kuesionerTipeSelect) {
                kuesionerTipeSelect.value = 'isian'; 
            }
            resetOpsiContainer();
            toggleOpsiWrapper('isian');
            $('#modalForm').modal('show');
        });
    }

    $('#modalForm').on('hidden.bs.modal', function () {
        resetOpsiContainer(); 
        toggleOpsiWrapper('isian');
    });

    function tambahInputOpsi(value = '') {
        if (!dynamicOpsiContainer) return;
        var placeholderText = 'Teks Opsi ' + (dynamicOpsiContainer.children.length + 1);

        var newOpsiHTML = `
            <div class="input-group mb-2 dynamic-opsi-item">
                <span class="input-group-text" style="cursor: grab;"><i class="bi bi-grip-vertical"></i></span>
                <input type="text" name="opsi[]" class="form-control" placeholder="${placeholderText}" value="${value}">
                
                <span class="input-group-text text-danger btn-remove-opsi" style="cursor: pointer;" title="Hapus Opsi">
                    <i class="bi bi-trash"></i>
                </span>
                </div>`;
        
        dynamicOpsiContainer.insertAdjacentHTML('beforeend', newOpsiHTML);
    }

    function resetOpsiContainer() {
        if (!dynamicOpsiContainer) return;
        dynamicOpsiContainer.innerHTML = ''; 
        tambahInputOpsi(); 
        tambahInputOpsi(); 
    }

    function updateOpsiPlaceholders() {
        if (!dynamicOpsiContainer) return;
        var allItems = dynamicOpsiContainer.querySelectorAll('.dynamic-opsi-item');
        allItems.forEach((item, index) => {
            var input = item.querySelector('input[name="opsi[]"]');
            if (input) {
                input.placeholder = 'Teks Opsi ' + (index + 1);
            }
        });
    }

    var btnAddOpsi = document.getElementById('btnAddOpsi');
    if (btnAddOpsi) {
        btnAddOpsi.addEventListener('click', function() {
            tambahInputOpsi();
        });
    }

    if (dynamicOpsiContainer) {
        dynamicOpsiContainer.addEventListener('click', function(e) {
            var removeButton = e.target.closest('.btn-remove-opsi');
            if (removeButton) {
                
                if (dynamicOpsiContainer.querySelectorAll('.dynamic-opsi-item').length <= 2) {
                    sayAlert('errorModal', 'Gagal', 'Minimal harus ada 2 opsi jawaban untuk Pilihan Ganda.', 'warning');
                    return;
                }
                
                removeButton.closest('.dynamic-opsi-item').remove();
                
                updateOpsiPlaceholders();
            }
        });
    }

    resetOpsiContainer();

</script>
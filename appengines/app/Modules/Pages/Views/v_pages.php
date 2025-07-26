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
                            <th show>Halaman</th>
                            <th class="text-center">Status</th>
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
    // ===== Classic Editor ===== //
    table = createTable({
        apiUrl: '<?php echo site_url("pages/datalist") ?>',
    });
    addAction();

    // ===== Inisialisasi quill editor ===== //
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Tulis konten di sini...',
        modules: {
            toolbar: [
                // [{
                //     'size': ['small', false, 'large', 'huge']
                // }],
                [{
                    'header': [1, 2, 3, 4, 5, 6, false]
                }],
                // ['bold', 'italic', 'underline', 'strike'],
                ['bold', 'italic', 'underline'],
                ['blockquote', 'code-block'],
                // [{
                //     'color': []
                // }, {
                //     'background': []
                // }],
                [{
                    'script': 'sub'
                }, {
                    'script': 'super'
                }],
                [{
                    'list': 'ordered'
                }, {
                    'list': 'bullet'
                }],
                [{
                    'indent': '-1'
                }, {
                    'indent': '+1'
                }],
                [{
                    'align': []
                }],
                ['link', 'image', 'video'],
                // ['clean']
            ],
            resize: {
                tools: [
                    'left', 'center', 'right',
                ]
            },
        }
    });

    // ===== edit Item with Quill Js ===== //
    function editItem(event) {
        const closest = event.target.closest('div');
        if (closest) {
            showLoading();
            const id = closest.getAttribute('id');
            const baseURL = window.location.href.split('/').slice(0, -1).join('/') + '/' + currentUrl;
            const url = `${baseURL}/edit/${id}`;

            fetch(url, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        $('.modal-title').text('Ubah Data');
                        $('#modalForm').modal('show');

                        Object.entries(data).forEach(([key, value]) => {
                            if (key === 'konten' && quill) {
                                quill.root.innerHTML = value || '';
                                return;
                            }
                            const elements = document.querySelectorAll(`[name="${key}"], [name="${key}[]"]`);
                            if (elements.length > 0) {
                                elements.forEach(el => {
                                    if (el.type === "checkbox") {
                                        el.checked = Array.isArray(value) ? value.includes(el.value) : (value == el.value || value == "1");
                                    } else if (el.type === "radio") {
                                        el.checked = (el.value == value);
                                    } else if (el.tagName === "SELECT") {
                                        el.value = value || "";
                                    } else {
                                        el.value = value || "";
                                    }
                                });
                            }
                        });
                        // toggleTanggalInput();
                    }
                })
                .catch(error => {
                    sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
                })
                .finally(() => {
                    setTimeout(() => {
                        hideLoading();
                    }, 300);
                });
        }
    }

    // ===== saat tombol simpan, pastikan quill editor dipindahkan ke textarea ===== //
    document.querySelector('#btnSimpan').addEventListener('click', function() {
        var isiKonten = quill.root.innerHTML.trim();
        document.querySelector('#konten').value = isiKonten;

        if (isiKonten === '' || isiKonten === '<p><br></p>') {
            sayAlert('errorModal', 'Error', 'Konten tidak boleh kosong.', 'warning');
            return;
        }
    })
    // ===== saat user mengklik tombol tambah, clear editor quill ===== //
    document.querySelector('#add').addEventListener('click', function() {
        // Kosongkan isi Quill editor
        quill.setContents([]);

        // Kosongkan juga isi textarea hidden (jaga-jaga)
        document.querySelector('#konten').value = '';
    })

    // ===== tambah foto untuk quill ===== //
    quill.getModule('toolbar').addHandler('image', () => {
        var input = document.createElement('input');
        input.setAttribute('type', 'file');
        input.setAttribute('accept', 'image/*');
        input.click();

        input.onchange = async () => {
            var file = input.files[0];
            if (file) {
                var formData = new FormData();
                formData.append('upload', file);

                saveData({
                    url: '<?= base_url('pages/upload') ?>',
                    formData: formData,
                    onSuccess: (json) => {
                        if (json && json.url) {
                            // Sisipkan gambar
                            const range = quill.getSelection();
                            quill.insertEmbed(range.index, 'image', json.url);

                            // Update token CSRF untuk upload berikutnya
                            if (json.xname && json.xhash) {
                                document.querySelectorAll('[name="' + json.xname + '"]').forEach(input => {
                                    input.value = json.xhash;
                                });
                            }
                        } else {
                            alert('Upload gagal');
                        }
                    },
                    onError: (err) => {
                        console.error('Upload error:', err);
                        alert('Upload error.');
                    }
                });
            }
        };
    });

    // ===== nama dan slug ===== //
    var namaInput = document.querySelector('input[name="nama"]');
    var slugInput = document.querySelector('input[name="slug"]');

    if (namaInput && slugInput) {
        namaInput.addEventListener('input', function() {
            var slug = namaInput.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            slugInput.value = slug;
        });
    }


    // ===== status dan tanggal publish ===== //
    // var statusDraft = document.getElementById('status-draft');
    // var statusPublish = document.getElementById('status-publish');
    // var tanggalInput = document.getElementById('tanggal-input');

    // function toggleTanggalInput() {
    //     if (statusPublish.checked) {
    //         tanggalInput.disabled = false;
    //     } else {
    //         tanggalInput.disabled = true;
    //     }
    // }

    // toggleTanggalInput();

    // statusDraft.addEventListener('change', toggleTanggalInput);
    // statusPublish.addEventListener('change', toggleTanggalInput);
</script>


<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin: 2% auto">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <?php echo form_open('pages/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body">
                <input type="hidden" value="" name="id" />
                <input name="slug" type="text" class="form-control bg-light" value="" hidden>
                <div class="row mb-2">
                    <div class="col">
                        <label class="col-md-3 col-form-label">Judul</label>
                        <input name="title" type="text" class="form-control" required placeholder="Masukkan judul halaman">
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col">
                        <label for="konten" class="col-md-3 col-form-label">Konten</label>
                        <div id="toolbar"></div>
                        <div id="quill-editor" style="height: 400px;"></div>
                        <textarea name="konten" id="konten" hidden placeholder="Masukkan konten halaman"></textarea>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col">
                        <label class="col-md-3 col-form-label">Tanggal</label>
                        <input name="tanggal" id="tanggal-input" type="date" class="form-control"
                            value="<?= esc(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col">
                        <label class="col-md-3 col-form-label">Author</label>
                        <input name="nama" type="text" value="<?= $user->nama ?>" class="form-control bg-light" required readonly>
                        <input name="user_id" type="text" value="<?= $user->id_user ?>" class="form-control" required hidden>
                    </div>
                    <div class="col">
                        <div class="mb-3">
                            <label class="form-label d-block">Status</label>
                            <div class="btn-group" role="group" aria-label="Status pilihan">
                                <input type="radio" class="btn-check" name="status" id="status-draft" value="draft" autocomplete="off" checked>
                                <label class="btn btn-outline-secondary me-1" for="status-draft">Draft</label>

                                <input type="radio" class="btn-check" name="status" id="status-publish" value="publish" autocomplete="off">
                                <label class="btn btn-outline-success" for="status-publish">Publish</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-2">

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
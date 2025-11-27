<!-- v_hasilPengujian.php -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>

                <!-- [ADDED] Filter Status (LnStatus) -->
                <div class="d-flex align-items-center" style="gap:8px;">
                    <label class="mb-0 small text-muted">Status:</label>
                    <select id="statusFilter" class="form-select form-select-sm" style="width:300px;">
                        <option value="">— Semua status —</option>
                        <option value="tolak">LHUS ditolak</option> <!-- [NEW] -->
                        <option value="4">Sedang dalam pengujian</option>
                        <option value="terunggah">LHUS terunggah (belum dikirim)</option> <!-- [NEW] -->
                        <option value="5">LHUS diverifikasi manajer (terkirim)</option>
                        <option value="6">LHUS disetujui</option>
                    </select>
                </div>
                <!-- [ADDED] end -->
            </div>
            <div class="card-body">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="25%">Pemesan</th> <!-- DITAMBAHKAN -->
                            <th show width="25%">Status Layanan</th>
                            <th show width="15%">Aksi layanan</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--  Modal Detail (tetap ada) -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document"
        style="max-width:1200px; margin: 1.5% auto;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Item Layanan</h5>
                <button id="btnSaveKomentar" type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Detail Item Layanan</h6>
                </div>

                <table id="tableDetail" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Layanan</th>
                            <th width="5%">Jumlah</th>
                            <th width="25%">Metode</th>
                            <th width="5%">Status File</th>
                            <th width="5%">LHUS</th>
                            <th width="20%">Keterangan Manajer</th>
                            <th width="5%">Acc Manajer</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <!-- Sample Identity Details Section -->
                <div class="detail-table mt-4" id="sampleIdentitySection" style="display:none;">
                    <h6 class="mb-3">Identitas Sampel:</h6>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">Jenis Sampel:</label>
                                    <p class="mb-0" id="sampleJenis">-</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">Kemasan Sampel:</label>
                                    <p class="mb-0" id="sampleKemasan">-</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">Sifat Sampel:</label>
                                    <p class="mb-0" id="sampleSifat">-</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-muted small">Sisa Sampel:</label>
                                    <p class="mb-0" id="sampleSisa">-</p>
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="fw-bold text-muted small">Deskripsi:</label>
                                    <div class="border rounded p-2"
                                        style="max-height: 160px; overflow-y: auto; background-color: #f8f9fa;">
                                        <p class="mb-0 text-wrap small" id="sampleDeskripsi">-</p>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="fw-bold text-muted small">Keterangan Khusus:</label>
                                    <div class="border rounded p-2"
                                        style="max-height: 160px; overflow-y: auto; background-color: #f8f9fa;">
                                        <p class="mb-0 text-wrap small" id="sampleKeteranganKhusus">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="modal-footer">
                            <button id="btnKirimDetail" class="btn btn-success" type="button"
                                title="Kirim semua item (approve)" disabled data-enc="">
                                <i class="bi bi-send"></i> Kirim
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- NOTE: modalUploadLhus tetap ada (tidak dipakai oleh default flow langsung-upload), disimpan untuk fallback -->
            <div class="modal fade" id="modalUploadLhus" data-bs-backdrop="static" data-bs-keyboard="false"
                tabindex="-1">
                <div class="modal-dialog modal-md" role="document" style="margin: 4% auto">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Unggah File LHUS</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div style="border-bottom:1px solid #e9ecef"></div>
                        <div class="modal-body">
                            <form id="formUploadLhus" action="<?php echo site_url('hasilpengujian/upload') ?>"
                                method="post" enctype="multipart/form-data" novalidate>
                                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                                <input type="hidden" name="id" id="upload_lhus_id" value="">
                                <input type="hidden" name="detKode" id="upload_detKode" value="">

                                <div class="mb-3">
                                    <label for="lhus_file" class="form-label">Pilih File (jpg, png, pdf, docx,
                                        xlsx)</label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="file" name="lhus_file" id="lhus_file" class="form-control"
                                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" style="max-width:360px">
                                        <button type="button" id="btnViewExistingLhus"
                                            class="btn btn-outline-primary btn-sm" title="Lihat Bukti" disabled>
                                            <span aria-hidden="true"></span> <span class="d-none d-sm-inline">Lihat
                                                Bukti</span>
                                        </button>
                                    </div>
                                    <div id="lhus-selection" class="form-text mt-2">Anda bisa unggah file baru untuk
                                        mengganti.
                                    </div>
                                    <div class="form-text text-muted">Ukuran maksimal 5MB.</div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <div class="text-start">
                                <button class="btn btn-light" type="button" id="btnCancelUpload"
                                    data-bs-dismiss="modal">Batal</button>
                            </div>
                            <div>
                                <button class="btn btn-primary" id="btnUploadLhus" type="button">Unggah</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Catatan Kaji Ulang -->
            <div class="modal fade" id="modalCatatanKajiUlang" tabindex="-1"
                aria-labelledby="modalCatatanKajiUlangLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="modalCatatanKajiUlangLabel">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>Catatan Kaji Ulang
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-muted small">Jumlah Kaji Ulang:</label>
                                <p class="mb-0" id="jumlahKajiUlang">-</p>
                            </div>
                            <div>
                                <label class="form-label fw-bold text-muted small">Catatan:</label>
                                <div class="border rounded p-3"
                                    style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                                    <p class="mb-0 text-wrap" id="catatanKajiUlangContent"
                                        style="white-space: pre-wrap;">-</p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                table = createTable({
                    apiUrl: '<?php echo site_url("hasilpengujian/datalist") ?>',
                    dataSrc: 'items'
                });
                addAction();

                // ============================================================
                // EVENT DELEGATION UNTUK BADGE UJI ULANG
                // Menggunakan onclick langsung di element untuk menghindari duplikat listener
                // ============================================================

                // ============================================================
                // FUNGSI UNTUK MENAMPILKAN MODAL CATATAN KAJI ULANG
                // ============================================================
                function showCatatanKajiUlang(encId) {
                    const url = '<?php echo site_url("hasilpengujian/getCatatanKajiUlang/") ?>' + encId;

                    // Hapus semua backdrop yang mungkin tertinggal
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

                    // Reset body class jika ada modal yang tidak tertutup dengan benar
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');

                    const modalElement = document.getElementById('modalCatatanKajiUlang');
                    if (!modalElement) {
                        alert('Modal tidak ditemukan');
                        return;
                    }

                    // Pindahkan modal ke body jika belum di body (untuk menghindari masalah stacking context)
                    if (modalElement.parentElement !== document.body) {
                        document.body.appendChild(modalElement);
                    }

                    // Set loading state
                    document.getElementById('jumlahKajiUlang').textContent = 'Memuat...';
                    document.getElementById('catatanKajiUlangContent').textContent = 'Memuat...';

                    // Dispose existing modal instance if any to prevent duplicates
                    let modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) {
                        modalInstance.dispose();
                    }

                    // Create fresh modal instance and show
                    modalInstance = new bootstrap.Modal(modalElement);
                    modalInstance.show();

                    // Fetch data
                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('jumlahKajiUlang').textContent = data.data.jumlah_kaji_ulang + ' kali';
                                document.getElementById('catatanKajiUlangContent').textContent = data.data.catatan_kaji_ulang || '-';
                            } else {
                                document.getElementById('jumlahKajiUlang').textContent = '-';
                                document.getElementById('catatanKajiUlangContent').textContent = data.message || 'Gagal memuat data';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            document.getElementById('jumlahKajiUlang').textContent = '-';
                            document.getElementById('catatanKajiUlangContent').textContent = 'Terjadi kesalahan saat memuat data';
                        });
                }



                document.querySelector('#btnSimpan')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    const form = document.querySelector('#myform');
                    const formData = new FormData(form);
                    const actionUrl = form.getAttribute('action');
                    saveData({
                        url: actionUrl,
                        formData: formData,
                        onSuccess: function (data) {
                            if (data.res === true) {
                                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                                sayAlert('successModal', 'Berhasil', 'Data berhasil disimpan.', 'success');
                                if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
                            }
                        }
                    });
                });

                /* ---------------- existing helper functions left unchanged (saveData, confirmApprove, deleteItem, loadDetail) ---------------- */
                function saveData({ url, formData, onSuccess, onError }) {
                    showLoading();
                    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
                    const csrfToken = csrfInput ? csrfInput.value : '';
                    fetch(url, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': csrfToken } })
                        .then(response => response.json())
                        .then(data => {
                            if (data.xname && data.xhash) {
                                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                                    input.value = data.xhash;
                                });
                            }
                            if (typeof onSuccess === 'function') { onSuccess(data); return; }
                            if ($('#modalForm').hasClass('show')) $('#modalForm').modal('hide');
                            if (data.res === true) {
                                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                                sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
                            } else if (data.res === 'reload' || data.res === 'refresh') {
                                sayAlert('errorModal', 'Error', data.link, 'warning');
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
                            if (typeof onError === 'function') { onError(error); }
                            else sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
                        })
                        .finally(() => { hideLoading(); });
                }

                //  function confirmApprove(e) { ... } (tetap dikomentari)

                // ============================================================
                // LOAD DETAIL LAYANAN (MODAL)
                // ============================================================
                var cachedSampleData = {}; // Cache untuk identitas sampel

                function setDetailTableMessage(message) {
                    const tbody = document.querySelector('#tableDetail tbody');
                    if (!tbody) return;

                    const colCount = document.querySelectorAll('#tableDetail thead th').length || 1;
                    const tr = document.createElement('tr');
                    const td = document.createElement('td');
                    td.colSpan = colCount;
                    td.className = 'text-center text-muted py-3';
                    td.textContent = message;
                    tr.appendChild(td);
                    tbody.innerHTML = '';
                    tbody.appendChild(tr);
                }

                function renderDetailTableRows(items) {
                    const tbody = document.querySelector('#tableDetail tbody');
                    if (!tbody) return;

                    if (!Array.isArray(items) || items.length === 0) {
                        setDetailTableMessage('Belum ada item layanan.');
                        return;
                    }

                    const fragment = document.createDocumentFragment();
                    items.forEach((row) => {
                        const tr = document.createElement('tr');
                        (row || []).forEach((col) => {
                            const td = document.createElement('td');
                            td.innerHTML = col ?? '';
                            tr.appendChild(td);
                        });
                        fragment.appendChild(tr);
                    });

                    tbody.innerHTML = '';
                    tbody.appendChild(fragment);
                    attachUploaderTriggers();
                }

                function loadSampleIdentity(lnKode) {
                    const sampleSection = document.getElementById('sampleIdentitySection');

                    if (!lnKode) {
                        if (sampleSection) sampleSection.style.display = 'none';
                        return;
                    }

                    // Cek apakah data sudah di-cache
                    if (cachedSampleData[lnKode]) {
                        // Gunakan data dari cache
                        const data = cachedSampleData[lnKode];
                        document.getElementById('sampleJenis').textContent = data.jenis || '-';
                        document.getElementById('sampleKemasan').textContent = data.kemasan || '-';
                        document.getElementById('sampleSifat').textContent = data.sifat || '-';
                        document.getElementById('sampleSisa').textContent = data.sisa || '-';
                        document.getElementById('sampleDeskripsi').textContent = data.deskripsi || '-';
                        document.getElementById('sampleKeteranganKhusus').textContent = data.keterangan_khusus || '-';
                        sampleSection.style.display = 'block';
                    } else {
                        // Fetch data baru dari server
                        fetch(`<?php echo site_url("hasilpengujian/getSampleIdentity/") ?>${lnKode}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.data) {
                                    // Simpan ke cache
                                    cachedSampleData[lnKode] = data.data;

                                    // Populate sample identity fields
                                    document.getElementById('sampleJenis').textContent = data.data.jenis || '-';
                                    document.getElementById('sampleKemasan').textContent = data.data.kemasan || '-';
                                    document.getElementById('sampleSifat').textContent = data.data.sifat || '-';
                                    document.getElementById('sampleSisa').textContent = data.data.sisa || '-';
                                    document.getElementById('sampleDeskripsi').textContent = data.data.deskripsi || '-';
                                    document.getElementById('sampleKeteranganKhusus').textContent = data.data.keterangan_khusus || '-';
                                    sampleSection.style.display = 'block';
                                } else {
                                    // Jika tidak ada data, sembunyikan section
                                    sampleSection.style.display = 'none';
                                }
                            })
                            .catch(error => {
                                console.error('Error loading sample identity:', error);
                                sampleSection.style.display = 'none';
                            });
                    }
                }

                async function loadDetail(id, lnKode) {
                    console.log('loadDetail called with id:', id, 'lnKode:', lnKode);

                    const modalElement = document.getElementById('modalDetail');
                    if (modalElement) {
                        try {
                            const existing = bootstrap.Modal && bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(modalElement) : null;
                            const isShown = modalElement.classList.contains('show') || (existing && typeof existing._isShown !== 'undefined' && existing._isShown);
                            if (!isShown) {
                                const modal = existing || new bootstrap.Modal(modalElement);
                                modal.show();
                            }
                        } catch (e) {
                            if (typeof $ !== 'undefined' && !$('#modalDetail').hasClass('show')) $('#modalDetail').modal('show');
                        }
                    }

                    const modalDetailEl = document.getElementById('modalDetail');
                    if (modalDetailEl) {
                        modalDetailEl.dataset.encLn = id;
                        if (lnKode) {
                            modalDetailEl.dataset.lnKode = lnKode;
                        } else {
                            lnKode = modalDetailEl.dataset.lnKode || '';
                        }
                    }

                    setDetailTableMessage('Memuat data item...');

                    const detailUrl = `<?php echo site_url("hasilpengujian/detailList/") ?>${id}?_ts=${Date.now()}`;

                    try {
                        const response = await fetch(detailUrl);
                        const data = await response.json();

                        renderDetailTableRows(data.items || []);

                        const btnKirim = document.getElementById('btnKirimDetail');
                        if (btnKirim) {
                            if (data.encLn) {
                                btnKirim.setAttribute('data-enc', data.encLn);
                            }

                            if (data.allFilesUploaded === true) {
                                btnKirim.removeAttribute('disabled');
                                btnKirim.classList.remove('disabled');
                            } else {
                                btnKirim.setAttribute('disabled', 'disabled');
                                btnKirim.classList.add('disabled');
                            }
                        }

                        const effectiveLnKode = data.lnKode || lnKode || (modalDetailEl ? modalDetailEl.dataset.lnKode : '');
                        loadSampleIdentity(effectiveLnKode);
                    } catch (error) {
                        console.error('Error loading detail data:', error);
                        setDetailTableMessage('Gagal memuat data. Silakan coba lagi.');

                        const btnKirim = document.getElementById('btnKirimDetail');
                        if (btnKirim) {
                            btnKirim.setAttribute('disabled', 'disabled');
                            btnKirim.classList.add('disabled');
                        }
                    }
                }

                // Event delegation untuk button kirim (dipasang di document level)
                document.addEventListener('click', function (e) {
                    if (e.target && e.target.id === 'btnKirimDetail') {
                        e.preventDefault();
                        const encId = e.target.getAttribute('data-enc') || '';
                        if (encId && !e.target.hasAttribute('disabled')) {
                            doSendLhus(encId);
                        } else if (!encId) {
                            sayAlert('errorModal', 'Error', 'ID layanan tidak ditemukan', 'warning');
                        }
                    }
                });
            </script>

            <script>
                /* [ADDED] Helpers agar URL filter bersih & fetch cache-busted */
                if (typeof window.buildApiUrlWithOptionalParam !== 'function') {
                    function buildApiUrlWithOptionalParam(path, key, value) {
                        try {
                            const u = new URL(path, window.location.origin);
                            const params = new URLSearchParams(u.search);
                            if (key && String(key) !== '') {
                                if (typeof value !== 'undefined' && value !== null && String(value) !== '') {
                                    params.set(key, String(value));
                                } else {
                                    params.delete(key);
                                }
                            }
                            const s = params.toString();
                            return u.pathname + (s ? '?' + s : '');
                        } catch (e) {
                            if (key && String(key) !== '' && value !== null && String(value) !== '') {
                                return path + (path.includes('?') ? '&' : '?') + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
                            }
                            return path;
                        }
                    }
                }
                if (typeof window.normalizeDoubleQuestion !== 'function') {
                    function normalizeDoubleQuestion(url) {
                        if (typeof url !== 'string') return url;
                        url = url.replace(/\?([^?]*)\?/, '?$1&');
                        url = url.replace(/&{2,}/g, '&');
                        url = url.replace(/\?&/, '?');
                        if (url.endsWith('&')) url = url.slice(0, -1);
                        return url;
                    }
                }

                /* [ADDED] Patch fetchData agar tambahkan cache-buster */
                (function patchFetchData() {
                    if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function' && !table.__fetchPatchedHP) {
                        const _origFetch = table.fetchData.bind(table);
                        var _currentAbort = null;
                        table.fetchData = function (opts = {}) {
                            try {
                                const cfg = table.getConfig();
                                if (cfg && typeof cfg.apiUrl === 'string') {
                                    const u = new URL(cfg.apiUrl, window.location.origin);
                                    u.searchParams.set('_ts', Date.now().toString());
                                    cfg.apiUrl = normalizeDoubleQuestion(u.pathname + (u.search ? u.search : ''));
                                }
                            } catch (err) { }
                            try { if (_currentAbort) _currentAbort.abort(); } catch (e) { }
                            try { _currentAbort = new AbortController(); opts.signal = _currentAbort.signal; } catch (e) { }
                            return _origFetch(opts);
                        };
                        table.__fetchPatchedHP = true;
                    }
                })();

                /* [ADDED] Wiring dropdown Status → param lnStatus */
                (function attachStatusFilter() {
                    const sel = document.getElementById('statusFilter');
                    if (!sel || sel.dataset.bound === '1') return;
                    sel.addEventListener('change', function () {
                        const val = (this.value || '').toString().trim();
                        if (table?.getConfig) {
                            const cfg = table.getConfig();
                            cfg.apiUrl = normalizeDoubleQuestion(
                                buildApiUrlWithOptionalParam('<?php echo site_url("hasilpengujian/datalist") ?>', 'lnStatus', (val === '' ? null : val))
                            );
                            table.fetchData({ reload: true, page: 1 });
                        }
                    });
                    sel.dataset.bound = '1';
                })();
            </script>

            <script>
                /**
                 * autoUploadFile(input)
                 * - Input element must have attributes:
                 *    data-ln = encrypted ln (hex)
                 *    data-detlist = comma separated detKode(s) OR empty
                 * Behavior: upload file via fetch to hasilpengujian/upload and update UI inline.
                 */
                async function autoUploadFile(input) {
                    if (!input || !input.files || input.files.length === 0) return;
                    const file = input.files[0];
                    const name = file.name || '';
                    const ext = name.split('.').pop().toLowerCase();
                    const allowedExt = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                    const maxSize = 5 * 1024 * 1024;
                    if (!allowedExt.includes(ext)) { alert('Format file tidak diperbolehkan.'); input.value = ''; return; }
                    if (file.size > maxSize) { alert('Ukuran file maksimal 5MB.'); input.value = ''; return; }

                    const encLn = input.getAttribute('data-ln') || '';
                    const detlist = input.getAttribute('data-detlist') || '';
                    const detCodes = detlist.split(',').map(s => s.trim()).filter(Boolean);
                    var detKodeToSend = '';
                    if (detCodes.length === 1) detKodeToSend = detCodes[0];

                    const fd = new FormData();
                    fd.append('lhus_file', file, file.name);
                    fd.append('id', encLn);
                    if (detKodeToSend) fd.append('detKode', detKodeToSend);

                    // attach CSRF if present in DOM as hidden input (common CI pattern)
                    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
                    if (csrfInput) fd.append(csrfInput.name, csrfInput.value);

                    // UI: set uploading state on nearest button (if any)
                    const parent = input.parentElement;
                    const btn = parent ? parent.querySelector('button') : null;
                    const originalHtml = btn ? btn.innerHTML : null;
                    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengunggah...'; }

                    try {
                        const res = await fetch('<?php echo site_url("hasilpengujian/upload") ?>', {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin'
                        });
                        const json = await res.json();
                        if (json.xname && json.xhash) {
                            document.querySelectorAll('[name="' + json.xname + '"]').forEach(i => i.value = json.xhash);
                        }

                        if (json && (json.res === true || json.res === 'true')) {
                            const fileUrl = json.url || null;

                            // Update tombol lihat file di kolom LHUS
                            const tr = input.closest('tr');
                            if (tr && fileUrl) {
                                // Cari tombol eye (lihat file) di row yang sama
                                const eyeIcon = tr.querySelector('.bi-eye');
                                if (eyeIcon && eyeIcon.parentElement) {
                                    const eyeSpan = eyeIcon.parentElement;
                                    // Update tombol eye menjadi aktif dengan URL file
                                    eyeSpan.className = 'text-primary btn-action';
                                    eyeSpan.title = 'Lihat File';
                                    eyeSpan.setAttribute('onclick', 'window.open(\'' + fileUrl + '\', \'_blank\')');
                                    eyeSpan.style.cursor = 'pointer';
                                }

                                // Update status badge ke "lhus ter-unggah"
                                const statusCell = tr.querySelector('td:nth-child(5)'); // kolom Status File
                                if (statusCell) {
                                    statusCell.innerHTML = '<div class="text-center"><span class="badge bg-info">lhus ter-unggah</span></div>';
                                }
                            }

                            // Reset input file
                            input.value = '';

                            if (typeof sayAlert === 'function') {
                                sayAlert('successModal', 'Berhasil', json.msg || 'File berhasil diunggah.', 'success');
                                // Reload detail untuk memastikan semua data terupdate
                                setTimeout(() => {
                                    loadDetail(encLn);
                                }, 500);
                            } else {
                                alert(json.msg || 'File berhasil diunggah.');
                            }
                        } else {
                            const message = (json && json.msg) ? json.msg : 'Gagal mengunggah file.';
                            if (typeof sayAlert === 'function') sayAlert('errorModal', 'Gagal', message, 'warning'); else alert(message);
                            input.value = '';
                        }
                    } catch (err) {
                        console.error(err);
                        if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat mengunggah file.', 'warning'); else alert('Terjadi kesalahan saat mengunggah file.');
                        input.value = '';
                    }
                }

                /* Utility: attach click-to-open file input for rows created by server data.
                   Server will render input.lhus-uploader-input inside LHUS column - see controller detailList() output.
                   If table is re-rendered, ensure addAction() (existing) runs again or call attachUploaderTriggers().
                */
                function attachUploaderTriggers() {
                    document.querySelectorAll('input.lhus-uploader-input').forEach(function (inp) {
                        // ensure event only once
                        if (!inp.dataset._hasAutoUpload) {
                            inp.dataset._hasAutoUpload = '1';
                            inp.addEventListener('change', function () { autoUploadFile(inp); });
                        }
                    });
                }

                // initial attach (if table loads elements on render)
                attachUploaderTriggers();

                // Re-attach after table fetches new data (if your createTable calls addAction or trigger event, ensure attachUploaderTriggers runs)
                if (typeof table !== 'undefined' && table.on) {
                    table.on('draw', attachUploaderTriggers); // if createTable exposes events
                }
            </script>

            <script>
                document.addEventListener('click', function (ev) {
                    const target = ev.target;
                    const btn = target.closest ? target.closest('#btnViewExistingLhus') : null;
                    if (!btn) return;
                    const url = btn.getAttribute('data-url') || btn.dataset.url || null;
                    if (url && url !== '#' && url !== '') {
                        const w = window.open('', '_blank');
                        if (w) {
                            try { w.opener = null; w.location = url; } catch (err) { window.open(url, '_blank'); }
                        } else { window.open(url, '_blank'); }
                    } else {
                        sayAlert('errorModal', 'Info', 'Tidak ada file bukti.', 'warning');
                    }
                });

                (function () {
                    const fi = document.getElementById('lhus_file');
                    const sel = document.getElementById('lhus-selection');
                    if (!fi) return;
                    fi.addEventListener('change', function (e) {
                        const f = e.target.files && e.target.files[0];
                        if (f) {
                            if (sel) sel.textContent = 'Anda memilih: ' + f.name;
                            const viewBtn = document.getElementById('btnViewExistingLhus');
                            if (viewBtn) viewBtn.setAttribute('disabled', 'disabled');
                        } else {
                            if (sel) sel.textContent = 'Anda bisa unggah file baru untuk mengganti.';
                        }
                    });
                })();

                document.getElementById('btnUploadLhus')?.addEventListener('click', function (e) {
                    e.preventDefault();
                    const form = document.getElementById('formUploadLhus');
                    const formData = new FormData(form);
                    const url = form.getAttribute('action');
                    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
                    const csrfToken = csrfInput ? csrfInput.value : '';

                    if (!formData.get('lhus_file') || formData.get('lhus_file').size === 0) {
                        sayAlert('errorModal', 'Error', 'Pilih file terlebih dahulu.', 'warning');
                        return;
                    }

                    showLoading();

                    fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-CSRF-TOKEN': csrfToken }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.xname && data.xhash) {
                                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                            }
                            if (data.res === true) {
                                sayAlert('successModal', 'Berhasil', data.msg || 'File berhasil diunggah.', 'success');
                                $('#modalUploadLhus').modal('hide');
                                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                            } else {
                                sayAlert('errorModal', 'Gagal', data.msg || 'Upload gagal.', 'warning');
                            }
                        })
                        .catch(err => { console.error(err); sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat upload.', 'warning'); })
                        .finally(() => { hideLoading(); });
                });

                function doSendLhus(encId) {
                    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
                    const csrfToken = csrfInput ? csrfInput.value : '';
                    // disable send button segera
                    const btnKirim = document.getElementById('btnKirimDetail');
                    if (btnKirim) {
                        btnKirim.setAttribute('disabled', 'disabled');
                    }
                    showLoading();

                    if (!encId || encId === '') {
                        hideLoading();
                        if (btnKirim) btnKirim.removeAttribute('disabled');
                        sayAlert('errorModal', 'Error', 'ID tidak ditemukan.', 'warning');
                        return;
                    }

                    const body = new URLSearchParams();
                    body.append('id', encId);

                    fetch('<?php echo site_url("hasilpengujian/submit") ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: body.toString()
                    })
                        .then(res => {
                            if (!res.ok) return res.text().then(t => { throw new Error('HTTP ' + res.status + ': ' + t); });
                            return res.json();
                        })
                        .then(data => {
                            if (data.xname && data.xhash) {
                                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
                            }

                            // 1) TUTUP modal detail segera (sebelum notifikasi)
                            try {
                                const modalEl = document.getElementById('modalDetail');
                                if (modalEl) {
                                    // Bootstrap 5 preferred
                                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                        const inst = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                                        try { inst.hide(); } catch (e) { /* ignore */ }
                                    } else if (typeof $ !== 'undefined') {
                                        // jQuery fallback
                                        try { $('#modalDetail').modal('hide'); } catch (e) { }
                                    }
                                }
                            } catch (e) {
                                console.warn('hide modal error', e);
                            }

                            // 2) Tampilkan notifikasi
                            if (data.res === true) {
                                if (typeof sayAlert === 'function') {
                                    sayAlert('successModal', 'Berhasil', data.msg || 'File berhasil dikirim.', 'success');
                                } else {
                                    alert(data.msg || 'File berhasil dikirim.');
                                }

                                // 3) refresh tabel sedikit setelah notifikasi ditampilkan
                                const REFRESH_DELAY = 300; // ms - sesuaikan jika perlu
                                setTimeout(function () {
                                    try {
                                        if (typeof table !== 'undefined' && typeof table.fetchData === 'function') {
                                            table.fetchData({ reload: true });
                                        }
                                    } catch (e) { console.warn('table.fetchData error', e); }
                                }, REFRESH_DELAY);

                            } else {
                                if (typeof sayAlert === 'function') sayAlert('errorModal', 'Gagal', data.msg || 'Kirim gagal.', 'warning');
                                else alert(data.msg || 'Kirim gagal.');
                                // jika gagal, kita bisa buka kembali modal (opsional) — di sini biarkan tertutup
                            }
                        })
                        .catch(err => {
                            console.error('doSendLhus error:', err);
                            if (typeof sayAlert === 'function') sayAlert('errorModal', 'Error', 'Terjadi kesalahan saat mengirim LHUS.', 'warning');
                            else alert('Terjadi kesalahan saat mengirim LHUS.');
                        })
                        .finally(() => {
                            hideLoading();
                            if (btnKirim) btnKirim.removeAttribute('disabled');
                        });
                }

                // override confirmApprove to support confirmApprove(event, encId)
                if (typeof window.confirmApprove === 'function') {
                    window._orig_confirmApprove = window.confirmApprove;
                }
                window.confirmApprove = function (e, encId) {
                    if (typeof encId !== 'undefined' && encId) {
                        e.preventDefault();
                        return sayConfirm(
                            'Konfirmasi',
                            'Yakin ingin mengirim file LHUS untuk data ini?',
                            () => { doSendLhus(encId); },
                            'success',
                            'Kirim',
                            'Batal'
                        );
                    }
                    if (typeof window._orig_confirmApprove === 'function') {
                        return window._orig_confirmApprove(e);
                    }
                    try {
                        e.preventDefault();
                        var id = e.currentTarget && e.currentTarget.closest ? e.currentTarget.closest('div').id : null;
                        if (!id) return;
                        return sayConfirm(
                            'Konfirmasi',
                            'Yakin ingin mengirim file LHUS untuk data ini?',
                            () => { doSendLhus(id); },
                            'success',
                            'Kirim',
                            'Batal'
                        );
                    } catch (err) { console.warn('confirmApprove fallback error:', err); }
                    return;
                };
            </script>
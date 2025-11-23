<div class="row"> 
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <!-- Kategori Status (LnStatus) -->
                <div class="d-flex align-items-center" style="gap:8px;">
                    <label class="mb-0 small text-muted">Status:</label>
                    <select id="statusFilter" class="form-select form-select-sm" style="width:240px;">
                        <option value="">— Semua status —</option>
                        <option value="1">Layanan belum direview</option>
                        <option value="3">Layanan terkirim ke admin</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="8%">No.</th>
                            <th show width="35%">Pemesan</th>
                            <th show width="25%">Status layanan</th>
                            <th show width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="modalDetail" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Review Layanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Detail Item Layanan</h6>
                </div>

                <table id="tableDetail" class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Layanan</th>
                            <th width="8%">Jumlah</th>
                            <th width="25%">Metode</th>
                            <th width="10%">Status</th>
                            <th width="20%">Berikan keterangan</th>
                            <th width="12%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <!-- Sample Identity Details Section - DIPINDAHKAN KE BAWAH -->
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
                                    <div class="border rounded p-2" style="max-height: 160px; overflow-y: auto; background-color: #f8f9fa;">
                                        <p class="mb-0 text-wrap small" id="sampleDeskripsi">-</p>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="fw-bold text-muted small">Keterangan Khusus:</label>
                                    <div class="border rounded p-2" style="max-height: 160px; overflow-y: auto; background-color: #f8f9fa;">
                                        <p class="mb-0 text-wrap small" id="sampleKeteranganKhusus">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    // ============================================================
    // CREATE MODAL WRAPPER (untuk isolasi tabel di dalam modal)
    // ============================================================
    function createModal(customConfig = {}) {
        // Gunakan createTable1 untuk isolasi tabel modal
        if (typeof createTable1 === 'function') {
            return createTable1(customConfig);
        } else {
            console.warn('createTable1 tidak ditemukan, fallback ke createTable');
            return createTable(customConfig);
        }
    }

    // ============================================================
    // HELPERS URL
    // ============================================================
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
        } catch(e) {
            if (key && String(key) !== '' && value !== null && String(value) !== '') {
                return path + (path.includes('?') ? '&' : '?') + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
            }
            return path;
        }
    }

    function normalizeDoubleQuestion(url) {
        if (typeof url !== 'string') return url;
        url = url.replace(/\?([^?]*)\?/, '?$1&');
        url = url.replace(/&{2,}/g, '&');
        url = url.replace(/\?&/, '?');
        if (url.endsWith('&')) url = url.slice(0, -1);
        return url;
    }

    // ============================================================
    // CSRF UTILITY
    // ============================================================
    function _getCsrf() {
        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        return csrfInput ? csrfInput.value : '';
    }

    // ============================================================
    // TABEL UTAMA
    // ============================================================
    table = createTable({
        apiUrl: '<?php echo site_url("kajiulang/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    // Patch normalize apiUrl saat reload
    if (typeof table !== 'undefined' && table && typeof table.getConfig === 'function' && typeof table.fetchData === 'function' && !table.__fetchPatched) {
        const _origFetch = table.fetchData.bind(table);
        let _currentAbort = null;
        table.fetchData = function(opts = {}) {
            try {
                const cfg = table.getConfig();
                if (cfg && typeof cfg.apiUrl === 'string') {
                    const u = new URL(cfg.apiUrl, window.location.origin);
                    u.searchParams.set('_ts', Date.now().toString()); // cache-buster
                    cfg.apiUrl = normalizeDoubleQuestion(u.pathname + (u.search ? u.search : ''));
                }
            } catch (err) {}
            try { if (_currentAbort) _currentAbort.abort(); } catch(e){}
            try {
                _currentAbort = new AbortController();
                opts.signal = _currentAbort.signal;
            } catch(e){}
            return _origFetch(opts);
        };
        table.__fetchPatched = true;
    }

    // ============================================================
    // FILTER STATUS DROPDOWN
    // ============================================================
    (function attachStatusFilter(){
        const sel = document.getElementById('statusFilter');
        if (!sel || sel.dataset.bound === '1') return;
        sel.addEventListener('change', function(){
            const val = (this.value || '').toString().trim();
            if (table?.getConfig) {
                const cfg = table.getConfig();
                cfg.apiUrl = normalizeDoubleQuestion(
                    buildApiUrlWithOptionalParam('<?= site_url("kajiulang/datalist") ?>', 'lnStatus', (val === '' ? null : val))
                );
                table.fetchData({ reload: true, page: 1 });
            }
        });
        sel.dataset.bound = '1';
    })();

    // ============================================================
    // BOOTSTRAP MODAL INSTANCE
    // ============================================================
    window._modalDetailEl = window._modalDetailEl || document.getElementById('modalDetail');
    if (typeof window._modalDetailInstance === 'undefined' || window._modalDetailInstance === null) {
        try {
            window._modalDetailInstance = window._modalDetailEl ? new bootstrap.Modal(window._modalDetailEl) : null;
        } catch (err) {
            window._modalDetailInstance = null;
        }
    }

    // ============================================================
    // SAVE KOMENTAR ASYNC
    // ============================================================
    async function saveKomentarAsync() {
        const modalEl = document.getElementById('modalDetail');
        if (!modalEl) return { ok: false, msg: 'Modal tidak ditemukan' };

        // Ambil encLn dari modal dataset
        let encLn = modalEl.dataset.encLn || null;
        if (!encLn) {
            console.warn('LN tidak ditemukan untuk menyimpan komentar');
            return { ok: false, msg: 'LN tidak ditemukan' };
        }

        // Kumpulkan textarea/input komentar dalam modal (kelas .komentar-input)
        const inputs = modalEl.querySelectorAll('.komentar-input');
        const items = [];
        inputs.forEach(function(inp) {
            const detail = inp.getAttribute('data-detail');
            const val = inp.value;
            if (detail !== null && detail !== '') {
                items.push({ detailKode: parseInt(detail, 10), komentar: val });
            }
        });

        if (items.length === 0) {
            // Tidak ada yang disimpan
            return { ok: true, skipped: true };
        }

        const csrfToken = _getCsrf();

        try {
            const res = await fetch('<?php echo site_url("kajiulang/savekomentar") ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lnId: encLn,
                    items: items
                })
            });

            const data = await res.json();

            // Update token bila dikembalikan
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }

            if (data.res) {
                // Reload data silent jika perlu
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
                return { ok: true, data: data };
            } else {
                console.warn('Gagal menyimpan komentar:', data.msg || null);
                return { ok: false, data: data };
            }
        } catch (err) {
            console.error('Error saat menyimpan komentar:', err);
            return { ok: false, error: err };
        }
    }

    // ============================================================
    // AUTO-SAVE KOMENTAR SAAT MODAL DITUTUP
    // ============================================================
    document.addEventListener('click', function(e) {
        // Auto-save saat klik tombol close (X) atau tombol "Tutup"
        if (e.target.matches('[data-bs-dismiss="modal"]') || e.target.closest('[data-bs-dismiss="modal"]')) {
            e.preventDefault();
            // Simpan komentar terlebih dahulu sebelum tutup modal
            saveKomentarAsync().then(() => {
                // Setelah simpan selesai, baru tutup modal
                if (_modalDetailInstance) _modalDetailInstance.hide();
                else if (typeof $ === 'function') $('#modalDetail').modal('hide');
            });
            return;
        }
    });

    // Event listener untuk modal hide (backup untuk auto-save)
    document.addEventListener('hide.bs.modal', function(e) {
        if (e.target.id === 'modalDetail') {
            // Pastikan komentar tersimpan saat modal ditutup
            saveKomentarAsync().catch(err => console.warn('Auto-save komentar gagal:', err));
        }
    });

    // ============================================================
    // LOAD DETAIL LAYANAN (MODAL)
    // ============================================================
    let trackingDetailTable;
    let cachedSampleData = {}; // Cache untuk identitas sampel

    function loadDetail(id, lnKode) {
        // Initialize or refresh the detail table with createModal (isolated)
        if (!trackingDetailTable) {
            trackingDetailTable = createModal({
                tableId: 'tableDetail',
                apiUrl: `<?php echo site_url("kajiulang/detailList/") ?>${id}`,
                itemsPerPage: 10,
                showFilter: false,
                treeview: false,
                numbering: false
            });
        } else {
            trackingDetailTable.refresh({
                apiUrl: `<?php echo site_url("kajiulang/detailList/") ?>${id}`
            });
        }

        // Simpan encLn dan lnKode ke modal dataset
        const modalEl = document.getElementById('modalDetail');
        if (modalEl) {
            modalEl.dataset.encLn = id;
            // Jika lnKode tidak diberikan, ambil dari dataset yang tersimpan
            if (lnKode) {
                modalEl.dataset.lnKode = lnKode;
            } else {
                lnKode = modalEl.dataset.lnKode || '';
            }
        }

        // Load atau tampilkan identitas sampel
        const sampleSection = document.getElementById('sampleIdentitySection');
        
        if (lnKode) {
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
                fetch(`<?php echo site_url("kajiulang/getSampleIdentity/") ?>${lnKode}`)
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
                            sampleSection.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching sample identity:', error);
                        // Jangan sembunyikan section jika ada error, biarkan tampil dengan data terakhir
                        if (!cachedSampleData[lnKode]) {
                            sampleSection.style.display = 'none';
                        }
                    });
            }
        } else {
            // Jika lnKode tidak ada, jangan sembunyikan jika section sudah visible
            // (kemungkinan reload dari approve/reject)
            if (sampleSection.style.display !== 'block') {
                sampleSection.style.display = 'none';
            }
        }

        // Show modal
        try {
            if (_modalDetailInstance) _modalDetailInstance.show();
            else if (typeof $ === 'function') $('#modalDetail').modal('show');
        } catch (err) {
            if (typeof $ === 'function' && $('#modalDetail').modal) $('#modalDetail').modal('show');
        }
    }

    // ============================================================
    // HANDLE APPROVE/REJECT (UNIFIED)
    // ============================================================
    async function handleApproveReject(el, isAccept) {
        if (!el) return;
        
        const ln = el.dataset.ln;
        const detail = el.dataset.detail;
        
        if (!ln || (detail === undefined || detail === null)) {
            console.warn('handleApproveReject: missing ln or detail', ln, detail);
            return;
        }

        if (el.dataset.sending === '1') return;
        el.dataset.sending = '1';
        el.style.pointerEvents = 'none';

        try {
            // 1. Save komentar terlebih dahulu
            try {
                const komentarResult = await saveKomentarAsync();
                if (!komentarResult.ok && !komentarResult.skipped) {
                    console.warn('Penyimpanan komentar bermasalah (melanjutkan):', komentarResult);
                }
            } catch (err) {
                console.error('saveKomentarAsync error (ignored):', err);
            }

            // 2. Approve/Reject
            const csrfToken = _getCsrf();
            const formData = new FormData();
            formData.append('ln', ln);
            formData.append('detail', detail);

            const url = isAccept
                ? '<?php echo site_url("kajiulang/approveDetail") ?>'
                : '<?php echo site_url("kajiulang/rejectDetail") ?>';

            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await res.json();

            // Update CSRF token
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
            }

            if (data.res) {
                // Reload detail dan table
                const modalEl = document.getElementById('modalDetail');
                const savedLnKode = modalEl ? modalEl.dataset.lnKode : '';
                try { loadDetail(ln, savedLnKode); } catch (err) { console.error('loadDetail error', err); }
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
            } else {
                console.warn((isAccept ? 'Gagal menyetujui' : 'Gagal menolak'), data.msg || null);
            }
        } catch (err) {
            console.error('Error saat handleApproveReject:', err);
        } finally {
            el.dataset.sending = '0';
            el.style.pointerEvents = 'auto';
        }
    }

    // ============================================================
    // EVENT DELEGATION UNTUK APPROVE/REJECT BUTTONS
    // ============================================================
    document.addEventListener('click', function(e) {
        // ACCEPT BUTTON
        const acceptEl = e.target.closest ? e.target.closest('.btn-accept-manager') : null;
        if (acceptEl) {
            e.preventDefault();
            handleApproveReject(acceptEl, true);
            return;
        }

        // REJECT BUTTON
        const rejectEl = e.target.closest ? e.target.closest('.btn-reject-manager') : null;
        if (rejectEl) {
            e.preventDefault();
            handleApproveReject(rejectEl, false);
            return;
        }
    });
</script>
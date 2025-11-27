<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?= esc($title ?? 'Hasil Kuisioner') ?></label>
            </div>
            <div class="card-body">
                <table id="response-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="6%">No.</th>
                            <th show width="34%">Nama Layanan</th>
                            <th show width="40%">Pemesan</th>
                            <th show width="20%">Hasil Kuisioner</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalResponseDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Detail Hasil Kuisioner</h5>
                    <small class="text-muted" id="response-meta"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="response-detail" class="list-group list-group-flush"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    const responseTable = createTable({
        tableId: 'response-table',
        apiUrl: '<?= site_url("response_kuisioner/datalist") ?>',
        numbering: true,
        dataSrc: 'items'
    });

    function showResponseModal(encId) {
        const modalEl = document.getElementById('modalResponseDetail');
        const metaEl = document.getElementById('response-meta');
        const detailEl = document.getElementById('response-detail');

        if (metaEl) metaEl.textContent = 'Memuat data...';
        if (detailEl) detailEl.innerHTML = '<div class="text-center py-3 text-muted">Memuat jawaban...</div>';

        const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
            ? bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;

        if (modalInstance) {
            modalInstance.show();
        } else if (typeof $ !== 'undefined' && $('#modalResponseDetail').modal) {
            $('#modalResponseDetail').modal('show');
        }

        fetch('<?= site_url("response_kuisioner/detail/") ?>' + encId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    if (detailEl) detailEl.innerHTML = '<div class="text-center text-danger py-3">' + escapeHtml(data.msg || 'Gagal memuat data') + '</div>';
                    return;
                }

                if (metaEl) {
                    const metaText = `No. Invoice: ${escapeHtml(data.meta.no_transaksi ?? '-')}`;
                    metaEl.textContent = metaText;
                }

                if (detailEl) {
                    if (!Array.isArray(data.items) || data.items.length === 0) {
                        detailEl.innerHTML = '<div class="text-center py-3 text-muted">Belum ada jawaban.</div>';
                        return;
                    }

                    const html = data.items.map(item => {
                        const answer = item.jawaban ? escapeHtml(String(item.jawaban)) : '<span class="text-muted">-</span>';
                        return `
                            <div class="list-group-item">
                                <div class="fw-semibold mb-1">${item.no}. ${escapeHtml(item.pertanyaan ?? '')}</div>
                                <div>${answer}</div>
                            </div>
                        `;
                    }).join('');

                    detailEl.innerHTML = html;
                }
            })
            .catch(err => {
                console.error(err);
                if (detailEl) {
                    detailEl.innerHTML = '<div class="text-center text-danger py-3">Terjadi kesalahan saat memuat data.</div>';
                }
            });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
</script>
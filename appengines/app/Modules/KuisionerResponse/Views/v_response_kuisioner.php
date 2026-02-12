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
                            <th width="6%">No.</th>
                            <th width="20%">No Invoice</th>
                            <th width="54%">Pemesan</th>
                            <th width="20%">Hasil Kuisioner</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalResponseDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Detail Hasil Kuisioner</h5>
                    <small class="text-muted" id="response-meta"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-wrapper">
                    <table id="response-detail-table" class="saytable border-top-bottom">
                        <thead>
                            <tr>
                                <th width="6%">No.</th>
                                <th width="45%">Pertanyaan</th>
                                <th width="35%">Jawaban</th>
                                <th width="20%">Jenis Jawaban</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    var responseTable = createTable({
        tableId: 'response-table',
        apiUrl: '<?= site_url("response_kuisioner/datalist") ?>',
        numbering: true,
        dataSrc: 'items'
    });

    var detailTable = null;

    function showResponseModal(encId) {
        const modalEl = document.getElementById('modalResponseDetail');
        const metaEl = document.getElementById('response-meta');

        if (metaEl) metaEl.textContent = 'Memuat data...';

        const modalInstance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
            ? bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;

        if (modalInstance) {
            modalInstance.show();
        } else if (typeof $ !== 'undefined' && $('#modalResponseDetail').modal) {
            $('#modalResponseDetail').modal('show');
        }

        // Reset atau create detail table
        const tableBody = document.querySelector('#response-detail-table tbody');
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-table"></div><em>Memuat jawaban...</em></td></tr>';
        }

        detailTable = createModal({
            tableId: 'response-detail-table',
            apiUrl: '<?= site_url("response_kuisioner/detaillist/") ?>' + encId,
            showFilter: false,
            numbering: true,
            treeview: false,
            dataSrc: 'items'
        });

        // Fetch header data untuk meta
        fetch('<?= site_url("response_kuisioner/detail/") ?>' + encId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.meta && metaEl) {
                    const metaText = `No. Invoice: ${escapeHtml(data.meta.no_transaksi ?? '-')} | Pemesan: ${escapeHtml(data.meta.pemesan ?? '-')}`;
                    metaEl.textContent = metaText;
                }
            })
            .catch(err => {
                console.error('Error fetching meta:', err);
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
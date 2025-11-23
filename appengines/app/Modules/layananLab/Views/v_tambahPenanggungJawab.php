<!-- Modal Lihat Tim -->
<div class="modal fade" id="modalTim" tabindex="-1" aria-labelledby="modalTimLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTimLabel">
                    <i class="bi bi-people-fill"></i> Tim Penanggung Jawab
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="10%" class="text-center">No</th>
                            <th width="45%" class="text-center">Manajer Teknis</th>
                            <th width="45%" class="text-center">Penyelia</th>
                        </tr>
                    </thead>
                    <tbody id="timTableBody">
                        <tr>
                            <td colspan="3" class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Manajemen Layanan -->
<div class="modal fade" id="modallayanan" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Manajemen Layanan Penyelia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
         <div class="table-wrapper">
            <table id="layanan-table" class="saytable border-top-bottom">
              <thead>
                <tr>
                  <th width="8%">No.</th>
                  <th>Nama Layanan</th>
                  <th>Status</th>
                  <th class="action text-end">Aksi</th>
                </tr>
              </thead>
              <tbody id="layanan-table-body"></tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>
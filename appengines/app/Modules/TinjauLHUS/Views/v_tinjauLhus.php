<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
            </div>
            <div class="card-body">

                <!--  Hidden CSRF untuk Ajax -->
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

                <table id="data-table" class="saytable border-top-bottom">
                    <thead>
                        <tr>
                            <th show width="5%">No.</th>
                            <th show width="15%">No. Invoice & Tanggal</th>
                            <th show width="15%">Pemesan</th>
                            <th show width="30%">Nama Layanan</th>
                            <th show width="15%">LHUS (Tinjau)</th>
                            <th show width="15%">Status</th>
                            <th show width="20%" class="action text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // 🔹 Init table
    table = createTable({
        apiUrl: '<?php echo site_url("tinjaulhus/datalist") ?>',
        dataSrc: 'items'
    });
    addAction();

    /**
     * 🔹 Proses LHUS (terima / tolak)
     */
    function prosesLhus(id, aksi) {
        if (!id || !aksi) return;
        if (!confirm('Yakin ingin memproses LHUS ini?')) return;

        const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
        const csrfName  = csrfInput ? csrfInput.getAttribute("name") : "";
        const csrfToken = csrfInput ? csrfInput.value : "";

        let formData = new FormData();
        formData.append(csrfName, csrfToken);

        fetch('<?php echo site_url("tinjaulhus/proses/") ?>' + id + '/' + aksi, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                sayAlert('successModal', 'Berhasil', 'Proses LHUS berhasil', 'success');
                if (typeof table !== 'undefined') table.fetchData({ reload: true });
            } else {
                sayAlert('errorModal', 'Gagal', data.msg || 'Proses LHUS gagal dilakukan', 'warning');
            }

            //  update CSRF token jika ada
            if (data.xname && data.xhash) {
                document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => {
                    input.value = data.xhash;
                });
            }
        })
        .catch(err => {
            console.error(err);
            sayAlert('errorModal', 'Error', 'Terjadi kesalahan sistem', 'warning');
        });
    }

    /**
     *  Lihat detail layanan
     * NOTE: endpoint diarahkan ke tinjaulhus/detaillist/
     * colspan disesuaikan ke 5 (No,Kode,Layanan,Biaya,Keterangan)
     */
   // 🔹 Tombol Lihat Detail
    function loadDetail(id) {
        const url = '<?php echo site_url("hasilpengujian/detaillist/") ?>' + id;
        const tbody = document.querySelector('#detail-body');

        // tampilkan loading
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(t => { throw new Error('HTTP ' + response.status + ': ' + t); });
                }
                return response.json();
            })
            .then(data => {
                tbody.innerHTML = '';
                if (data.items && data.items.length > 0) {
                    data.items.forEach(function(row) {
                        let tr = '<tr>';
                        row.forEach(function(col) { tr += '<td>' + col + '</td>'; });
                        tr += '</tr>';
                        tbody.innerHTML += tr;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">Tidak ada data</td></tr>';
                }
                // tampilkan modal
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('modalDetail');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    $('#modalDetail').modal('show');
                }
            })
            .catch(error => {
                console.error('loadDetail error:', error);
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error load data</td></tr>';
                if (typeof bootstrap !== 'undefined') {
                    const modalEl = document.getElementById('modalDetail');
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                } else {
                    $('#modalDetail').modal('show');
                }
            });
    }
</script>

<!-- 🔹 Modal Detail -->
<div class="modal fade" id="modalDetail" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-lg" role="document" style="margin: 2% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Item Layanan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th width="5%">No</th>
              <!-- <th width="15%">Kode</th> -->
              <th width="40%">Layanan</th>
              <th width="20%">Biaya</th>
              <th width="20%">Keterangan</th>
            </tr>
          </thead>
          <tbody id="detail-body">
            <tr><td colspan="5" class="text-center">Loading...</td></tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Keranjang Sewa Alat (Pesan Sewa Alat Baru) -->
<div class="modal fade" id="modalFormAlat" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
  aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document" style="margin: 2% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pilih Alat untuk Disewa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- CSRF Token -->
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />

        <!-- Tabel Pilih Layanan Alat -->
        <div class="mb-4">
          <table id="layanan-alat-table" class="saytable border-top-bottom">
            <thead>
              <tr>
                <th width="5%">No</th>
                <th width="25%">Parameter</th>
                <th width="25%">Instrumen/Alat/Tempat</th>
                <th width="15%">Biaya</th>
                <th width="5%">Jumlah</th>
                <th width="20%">Keterangan</th>
                <th style="width:5%" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="layanan-alat-table-body"></tbody>
          </table>
        </div>

        <hr class="my-4">

        <!-- Tabel Preview Keranjang Alat -->
        <div>
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-3 gap-3">
            <!-- Kiri -->
            <div class="d-flex align-items-end">
              <h6 class="fw-bold text-success mb-0">
                <i class="bi bi-cart3"></i> Keranjang Anda
                (<span id="jumlahItemKeranjangAlat">0</span> Item)
              </h6>
            </div>

            <!-- Kanan -->
            <div class="col-md-3">
              <label for="tglPelaksanaan" class="form-label mb-1">
                Pilih Tanggal Pelaksanaan <span class="text-danger">*</span>
              </label>
              <input type="date" class="form-control" id="tglPelaksanaan" name="tglPelaksanaan" required>
            </div>
          </div>


          <div id="keranjangAlatKosong" class="alert alert-warning text-center" style="display:none;">
            <i class="bi bi-cart-x"></i> Keranjang masih kosong. Silakan pilih alat di atas.
          </div>

          <table id="preview-keranjang-alat-table" class="saytable border-top-bottom">
            <thead>
              <tr>
                <th width="5%">No</th>
                <th width="22%">Parameter</th>
                <th width="22%">Instrumen/Alat/Tempat</th>
                <th width="10%">Diskon</th>
                <th width="15%">Biaya</th>
                <th width="5%">Jumlah</th>
                <th width="25%">Keterangan</th>
                <th style="width:10%" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="preview-keranjang-alat-table-body"></tbody>
            <tfoot>
              <tr class="table-active align-middle">
                <td colspan="8">
                  <div class="d-flex justify-content-end">
                    <div class="fw-bold fs-5">
                      TOTAL KESELURUHAN:
                      <span id="grandTotalAlat" class="text-primary">Rp 0</span>
                    </div>
                  </div>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Tutup
        </button>
        <button id="btnCheckoutAlatFromModal" class="btn btn-success" disabled>
          <i class="bi bi-cart-check"></i> Checkout Sekarang
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Load dependencies yang diperlukan untuk modal ini -->
<script src="<?= base_url('assets/js/sayTable.js?v=0.11') ?>"></script>

<script>
    /**
     * buildApiUrlWithOptionalParam
     * - path: path ke endpoint, mis. '<?= site_url("keranjang_alat/datalist") ?>'
    * - key / value: jika diberikan, tambahkan sebagai query string(tanpa page / limit)
      *
     * Output: path atau path + '?key=value'
    */
  function buildApiUrlWithOptionalParam(path, key, value) {
    try {
      const u = new URL(path, window.location.origin);
      const params = new URLSearchParams(u.search);

      if (typeof key === 'string' && key !== '' && typeof value !== 'undefined' && value !== null && String(value) !== '') {
        params.set(key, String(value));
      } else {
        if (typeof key === 'string' && key !== '') params.delete(key);
      }

      const s = params.toString();
      return u.pathname + (s ? '?' + s : '');
    } catch (e) {
      if (typeof key === 'string' && key !== '' && typeof value !== 'undefined' && value !== null && String(value) !== '') {
        return path + '?' + encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
      }
      return path;
    }
  }

  /**
   * normalizeDoubleQuestion
   * - ubah pola like "...?a=b?c=d" menjadi "...?a=b&c=d"
   * - collapse accidental "&&"
   */
  function normalizeDoubleQuestion(url) {
    if (typeof url !== 'string') return url;
    let n = url.replace(/\?([^?]*)\?/, '?$1&');
    n = n.replace(/&{2,}/g, '&');
    return n;
  }

  //  Inisialisasi variabel global untuk tabel modal
  let layananAlatTable = null;
  let previewKeranjangAlatTable = null;
  const jenFilterAlat = document.getElementById('jenFilterAlat');

  /* Helper build URL layanan alat (untuk modal)
  - memastikan jika jen diberikan -> url ...?kode=B*/
  function buildLayananAlatUrl(jen = '') {
    const base = '<?= site_url("keranjang_alat/dataListLayanan") ?>';
    return buildApiUrlWithOptionalParam(base, (jen && jen !== '') ? 'kode' : '', jen || '');
  }

  //   createOrRefreshLayananAlatTable(jen)
  function createOrRefreshLayananAlatTable(jen) {
    const api = buildLayananAlatUrl(jen || '');

    if (layananAlatTable && typeof layananAlatTable.getConfig === 'function') {
      try {
        const cfg = layananAlatTable.getConfig();
        if (cfg && typeof cfg === 'object') {
          cfg.apiUrl = api;
          cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
        }
        if (typeof layananAlatTable.fetchData === 'function') {
          layananAlatTable.fetchData({
            reload: true
          });
          return;
        }
      } catch (e) {
        try {
          if (typeof layananAlatTable.destroy === 'function') layananAlatTable.destroy();
        } catch (e2) {
          /*ignore*/
        }
        layananAlatTable = null;
      }
    }

    layananAlatTable = createModal({
      apiUrl: api,
      tableId: 'layanan-alat-table',
      numbering: true,
      dataSrc: 'items',
      preserveQuery: true
    });

    if (layananAlatTable && typeof layananAlatTable.fetchData === 'function' && typeof layananAlatTable.getConfig === 'function') {
      const orig = layananAlatTable.fetchData.bind(layananAlatTable);
      layananAlatTable.fetchData = function (opts = {}) {
        try {
          const cfg = layananAlatTable.getConfig();
          if (cfg && cfg.apiUrl && typeof cfg.apiUrl === 'string') {
            cfg.apiUrl = normalizeDoubleQuestion(cfg.apiUrl);
          }
        } catch (err) {
          // Error handling
        }
        return orig(opts);
      };
    }
  }

  /* applyJenFilterAlat: dipanggil saat select berubah */
  function applyJenFilterAlat() {
    const jen = (jenFilterAlat && jenFilterAlat.value) ? jenFilterAlat.value.trim() : '';
    createOrRefreshLayananAlatTable(jen);
  }

  // event listener select change
  if (jenFilterAlat) {
    jenFilterAlat.addEventListener('change', function () {
      applyJenFilterAlat();
    });
  }

  document.getElementById('modalFormAlat').addEventListener('shown.bs.modal', function () {
    const currentJen = (jenFilterAlat && jenFilterAlat.value) ? jenFilterAlat.value.trim() : '';
    createOrRefreshLayananAlatTable(currentJen);

    const previewBody = document.querySelector('#preview-keranjang-alat-table-body');
    if (previewBody) previewBody.innerHTML = '';

    if (previewKeranjangAlatTable && typeof previewKeranjangAlatTable.destroy === 'function') {
      try {
        previewKeranjangAlatTable.destroy();
      } catch (e) {
        // Error handling
      }
    }
    previewKeranjangAlatTable = null;

    previewKeranjangAlatTable = createModal({
      apiUrl: '<?= site_url("keranjang_alat/datalist") ?>',
      tableId: 'preview-keranjang-alat-table',
      showFilter: false,
      numbering: true,
      treeview: true,
      itemsPerPage: 10,
      dataSrc: 'items',
      onData: function (items) {
        const tbody = document.querySelector('#preview-keranjang-alat-table-body');
        if (tbody) tbody.innerHTML = '';
      }
    });

    if (previewKeranjangAlatTable && typeof previewKeranjangAlatTable.fetchData === 'function') {
      const origFetch = previewKeranjangAlatTable.fetchData.bind(previewKeranjangAlatTable);
      previewKeranjangAlatTable.fetchData = function (opts = {}) {
        return origFetch(Object.assign({}, opts, {
          reload: true
        }));
      };

      previewKeranjangAlatTable.fetchData({
        reload: true
      });
    }

    setTimeout(() => {
      updateKeranjangAlatCounter();
      calculateGrandTotalAlat();
    }, 400);
  });

  /* =========================
     Event tombol "Pesan Sewa Alat Baru"
     ========================= */
  document.querySelector('#add_alat').addEventListener('click', function () {
    fetch('<?php echo site_url("keranjang_alat/checkVerified") ?>', {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(r => r.json())
      .then(data => {
        if (data.verified) {
          const modalFormAlat = new bootstrap.Modal(document.getElementById('modalFormAlat'));
          modalFormAlat.show();
        } else {
          sayAlert('warningModal', 'Verifikasi Diperlukan', 'Akun anda belum diverifikasi. Silakan lengkapi data di halaman profil.', 'warning');
          setTimeout(() => {
            loadContent('<?php echo site_url("profiluser") ?>');
          }, 1200);
        }
      })
      .catch(err => {
        sayAlert('errorModal', 'Error', 'Gagal memeriksa status verifikasi.', 'warning');
      });
  });

  /* saveData */
  function saveData({
    url,
    formData,
    onSuccess,
    onError
  }) {
    showLoading();
    const csrfInput = document.querySelector('[name="<?= csrf_token() ?>"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(res => res.json())
      .then(data => {
        if (data.xname && data.xhash) {
          document.querySelectorAll('[name="' + data.xname + '"]').forEach(i => i.value = data.xhash);
        }
        if (typeof onSuccess === 'function') {
          onSuccess(data);
          return;
        }

        if (data.res === true) {
          if (previewKeranjangAlatTable && typeof previewKeranjangAlatTable.fetchData === 'function') {
            previewKeranjangAlatTable.fetchData({
              reload: true
            });
          }
          sayAlert('successModal', 'Success', 'Data berhasil disimpan.', 'success');
        } else if (data.res === 'refresh') {
          loadContent(data.link);
        } else if (data.res === 'redirect') {
          window.location.href = data.link;
        } else {
          sayAlert('errorModal', 'Error', data.msg ?? 'Data gagal disimpan.', 'warning');
        }
      })
      .catch(err => {
        if (typeof onError === 'function') onError(err);
        else sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
      })
      .finally(() => hideLoading());
  }

  /* updateKeranjangAlatCounter & calculateGrandTotalAlat */
  function updateKeranjangAlatCounter() {
    fetch('<?= site_url("keranjang_alat/datalist") ?>')
      .then(res => res.json())
      .then(data => {
        const jumlahItem = data.items ? data.items.length : 0;

        const counterEl = document.getElementById('jumlahItemKeranjangAlat');
        if (counterEl) {
          counterEl.textContent = jumlahItem;
        }

        const keranjangKosong = document.getElementById('keranjangAlatKosong');
        const previewTable = document.getElementById('preview-keranjang-alat-table');
        const btnCheckout = document.getElementById('btnCheckoutAlatFromModal');

        if (jumlahItem === 0) {
          if (keranjangKosong) keranjangKosong.style.display = 'block';
          if (previewTable) previewTable.style.display = 'none';
          if (btnCheckout) {
            btnCheckout.disabled = true;
          }
        } else {
          if (keranjangKosong) keranjangKosong.style.display = 'none';
          if (previewTable) previewTable.style.display = 'table';
          if (btnCheckout) {
            btnCheckout.disabled = false;
          }
        }
      })
      .catch(err => {
        // Error handling
      });
  }

  function calculateGrandTotalAlat() {
    fetch('<?= site_url("keranjang_alat/datalist") ?>')
      .then(res => res.json())
      .then(data => {
        if (data.items && data.items.length > 0) {
          let grandTotal = 0;
          data.items.forEach(item => {
            let totalStr = null;
            for (let i = 0; i < item.length; i++) {
              if (typeof item[i] === 'string' && item[i].indexOf('row-total') !== -1) {
                const tmp = document.createElement('div');
                tmp.innerHTML = item[i];
                const rt = tmp.querySelector('.row-total');
                if (rt) {
                  totalStr = rt.textContent || rt.innerText || null;
                  break;
                }
              }
            }
            if (!totalStr) {
              for (let i = item.length - 1; i >= 0; i--) {
                if (typeof item[i] === 'string' && item[i].indexOf('Rp') !== -1) {
                  const tmp2 = document.createElement('div');
                  tmp2.innerHTML = item[i];
                  totalStr = (tmp2.textContent || tmp2.innerText || '').trim();
                  break;
                }
              }
            }
            if (totalStr) {
              let cleaned = totalStr.replace(/[^0-9,.-]/g, '');
              cleaned = cleaned.replace(/\./g, '').replace(/,/g, '.');
              const totalNum = parseFloat(cleaned);
              if (!isNaN(totalNum)) grandTotal += totalNum;
            }
          });
          document.getElementById('grandTotalAlat').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(grandTotal);
        } else {
          document.getElementById('grandTotalAlat').textContent = 'Rp 0';
        }
      })
      .catch(err => {
        // Error handling
      });
  }

  /* =========================
     Event delegation: masukkan item / checkout / delete
     ========================= */
  // Prevent duplicate event handlers
  if (!window.keranjangAlatClickHandlerAttached) {
    window.keranjangAlatClickHandlerAttached = true;

    document.addEventListener('click', function (e) {
      // Tombol masukkan
      if (e.target.closest('.btnMasukkanAlat')) {
        let btn = e.target.closest('.btnMasukkanAlat');
        let tr = btn.closest('tr');

        let biaya = parseFloat(btn.dataset.biaya) || 0;
        let diskon = parseFloat(btn.dataset.diskon) || 0;
        let jumlahInput = tr.querySelector('.jumlah');
        let jumlah = parseInt(jumlahInput ? jumlahInput.value : 1) || 1;
        if (jumlah < 1) jumlah = 1;
        let total = (biaya * jumlah) * (1 - (diskon / 100));

        let data = {
          detUjiKode: btn.dataset.kode,
          detAlat: btn.dataset.alat,
          detBiaya: biaya,
          detParameter: btn.dataset.parameter,
          detNamaLayanan: btn.dataset.namaLayanan || '',
          detDiskon: diskon,
          detJumlah: jumlah,
          detKeterangan: tr.querySelector('.keterangan') ? tr.querySelector('.keterangan').value : '',
          detTotal: total
        };

        if (!data.detUjiKode) {
          sayAlert('errorModal', 'Gagal', 'Kode Alat tidak ditemukan.', 'error');
          return;
        }
        if (parseInt(data.detJumlah) < 1) {
          sayAlert('errorModal', 'Gagal', 'Jumlah minimal 1.', 'error');
          return;
        }

        let formData = new FormData();
        for (const key in data) formData.append(key, data[key]);

        let csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

        saveData({
          url: "<?= site_url('keranjang_alat/submit') ?>",
          formData: formData,
          onSuccess: function (res) {
            if (res.xname && res.xhash) {
              let csrfField = document.querySelector('input[name="' + res.xname + '"]');
              if (csrfField) csrfField.value = res.xhash;
            }
            if (res.res === true) {
              if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
                reload: true
              });
              if (previewKeranjangAlatTable && typeof previewKeranjangAlatTable.fetchData === 'function') {
                previewKeranjangAlatTable.fetchData({
                  reload: true
                });
                setTimeout(function () {
                  updateKeranjangAlatCounter();
                  calculateGrandTotalAlat();
                }, 400);
              }
              if (jumlahInput) jumlahInput.value = 1;
              if (tr.querySelector('.keterangan')) tr.querySelector('.keterangan').value = '';
              sayAlert('successModal', 'Berhasil', res.msg ?? 'Alat berhasil ditambahkan ke keranjang.', 'success');
            } else {
              sayAlert('errorModal', 'Gagal', res.msg ?? 'Terjadi kesalahan saat menambahkan ke keranjang.', 'error');
            }
          },
          onError: function () {
            sayAlert('errorModal', 'Gagal', 'Terjadi kesalahan koneksi ke server.', 'error');
          }
        });
      }

      if (e.target.closest('#btnCheckoutAlatFromModal')) {
        e.preventDefault();

        // Validasi tanggal pelaksanaan
        const tglPelaksanaan = document.getElementById('tglPelaksanaan').value;

        if (!tglPelaksanaan) {
          sayAlert('errorModal', 'Gagal', 'Harap pilih tanggal pelaksanaan sewa!', 'error');
          return;
        }

        const btn = e.target.closest('#btnCheckoutAlatFromModal');
        const customMsg = btn ? (btn.getAttribute('data-confirm') || '') : '';
        const message = customMsg || 'Apakah Anda yakin ingin melakukan checkout untuk sewa alat?';

        sayConfirm('Konfirmasi Checkout', message, () => {
          doCheckoutAlat();
        }, 'success', 'checkout');
      }
    });
  } // End of keranjangAlatClickHandlerAttached check

  /* deleteItemFromPreview - NAMA HARUS SAMA dengan yang di aksiKeranjang() */
  function deleteItemFromPreview(eOrEl) {

    let el;
    if (eOrEl instanceof Event) {
      eOrEl.preventDefault();
      el = eOrEl.currentTarget || eOrEl.target;
    } else el = eOrEl;

    if (el && !el.hasAttribute('data-index')) el = el.closest('[data-index]');
    if (!el) {
      return;
    }

    const idx = el.getAttribute('data-index');

    if (!idx) {
      return;
    }

    const deleteUrl = "<?= site_url('keranjang_alat/delete/') ?>" + idx;

    fetch(deleteUrl)
      .then(res => res.json())
      .then(data => {
        if (data.xname && data.xhash) {
          document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
        if (data.res === true) {
          if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
            reload: true
          });
          if (previewKeranjangAlatTable && typeof previewKeranjangAlatTable.fetchData === 'function') {
            previewKeranjangAlatTable.fetchData({
              reload: true
            });
            setTimeout(function () {
              updateKeranjangAlatCounter();
              calculateGrandTotalAlat();
            }, 400);
          }
          sayAlert('successModal', 'Sukses', data.msg, 'success');
        } else {
          sayAlert('errorModal', 'Gagal', data.msg ?? 'Hapus item gagal.', 'error');
        }
      })
      .catch(err => {
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
      });
  }

  /* doCheckoutAlat */
  function doCheckoutAlat() {
    const formData = new FormData();
    const csrfInput = document.querySelector('input[name="<?= csrf_token() ?>"]');
    if (csrfInput) formData.append('<?= csrf_token() ?>', csrfInput.value);

    // Tambahkan tanggal pelaksanaan
    const tglPelaksanaan = document.getElementById('tglPelaksanaan').value;
    formData.append('tglPelaksanaan', tglPelaksanaan);

    const checkoutUrl = '<?= site_url("keranjang_alat/checkout") ?>';

    fetch(checkoutUrl, {
      method: 'POST',
      body: formData
    })
      .then(res => {
        return res.json();
      })
      .then(data => {

        if (data.xname && data.xhash) {
          document.querySelectorAll('[name="' + data.xname + '"]').forEach(input => input.value = data.xhash);
        }
        if (data.res === true) {
          if (typeof table !== 'undefined' && typeof table.fetchData === 'function') table.fetchData({
            reload: true
          });
          if (previewKeranjangAlatTable && typeof previewKeranjangAlatTable.fetchData === 'function') {
            previewKeranjangAlatTable.fetchData({
              reload: true
            });
            setTimeout(function () {
              updateKeranjangAlatCounter();
              calculateGrandTotalAlat();
            }, 400);
          }
          const modalFormAlat = bootstrap.Modal.getInstance(document.getElementById('modalFormAlat'));
          if (modalFormAlat) modalFormAlat.hide();

          // Reset tanggal pelaksanaan
          document.getElementById('tglPelaksanaan').value = '';

          sayAlert('successModal', 'Sukses', data.msg, 'success');
        } else {
          sayAlert('errorModal', 'Gagal', data.msg ?? 'Checkout gagal.', 'error');
        }
      })
      .catch(err => {
        sayAlert('errorModal', 'Error', 'Terjadi kesalahan koneksi ke server.', 'error');
      });
  }
</script>
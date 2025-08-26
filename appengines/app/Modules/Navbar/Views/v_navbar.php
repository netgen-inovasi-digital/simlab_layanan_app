<style>
  .navbar-item {
    padding: 5px 15px;
    border: 1px solid #ccc;
    /* background-color: #f8f9fa; */
    margin-bottom: 5px;
    cursor: grab;
    transition: margin-left 0.2s ease;
  }

  .child {
    margin-left: 30px;
  }

  .drag-placeholder {
    height: 40px;
    border: 2px dashed #0d6efd;
    margin-bottom: 5px;
    border-radius: 5px;
  }

  .toggle-status {
    cursor: pointer;
  }
</style>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <label class="card-title mb-0"><?= $title ?></label>
        <button id="add" class="btn btn-primary">
          <i class="bi bi-plus-circle-dotted"></i> Tambah
        </button>
      </div>

      <div class="card-body">
        <div id="navbar" class="d-flex flex-column">
          <?php
          $encrypter = \Config\Services::encrypter();
          foreach ($getNavbar as $row) {
            $id = bin2hex($encrypter->encrypt($row->id_navbar));
          ?>
            <div
              id="<?= $id ?>"
              class="border rounded px-3 py-2 d-flex justify-content-between align-items-center bg-light navbar-item <?= ($row->kode_induk != 0) ? 'child' : '' ?>"
              draggable="true"
              data-status="<?= $row->status ?>">

              <!-- Kiri: Drag handle dan nama -->
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-grip-vertical text-muted" title="Drag untuk urutkan"></i>
                <span><?= esc($row->nama) ?></span>
              </div>

              <!-- Kanan: Toggle & aksi -->
              <div class="d-flex align-items-center gap-2">
                <div class="form-check form-switch m-0">
                  <input
                    class="form-check-input toggle-status"
                    type="checkbox"
                    role="switch"
                    data-id="<?= $id ?>"
                    <?= $row->status === 'Y' ? 'checked' : '' ?>
                    data-bs-toggle="tooltip"
                    title="Aktif / Nonaktif">
                </div>

                <?= (!in_array($row->id_navbar, [11, 38, 33, 34, 35, 36]))
                  ? aksi($id)
                  : '<span class="text-muted">---</span>'; ?>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
function aksi($id)
{
  return '<div id="' . $id . '">
        <span class="text-dark" title="Ubah" onclick="editItemNavbar(event)">
            <i class="bi bi-pencil-square"></i></span> 
        <label class="divider">|</label>
        <span class="text-danger" title="Hapus" onclick="deleteItem(event)">
            <i class="bi bi-x-circle"></i></span>
    </div>';
}
?>

<script>
  addAction();
  var draggedItem = null;
  var dragStartX = 0;
  var navbarMenu = document.getElementById("navbar");
  var placeholder = document.createElement("div");
  placeholder.classList.add("drag-placeholder");

  function addDragEvents(item) {
    item.addEventListener("dragstart", (e) => {
      draggedItem = item;
      dragStartX = e.clientX;
      item.style.opacity = "0.5";
      setTimeout(() => {
        navbarMenu.insertBefore(placeholder, item.nextSibling);
        item.style.display = "none";
      }, 0);
    });

    item.addEventListener("dragend", (e) => {
      item.style.display = "block";
      item.style.opacity = "1";

      var currentIndex = [...navbarMenu.children].indexOf(placeholder);
      var previousItem = navbarMenu.children[currentIndex - 1];

      if (e.clientX - dragStartX > 20 && navbarMenu.firstElementChild !== draggedItem) {
        let potentialParent = previousItem;
        while (potentialParent && potentialParent.classList.contains("child")) {
          var idx = [...navbarMenu.children].indexOf(potentialParent);
          potentialParent = navbarMenu.children[idx - 1];
        }
        if (potentialParent) {
          draggedItem.classList.add("child");
        }
      } else if (e.clientX - dragStartX < -20) {
        draggedItem.classList.remove("child");
      }
      navbarMenu.insertBefore(draggedItem, placeholder);
      placeholder.remove();
      updateKodeNavbar();
      saveAll();
    });

    item.addEventListener("dragover", (e) => {
      e.preventDefault();
      var after = getDragAfterElement(navbarMenu, e.clientY);
      if (after == null)
        navbarMenu.appendChild(placeholder);
      else navbarMenu.insertBefore(placeholder, after);
    });
  }

  function getDragAfterElement(container, y) {
    var elements = [...container.querySelectorAll(".navbar-item:not([style*='display: none'])")];
    return elements.reduce((closest, child) => {
      var box = child.getBoundingClientRect();
      var offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        return {
          offset: offset,
          element: child
        };
      } else return closest;
    }, {
      offset: Number.NEGATIVE_INFINITY
    }).element;
  }

  function updateKodeNavbar() {
    var items = [...document.querySelectorAll("#navbar .navbar-item")];
    let mainCounter = 0;
    let childCounter = 0;
    let currentMainKode = '';
    let maxCode = 0;

    items.forEach((item) => {
      if (!item.classList.contains("child")) {
        mainCounter++;
        childCounter = 0;
        currentMainKode = mainCounter.toString();

        item.setAttribute("data-code", currentMainKode);
        item.setAttribute("data-parent", "0");
      } else {
        childCounter++;
        var childCode = `${currentMainKode}.${childCounter}`;
        item.setAttribute("data-code", childCode);
        item.setAttribute("data-parent", currentMainKode);
      }
      var kode = item.getAttribute("data-code");
      var indukKode = parseInt(kode.split('.')[0]); // Ambil sebelum titik
      if (indukKode > maxCode) {
        maxCode = indukKode;
      }
    });
    document.querySelector('[name="code"]').value = maxCode;
  }
  // Inisialisasi
  document.querySelectorAll(".navbar-item").forEach(addDragEvents);
  updateKodeNavbar();

  function saveAll() {
    var tokenName = "<?= csrf_token() ?>";
    var elName = document.querySelector(`[name="${tokenName}"]`);
    var tokenValue = elName.value;

    var formData = new FormData();
    var items = document.querySelectorAll(".navbar-item");

    items.forEach((el, i) => {
      formData.append(`items[${i}][id]`, el.id);
      formData.append(`items[${i}][code]`, el.dataset.code);
      formData.append(`items[${i}][parent]`, el.dataset.parent);
      formData.append(`items[${i}][sort_order]`, i + 1); // urutan disini
    });
    formData.append(tokenName, tokenValue);

    fetch('./navbar/updated', {
        method: 'POST',
        body: formData
      }).then(response => response.json())
      .then(data => {
        elName.value = data.xhash;
      })
      .catch(error => {});
  }

  // Event listener untuk toggle status
  document.querySelectorAll('.toggle-status').forEach(toggle => {
    toggle.addEventListener('change', function() {
      var id = this.dataset.id;
      var newStatus = this.checked ? 'Y' : 'N';
      var tokenName = "<?= csrf_token() ?>";
      var tokenValue = document.querySelector(`[name="${tokenName}"]`).value;

      var formData = new FormData();
      formData.append('id', id);
      formData.append('status', newStatus);
      formData.append(tokenName, tokenValue);

      fetch('./navbar/toggle', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          document.querySelector(`[name="${tokenName}"]`).value = data.xhash;
          // Optional: tampilkan notifikasi berhasil
        })
        .catch(error => {
          console.error('Gagal toggle status:', error);
          // Optional: kembalikan checkbox jika gagal
          this.checked = !this.checked;
        });
    });
  });


  function resetOpsiSumber() {
    opsiHalaman.classList.add('d-none');
    opsiBerita.classList.add('d-none');
    opsiUrlNama.classList.add('d-none');
    opsiUrlInput.classList.add('d-none');
  }

  // ===== dropdown sumber menu ===== //
  var opsiHalaman = document.querySelector('#opsiHalaman');
  var opsiBerita = document.querySelector('#opsiBerita');
  var opsiUrlNama = document.querySelector('#opsiUrl');
  var opsiUrlInput = document.querySelector('#opsiUrlInput');

  document.querySelector('#sumberMenu').addEventListener('change', function() {
    var value = this.value;

    // Sembunyikan semua
    resetOpsiSumber();

    if (value === 'halaman') {
      opsiHalaman.classList.remove('d-none');
    } else if (value === 'berita') {
      opsiBerita.classList.remove('d-none');
    } else if (value === 'manual') {
      opsiUrlNama.classList.remove('d-none');
      opsiUrlInput.classList.remove('d-none');
    }
  });


  document.querySelectorAll('#opsiHalaman select, #opsiBerita select').forEach(select => {
    select.addEventListener('change', function() {
      var selectedOption = this.options[this.selectedIndex];
      var nama = selectedOption.getAttribute('data-nama') || '';
      document.querySelector('#namaHidden').value = nama;
    });
  });

  //===== edit Item ===== //
  function editItemNavbar(event) {
    var closest = event.target.closest('div');
    if (closest) {
      showLoading();

      var id = closest.getAttribute('id');
      var baseURL = window.location.href.split('/').slice(0, -1).join('/') + '/' + currentUrl;
      var url = `${baseURL}/edit/${id}`;

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

            // Reset semua opsi sumber menu
            resetOpsiSumber();

            // Isi nilai nama (nama_menu)
            const inputNama = document.querySelector('[name="nama"]');
            if (inputNama) inputNama.value = data.nama || '';


            // Tentukan sumber dari URL
            var selectSumber = document.querySelector('[name="sumber_menu"]');
            var selectHalaman = document.querySelector('[name="url_halaman"]');
            var selectBerita = document.querySelector('[name="url_berita"]');
            var inputUrlManual = document.querySelector('[name="url_manual"]');
            var inputNamaManual = document.querySelector('[name="nama_menu_url"]');

            if (data.url?.startsWith('hal/')) {
              if (selectSumber) selectSumber.value = 'halaman';
              if (selectHalaman) selectHalaman.value = data.url.replace('hal/', '');
              document.querySelector('#opsiHalaman')?.classList.remove('d-none');
            } else if (data.url?.startsWith('berita/')) {
              if (selectSumber) selectSumber.value = 'berita';
              if (selectBerita) selectBerita.value = data.url.replace('berita/', '');
              document.querySelector('#opsiBerita')?.classList.remove('d-none');
            } else {
              if (selectSumber) selectSumber.value = 'manual';
              if (inputUrlManual) inputUrlManual.value = data.url || '';
              if (inputNamaManual) inputNamaManual.value = data.nama || '';
              document.querySelector('#opsiUrl')?.classList.remove('d-none');
              document.querySelector('#opsiUrlInput')?.classList.remove('d-none');
            }

            // Isi ID terenkripsi
            document.querySelector('[name="id"]').value = data.id || '';
          }
        })
        .catch(error => {
          console.error(error);
          sayAlert('errorModal', 'Error', 'Terjadi kesalahan pada sistem.', 'warning');
        })
        .finally(() => {
          setTimeout(() => {
            hideLoading();
          }, 300);
        });
    }
  }

  // ===== tooltip ===== //
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.forEach(function(tooltipTriggerEl) {
    new bootstrap.Tooltip(tooltipTriggerEl)
  })
</script>

<div class="modal fade" id="modalForm" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog" role="document" style="margin: 2% auto">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modal title</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
        </button>
      </div>
      <?php echo form_open('navbar/submit', array('id' => 'myform', 'novalidate' => '')) ?>
      <div class="modal-body">
        <input type="hidden" value="" name="id" />
        <input type="hidden" value="" name="code" />
        <input type="hidden" name="nama" id="namaHidden">
        <div class="row mb-2">
          <label class="col-md-4 col-form-label">Sumber Menu</label>
          <div class="col">
            <select id="sumberMenu" name="sumber_menu" class="form-select" required>
              <option value="">-- Pilih Sumber --</option>
              <option value="halaman">Halaman</option>
              <option value="berita">Berita</option>
              <option value="manual">URL</option>
            </select>
          </div>
        </div>

        <div class="row mb-2 d-none" id="opsiHalaman">
          <label class="col-md-4 col-form-label">Pilih Halaman</label>
          <div class="col">
            <select name="url_halaman" class="form-select">
              <option value="">-- pilih data --</option>
              <?php foreach ($getPages as $pages): ?>
                <option value="<?= $pages->slug ?>" data-nama="<?= htmlspecialchars($pages->title) ?>"><?= $pages->title ?> </option>
              <?php endforeach ?>
            </select>
          </div>
        </div>

        <div class="row mb-2 d-none" id="opsiBerita">
          <label class="col-md-4 col-form-label">Pilih Berita</label>
          <div class="col">
            <select name="url_berita" class="form-select">
              <option value="">-- pilih data --</option>
              <?php foreach ($getPosts as $post): ?>
                <option value="<?= $post->slug ?>" data-nama="<?= htmlspecialchars($post->title) ?>"><?= $post->title ?></option>
              <?php endforeach ?>
            </select>
          </div>
        </div>
        <div class="row mb-2 d-none" id="opsiUrl">
          <label class="col-md-4 col-form-label">Nama Menu</label>
          <div class="col">
            <input name="nama_menu_url" type="text" class="form-control" placeholder="Contoh: Youtube">
          </div>
        </div>

        <div class="row mb-2 d-none" id="opsiUrlInput">
          <label class="col-md-4 col-form-label">URL</label>
          <div class="col">
            <input name="url_manual" type="text" class="form-control" placeholder="https://youtube.com">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
        <button class="btn btn-success" type="submit"><i class="bi bi-check2-circle"></i> Simpan</button>
      </div>
      </form>
    </div>
  </div>
</div>
<style>
  .sosmed-item {
    padding: 5px 15px;
    border: 1px solid #ccc;
    /* background-color: #f8f9fa; */
    margin-bottom: 5px;
    cursor: grab;
    transition: margin-left 0.2s ease;
  }

  .drag-placeholder {
    height: 40px;
    border: 2px dashed #0d6efd;
    margin-bottom: 5px;
    border-radius: 5px;
  }

  .status-toggle {
    cursor: pointer;
  }
</style>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <label class="card-title mb-0"><?php echo $title ?></label>
        <button id="add" class="btn btn-primary">
          <i class="bi bi-plus-circle-dotted"></i> Tambah
        </button>
      </div>
      <div class="card-body ps-4 pe-4">
        <div id="sosmed">
          <?php
          $encrypter = \Config\Services::encrypter();
          foreach ($getSosmed as $row) {
            $id = bin2hex($encrypter->encrypt($row->id_sosmed));
          ?>
            <div id="<?= $id ?>" class="sosmed-item <?= ($row->urutan != 0) ? 'child' : '' ?>" draggable="true">
              <div class="d-flex justify-content-between">
                <div><i class="bi <?= $row->icon ?> me-2"></i> <?= esc($row->nama) . " : " . esc($row->link) ?></div>
                <div class="d-flex align-items-center gap-2">
                  <!-- Toggle Status -->
                  <div class="form-check form-switch m-0">
                    <input
                      class="form-check-input status-toggle"
                      type="checkbox"
                      role="switch"
                      data-id="<?= $id ?>"
                      <?= $row->status === 'Y' ? 'checked' : '' ?>
                      data-bs-toggle="tooltip"
                      title="Aktif/Nonaktif">
                  </div>

                  <!-- Aksi -->
                  <?= aksi($id) ?>
                </div>
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
        <span class="text-dark" title="Ubah" onclick="editItem(event)">
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
  var sosmed = document.getElementById("sosmed");
  var placeholder = document.createElement("div");
  placeholder.classList.add("drag-placeholder");

  function addDragEvents(item) {
    item.addEventListener("dragstart", (e) => {
      draggedItem = item;
      dragStartX = e.clientX;
      item.style.opacity = "0.5";
      setTimeout(() => {
        sosmed.insertBefore(placeholder, item.nextSibling);
        item.style.display = "none";
      }, 0);
    });

    item.addEventListener("dragend", (e) => {
      item.style.display = "block";
      item.style.opacity = "1";

      const currentIndex = [...sosmed.children].indexOf(placeholder);
      const previousItem = sosmed.children[currentIndex - 1];

      if (e.clientX - dragStartX > 20 && sosmed.firstElementChild !== draggedItem) {
        let potentialParent = previousItem;
        while (potentialParent && potentialParent.classList.contains("child")) {
          const idx = [...sosmed.children].indexOf(potentialParent);
          potentialParent = sosmed.children[idx - 1];
        }
        if (potentialParent) {
          draggedItem.classList.add("child");
        }
      } else if (e.clientX - dragStartX < -20) {
        draggedItem.classList.remove("child");
      }
      sosmed.insertBefore(draggedItem, placeholder);
      placeholder.remove();
      updateKodeSosmed();
      saveAll();
    });

    item.addEventListener("dragover", (e) => {
      e.preventDefault();
      const after = getDragAfterElement(sosmed, e.clientY);
      if (after == null)
        sosmed.appendChild(placeholder);
      else sosmed.insertBefore(placeholder, after);
    });
  }

  function getDragAfterElement(container, y) {
    const elements = [...container.querySelectorAll(".sosmed-item:not([style*='display: none'])")];
    return elements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
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

  function updateKodeSosmed() {
    const items = [...document.querySelectorAll("#sosmed .sosmed-item")];
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
        const childCode = `${currentMainKode}.${childCounter}`;
        item.setAttribute("data-code", childCode);
        item.setAttribute("data-parent", currentMainKode);
      }
      const kode = item.getAttribute("data-code");
      const indukKode = parseInt(kode.split('.')[0]); // Ambil sebelum titik
      if (indukKode > maxCode) {
        maxCode = indukKode;
      }
    });
    document.querySelector('[name="code"]').value = maxCode;
  }
  // Inisialisasi
  document.querySelectorAll(".sosmed-item").forEach(addDragEvents);
  updateKodeSosmed();

  function saveAll() {
    const tokenName = "<?= csrf_token() ?>";
    const elName = document.querySelector(`[name="${tokenName}"]`);
    const tokenValue = elName.value;

    const formData = new FormData();
    const items = document.querySelectorAll(".sosmed-item");

    items.forEach((el, i) => {
      formData.append(`items[${i}][id]`, el.id);
      formData.append(`items[${i}][code]`, el.dataset.code);
      formData.append(`items[${i}][parent]`, el.dataset.parent);
    });
    formData.append(tokenName, tokenValue);

    fetch('./sosmed/updated', {
        method: 'POST',
        body: formData
      }).then(response => response.json())
      .then(data => {
        elName.value = data.xhash;
      })
      .catch(error => {});
  }

  document.querySelectorAll('.status-toggle').forEach(input => {
    input.addEventListener('change', function() {
      var id = this.dataset.id;
      var status = this.checked ? 'Y' : 'N';
      const tokenName = "<?= csrf_token() ?>";
      const tokenValue = document.querySelector(`[name="${tokenName}"]`).value;

      const formData = new FormData();
      formData.append('id', id);
      formData.append('status', status);
      formData.append(tokenName, tokenValue);

      fetch('./sosmed/toggle', {
          method: 'POST',
          body: formData,
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
      <?php echo form_open('sosmed/submit', array('id' => 'myform', 'novalidate' => '')) ?>
      <div class="modal-body">
        <input type="hidden" value="" name="id" />
        <input type="hidden" value="" name="code" />
        <div class="row mb-2">
          <label class="col-md-4 col-form-label">Nama Sosmed</label>
          <div class="col">
            <select name="icon" class="form-select" required>
              <option value="">-- Pilih Sosial Media --</option>
              <option value="bi-facebook">Facebook</option>
              <option value="bi-twitter-x">X (Twitter)</option>
              <option value="bi-instagram">Instagram</option>
              <option value="bi-linkedin">LinkedIn</option>
              <option value="bi-youTube">YouTube</option>
              <option value="bi-music-note">TikTok</option>
              <option value="bi-whatsapp">WhatsApp</option>
              <option value="bi-telegram">Telegram</option>
            </select>
          </div>
        </div>
        <div class="row mb-2">
          <label class="col-md-4 col-form-label">Link</label>
          <div class="col">
            <input name="link" type="text" class="form-control" required placeholder="https://www.facebook.com/klinik">
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
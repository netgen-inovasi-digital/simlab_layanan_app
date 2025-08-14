<style>
    .layanan-item {
        padding: 5px 15px;
<<<<<<< HEAD
=======
        border: 1px solid #ccc;
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
        /* background-color: #f8f9fa; */
        margin-bottom: 5px;
        cursor: grab;
        transition: margin-left 0.2s ease;
        min-height: 180px;
    }

    .layanan-item:active {
        cursor: grabbing;
    }

    .layanan-item img {
        pointer-events: none;
    }

    .child {
        margin-left: 30px;
    }

    .drag-placeholder {
        height: 40px;
        width: 40px;
        border: 2px dashed #0d6efd;
        margin-bottom: 5px;
        border-radius: 5px;
    }

    .status-toggle {
        cursor: pointer;
    }
</style>

<div class="row">
<<<<<<< HEAD
  <div class="col-md-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= esc($title) ?></h5>
        <button id="add" class="btn btn-primary">
          <i class="bi bi-plus-circle-dotted"></i> Tambah
        </button>
      </div>

      <div class="card-body">
        <div id="layanan" class="row g-2">
          <?php
          $encrypter = \Config\Services::encrypter();
          foreach ($getLayanan as $row) {
            $id = bin2hex($encrypter->encrypt($row->id_layanan));
          ?>
            <div id="<?= $id ?>" class="layanan-item col-12 col-sm-6 col-md-4 px-2" draggable="true" data-code="<?= $row->urutan ?>" data-parent="0">
              <div class="border rounded shadow-sm p-3 h-100 d-flex flex-column justify-content-between bg-light position-relative">
                
                <!-- Drag Indicator -->
                <div class="position-absolute top-0 start-50 translate-middle-x mt-1" style="z-index: 2;">
                  
                </div>

                <!-- Gambar -->
                <div class="text-center my-3">
                  <img src="<?= base_url('uploads/' . $row->foto) ?>"
                      alt="<?= esc($row->judul) ?>"
                      class="img-fluid" style="max-height: 80px; object-fit: contain;">
                </div>

                <!-- Judul & Deskripsi -->
                <div class="text-center px-2">
                  <h6 class="fw-bold mb-1"><?= esc($row->judul) ?></h6>
                  <p class="text-muted small"><?= esc($row->deskripsi) ?></p>
                </div>

                <!-- Aksi & Toggle -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                  <?= aksi($id) ?>
                  <div class="form-check form-switch">
                    <input class="form-check-input status-toggle"
                          type="checkbox"
                          data-id="<?= $id ?>"
                          data-bs-toggle="tooltip"
                          title="Aktif/Nonaktif"
                          <?= ($row->status == 'Y') ? 'checked' : '' ?>>
                  </div>
                </div>

              </div>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>

=======
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <button id="add" class="btn btn-primary">
                    <i class="bi bi-plus-circle-dotted"></i> Tambah
                </button>
            </div>
            <div class="card-body ps-4 pe-4">
                <div id="layanan" class="row g-3">
                    <?php
                    $encrypter = \Config\Services::encrypter();
                    foreach ($getLayanan as $row) {
                        $id = bin2hex($encrypter->encrypt($row->id_layanan));
                    ?>
                        <div id="<?= $id ?>"
                            class="layanan-item col-12 col-sm-6 col-md-4 mb-1"
                            draggable="true"
                            data-code="<?= $row->urutan ?>" data-parent="0">

                            <div class="p-3 h-100 d-flex flex-column justify-content-between">
                                <!-- Gambar -->
                                <div class="text-center mb-3">
                                    <img src="<?= base_url('uploads/' . $row->foto) ?>"
                                        alt="<?= esc($row->judul) ?>"
                                        class="img-fluid" style="max-height: 80px; object-fit: contain;">
                                </div>

                                <!-- Judul & Deskripsi -->
                                <div class="text-center">
                                    <h6 class="fw-bold"><?= esc($row->judul) ?></h6>
                                    <p class="text-muted small mb-0"><?= esc($row->deskripsi) ?></p>
                                </div>

                                <!-- Aksi & Toggle -->
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <?= aksi($id) ?>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle"
                                            type="checkbox"
                                            data-id="<?= $id ?>"
                                            data-bs-toggle="tooltip"
                                            title="Aktif/Nonaktif"
                                            <?= ($row->status == 'Y') ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

            </div>
        </div>
    </div>
</div>
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615

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
    var layananContainer = document.querySelector("#layanan");
    var placeholder = document.createElement("div");
    placeholder.classList.add("col-sm-6", "col-md-4", "m-2", "layanan-item", "drag-placeholder");

    function addLayananDragEvents(item) {
        item.addEventListener("dragstart", (e) => {
            draggedItem = item;
            item.style.opacity = "0.5";
            setTimeout(() => {
                layananContainer.insertBefore(placeholder, item.nextSibling);
                item.style.display = "none";
            }, 0);
        });

        item.addEventListener("dragend", (e) => {
            item.style.display = "block";
            item.style.opacity = "1";
            layananContainer.insertBefore(draggedItem, placeholder);
            placeholder.remove();
            updateLayananKode();
            saveAllLayanan();
        });

        item.addEventListener("dragover", (e) => {
            e.preventDefault();
            var after = getDragAfterElement(layananContainer, e.clientX, e.clientY);
            if (after == null)
                layananContainer.appendChild(placeholder);
            else
                layananContainer.insertBefore(placeholder, after);
        });
    }

    function getDragAfterElement(container, x, y) {
        const items = [...container.querySelectorAll(".layanan-item:not(.drag-placeholder):not([style*='display: none'])")];

        return items.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offsetY = y - box.top - box.height / 2;
            const offsetX = x - box.left - box.width / 2;

            const distance = Math.sqrt(offsetX ** 2 + offsetY ** 2); // gabungan jarak X dan Y

            if (offsetY < 0 && distance < closest.distance) {
                return {
                    distance,
                    element: child
                };
            } else {
                return closest;
            }
        }, {
            distance: Number.POSITIVE_INFINITY
        }).element;
    }



    function updateLayananKode() {
        var items = document.querySelectorAll(".layanan-item:not(.drag-placeholder)");
        items.forEach((el, i) => {
            el.dataset.code = i + 1;
        });
    }


    document.querySelectorAll(".layanan-item").forEach(addLayananDragEvents);

    function saveAllLayanan() {
        var tokenName = "<?= csrf_token() ?>";
        var elName = document.querySelector(`[name="${tokenName}"]`);
        var tokenValue = elName.value;

        var formData = new FormData();
        document.querySelectorAll(".layanan-item:not(.drag-placeholder)").forEach((el, i) => {
            formData.append(`items[${i}][id]`, el.id);
            formData.append(`items[${i}][code]`, el.dataset.code);
        });
        formData.append(tokenName, tokenValue);

        fetch('./layanan/updated', {
                method: 'POST',
                body: formData
            }).then(res => res.json())
            .then(data => elName.value = data.xhash)
            .catch(err => console.error(err));
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

            fetch('./layanan/toggle', {
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

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
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
            <?php echo form_open('layanan/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body">
                <input type="hidden" value="" name="id" />
                <input type="hidden" name="code" value="<?= count($getLayanan) ?>">
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Judul</label>
                    <div class="col">
                        <input name="judul" type="text" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Deskripsi</label>
                    <div class="col">
                        <textarea name="deskripsi" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Foto/Icon</label>
                    <div class="col">
                        <input name="foto" type="file" class="form-control">
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Halaman</label>
                    <div class="col">
                        <select name="link" class="form-select">
                            <option value="">-- pilih halaman --</option>
                            <?php foreach ($getHalaman as $halaman): ?>
                                <option value="<?= $halaman->slug ?>" data-nama="<?= htmlspecialchars($halaman->title) ?>"><?= $halaman->title ?> </option>
                            <?php endforeach ?>
                        </select>
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
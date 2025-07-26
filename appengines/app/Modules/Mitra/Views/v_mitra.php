<style>
    .mitra-item {
        padding: 5px 15px;
        border: 1px solid #ccc;
        /* background-color: #f8f9fa; */
        margin-bottom: 5px;
        cursor: grab;
        transition: margin-left 0.2s ease;
        min-height: 180px;
    }

    .mitra-item:active {
        cursor: grabbing;
    }

    .mitra-item img {
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
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
                <button id="add" class="btn btn-primary">
                    <i class="bi bi-plus-circle-dotted"></i> Tambah
                </button>
            </div>
            <div class="card-body ps-4 pe-4">
                <div id="mitra" class="row g-3">
                    <?php
                    $encrypter = \Config\Services::encrypter();
                    foreach ($getMitra as $row) {
                        $id = bin2hex($encrypter->encrypt($row->id_mitra));
                    ?>
                        <div id="<?= $id ?>"
                            class="mitra-item col-12 col-sm-6 col-md-4 mb-1"
                            draggable="true"
                            data-code="<?= $row->urutan ?>"
                            data-parent="0">

                            <div class="p-3 h-100 d-flex flex-column justify-content-between align-items-center text-center bg-light rounded shadow-sm">
                                <!-- Gambar -->
                                <div class="mb-3">
                                    <img src="<?= base_url('uploads/' . $row->foto) ?>"
                                        alt="<?= esc($row->nama) ?>"
                                        class="img-fluid"
                                        style="max-height: 80px; object-fit: contain;">
                                </div>

                                <!-- Nama Mitra -->
                                <h6 class="fw-bold"><?= esc($row->nama) ?></h6>

                                <!-- Aksi & Toggle -->
                                <div class="d-flex justify-content-between align-items-center w-100 mt-3">
                                    <?= aksi($id) ?>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle"
                                            type="checkbox"
                                            data-id="<?= $id ?>"
                                            <?= ($row->status == 'Y') ? 'checked' : '' ?>
                                            data-bs-toggle="tooltip"
                                            title="Aktif/Nonaktif"
                                            >
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
    var mitra = document.getElementById("mitra");
    var placeholder = document.createElement("div");
    placeholder.classList.add("col-sm-6", "col-md-4", "m-2", "mitra-item", "drag-placeholder");

    function addDragEvents(item) {
        item.addEventListener("dragstart", (e) => {
            draggedItem = item;
            item.style.opacity = "0.5";
            setTimeout(() => {
                mitra.insertBefore(placeholder, item.nextSibling);
                item.style.display = "none";
            }, 0);
        });

        item.addEventListener("dragend", () => {
            item.style.display = "block";
            item.style.opacity = "1";

            mitra.insertBefore(draggedItem, placeholder);
            placeholder.remove();

            updateKodeMitra();
            saveAll(); 
        });

        item.addEventListener("dragover", (e) => {
            e.preventDefault();
            const after = getDragAfterElement(mitra, e.clientX, e.clientY);
            if (after == null)
                mitra.appendChild(placeholder);
            else
                mitra.insertBefore(placeholder, after);
        });
    }

    function getDragAfterElement(container, x, y) {
        const elements = [...container.querySelectorAll(".mitra-item:not(.drag-placeholder):not([style*='display: none'])")];

        return elements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offsetY = y - box.top - box.height / 2;
            const offsetX = x - box.left - box.width / 2;

            const distance = Math.sqrt(offsetX ** 2 + offsetY ** 2);

            return (distance < closest.distance) ? {
                distance,
                element: child
            } : closest;
        }, {
            distance: Number.POSITIVE_INFINITY
        }).element;
    }

    function updateKodeMitra() {
        const items = document.querySelectorAll(".mitra-item:not(.drag-placeholder)");
        items.forEach((el, i) => {
            el.dataset.code = i + 1;
        });
    }

    // Inisialisasi semua item mitra
    document.querySelectorAll(".mitra-item").forEach(addDragEvents);
    updateKodeMitra();

    function saveAll() {
        const tokenName = "<?= csrf_token() ?>";
        const elName = document.querySelector(`[name="${tokenName}"]`);
        const tokenValue = elName.value;

        const formData = new FormData();
        const items = document.querySelectorAll(".mitra-item");

        items.forEach((el, i) => {
            formData.append(`items[${i}][id]`, el.id);
            formData.append(`items[${i}][code]`, el.dataset.code);
            formData.append(`items[${i}][parent]`, "0"); // selalu 0
        });
        formData.append(tokenName, tokenValue);

        fetch('./mitra/updated', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
            .then(data => {
                elName.value = data.xhash;
            }).catch(error => {
                console.error("Gagal menyimpan urutan:", error);
            });
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

            fetch('./mitra/toggle', {
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
            <?php echo form_open('mitra/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body">
                <input type="hidden" value="" name="id" />
                <input type="hidden" name="code" value="<?= count($getMitra) ?>">
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Nama</label>
                    <div class="col">
                        <input name="nama" type="text" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Foto</label>
                    <div class="col">
                        <input name="foto" type="file" class="form-control">
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
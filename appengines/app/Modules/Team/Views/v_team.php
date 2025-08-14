<style>
    .team-item {
        padding: 5px 15px;
<<<<<<< HEAD
=======
        border: 1px solid #ccc;
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
        /* background-color: #f8f9fa; */
        margin-bottom: 5px;
        cursor: grab;
        transition: margin-left 0.2s ease;
    }

    .team-item:active {
        cursor: grabbing;
    }

    .team-item img {
        pointer-events: none;
    }

    .child {
        margin-left: 30px;
    }

    .drag-placeholder {
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
<<<<<<< HEAD
                <div id="team" class="row g-2">
=======
                <div id="team" class="row g-3">
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
                    <?php
                    $encrypter = \Config\Services::encrypter();
                    foreach ($getTeam as $row) {
                        $id = bin2hex($encrypter->encrypt($row->id_team));
                    ?>
                        <div id="<?= $id ?>"
                            class="team-item col-12 col-sm-6 col-md-4 mb-1"
                            draggable="true"
                            data-code="<?= $row->urutan ?>"
                            data-parent="0">

<<<<<<< HEAD
                            <div class="border p-3 h-100 d-flex flex-column justify-content-between align-items-center text-center bg-light rounded shadow-sm">
=======
                            <div class="p-3 h-100 d-flex flex-column justify-content-between align-items-center text-center bg-light rounded shadow-sm">
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
                                <!-- Foto -->
                                <div class="mb-3">
                                    <img src="<?= base_url('uploads/' . $row->foto) ?>"
                                        alt="<?= esc($row->nama) ?>"
                                        class="img-fluid"
<<<<<<< HEAD
                                        style="max-height: 100px; object-fit: contain;">
=======
                                        style="max-height: 80px; object-fit: contain;">
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
                                </div>

                                <!-- Nama & Spesialis -->
                                <div>
                                    <h6 class="fw-bold mb-1"><?= esc($row->nama) ?></h6>
                                    <p class="text-muted small mb-0"><?= esc($row->spesialis) ?></p>
                                </div>

                                <!-- Aksi & Toggle -->
                                <div class="d-flex justify-content-between align-items-center w-100 mt-3">
                                    <?= aksi($id) ?>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle"
                                            type="checkbox"
                                            data-id="<?= $id ?>"
                                            <?= ($row->status == 'Y') ? 'checked' : '' ?>
                                            data-bs-toggle="tooltip"
                                            title="Aktif/Nonaktif">
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
    var team = document.getElementById("team");
    var placeholder = document.createElement("div");
    placeholder.classList.add("col-sm-6", "col-md-4", "m-2", "team-item", "drag-placeholder");

    function addDragEvents(item) {
        item.addEventListener("dragstart", (e) => {
            draggedItem = item;
            item.style.opacity = "0.5";
            setTimeout(() => {
                team.insertBefore(placeholder, item.nextSibling);
                item.style.display = "none";
            }, 0);
        });

        item.addEventListener("dragend", () => {
            item.style.display = "block";
            item.style.opacity = "1";

            team.insertBefore(draggedItem, placeholder);
            placeholder.remove();

            updateKodeTeam();
            saveAll();
        });

        item.addEventListener("dragover", (e) => {
            e.preventDefault();
            var after = getDragAfterElement(team, e.clientX, e.clientY);
            if (after == null)
                team.appendChild(placeholder);
            else
                team.insertBefore(placeholder, after);
        });
    }

    function getDragAfterElement(container, x, y) {
        var elements = [...container.querySelectorAll(".team-item:not(.drag-placeholder):not([style*='display: none'])")];

        return elements.reduce((closest, child) => {
            var box = child.getBoundingClientRect();
            var offsetY = y - box.top - box.height / 2;
            var offsetX = x - box.left - box.width / 2;

            var distance = Math.sqrt(offsetX ** 2 + offsetY ** 2);

            return (distance < closest.distance) ? {
                distance,
                element: child
            } : closest;
        }, {
            distance: Number.POSITIVE_INFINITY
        }).element;
    }

    function updateKodeTeam() {
        var items = document.querySelectorAll(".team-item:not(.drag-placeholder)");
        items.forEach((el, i) => {
            el.dataset.code = i + 1;
        });
    }

    // Inisialisasi semua item team
    document.querySelectorAll(".team-item").forEach(addDragEvents);
    updateKodeTeam();

    function saveAll() {
        var tokenName = "<?= csrf_token() ?>";
        var elName = document.querySelector(`[name="${tokenName}"]`);
        var tokenValue = elName.value;

        var formData = new FormData();
        var items = document.querySelectorAll(".team-item");

        items.forEach((el, i) => {
            formData.append(`items[${i}][id]`, el.id);
            formData.append(`items[${i}][code]`, el.dataset.code);
            formData.append(`items[${i}][parent]`, "0"); // selalu 0
        });
        formData.append(tokenName, tokenValue);

        fetch('./team/updated', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
            .then(data => {
                elName.value = data.xhash;
            }).catch(error => {
                console.error("Gagal menyimpan urutan:", error);
            });
    }

    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            var id = this.dataset.id;
            var newStatus = this.checked ? 'Y' : 'N';
            var tokenName = "<?= csrf_token() ?>";
            var tokenValue = document.querySelector(`[name="${tokenName}"]`).value;

            var formData = new FormData();
            formData.append('id', id);
            formData.append('status', newStatus);
            formData.append(tokenName, tokenValue);

            fetch('./team/toggle', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    document.querySelector(`[name="${tokenName}"]`).value = data.xhash;
                    // tampilkan notifikasi berhasil
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
            <?php echo form_open('team/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body">
                <input type="hidden" value="" name="id" />
                <input type="hidden" name="code" value="<?= count($getTeam) ?>">
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Nama</label>
                    <div class="col">
                        <input name="nama_team" type="text" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Spesialis</label>
                    <div class="col">
                        <input name="spesialis" type="text" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-2">
                    <label class="col-md-3 col-form-label">Foto</label>
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
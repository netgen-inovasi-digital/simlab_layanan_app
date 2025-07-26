<style>
    .hero-item {
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
                <div id="hero">
                    <?php
                    $encrypter = \Config\Services::encrypter();
                    foreach ($getHero as $row) {
                        $id = bin2hex($encrypter->encrypt($row->id_hero));
                    ?>
                        <div id="<?= $id ?>"
                            class="hero-item draggable-row mb-3 p-3 bg-light rounded shadow-sm"
                            draggable="true"
                            data-code="<?= $row->urutan ?>"
                            data-parent="0">

                            <div class="d-flex justify-content-between align-items-center">
                                <!-- Bagian Kiri: Gambar + Teks -->
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= base_url('uploads/' . $row->foto) ?>" alt="" class="rounded me-3" width="80" style="object-fit: contain;">
                                    <div>
                                        <div><strong><?= esc($row->judul) ?></strong></div>
                                        <div class="text-muted small"><?= esc($row->deskripsi) ?></div>
                                    </div>
                                </div>

                                <!-- Bagian Kanan: Aksi + Toggle -->
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input status-toggle"
                                            type="checkbox"
                                            data-id="<?= $id ?>"
                                            <?= ($row->status == 'Y') ? 'checked' : '' ?>
                                            data-bs-toggle="tooltip"
                                            title="Aktif/Nonaktif">
                                    </div>
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
    var hero = document.getElementById("hero");
    var placeholder = document.createElement("div");
    placeholder.classList.add("drag-placeholder");

    function addDragEvents(item) {
        item.addEventListener("dragstart", (e) => {
            draggedItem = item;
            item.style.opacity = "0.5";
            setTimeout(() => {
                hero.insertBefore(placeholder, item.nextSibling);
                item.style.display = "none";
            }, 0);
        });

        item.addEventListener("dragend", () => {
            item.style.display = "block";
            item.style.opacity = "1";

            hero.insertBefore(draggedItem, placeholder);
            placeholder.remove();

            updateKodeHero(); // hanya update urutan linear
            saveAll();
        });

        item.addEventListener("dragover", (e) => {
            e.preventDefault();
            var after = getDragAfterElement(hero, e.clientY);
            if (after == null)
                hero.appendChild(placeholder);
            else
                hero.insertBefore(placeholder, after);
        });
    }

    function getDragAfterElement(container, y) {
        var elements = [...container.querySelectorAll(".hero-item:not([style*='display: none'])")];
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

    function updateKodeHero() {
        var items = [...document.querySelectorAll("#hero .hero-item")];
        let counter = 0;

        items.forEach((item) => {
            counter++;
            item.setAttribute("data-code", counter); // urutan linear
            item.setAttribute("data-parent", "0"); // tidak ada parent
        });

        // Jika masih ingin menyimpan nilai terbesar ke hidden input
        document.querySelector('[name="code"]').value = counter;
    }

    // Inisialisasi semua item hero
    document.querySelectorAll(".hero-item").forEach(addDragEvents);
    updateKodeHero();

    function saveAll() {
        var tokenName = "<?= csrf_token() ?>";
        var elName = document.querySelector(`[name="${tokenName}"]`);
        var tokenValue = elName.value;

        var formData = new FormData();
        var items = document.querySelectorAll(".hero-item");

        items.forEach((el, i) => {
            formData.append(`items[${i}][id]`, el.id);
            formData.append(`items[${i}][code]`, el.dataset.code);
            formData.append(`items[${i}][parent]`, "0"); // selalu 0
        });
        formData.append(tokenName, tokenValue);

        fetch('./hero/updated', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
            .then(data => {
                elName.value = data.xhash;
            }).catch(error => {
                console.error("Gagal menyimpan urutan:", error);
            });
    }

    // ===== Event listener untuk toggle status ===== //
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

            fetch('./hero/toggle', {
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
            <?php echo form_open('hero/submit', array('id' => 'myform', 'novalidate' => '')) ?>
            <div class="modal-body">
                <input type="hidden" value="" name="id" />
                <input type="hidden" name="code" value="<?= count($getHero) ?>">
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
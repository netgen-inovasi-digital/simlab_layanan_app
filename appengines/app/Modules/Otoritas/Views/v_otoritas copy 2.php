<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title ?></label>
            </div>
            <div class="card-body">
                <div class="row mb-3 pb-3 border-bottom">
                    <label class="col-auto col-form-label fw-medium">Role</label>
                    <div class="col-auto d-flex">
                        <select id="role" name="role" class="form-select" required>
                            <option value="">-- pilih role --</option>
                            <?php foreach($role as $row) { ?>
                                <option value="<?php echo $row->idRole ?>"><?php echo $row->namaRole ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <small id="info"><em>-- Silahkan pilih role terlebih dahulu.</em></small>
                <div id="menu" class="row d-none">
                    <?php echo form_open('#', array('id'=>'myform')) ?>
                    <div class="col">
                        <ul class="">
                        <?php 
                            echo buildMenu2(0, $menu, 0, $prefArray);
                        ?>
                        </ul>
                    </div>
                    <?php echo form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('role').addEventListener('change', (event) => {
    document.querySelectorAll('.form-check input').forEach(input => {
        input.checked = false;
    });
    document.querySelectorAll('.setting').forEach(item => {
        item.classList.add('d-none');
    });
    const role = event.target.value;
    if(role!="") {
        document.querySelector('#info').classList.add('d-none');
        document.querySelector('#menu').classList.remove('d-none');
        fetch(`./otoritas/edit?s=${role}`)
        .then(response => response.json())
        .then(data => { 
            data.menu.forEach(val => {
                const checkbox = document.querySelector(`.form-check input[value="${val}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    const setting = document.getElementById(`setting${val}`);
                    if(setting) setting.classList.remove('d-none');
                } 
            });
        }) .catch(error => {});
    }
})

var checkboxes = document.querySelectorAll('.form-check-input');
checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', (event) => {
        const value = event.target.value;
        const role = $('#role').val();
        const parent = event.target.getAttribute('parent');
        let checked = "";
        const isChecked = event.target.checked; 
        if(isChecked) {
            checked = true;
            if (parent !== undefined) {
                document.querySelectorAll('.form-check input[value="' + parent + '"]').forEach(input => {
                    input.checked = true; 
                });
            } else {
                document.querySelectorAll('.form-check input[parent="' + value + '"]').forEach(input => {
                    input.checked = true;
                });
            }
            const setting = document.getElementById(`setting${value}`);
            if(setting) setting.classList.remove('d-none');
        } else {
            if (parent !== undefined) {
                const total = document.querySelectorAll('.form-check input[parent="' + parent + '"]:checked').length;
                if (total === 0) {
                    const parentInput = document.querySelector('.form-check input[value="' + parent + '"]');
                    if (parentInput) parentInput.checked = false; 
                }
            } else {
                const childInputs = document.querySelectorAll('.form-check input[parent="' + value + '"]');
                childInputs.forEach(input => { input.checked = false; });
            }
            const setting = document.getElementById(`setting${value}`);
            if(setting) setting.classList.add('d-none');
        }
        const data = [];
        document.querySelectorAll('.form-check input:checked').forEach(input => {
            if (!data.includes(input.value)) {
                data.push(input.value); 
            }
        });

        const form = document.querySelector('#myform');
        const formData = new FormData(form);
        formData.append('role', role);
        data.forEach(value => formData.append('menu[]', value));

        fetch('./otoritas/submit', {
            method: 'POST', body: formData,
        })
        .then(response => response.json())
        .then(data => {
            $('[name='+data.xname+']').val(data.xhash);
        }) .catch(error => {});
    });
});
</script>

<?php 
function buildMenu2($parent, $menu, $tag, $prefArray) {
    $html = "";
    // Memeriksa apakah $parent memiliki anak di dalam parent_menus
    if (isset($menu['parent_menus'][$parent])) {
        $isFirstItem = true; // Flag untuk mendeteksi item pertama

        foreach ($menu['parent_menus'][$parent] as $menu_id) {
            // Ambil data menu saat ini
            $currentMenu = $menu['menus'][$menu_id];
            $id = $currentMenu->kodeMenu;
            $icon = $currentMenu->iconMenu;
            $name = $currentMenu->namaMenu;
            // $label1 = $currentMenu->label1;
            // $label2 = $currentMenu->label2;
            // $label3 = $currentMenu->label3;

            $setting = "";
            if(in_array($id, $prefArray)) {
                $setting = '<div id="setting'.$id.'" class="setting pe-2 d-none">
                    <i role="button" onclick="setClick('.$id.', \'' . strval($name) . '\')" class="fa fa-cog text-success fs-5"></i>
                </div>';
            }

            
            // if($label1!="" || $label2!="" || $label3!="") {
            //     $setting = '<div id="setting'.$id.'" class="setting pe-2 d-none">
            //         <i role="button" onclick="setClick('.$id.', \'' . strval($name) . '\')" class="fa fa-cog text-success fs-5"></i>
            //     </div>';
            // }

            // Tambahkan garis pemisah setelah setiap menu induk, kecuali sebelum item pertama
            if ($parent == 0 && !$isFirstItem) {
                $html .= '<hr class="mt-2 mb-2">';
            }

            $isFirstItem = false; // Setelah iterasi pertama, set flag menjadi false
            // Jika menu ini tidak memiliki anak (menu leaf)
            if (!isset($menu['parent_menus'][$menu_id])) {
                $html .= '<div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="' . $id . '" id="check' . $id . '" parent="' . $parent . '">
                            <label class="form-check-label ms-2" for="check' . $id . '"><i class="fa ' . $icon . ' fa-fw text-muted me-1"></i> ' . $name . '</label>
                        </div>
                        '.$setting.'
                    </div>';
            } else {
                // Jika menu ini memiliki anak (menu parent)
                $html .= '<li id="' . $tag . '" class="list-unstyled">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="' . $id . '" id="check' . $id . '">
                                <label class="form-check-label ms-2" for="check' . $id . '"><i class="fa ' . $icon . ' fa-fw text-muted me-1"></i> ' . $name . '</label>
                            </div>
                            '.$setting.'
                        </div>';

                $html .= '<ul id="' . $tag . '" class="">';
                // Rekursif untuk membangun menu anak
                $html .= buildMenu2($menu_id, $menu, $tag, $prefArray);
                $html .= '</ul></li>';
            }
        }
    }
    return $html;
}
?>

<script>

function setClick(kode, name) {
    alert(kode);
}

function setClick(kode, name){
    document.getElementById("kode").value=kode;
    const rl = document.getElementById("role").value;
    let bodySet = document.getElementById("bodySet");
    bodySet.innerHTML = "";
    fetch(`./otoritas/set?rl=${rl}&id=${kode}`)
    .then(response => response.json())
    .then(data => { 
        data.forEach((item, index) => {
            let newItem = document.createElement("div");
            newItem.classList.add("d-flex", "justify-content-between");
            newItem.setAttribut
            
            let label = document.createElement("label");
            label.classList.add("form-check-label", "ms-2");
            label.setAttribute("for", item.id);
            label.textContent = item.label;
            
            let checkbox = document.createElement("input");
            checkbox.classList.add("form-check-input", "border-secondary");
            checkbox.setAttribute("type", "checkbox");
            checkbox.setAttribute("id", item.id);
            checkbox.setAttribute("value", item.value);
            checkbox.setAttribute("onchange", "valueChange(this)");
            if (item.value === "1") {
                checkbox.checked = true;
            }
            newItem.appendChild(label);
            newItem.appendChild(checkbox);
            bodySet.appendChild(newItem);
            // Tambahkan <hr> sesuai jumlah data
            if ((data.length === 2 && index === 0) || (data.length === 3 && (index === 0 || index === 1))) {
                let hr = document.createElement("hr");
                hr.classList.add("mt-2", "mb-2");
                bodySet.appendChild(hr);
            }
        });
    }) .catch(error => {});
    $('#modalSet').modal('show');
    $('.modal-title-set').text('Setting ' + name);
}

function valueChange(el){
    const rl = document.getElementById("role").value;
    const kd = document.getElementById("kode").value;
    const f = el.id; let val = 0;
    if(el.checked) val = 1;

    fetch(`./otoritas/value?rl=${rl}&id=${kd}&f=${f}&val=${val}`)
    .then(response => response.json())
    .then(data => { 
    }) .catch(error => {});
}
</script>

<div class="modal fade" id="modalSet" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title modal-title-set">Setting</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <input id="kode" type="hidden"/>
            <div id="bodySet" class="modal-body pe-4">
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal"><i class="fa-regular fa-circle-xmark"></i> Tutup</button>
            </div>
        </div>
    </div>
</div>
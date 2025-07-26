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
                            echo buildMenu2(0, $menu);
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
                }
            });
        }) .catch(error => {});
    }
})

var checkboxes = document.querySelectorAll('.form-check-input');
checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', (event) => {
        //console.log(`Checkbox ${value} is ${isChecked ? 'checked' : 'unchecked'}`);
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

        // fetch('./otoritas/submit?role=' + encodeURIComponent(role) +
        //     `&menu[]=${data.map(encodeURIComponent).join('&menu[]=')}` +
        //     '&checked=' + encodeURIComponent(checked), {
        //     method: 'GET',
        // })
        // .then(response => response.text())
        // .then(msg => {
        //     console.log(msg);
        // }).catch(error => {});
    });
});
</script>

<?php 
function buildMenu2($parent, $menu, $tag=0) {
    $html="";
    if (isset($menu['parent_menus'][$parent])) {
        foreach ($menu['parent_menus'][$parent] as $menu_id) {
            
            if (!isset($menu['parent_menus'][$menu_id])) {
                // $html .= '<li id="'.$tag.'" class="">'.$menu['menus'][$menu_id]->namaMenu.'</li>';
                $id = $menu['menus'][$menu_id]->kodeMenu;
                //$parent = $menu['menus'][$menu_id]->induk;
                $html .= '<div class="form-check p-0 ms-3">
                        <input class="form-check-input" type="checkbox" value="'.$id.'" id="check'.$id.'" parent="'.$parent.'"> 
                        <label class="form-check-label ms-2" for="check'.$id.'"><i class="fa '.$menu['menus'][$menu_id]->iconMenu.' fa-fw text-muted me-1"></i> '.$menu['menus'][$menu_id]->namaMenu.'</label>'.
                    '</div>';
            }
            
            if (isset($menu['parent_menus'][$menu_id])) {
                $id = $menu['menus'][$menu_id]->kodeMenu;
                if($parent==0){ $tag++;
                $html .= '<hr class="m-2">'; }
                
                $html .= "<li id='".$tag."' class=''>"; //.$menu['menus'][$menu_id]->namaMenu;
                
                $html .= '<div class="form-check p-0 ms-3" id="'.$tag.'">
                        <input class="form-check-input" type="checkbox" value="'.$id.'" id="check'.$id.'"> 
                        <label class="form-check-label ms-2" for="check'.$id.'"><i class="fa '.$menu['menus'][$menu_id]->iconMenu.' fa-fw text-muted me-1"></i> '.$menu['menus'][$menu_id]->namaMenu.'</label>'.
                    '</div>';
                
                $html .= '<ul id="'.$tag.'" class="">';
                $html .= buildMenu2($menu_id, $menu, $tag);
                $html .= '</ul>';
                $html .= "</li>";
            }
        }
    }
    return $html;
}
?>

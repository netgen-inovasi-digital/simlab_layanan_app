<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title; ?></label>
            </div>

            <div class="card-body">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo esc($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card border-0 bg-light">
                            <div class="card-body py-3">
                                <label class="form-label fw-bold mb-3">Filter Rekap Pembayaran</label>
                                <div class="row g-2 align-items-end">

                                    <div class="col-md-3">
                                        <label for="jenis_layanan" class="form-label small">Jenis Layanan</label>
                                        <select id="jenis_layanan" class="form-select">
                                            <option value="semua">Semua</option>
                                            <?php foreach ($jenis_layanan_options as $option): ?>
                                            <option value="<?php echo esc($option->kode); ?>">
                                                <?php echo esc($option->kode . ' - ' . $option->nama); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="periode" class="form-label small">Periode</label>
                                        <select id="periode" class="form-select">
                                            <option value="hari_ini" selected>Hari Ini</option>
                                            <option value="7_hari">7 Hari Terakhir</option>
                                            <option value="pilih_bulan">Pilih Bulan</option>
                                            <option value="custom">Pilih Tanggal</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2 d-none" id="bulan-wrapper">
                                        <label for="bulan" class="form-label small">Bulan</label>
                                        <select id="bulan" class="form-select">
                                            <?php
                                            $bulanList = [
                                                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                                                '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                                                '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                                                '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                            ];
                                            foreach ($bulanList as $key => $nama): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $nama; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-2 d-none" id="tahun-wrapper">
                                        <label for="tahun" class="form-label small">Tahun</label>
                                        <input type="number" id="tahun" class="form-control" value="<?php echo date('Y'); ?>" min="2000" max="<?php echo date('Y'); ?>">
                                    </div>

                                    <div class="col-md-2" id="awal-wrapper">
                                        <label for="awal" class="form-label small">Tanggal Awal</label>
                                        <input type="date" id="awal" class="form-control" disabled>
                                    </div>

                                    <div class="col-md-2" id="akhir-wrapper">
                                        <label for="akhir" class="form-label small">Tanggal Akhir</label>
                                        <input type="date" id="akhir" class="form-control" disabled>
                                    </div>

                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="d-flex gap-2 w-100">
                                            <button type="button" class="btn btn-success w-100 rounded-pill py-2 download">
                                                <i class="bi bi-file-earmark-excel me-1"></i> Download
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="rekap-hasil" class="mt-4">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDownloadRekap" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalDownloadRekapLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDownloadRekapLabel"><i class="bi bi-file-earmark-excel me-2"></i>Download Rekap Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="downloadJenisLayanan" class="form-label">Jenis Layanan</label>
                        <select id="downloadJenisLayanan" class="form-select">
                            <option value="semua">Semua</option>
                            <?php foreach ($jenis_layanan_options as $option): ?>
                            <option value="<?php echo esc($option->kode); ?>">
                                <?php echo esc($option->kode . ' - ' . $option->nama); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="downloadTanggalAwal" class="form-label">Tanggal Awal</label>
                        <input type="date" id="downloadTanggalAwal" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="downloadTanggalAkhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="downloadTanggalAkhir" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button class="btn btn-success" type="button" onclick="executeDownload()">
                    <i class="bi bi-download me-1"></i> Download Excel
                </button>
            </div>
        </div>
    </div>
</div>
<script>
    var jenisLayananSelect = document.getElementById('jenis_layanan');
    var periodeSelect = document.getElementById('periode');
    var awalInput = document.getElementById('awal');
    var akhirInput = document.getElementById('akhir');
    var bulanWrapper = document.getElementById('bulan-wrapper');
    var tahunWrapper = document.getElementById('tahun-wrapper');
    var bulanSelect = document.getElementById('bulan');
    var tahunInput = document.getElementById('tahun');
    var awalWrapper = document.getElementById('awal-wrapper');
    var akhirWrapper = document.getElementById('akhir-wrapper');
    var rekapHasilContainer = document.getElementById('rekap-hasil');

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    function setTanggalOtomatis() {
        const now = new Date();
        let awal, akhir;

        awalWrapper.classList.remove('d-none');
        akhirWrapper.classList.remove('d-none');
        bulanWrapper.classList.add('d-none');
        tahunWrapper.classList.add('d-none');

        switch (periodeSelect.value) {
            case 'hari_ini':
                awal = akhir = formatDate(now);
                awalInput.disabled = true;
                akhirInput.disabled = true;
                break;
            case '7_hari':
                const tujuhHariLalu = new Date(now);
                tujuhHariLalu.setDate(now.getDate() - 6);
                awal = formatDate(tujuhHariLalu);
                akhir = formatDate(now);
                awalInput.disabled = true;
                akhirInput.disabled = true;
                break;
            case 'pilih_bulan':
                awalWrapper.classList.add('d-none');
                akhirWrapper.classList.add('d-none');
                bulanWrapper.classList.remove('d-none');
                tahunWrapper.classList.remove('d-none');
                return;
            case 'custom':
                awalInput.disabled = false;
                akhirInput.disabled = false;
                awalInput.value = '';
                akhirInput.value = '';
                return;
        }
        awalInput.value = awal;
        akhirInput.value = akhir;
    }

    function renderAllTables(dataArray) {
        const formatCurrency = (number) => `Rp ${Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number)}`;
        let allTablesHTML = ''; 

        if (!dataArray || dataArray.length === 0) {
            return '<div class="alert alert-info">Tidak ada data rekap untuk ditampilkan.</div>';
        }
        
        dataArray.forEach(data => {
            const { title, kolom_header, ulm_detail, non_ulm_detail, total_ulm, total_non_ulm } = data;

            let headerHTML = '';
            if (kolom_header && kolom_header.length > 0) {
                kolom_header.forEach(kolom => {
                    headerHTML += `<th class="text-nowrap">${kolom.kdKolomLabel} (${kolom.kdPersenNONULM}%)</th>`;
                });
            }

            let ulmRowHTML = '';
            if (kolom_header && kolom_header.length > 0) {
                if (ulm_detail && ulm_detail.length > 0) {
                    ulm_detail.forEach(detail => {
                        ulmRowHTML += `<td>${formatCurrency(detail.value)}</td>`;
                    });
                } else {
                    kolom_header.forEach(() => {
                         ulmRowHTML += `<td>${formatCurrency(0)}</td>`;
                    });
                }
            }


            let nonUlmRowHTML = '';
             if (kolom_header && kolom_header.length > 0) {
                if (non_ulm_detail && non_ulm_detail.length > 0) {
                    non_ulm_detail.forEach(detail => {
                        nonUlmRowHTML += `<td>${formatCurrency(detail.value)}</td>`;
                    });
                } else {
                    kolom_header.forEach(() => {
                         nonUlmRowHTML += `<td>${formatCurrency(0)}</td>`;
                    });
                }
            }
            
            allTablesHTML += `
            <div class="mb-4"> 
                <h5 class="mb-3">${title}</h5>
                <div class="table-responsive">
                    <table class="table table-bordered text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Pendapatan</th>
                                <th class="text-nowrap">Total Pembayaran</th>
                                ${headerHTML} 
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-start fw-bold">ULM</td>
                                <td>${formatCurrency(total_ulm)}</td>
                                ${ulmRowHTML}
                            </tr>
                            <tr>
                                <td class="text-start fw-bold">Non-ULM</td>
                                <td>${formatCurrency(total_non_ulm)}</td>
                                ${nonUlmRowHTML}
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            `;
        });
        
        return allTablesHTML; 
    }

    function tampilkanRekap() {
        rekapHasilContainer.innerHTML = `
            <div class="d-flex justify-content-center align-items-center" style="min-height: 150px;">
                <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
                <span class="ms-2">Memuat data...</span>
            </div>`;

        const jenis = jenisLayananSelect.value;
        let tanggal_awal = awalInput.value;
        let tanggal_akhir = akhirInput.value;

        if (periodeSelect.value === 'pilih_bulan') {
            const bulan = bulanSelect.value;
            const tahun = tahunInput.value;
            tanggal_awal = `${tahun}-${bulan}-01`;
            const lastDayOfMonth = new Date(tahun, parseInt(bulan), 0);
            tanggal_akhir = formatDate(lastDayOfMonth);
        }

        if (!tanggal_awal || !tanggal_akhir) {
            rekapHasilContainer.innerHTML = ''; 
            if (typeof sayAlert === 'function') {
                sayAlert('errorModal', 'Error', 'Mohon pilih tanggal awal dan akhir', 'warning');
            } else {
                alert('Mohon pilih tanggal awal dan akhir');
            }
            return;
        }

        const apiUrl = `<?php echo site_url('rekap/datalist'); ?>?jenis_layanan=${jenis}&tanggal_awal=${tanggal_awal}&tanggal_akhir=${tanggal_akhir}`;

        fetch(apiUrl)
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    rekapHasilContainer.innerHTML = renderAllTables(response.data);
                } else {
                    const notificationHTML = `<div class="alert alert-info">${response.message || 'Tidak ada data pembayaran yang ditemukan.'}</div>`;
                    rekapHasilContainer.innerHTML = notificationHTML;
                }
            })
            .catch(err => {
                console.error('Fetch Error:', err);
                rekapHasilContainer.innerHTML = `<div class="alert alert-danger">Terjadi kesalahan saat mengambil data. Silakan coba lagi.</div>`;
            });
    }


    function confirmDownload() {
        const jenis = jenisLayananSelect.value;
        let tanggal_awal = awalInput.value;
        let tanggal_akhir = akhirInput.value;

        if (periodeSelect.value === 'pilih_bulan') {
            const bulan = bulanSelect.value;
            const tahun = tahunInput.value;
            tanggal_awal = `${tahun}-${bulan}-01`;
            const lastDayOfMonth = new Date(tahun, parseInt(bulan), 0);
            tanggal_akhir = formatDate(lastDayOfMonth);
        }

        if (!tanggal_awal || !tanggal_akhir) {
            if (typeof sayAlert === 'function') {
                sayAlert('errorModal', 'Error', 'Mohon atur periode filter utama terlebih dahulu', 'warning');
            } else {
                alert('Mohon atur periode filter utama terlebih dahulu');
            }
            return;
        }

        document.getElementById('downloadJenisLayanan').value = jenis;
        document.getElementById('downloadTanggalAwal').value = tanggal_awal;
        document.getElementById('downloadTanggalAkhir').value = tanggal_akhir;
        
        $('#modalDownloadRekap').modal('show');
    }

    async function executeDownload() {
        const jenis = document.getElementById('downloadJenisLayanan').value;
        const tanggal_awal = document.getElementById('downloadTanggalAwal').value;
        const tanggal_akhir = document.getElementById('downloadTanggalAkhir').value;

        if (!tanggal_awal || !tanggal_akhir) {
            if (typeof sayAlert === 'function') {
                 sayAlert('errorModal', 'Error', 'Tanggal Awal dan Tanggal Akhir di modal harus diisi!', 'warning');
            } else {
                alert('Tanggal Awal dan Tanggal Akhir di modal harus diisi!');
            }
            return;
        }

        const checkUrl = `<?php echo site_url('rekap/datalist'); ?>?jenis_layanan=${jenis}&tanggal_awal=${tanggal_awal}&tanggal_akhir=${tanggal_akhir}`;

        try {
            const response = await fetch(checkUrl);
            if (!response.ok) {
                throw new Error('Server error saat cek data: ' + response.statusText);
            }
            
            const result = await response.json();

            if (result.success === false || !result.data || result.data.length === 0) {
                if (typeof sayAlert === 'function') {
                    sayAlert('errorModal', 'Info', 'Tidak ada data untuk diekspor pada periode yang dipilih.', 'info');
                } else {
                    alert('Tidak ada data untuk diekspor pada periode yang dipilih.');
                }
                return;
            }

            let grandTotalUlm = 0;
            let grandTotalNonUlm = 0;
            result.data.forEach(item => {
                grandTotalUlm += item.total_ulm;
                grandTotalNonUlm += item.total_non_ulm;
            });

            if (grandTotalUlm == 0 && grandTotalNonUlm == 0) {
                if (typeof sayAlert === 'function') {
                    sayAlert('errorModal', 'Info', 'Tidak ada data untuk diekspor (total 0).', 'info');
                } else {
                    alert('Tidak ada data untuk diekspor (total 0).');
                }
                return;
            }

            const downloadUrl = `<?php echo site_url('rekap/download'); ?>?jenis_layanan=${jenis}&tanggal_awal=${tanggal_awal}&tanggal_akhir=${tanggal_akhir}`;
            window.open(downloadUrl, '_blank');
            
            $('#modalDownloadRekap').modal('hide');

        } catch (error) {
            console.error('Error during download check:', error);
            if (typeof sayAlert === 'function') {
                sayAlert('errorModal', 'Error', 'Gagal memverifikasi data: '(error.message || 'Unknown error'), 'error');
            } else {
                alert('Gagal memverifikasi data: ' + (error.message || 'Unknown error'));
            }
        }
    }


    
    document.querySelector('.download').addEventListener('click', function(e) {
        e.preventDefault();
        confirmDownload(); 
    });

    jenisLayananSelect.addEventListener('change', tampilkanRekap);
    
    periodeSelect.addEventListener('change', () => {
        setTanggalOtomatis();
        tampilkanRekap(); 
    });
    
    bulanSelect.addEventListener('change', tampilkanRekap);
    
    tahunInput.addEventListener('input', debounce(tampilkanRekap, 500));
    
    awalInput.addEventListener('change', tampilkanRekap);
    akhirInput.addEventListener('change', tampilkanRekap);


    setTanggalOtomatis();
    tampilkanRekap();
</script>
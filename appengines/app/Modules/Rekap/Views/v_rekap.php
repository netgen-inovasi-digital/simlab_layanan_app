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
                                            <option value="<?php echo esc($option->jenKode); ?>">
                                                <?php echo esc($option->jenKode . ' - ' . $option->jenNama); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label for="periode" class="form-label small">Periode</label>
                                        <select id="periode" class="form-select">
                                            <option value="hari_ini">Hari Ini</option>
                                            <option value="7_hari" selected>7 Hari Terakhir</option>
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
                                            <button type="button" class="btn btn-primary w-100 rounded-pill py-2 tampil">
                                                <i class="bi bi-search me-1"></i> Tampilkan
                                            </button>
                                            
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
                    <!-- Hasil tabel akan dimuat di sini oleh JavaScript -->
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
                            <option value="<?php echo esc($option->jenKode); ?>">
                                <?php echo esc($option->jenKode . ' - ' . $option->jenNama); ?>
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
    // --- ELEMENT SELECTORS ---
    const jenisLayananSelect = document.getElementById('jenis_layanan');
    const periodeSelect = document.getElementById('periode');
    const awalInput = document.getElementById('awal');
    const akhirInput = document.getElementById('akhir');
    const bulanWrapper = document.getElementById('bulan-wrapper');
    const tahunWrapper = document.getElementById('tahun-wrapper');
    const bulanSelect = document.getElementById('bulan');
    const tahunInput = document.getElementById('tahun');
    const awalWrapper = document.getElementById('awal-wrapper');
    const akhirWrapper = document.getElementById('akhir-wrapper');
    const rekapHasilContainer = document.getElementById('rekap-hasil');
    const tampilkanButton = document.querySelector('.tampil'); // Ambil tombol tampilkan

    // --- HELPER FUNCTIONS ---
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    /**
     * [BARU] Fungsi Debounce
     * Menunda eksekusi fungsi agar tidak dipanggil terlalu sering.
     */
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    // --- PENGELOLAAN FILTER TANGGAL ---
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

    // --- FUNGSI UNTUK MERENDER TABEL (VERSI DINAMIS) ---
    function renderRekapTable(data) {
        // Ekstrak data dari respons JSON baru
        // [PERUBAHAN] Tambahkan 'title'
        const { title, kolom_header, ulm_detail, non_ulm_detail, total_ulm, total_non_ulm } = data;
        const formatCurrency = (number) => `Rp ${Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(number)}`;

        // 1. Bangun Kolom Header (THEAD) secara dinamis
        let headerHTML = '';
        if (kolom_header && kolom_header.length > 0) {
            kolom_header.forEach(kolom => {
                // Ambil label dan persen dari DB
                headerHTML += `<th class="text-nowrap">${kolom.kdKolomLabel} (${kolom.kdPersenNONULM}%)</th>`;
            });
        }

        // 2. Bangun sel data (TD) untuk baris ULM
        let ulmRowHTML = '';
        if (ulm_detail && ulm_detail.length > 0) {
            ulm_detail.forEach(detail => {
                ulmRowHTML += `<td>${formatCurrency(detail.value)}</td>`;
            });
        }

        // 3. Bangun sel data (TD) untuk baris Non-ULM
        let nonUlmRowHTML = '';
        if (non_ulm_detail && non_ulm_detail.length > 0) {
            non_ulm_detail.forEach(detail => {
                nonUlmRowHTML += `<td>${formatCurrency(detail.value)}</td>`;
            });
        }

        // 4. Gabungkan semuanya menjadi tabel HTML
        // [PERUBAHAN] Ganti <h5> statis dengan data.title
        const tableHTML = `
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
        </div>`;
        
        return tableHTML;
    }

    // --- FUNGSI UTAMA UNTUK MENGAMBIL DAN MENAMPILKAN DATA ---
    function tampilkanRekap() {
        // [PERUBAHAN] Tampilkan loading di tombol
        if(tampilkanButton) {
            tampilkanButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Memuat...';
            tampilkanButton.disabled = true;
        }

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
            // [PERUBAHAN] Sembunyikan loading jika gagal validasi
            if(tampilkanButton) {
                tampilkanButton.innerHTML = '<i class="bi bi-search me-1"></i> Tampilkan';
                tampilkanButton.disabled = false;
            }
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
                    // Data ada (meskipun mungkin 0), render tabel
                    rekapHasilContainer.innerHTML = renderRekapTable(response.data);
                } else {
                    // Handle jika data.success == false (data tidak ditemukan)
                    const notificationHTML = `<div class="alert alert-info">${response.message || 'Tidak ada data pembayaran yang ditemukan.'}</div>`;
                    rekapHasilContainer.innerHTML = notificationHTML;
                }
            })
            .catch(err => {
                console.error('Fetch Error:', err);
                rekapHasilContainer.innerHTML = `<div class="alert alert-danger">Terjadi kesalahan saat mengambil data. Silakan coba lagi.</div>`;
            })
            .finally(() => {
                // [PERUBAHAN] Kembalikan tombol ke state normal
                if(tampilkanButton) {
                    tampilkanButton.innerHTML = '<i class="bi bi-search me-1"></i> Tampilkan';
                    tampilkanButton.disabled = false;
                }
            });
    }

    // --- FUNGSI UNTUK MODAL DAN DOWNLOAD ---

    /**
     * 1. Fungsi ini dipanggil saat tombol download utama diklik.
     */
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

    /**
     * 2. (INI YANG DIUBAH)
     * Fungsi ini dipanggil oleh tombol "Download Excel" DI DALAM MODAL.
     * Sekarang akan mengecek data dulu sebelum men-download.
     */
    async function executeDownload() {
        // Ambil nilai dari MODAL
        const jenis = document.getElementById('downloadJenisLayanan').value;
        const tanggal_awal = document.getElementById('downloadTanggalAwal').value;
        const tanggal_akhir = document.getElementById('downloadTanggalAkhir').value;

        // Validasi
        if (!tanggal_awal || !tanggal_akhir) {
            if (typeof sayAlert === 'function') {
                 sayAlert('errorModal', 'Error', 'Tanggal Awal dan Tanggal Akhir di modal harus diisi!', 'warning');
            } else {
                alert('Tanggal Awal dan Tanggal Akhir di modal harus diisi!');
            }
            return;
        }

        // BUAT URL UNTUK CEK DATA (KE dataList)
        const checkUrl = `<?php echo site_url('rekap/datalist'); ?>?jenis_layanan=${jenis}&tanggal_awal=${tanggal_awal}&tanggal_akhir=${tanggal_akhir}`;

        try {
            // Panggil API untuk cek data
            const response = await fetch(checkUrl);
            if (!response.ok) {
                throw new Error('Server error saat cek data: ' + response.statusText);
            }
            
            const data = await response.json();

            // Cek jika data.success == false (artinya data kosong ATAU total 0)
            // Kita cek totalnya langsung
            if (data.success === false || (data.data.total_ulm == 0 && data.data.total_non_ulm == 0)) {
                
                // TAMPILKAN SAYALERT DAN BERHENTI
                if (typeof sayAlert === 'function') {
                    sayAlert('errorModal', 'Info', 'Tidak ada data untuk diekspor pada periode yang dipilih.', 'info');
                } else {
                    alert('Tidak ada data untuk diekspor pada periode yang dipilih.');
                }
                return; // Berhenti di sini, jangan download
            }

            // Jika data.success == true, LANJUTKAN DOWNLOAD
            
            // Buat URL untuk download
            const downloadUrl = `<?php echo site_url('rekap/download'); ?>?jenis_layanan=${jenis}&tanggal_awal=${tanggal_awal}&tanggal_akhir=${tanggal_akhir}`;
            window.open(downloadUrl, '_blank');
            
            // Tutup modal
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


    // --- EVENT LISTENERS ---
    
    // [PERUBAHAN] Tombol Tampilkan sekarang hanya salah satu cara untuk memicu
    tampilkanButton.addEventListener('click', function(e) {
        e.preventDefault();
        tampilkanRekap();
    });

    document.querySelector('.download').addEventListener('click', function(e) {
        e.preventDefault();
        confirmDownload(); 
    });


    // [PERUBAHAN] Event listener otomatis untuk filter
    jenisLayananSelect.addEventListener('change', tampilkanRekap);
    
    periodeSelect.addEventListener('change', () => {
        setTanggalOtomatis();
        tampilkanRekap(); // Panggil rekap setelah tanggal di-set
    });
    
    bulanSelect.addEventListener('change', tampilkanRekap);
    
    // Gunakan debounce untuk input ketik agar tidak memanggil API di setiap ketukan
    tahunInput.addEventListener('input', debounce(tampilkanRekap, 500));
    
    awalInput.addEventListener('change', tampilkanRekap);
    akhirInput.addEventListener('change', tampilkanRekap);


    // --- INITIALIZATION ---
    setTanggalOtomatis();
    tampilkanRekap(); // [PERUBAHAN] Langsung panggil saat halaman dimuat
</script>
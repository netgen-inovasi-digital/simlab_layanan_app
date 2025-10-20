<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <label class="card-title mb-0"><?php echo $title; ?></label>
            </div>

            <div class="card-body">
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

                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="d-flex gap-2 w-100">
                                            <button type="button" class="btn btn-primary w-100 rounded-pill py-2 tampil">Tampilkan</button>
                                            <button type="button" class="btn btn-success w-100 rounded-pill py-2 download">Download</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="rekap-hasil" class="mt-4"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- ELEMENT SELECTORS ---
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

    // --- HELPER FUNCTIONS ---
    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    // --- PENGELOLAAN FILTER TANGGAL ---
    function setTanggalOtomatis() {
        const now = new Date();
        let awal, akhir;

        // Reset semua dulu
        awalWrapper.classList.remove('d-none');
        akhirWrapper.classList.remove('d-none');
        bulanWrapper.classList.add('d-none');
        tahunWrapper.classList.add('d-none');

        switch (periodeSelect.value) {
            case 'hari_ini':
                awal = akhir = formatDate(now);
                // DIKEMBALIKAN: Logika disabled/enabled dikembalikan seperti kode asli Anda
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
                return; // Langsung keluar agar value tidak di-set

            case 'custom':
                awalInput.disabled = false;
                akhirInput.disabled = false;
                awalInput.value = '';
                akhirInput.value = '';
                return; // Langsung keluar agar value tidak di-set
        }

        awalInput.value = awal;
        akhirInput.value = akhir;
    }

    // --- FUNGSI UNTUK MERENDER TABEL ---
    function renderRekapTable(data) {
        const {
            ulm,
            non_ulm
        } = data;
        const formatCurrency = (number) => `Rp ${Intl.NumberFormat('id-ID').format(number)}`;

        const createRow = (title, dataRow) => `
            <tr>
                <td class="text-start fw-bold">${title}</td>
                <td>${formatCurrency(dataRow.total_pembayaran)}</td>
                <td>${formatCurrency(dataRow.bahan_kimia)}</td>
                <td>${formatCurrency(dataRow.operasional)}</td>
                <td>${formatCurrency(dataRow.jasa_profesi)}</td>
                <td>${formatCurrency(dataRow.pendapatan_instansi)}</td>
            </tr>
        `;

        const tableHTML = `
        <h5 class="mb-3">A. Layanan Pengujian Sampel</h5>
        <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">Pendapatan</th>
                        <th>Total Pembayaran</th>
                        <th>Bahan Kimia (35%)</th>
                        <th>Operasional (10%)</th>
                        <th>Jasa Profesi (45%)</th>
                        <th>Pendapatan Instansi (10%)</th>
                    </tr>
                </thead>
                <tbody>
                    ${createRow('ULM', ulm)}
                    ${createRow('Non-ULM', non_ulm)}
                </tbody>
            </table>
        </div>`;
        return tableHTML;
    }

    // --- FUNGSI UTAMA UNTUK MENGAMBIL DAN MENAMPILKAN DATA ---
    function tampilkanRekap() {
        const jenis = document.getElementById('jenis_layanan').value;
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
                let notificationHTML = '';
                let tableData;

                // PERUBAHAN: Menampilkan notifikasi DAN tabel berisi nol sesuai permintaan
                if (response.success) {
                    tableData = response.data;
                } else {
                    // Buat notifikasi
                    notificationHTML = `<div class="alert alert-info">Tidak ada data pembayaran yang ditemukan untuk periode yang dipilih.</div>`;
                    // Buat data kosong untuk tabel
                    tableData = {
                        ulm: { total_pembayaran: 0, bahan_kimia: 0, operasional: 0, jasa_profesi: 0, pendapatan_instansi: 0 },
                        non_ulm: { total_pembayaran: 0, bahan_kimia: 0, operasional: 0, jasa_profesi: 0, pendapatan_instansi: 0 }
                    };
                }
                // Gabungkan notifikasi (jika ada) dengan tabel
                rekapHasilContainer.innerHTML = notificationHTML + renderRekapTable(tableData);
            })
            .catch(err => {
                console.error('Fetch Error:', err);
                rekapHasilContainer.innerHTML = `<div class="alert alert-danger">Terjadi kesalahan saat mengambil data. Silakan coba lagi.</div>`;
            });
    }

    // --- EVENT LISTENERS ---
    document.querySelector('.tampil').addEventListener('click', function(e) {
        e.preventDefault();
        tampilkanRekap();
    });

    document.querySelector('.download').addEventListener('click', function() {
        const jenis = document.getElementById('jenis_layanan').value;
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
                sayAlert('errorModal', 'Error', 'Mohon pilih tanggal untuk di-download', 'warning');
            } else {
                alert('Mohon pilih tanggal untuk di-download');
            }
            return;
        }
        
        const url = `<?php echo site_url('rekap/download'); ?>?jenis_layanan=${jenis}&tanggal_awal=${tanggal_awal}&tanggal_akhir=${tanggal_akhir}`;
        window.open(url, '_blank');
    });

    // --- INITIALIZATION ---
    // DIKEMBALIKAN: Memanggil fungsi langsung saat script dimuat, sama seperti kode asli
    setTanggalOtomatis();
    periodeSelect.addEventListener('change', setTanggalOtomatis);
</script>
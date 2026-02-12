    <style>
        .board:hover {
            transform: translateY(-2px);
            background-color: white;
        }
    </style>

    <div class="row">
        <div class=" col-lg">
            <div class="board">
                <div class="board-left">
                    <p class="text-muted mb-1"><?= $greeting ?? '' ?>, <strong><?= $nama_user ?? '' ?></strong> 👋</p>
                    <div class="value">DASHBOARD</div>
                </div>
                <div class="board-right">
                    <i class="bi bi-bar-chart-fill fs-2 text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($role_id == 1) : ?>
        <!-- Total Overview Dashboard -->
        <div class="row mt-3">
            <div class="col-lg-12">
                <h5 class="mb-3">Total :</h5>
            </div>
        </div>

        <div class="row">
            <!-- Pengunjung Hari Ini -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>layanan masuk</h6>
                        <div class="value"><?= $totalLayananMasuk ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-clipboard-check fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Pengunjung Bulan Ini -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHU diterbitkan</h6>
                        <div class="value"><?= $totalLHUDiterbitkan ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-file-earmark-check fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Total Pengunjung -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Invoice</h6>
                        <div class="value"><?= $totalInvoice ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-receipt fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!--Jumlah Berita -->
            <div class=" col-lg-4 me">
                <div class="board">
                    <div class="board-left">
                        <h6>Layanan Pengujian Sampel</h6>
                        <div class="value"><?= $totalLayananPengujian ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-flask fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Jumlah Halaman -->
            <div class=" col-lg-4 me">
                <div class="board">
                    <div class="board-left">
                        <h6>Pelanggan Terdaftar</h6>
                        <div class="value"><?= $totalPelanggan ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <!-- User Icon -->
                        <i class="bi bi-people fs-2 text-primary"></i>

                    </div>
                </div>
            </div>

            <!-- Total Pengumuman -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Pengelola Layanan Sampel</h6>
                        <div class="value"><?= $totalPengelola ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <!-- Chart Icon -->
                        <i class="bi bi-person-gear fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Status Layanan -->
        <div class="row mt-3">
            <div class="col-lg-12">
                <h5 class="mb-3">Progress Status Layanan : </h5>
            </div>
        </div>

        <div class="row">
            <!-- Status In Review Manajer -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>In Review Manajer</h6>
                        <div class="value"><?= $statusInReviewManajer ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-clock-history fs-2 text-warning"></i>
                    </div>
                </div>
            </div>

            <!-- Status In Review Admin -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>In Review Admin</h6>
                        <div class="value"><?= $statusInReviewAdmin ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-hourglass-split fs-2 text-info"></i>
                    </div>
                </div>
            </div>

            <!-- Status Penerbitan Invoice -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Penerbitan Invoice</h6>
                        <div class="value"><?= $statusPenerbitanInvoice ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-file-earmark-plus fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Status Verifikasi Pembayaran -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Verifikasi Pembayaran</h6>
                        <div class="value"><?= $statusVerifikasiPembayaran ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-cash-coin fs-2 text-success"></i>
                    </div>
                </div>
            </div>

            <!-- Status Pengujian -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Pengujian</h6>
                        <div class="value"><?= $statusPengujian ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-search fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Status Peninjauan LHUS -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Peninjauan LHUS</h6>
                        <div class="value"><?= $statusMemprosesLHUS ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-file-earmark-text fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Status Memproses LHU -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Memproses LHU</h6>
                        <div class="value"><?= $statusMemprosesLHU ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-file-earmark-arrow-up fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>

            <!-- Status Selesai -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Selesai (LHU Terkirim)</h6>
                        <div class="value"><?= $statusSelesai ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-check2-all fs-2 text-success"></i>
                    </div>
                </div>
            </div>

            <!-- Total Kaji Ulang -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Uji Ulang</h6>
                        <div class="value"><?= $totalKajiUlang ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-arrow-repeat fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if ($role_id == 4) : ?>
        <!-- Progress Status Layanan Manajer Teknis -->
        <div class="row mt-3">
            <div class="col-lg-12">
                <h5 class="mb-3">Progress Status : </h5>
            </div>
        </div>

        <div class="row">
            <!-- Layanan Belum Direview -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Layanan Belum Direview</h6>
                        <div class="value"><?= $mtBelumReview ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-clock-history fs-2 text-warning"></i>
                    </div>
                </div>
            </div>

            <!-- Layanan Terkirim ke Admin -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Layanan Terkirim ke Admin</h6>
                        <div class="value"><?= $mtTerkirimKeAdmin ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-send-check fs-2 text-info"></i>
                    </div>
                </div>
            </div>

            <!-- LHUS Ditolak -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHUS Ditolak</h6>
                        <div class="value"><?= $mtLhusDitolak ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-x-circle fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- LHUS Belum Ditinjau -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHUS Belum Ditinjau</h6>
                        <div class="value"><?= $mtLhusBelumTinjau ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-file-earmark-text fs-2 text-secondary"></i>
                    </div>
                </div>
            </div>

            <!-- LHUS Diproses Kembali -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHUS Diproses Kembali</h6>
                        <div class="value"><?= $mtLhusDiprosesKembali ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-arrow-repeat fs-2 text-warning"></i>
                    </div>
                </div>
            </div>

            <!-- LHUS Disetujui -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHUS Disetujui</h6>
                        <div class="value"><?= $mtLhusDisetujui ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if ($role_id == 6) : ?>
        <!-- Progress Status Layanan Penyelia -->
        <div class="row mt-3">
            <div class="col-lg-12">
                <h5 class="mb-3">Progress Status Layanan  :</h5>
            </div>
        </div>

        <div class="row">
            <!-- Sedang Dalam Pengujian -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Dalam Pengujian</h6>
                        <div class="value"><?= $pySedangPengujian ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-flask fs-2 text-info"></i>
                    </div>
                </div>
            </div>

            <!-- LHUS Terunggah -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHUS Terunggah</h6>
                        <div class="value"><?= $pyLhusTerunggah ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-cloud-upload fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- LHUS Diverifikasi Manajer Teknis -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHUS Diverifikasi Manajer Teknis</h6>
                        <div class="value"><?= $pyLhusVerifikasi ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- LHUS Disetujui -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHUS Diterima</h6>
                        <div class="value"><?= $pyLhusDisetujui ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                    </div>
                </div>
            </div>

            <!-- LHUS Ditolak -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHUS Ditolak</h6>
                        <div class="value"><?= $pyLhusDitolak ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-x-circle fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if ($role_id == 2) : ?>
        <!-- Total Pelanggan -->
        <div class="row mt-3">
            <div class="col-lg-12">
                <h5 class="mb-3">Total :</h5>
            </div>
        </div>

        <div class="row">
            <!-- Layanan Sampel Dipesan -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Layanan Sampel Dipesan</h6>
                        <div class="value"><?= $plTotalLayananSampel ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-clipboard-check fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- LHU Terbit -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHU Terbit</h6>
                        <div class="value"><?= $plTotalLhuTerbit ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-file-earmark-check fs-2 text-success"></i>
                    </div>
                </div>
            </div>

            <!-- Transaksi -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Transaksi</h6>
                        <div class="value"><?= $plTotalTransaksi ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-receipt fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Status Layanan Pelanggan -->
        <div class="row mt-3">
            <div class="col-lg-12">
                <h5 class="mb-3">Progress Status Layanan :</h5>
            </div>
        </div>

        <div class="row">
            <!-- In Review Petugas -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>In Review Petugas</h6>
                        <div class="value"><?= $plInReviewPetugas ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-clock-history fs-2 text-info"></i>
                    </div>
                </div>
            </div>

            <!-- Pengujian Dilakukan -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Pengujian Dilakukan</h6>
                        <div class="value"><?= $plPengujian ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-flask fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Verifikasi Hasil Uji -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Verifikasi Hasil Uji</h6>
                        <div class="value"><?= $plVerifikasiHasilUji ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-search fs-2 text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Penerbitan LHUS -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Penerbitan LHUS</h6>
                        <div class="value"><?= $plPenerbitanLhus ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-file-earmark-text fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Verifikasi LHUS -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Verifikasi LHU</h6>
                        <div class="value"><?= $plVerifikasiLhus ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-hourglass-split fs-2 text-info"></i>
                    </div>
                </div>
            </div>

            <!-- LHU Diterbitkan -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>LHU Diterbitkan</h6>
                        <div class="value"><?= $plLhuDiterbitkan ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-check2-all fs-2 text-success"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Uji Ulang -->
            <div class="col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Uji Ulang</h6>
                        <div class="value"><?= $plUjiUlang ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-arrow-repeat fs-2 text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    </div>
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

    <?php if ($role_id != 2) : ?>
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

    </div>
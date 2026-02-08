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
        <div class="row">
            <!-- Pengunjung Hari Ini -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Total layanan masuk</h6>
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
                        <h6>Total LHU diterbitkan</h6>
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
                        <h6>Total Invoice</h6>
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
                        <h6>Total Layanan Pengujian Sampel</h6>
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
                        <h6>Total Pelanggan Terdaftar</h6>
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
                        <h6>Total Pengelola Layanan Sampel</h6>
                        <div class="value"><?= $totalPengelola ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <!-- Chart Icon -->
                        <i class="bi bi-person-gear fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    </div>
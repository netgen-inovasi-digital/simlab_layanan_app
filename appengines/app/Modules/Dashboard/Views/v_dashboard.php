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
<<<<<<< HEAD
                    <p class="text-muted mb-1"><?= $greeting ?? '' ?>, <strong><?= $nama_user ?? '' ?></strong> 👋</p>
=======
                    <p class="text-muted mb-1"><?= $greeting ?>, <strong><?= $nama_user ?></strong> 👋</p>
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
                    <div class="value">DASHBOARD</div>
                </div>
                <div class="board-right">
                    <i class="bi bi-bar-chart-fill fs-2 text-primary"></i>
                </div>
            </div>
        </div>
    </div>

<<<<<<< HEAD
    <?php if ($role_id != 2) : ?>
        <div class="row">
            <!-- Pengunjung Hari Ini -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Pengunjung (Hari Ini)</h6>
                        <div class="value"><?= $viewsToday ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-eye fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Pengunjung Bulan Ini -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Pengunjung (Bulan Ini)</h6>
                        <div class="value"><?= $viewsThisMonth ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-eye fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Total Pengunjung -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Pengunjung (Total)</h6>
                        <div class="value"><?= $viewsAllTime ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-eye fs-2 text-primary"></i>
                    </div>
=======
    <div class="row">
        <!-- Pengunjung Hari Ini -->
        <div class=" col-lg-4">
            <div class="board">
                <div class="board-left">
                    <h6>Hari Ini</h6>
                    <div class="value"><?= $viewsToday ?? 0 ?></div>
                </div>
                <div class="board-right">
                    <i class="bi bi-eye fs-2 text-primary"></i>
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615
                </div>
            </div>
        </div>

<<<<<<< HEAD
        <div class="row">
            <!--Jumlah Berita -->
            <div class=" col-lg-4 me">
                <div class="board">
                    <div class="board-left">
                        <h6>Total Berita</h6>
                        <div class="value"><?= $totalPosts ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <i class="bi bi-newspaper fs-2 text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Jumlah Halaman -->
            <div class=" col-lg-4 me">
                <div class="board">
                    <div class="board-left">
                        <h6>Total Halaman</h6>
                        <div class="value"><?= $totalPages ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <!-- User Icon -->
                        <i class="bi bi-file-earmark-text fs-2 text-primary"></i>

                    </div>
                </div>
            </div>

            <!-- Total Pengumuman -->
            <div class=" col-lg-4">
                <div class="board">
                    <div class="board-left">
                        <h6>Total Pengumuman</h6>
                        <div class="value"><?= $totalPengumuman ?? 0 ?></div>
                    </div>
                    <div class="board-right">
                        <!-- Chart Icon -->
                        <i class="bi bi-megaphone fs-2 text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
=======
        <!-- Pengunjung Bulan Ini -->
        <div class=" col-lg-4">
            <div class="board">
                <div class="board-left">
                    <h6>Bulan Ini</h6>
                    <div class="value"><?= $viewsThisMonth ?? 0 ?></div>
                </div>
                <div class="board-right">
                    <i class="bi bi-eye fs-2 text-primary"></i>
                </div>
            </div>
        </div>

        <!-- Total Pengunjung -->
        <div class=" col-lg-4">
            <div class="board">
                <div class="board-left">
                    <h6>Total Pengunjung</h6>
                    <div class="value"><?= $viewsAllTime ?? 0 ?></div>
                </div>
                <div class="board-right">
                    <i class="bi bi-eye fs-2 text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!--Jumlah Berita -->
        <div class=" col-lg-4 me">
            <div class="board">
                <div class="board-left">
                    <h6>Total Berita</h6>
                    <div class="value"><?= $totalPosts ?? 0 ?></div>
                </div>
                <div class="board-right">
                    <i class="bi bi-newspaper fs-2 text-primary"></i>
                </div>
            </div>
        </div>

        <!-- Jumlah Halaman -->
        <div class=" col-lg-4 me">
            <div class="board">
                <div class="board-left">
                    <h6>Total Halaman</h6>
                    <div class="value"><?= $totalPages ?? 0 ?></div>
                </div>
                <div class="board-right">
                    <!-- User Icon -->
                    <i class="bi bi-file-earmark-text fs-2 text-primary"></i>

                </div>
            </div>
        </div>

        <!-- Total Pengumuman -->
        <div class=" col-lg-4">
            <div class="board">
                <div class="board-left">
                    <h6>Total Pengumuman</h6>
                    <div class="value"><?= $totalPengumuman ?? 0 ?></div>
                </div>
                <div class="board-right">
                    <!-- Chart Icon -->
                    <i class="bi bi-megaphone fs-2 text-primary"></i>
                </div>
            </div>
        </div>
>>>>>>> df8c327176c0d2352c9b643155da517b0816f615

    </div>
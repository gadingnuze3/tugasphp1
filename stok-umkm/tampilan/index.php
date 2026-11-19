<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$categories = $conn->query("SELECT * FROM categories");
$cat_list = [];
while ($c = $categories->fetch_assoc()) {
    $cat_list[] = $c;
}

$selected_cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$where_clause = ($selected_cat > 0) ? "WHERE i.category_id = $selected_cat" : "";

$query = "SELECT i.*, c.name as category_name 
          FROM inventory i 
          JOIN categories c ON i.category_id = c.id 
          $where_clause
          ORDER BY (i.stock <= i.min_stock) DESC, i.name ASC";
$result = $conn->query($query);

$items = [];
$critical_count = 0;
$warning_count  = 0;
$safe_count     = 0;
$near_exp_count = 0;
$today          = new DateTime();

while ($row = $result->fetch_assoc()) {
    $stock = (float)$row['stock'];
    $min   = (float)$row['min_stock'];
    $usage = (float)$row['avg_daily_usage'];

    if ($stock <= ($min * 0.5)) {
        $row['status'] = 'KRITIS';
        $row['badge_class'] = 'bg-danger';
        $critical_count++;
    } elseif ($stock <= $min) {
        $row['status'] = 'MENIPIS';
        $row['badge_class'] = 'bg-warning text-dark';
        $warning_count++;
    } else {
        $row['status'] = 'AMAN';
        $row['badge_class'] = 'bg-success';
        $safe_count++;
    }

    $row['exp_status'] = 'AMAN';
    $row['exp_days']   = null;
    if (!empty($row['expired_date'])) {
        $exp_date = new DateTime($row['expired_date']);
        $diff     = (int)$today->diff($exp_date)->format("%r%a");
        $row['exp_days'] = $diff;
        if ($diff < 0) {
            $row['exp_status'] = 'EXPIRED';
            $near_exp_count++;
        } elseif ($diff <= 14) {
            $row['exp_status'] = 'SEGERA_EXP';
            $near_exp_count++;
        }
    }

    $row['days_left'] = ($usage > 0) ? round($stock / $usage, 1) : 999;
    $target_7_days = $usage * 7;
    $row['suggested_buy'] = max(0, ceil(($target_7_days + $min) - $stock));
    $row['estimated_cost'] = $row['suggested_buy'] * $row['cost_per_unit'];

    $items[] = $row;
}

// Pesan WhatsApp otomatis ke Distributor
$wa_text = "❄️ *ORDER RESTOCK FROZEN FOOD* ❄️%0A%0AHalo Distributor, berikut daftar stok freezer yang menipis & harus segera dikirim:%0A";
foreach ($items as $item) {
    if ($item['status'] != 'AMAN') {
        $wa_text .= "- *{$item['name']}*: Sisa {$item['stock']} {$item['unit']} (Order: {$item['suggested_buy']} {$item['unit']})%0A";
    }
}
$wa_link = "https://api.whatsapp.com/send?phone=6281294433811&text=" . $wa_text;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KKS Mart - Manajemen Stok Frozen Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep-freeze: #619be2;
            --ocean-cold: #134074;
            --ice-blue: #00A8E8;
            --frost-bg: #E4F1FC;
            --pure-snow: #FFFFFF;
            --ink: #0B2545;              /* warna teks utama (gelap, kontras di atas salju) */
            --glass: rgba(255, 255, 255, 0.78);
            --glass-border: rgba(255, 255, 255, 0.9);
        }

        /* ===== BACKGROUND SALJU (satu gambar untuk seluruh halaman) ===== */
        body {
            background-color: var(--frost-bg);
            background-image: url('/stok-umkm/img/Snow.jpg');
            background-size: cover;
            background-position: top center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--ink);
            min-height: 100vh;
        }

        .text-muted { color: #5B7189 !important; }

        /* ===== NAVBAR ===== */
        .navbar-frozen {
            background: linear-gradient(135deg, rgba(97, 155, 226, 0.95), rgba(19, 64, 116, 0.95));
            backdrop-filter: blur(6px);
        }

        /* ===== TOMBOL ===== */
        .btn-ice { background-color: var(--ice-blue); color: #fff; border: none; font-weight: 600; }
        .btn-ice:hover { background-color: #008ec4; color: #fff; }
        .btn-outline-ice {
            border: 1.5px solid var(--ocean-cold);
            color: var(--ocean-cold);
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.7);
        }
        .btn-outline-ice:hover { background-color: var(--ocean-cold); color: #fff; }

        /* ===== KARTU STATISTIK ===== */
        .card-stat {
            border: none;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(11, 37, 69, 0.15);
        }

        /* ===== PANEL TABEL (kaca beku di atas background salju) ===== */
        .table-container {
            background: var(--glass);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(11, 37, 69, 0.12);
            border: 1px solid var(--glass-border);
        }

        /* Sel tabel transparan supaya efek kaca tetap terlihat */
        .table-container .table,
        .table-container .table td,
        .table-container .table th {
            background-color: transparent !important;
            --bs-table-bg: transparent !important;
            --bs-table-hover-bg: rgba(97, 155, 226, 0.12);
            --bs-table-color: var(--ink);
            border-color: rgba(19, 64, 116, 0.12);
        }
        .table-container .table-hover > tbody > tr:hover > * {
            background-color: rgba(97, 155, 226, 0.14) !important;
        }

        .table thead th {
            background-color: rgba(214, 232, 250, 0.9) !important;
            color: var(--ocean-cold);
            border-bottom: none;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table thead th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .table thead th:last-child  { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }

        .code-frz {
            background-color: #DCEBFA;
            color: var(--ocean-cold);
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.82rem;
        }

        /* ===== FILTER & SEARCH ===== */
        .filter-pill { border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .filter-pill.btn-outline-secondary {
            background-color: rgba(255, 255, 255, 0.75);
            color: var(--ocean-cold);
            border-color: rgba(19, 64, 116, 0.35);
        }
        .filter-pill.btn-outline-secondary:hover {
            background-color: var(--ocean-cold);
            color: #fff;
            border-color: var(--ocean-cold);
        }
        .filter-pill.btn-primary { background-color: var(--ocean-cold); border-color: var(--ocean-cold); }

        .input-group .form-control,
        .input-group .input-group-text { background-color: rgba(255, 255, 255, 0.85); border-color: rgba(19, 64, 116, 0.2); }
        .form-control:focus, .form-select:focus {
            border-color: var(--ice-blue);
            box-shadow: 0 0 0 0.2rem rgba(0, 168, 232, 0.2);
        }

        /* ===== MODAL ===== */
        .modal-content { border: none; border-radius: 16px; overflow: hidden; color: var(--ink); }

        /* Judul halaman tetap terbaca di atas salju */
        h3.fw-bold { color: var(--ocean-cold); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-frozen mb-4 py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php">
            <i class="bi bi-snow2 text-info me-2"></i>KKS FROZEN FOOD<span class="fs-6 fw-normal opacity-75">| Cold Storage Inventory</span>
        </a>
        <div class="navbar-nav ms-auto d-flex flex-row align-items-center gap-3">
            <a class="nav-link active fw-semibold" href="index.php">Stok Freezer</a>
            <a class="nav-link" href="cetak_po.php">Cetak PO Belanja</a>
            <a class="nav-link" href="riwayat.php">Log & Profit</a>
            <span class="badge bg-info text-dark px-2 py-1">
                <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['full_name']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-light" onclick="return confirm('Keluar dari sistem?')">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Header & Tombol Aksi -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1">Pantauan Stok & Masa Simpan Freezer</h3>
            <p class="text-muted small mb-0">Kelola 15+ produk frozen food, pantau tanggal kedaluwarsa, & potong stok cepat</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-ice" data-bs-toggle="modal" data-bs-target="#modalAddItem">
                <i class="bi bi-plus-lg me-1"></i> Produk Beku Baru
            </button>
            <button class="btn btn-ice shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTransaction">
                <i class="bi bi-arrow-left-right me-1"></i> Catat Grosir / Restock
            </button>
        </div>
    </div>

    <!-- Notifikasi Sukses -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm" role="alert">
            ❄️ <strong>Berhasil!</strong> Data stok freezer sudah diperbarui secara real-time.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Banner Alert Gabungan (Stok & Expired) -->
    <?php if ($critical_count > 0 || $warning_count > 0 || $near_exp_count > 0): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2" style="background-color: rgba(253, 236, 234, 0.95); color: #8A1F11;">
        <div>
            <strong>🚨 Peringatan Freezer!</strong> 
            <?php if ($critical_count + $warning_count > 0): ?>
                Ada <b><?= $critical_count + $warning_count ?> produk</b> menipis/kritis.
            <?php endif; ?>
            <?php if ($near_exp_count > 0): ?>
                Serta <b><?= $near_exp_count ?> produk</b> mendekati tanggal kedaluwarsa (&le; 14 hari)!
            <?php endif; ?>
        </div>
        <a href="<?= $wa_link ?>" target="_blank" class="btn btn-sm btn-success fw-bold px-3">
            <i class="bi bi-whatsapp me-1"></i> Order ke Distributor via WA
        </a>
    </div>
    <?php endif; ?>

    <!-- 4 Kartu Indikator -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-stat bg-danger text-white p-3">
                <small class="fw-bold opacity-75">STOK KRITIS</small>
                <h2 class="fw-bold mb-0"><?= $critical_count ?> <span class="fs-6 fw-normal">Item</span></h2>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stat text-dark p-3" style="background-color: #FFD166;">
                <small class="fw-bold opacity-75">STOK MENIPIS</small>
                <h2 class="fw-bold mb-0"><?= $warning_count ?> <span class="fs-6 fw-normal">Item</span></h2>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stat text-white p-3" style="background-color: #E76F51;">
                <small class="fw-bold opacity-75">DEKAT EXPIRED</small>
                <h2 class="fw-bold mb-0"><?= $near_exp_count ?> <span class="fs-6 fw-normal">Item</span></h2>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-stat text-white p-3" style="background-color: var(--ocean-cold);">
                <small class="fw-bold opacity-75">STOK AMAN</small>
                <h2 class="fw-bold mb-0"><?= $safe_count ?> <span class="fs-6 fw-normal">Item</span></h2>
            </div>
        </div>
    </div>

    <!-- Filter Kategori & Search Bar -->
    <div class="table-container mb-5">
        <div class="row g-3 align-items-center mb-4">
            <div class="col-md-8 d-flex flex-wrap gap-2">
                <a href="index.php" class="btn btn-sm filter-pill <?= $selected_cat === 0 ? 'btn-primary' : 'btn-outline-secondary' ?>">Semua Produk</a>
                <?php foreach ($cat_list as $cat): ?>
                    <a href="index.php?cat=<?= $cat['id'] ?>" class="btn btn-sm filter-pill <?= $selected_cat === (int)$cat['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari ayam, dimsum, sosis, enoki...">
                </div>
            </div>
        </div>

        <!-- Tabel Stok -->
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Produk Frozen</th>
                        <th>Stok & Quick Edit</th>
                        <th>Min.</th>
                        <th>Masa Simpan (Exp)</th>
                        <th>Status</th>
                        <th>Saran Belanja</th>
                        <th>Hapus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><span class="code-frz"><?= htmlspecialchars($item['item_code']) ?></span></td>
                        <td>
                            <div class="fw-bold item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($item['category_name']) ?></small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <a href="actions.php?quick_type=OUT&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2 fw-bold" title="Terjual 1">-1</a>
                                <span class="fs-6 fw-bold"><?= number_format($item['stock'], 1) ?></span>
                                <a href="actions.php?quick_type=IN&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-success py-0 px-2 fw-bold" title="Restock 1">+1</a>
                                <span class="text-muted small"><?= htmlspecialchars($item['unit']) ?></span>
                            </div>
                        </td>
                        <td><?= number_format($item['min_stock'], 0) ?> <?= htmlspecialchars($item['unit']) ?></td>
                        <td>
                            <?php if ($item['exp_status'] === 'EXPIRED'): ?>
                                <span class="badge bg-dark">SUDAH EXPIRED</span>
                            <?php elseif ($item['exp_status'] === 'SEGERA_EXP'): ?>
                                <span class="text-danger fw-bold small"><i class="bi bi-hourglass-split"></i> <?= $item['exp_days'] ?> hari lagi</span>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= date('d M Y', strtotime($item['expired_date'])) ?></div>
                            <?php elseif (!empty($item['expired_date'])): ?>
                                <span class="small"><?= date('d M Y', strtotime($item['expired_date'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $item['badge_class'] ?> px-2 py-1"><?= $item['status'] ?></span></td>
                        <td>
                            <?php if ($item['suggested_buy'] > 0): ?>
                                <span class="fw-bold" style="color: var(--ocean-cold);">+<?= $item['suggested_buy'] ?> <?= htmlspecialchars($item['unit']) ?></span>
                                <div class="small text-muted">Rp <?= number_format($item['estimated_cost'], 0, ',', '.') ?></div>
                            <?php else: ?>
                                <span class="text-muted small">Cukup</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="actions.php?delete_id=<?= $item['id'] ?>" class="btn btn-sm btn-light text-danger border" onclick="return confirm('Hapus produk ini dari freezer?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL 1: Catat Penjualan Grosir / Restock -->
<div class="modal fade" id="modalTransaction" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="actions.php" method="POST">
                <input type="hidden" name="action" value="add_transaction">
                <div class="modal-header text-white" style="background-color: var(--deep-freeze);">
                    <h5 class="modal-title fw-bold">Catat Penjualan / Restock Freezer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih Produk Frozen</label>
                        <select name="inventory_id" class="form-select" required>
                            <?php foreach ($items as $item): ?>
                                <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?> (Sisa: <?= $item['stock'] ?> <?= htmlspecialchars($item['unit']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jenis Transaksi</label>
                        <select name="type" class="form-select" required>
                            <option value="OUT">🔴 Terjual (Eceran / Reseller / Online)</option>
                            <option value="IN">🟢 Barang Masuk dari Supplier / Pabrik</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Jumlah</label>
                        <input type="number" step="0.01" name="quantity" class="form-control" placeholder="Contoh: 5" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Orderan Reseller / Restock Mingguan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-ice w-100 py-2">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: Tambah Produk Frozen Baru -->
<div class="modal fade" id="modalAddItem" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="actions.php" method="POST">
                <input type="hidden" name="action" value="add_item">
                <div class="modal-header text-white" style="background-color: var(--deep-freeze);">
                    <h5 class="modal-title fw-bold">Tambah Produk Frozen Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold">Kode</label>
                            <input type="text" name="item_code" class="form-control" placeholder="FRZ-16" required>
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-semibold">Nama Produk</label>
                            <input type="text" name="name" class="form-control" placeholder="Chikuwa Ikan (500gr)" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Kategori</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($cat_list as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Expired Date</label>
                            <input type="date" name="expired_date" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold">Stok Awal</label>
                            <input type="number" step="0.01" name="stock" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Batas Min.</label>
                            <input type="number" step="0.01" name="min_stock" class="form-control" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Satuan</label>
                            <input type="text" name="unit" class="form-control" placeholder="Pack/Kg" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Rata-rata Laku/Hari</label>
                            <input type="number" step="0.01" name="avg_daily_usage" class="form-control" placeholder="Misal: 4" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Harga Modal / Satuan</label>
                            <input type="number" name="cost_per_unit" class="form-control" placeholder="Misal: 25000" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-ice w-100 py-2">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Fitur Pencarian Real-Time di Tabel
document.getElementById('searchInput').addEventListener('keyup', function() {
    let keyword = this.value.toLowerCase();
    let rows = document.querySelectorAll('#inventoryTable tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(keyword) ? '' : 'none';
    });
});
</script>
</body>
</html>
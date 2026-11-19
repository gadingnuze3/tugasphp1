<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Mode tampilan: default hanya produk menipis/kritis, ?semua=1 untuk semua produk yang disarankan belanja
$semua = isset($_GET['semua']) && $_GET['semua'] === '1';

$result = $conn->query("SELECT i.*, c.name AS category_name
                        FROM inventory i
                        JOIN categories c ON i.category_id = c.id
                        ORDER BY c.name ASC, i.name ASC");

$po_items    = [];
$total_biaya = 0;
$total_qty   = 0;

while ($row = $result->fetch_assoc()) {
    $stock = (float)$row['stock'];
    $min   = (float)$row['min_stock'];
    $usage = (float)$row['avg_daily_usage'];

    if ($stock <= ($min * 0.5)) {
        $row['status'] = 'KRITIS';
        $row['badge_class'] = 'bg-danger';
    } elseif ($stock <= $min) {
        $row['status'] = 'MENIPIS';
        $row['badge_class'] = 'bg-warning text-dark';
    } else {
        $row['status'] = 'AMAN';
        $row['badge_class'] = 'bg-success';
    }

    // Rumus sama dengan halaman Stok Freezer: target stok 7 hari + batas minimum
    $suggested = max(0, ceil((($usage * 7) + $min) - $stock));
    $row['suggested_buy']  = $suggested;
    $row['estimated_cost'] = $suggested * (float)$row['cost_per_unit'];

    $masuk = $semua ? ($suggested > 0) : ($row['status'] !== 'AMAN' && $suggested > 0);
    if ($masuk) {
        $po_items[]   = $row;
        $total_biaya += $row['estimated_cost'];
        $total_qty   += $suggested;
    }
}

$no_po   = 'PO-' . date('Ymd-His');
$tanggal = date('d M Y, H:i');

// Pesan WhatsApp ke distributor
$wa_text = "❄️ *ORDER RESTOCK FROZEN FOOD* ❄️%0A%0ANo. PO: $no_po%0A%0AHalo Distributor, mohon kirim barang berikut:%0A";
foreach ($po_items as $it) {
    $wa_text .= "- *" . $it['name'] . "*: " . $it['suggested_buy'] . " " . $it['unit'] . "%0A";
}
$wa_text .= "%0AEstimasi total: Rp " . number_format($total_biaya, 0, ',', '.');
$wa_link = "https://api.whatsapp.com/send?phone=6281294433811&text=" . $wa_text;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak PO Belanja - KKS Frozen Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep-freeze: #619be2;
            --ocean-cold: #134074;
            --ice-blue: #00A8E8;
            --frost-bg: #E4F1FC;
            --ink: #0B2545;
            --glass: rgba(255, 255, 255, 0.82);
            --glass-border: rgba(255, 255, 255, 0.9);
        }
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
        .navbar-frozen {
            background: linear-gradient(135deg, rgba(97, 155, 226, 0.95), rgba(19, 64, 116, 0.95));
            backdrop-filter: blur(6px);
        }
        .btn-ice { background-color: var(--ice-blue); color: #fff; border: none; font-weight: 600; }
        .btn-ice:hover { background-color: #008ec4; color: #fff; }
        .btn-outline-ice {
            border: 1.5px solid var(--ocean-cold);
            color: var(--ocean-cold);
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.7);
        }
        .btn-outline-ice:hover { background-color: var(--ocean-cold); color: #fff; }

        .po-sheet {
            background: var(--glass);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 24px rgba(11, 37, 69, 0.12);
            border: 1px solid var(--glass-border);
        }
        .po-head { border-bottom: 2px solid var(--ocean-cold); padding-bottom: 16px; margin-bottom: 20px; }
        .po-meta dt { font-weight: 600; color: #5B7189; font-size: 0.8rem; }
        .po-meta dd { margin-bottom: 8px; font-weight: 700; }

        .po-sheet .table,
        .po-sheet .table td,
        .po-sheet .table th,
        .po-sheet .table tfoot td {
            background-color: transparent !important;
            --bs-table-bg: transparent !important;
            --bs-table-color: var(--ink);
            border-color: rgba(19, 64, 116, 0.15);
        }
        .po-sheet .table thead th {
            background-color: rgba(214, 232, 250, 0.9) !important;
            color: var(--ocean-cold);
            border-bottom: none;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .po-sheet .table tfoot td { border-top: 2px solid var(--ocean-cold); font-weight: 800; font-size: 1.05rem; }
        .code-frz {
            background-color: #DCEBFA;
            color: var(--ocean-cold);
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.82rem;
        }
        .ttd-box { height: 70px; border-bottom: 1.5px solid var(--ink); }
        h3.fw-bold { color: var(--ocean-cold); }

        /* ===== MODE CETAK: hanya lembar PO, putih bersih ===== */
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .po-sheet {
                background: #fff !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                backdrop-filter: none !important;
            }
            .badge { border: 1px solid #999; color: #000 !important; background: #fff !important; }
            .container { max-width: 100% !important; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-frozen mb-4 py-3 shadow-sm no-print">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php">
            <i class="bi bi-snow2 text-info me-2"></i>KKS FROZEN FOOD<span class="fs-6 fw-normal opacity-75">| Cold Storage Inventory</span>
        </a>
        <div class="navbar-nav ms-auto d-flex flex-row align-items-center gap-3">
            <a class="nav-link" href="index.php">Stok Freezer</a>
            <a class="nav-link active fw-semibold" href="cetak_po.php">Cetak PO Belanja</a>
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

<div class="container pb-5">

    <!-- Header & Tombol Aksi -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 no-print">
        <div>
            <h3 class="fw-bold mb-1">Cetak PO Belanja</h3>
            <p class="text-muted small mb-0">Daftar belanja otomatis dari stok yang menipis, siap dicetak atau dikirim ke distributor</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($semua): ?>
                <a href="cetak_po.php" class="btn btn-outline-ice"><i class="bi bi-funnel me-1"></i> Hanya Menipis/Kritis</a>
            <?php else: ?>
                <a href="cetak_po.php?semua=1" class="btn btn-outline-ice"><i class="bi bi-list-check me-1"></i> Tampilkan Semua Saran</a>
            <?php endif; ?>
            <?php if (count($po_items) > 0): ?>
                <a href="<?= $wa_link ?>" target="_blank" class="btn btn-success fw-semibold"><i class="bi bi-whatsapp me-1"></i> Kirim via WA</a>
                <button class="btn btn-ice shadow-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak PO</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lembar PO -->
    <div class="po-sheet">
        <div class="po-head d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h2 class="fw-bold mb-0" style="color: var(--ocean-cold);"><i class="bi bi-snow2 me-2"></i>KKS FROZEN FOOD</h2>
                <div class="text-muted small">Purchase Order / Daftar Belanja Stok Freezer</div>
            </div>
            <dl class="po-meta mb-0 text-md-end">
                <dt>No. PO</dt>
                <dd><?= $no_po ?></dd>
                <dt>Tanggal</dt>
                <dd class="mb-0"><?= $tanggal ?></dd>
            </dl>
        </div>

        <?php if (count($po_items) === 0): ?>
            <div class="text-center py-5">
                <div class="fs-1">❄️</div>
                <h5 class="fw-bold mt-2">Belum ada yang perlu dibelanjakan</h5>
                <p class="text-muted mb-3">Semua stok freezer masih aman. Daftar ini akan terisi otomatis saat ada produk yang menipis.</p>
                <a href="index.php" class="btn btn-ice no-print">Kembali ke Stok Freezer</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:40px;">No</th>
                            <th>Kode</th>
                            <th>Produk Frozen</th>
                            <th class="text-end">Stok Saat Ini</th>
                            <th>Status</th>
                            <th class="text-end">Jumlah Order</th>
                            <th class="text-end">Harga Modal</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($po_items as $no => $it): ?>
                        <tr>
                            <td><?= $no + 1 ?></td>
                            <td><span class="code-frz"><?= htmlspecialchars($it['item_code']) ?></span></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($it['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($it['category_name']) ?></small>
                            </td>
                            <td class="text-end"><?= number_format($it['stock'], 1) ?> <?= htmlspecialchars($it['unit']) ?></td>
                            <td><span class="badge <?= $it['badge_class'] ?>"><?= $it['status'] ?></span></td>
                            <td class="text-end fw-bold"><?= $it['suggested_buy'] ?> <?= htmlspecialchars($it['unit']) ?></td>
                            <td class="text-end">Rp <?= number_format($it['cost_per_unit'], 0, ',', '.') ?></td>
                            <td class="text-end fw-bold">Rp <?= number_format($it['estimated_cost'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end">Total (<?= count($po_items) ?> produk)</td>
                            <td class="text-end"><?= $total_qty ?></td>
                            <td></td>
                            <td class="text-end">Rp <?= number_format($total_biaya, 0, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="text-muted small mb-4">
                Jumlah order dihitung dari kebutuhan 7 hari (rata-rata laku/hari × 7) ditambah batas minimum stok, dikurangi stok saat ini.
            </p>

            <div class="row text-center mt-4 pt-2">
                <div class="col-6 col-md-4 offset-md-1">
                    <div class="small text-muted mb-1">Dibuat oleh</div>
                    <div class="ttd-box"></div>
                    <div class="small fw-semibold mt-1"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                </div>
                <div class="col-6 col-md-4 offset-md-2">
                    <div class="small text-muted mb-1">Distributor</div>
                    <div class="ttd-box"></div>
                    <div class="small fw-semibold mt-1">( ........................ )</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
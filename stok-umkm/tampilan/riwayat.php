<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$logs = $conn->query("SELECT l.*, i.name as item_name, i.unit 
                      FROM stock_logs l 
                      JOIN inventory i ON l.inventory_id = i.id 
                      ORDER BY l.created_at DESC LIMIT 50");

// Simpan ke array supaya bisa dipakai untuk ringkasan & tabel
$log_list  = [];
$count_in  = 0;
$count_out = 0;
while ($log = $logs->fetch_assoc()) {
    if ($log['type'] === 'IN') { $count_in++; } else { $count_out++; }
    $log_list[] = $log;
}
$total_log = count($log_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Penjualan & Restock - KKS FROZEN FOOD</title>
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
            --glass: rgba(255, 255, 255, 0.78);
            --glass-border: rgba(255, 255, 255, 0.9);
        }

        /* ===== BACKGROUND SALJU ===== */
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
        h3.fw-bold { color: var(--ocean-cold); }

        /* ===== NAVBAR ===== */
        .navbar-frozen {
            background: linear-gradient(135deg, rgba(97, 155, 226, 0.95), rgba(19, 64, 116, 0.95));
            backdrop-filter: blur(6px);
        }

        /* ===== KARTU RINGKASAN ===== */
        .card-stat {
            border: none;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(11, 37, 69, 0.15);
        }

        /* ===== PANEL KACA ===== */
        .table-container {
            background: var(--glass);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(11, 37, 69, 0.12);
            border: 1px solid var(--glass-border);
        }
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
        .form-control:focus { border-color: var(--ice-blue); box-shadow: 0 0 0 0.2rem rgba(0, 168, 232, 0.2); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-frozen mb-4 py-3 shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="index.php">
            <i class="bi bi-snow2 text-info me-2"></i>KKS FROZEN FOOD<span class="fs-6 fw-normal opacity-75">| Cold Storage Inventory</span>
        </a>
        <div class="navbar-nav ms-auto d-flex flex-row align-items-center gap-3">
            <a class="nav-link" href="index.php">Stok Freezer</a>
            <a class="nav-link" href="cetak_po.php">Cetak PO Belanja</a>
            <a class="nav-link active fw-semibold" href="riwayat.php">Log & Profit</a>
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
    <!-- Header -->
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Log Penjualan & Restock Freezer</h3>
        <p class="text-muted small mb-0">50 aktivitas keluar-masuk stok terbaru, diurutkan dari yang paling baru</p>
    </div>

    <!-- Kartu Ringkasan -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card card-stat text-white p-3" style="background-color: var(--ocean-cold);">
                <small class="fw-bold opacity-75">TOTAL AKTIVITAS</small>
                <h2 class="fw-bold mb-0"><?= $total_log ?> <span class="fs-6 fw-normal">Catatan</span></h2>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card card-stat bg-success text-white p-3">
                <small class="fw-bold opacity-75">RESTOCK (MASUK)</small>
                <h2 class="fw-bold mb-0"><?= $count_in ?> <span class="fs-6 fw-normal">Kali</span></h2>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card card-stat bg-danger text-white p-3">
                <small class="fw-bold opacity-75">TERJUAL (KELUAR)</small>
                <h2 class="fw-bold mb-0"><?= $count_out ?> <span class="fs-6 fw-normal">Kali</span></h2>
            </div>
        </div>
    </div>

    <!-- Tabel Log -->
    <div class="table-container mb-5">
        <div class="row g-3 align-items-center mb-4">
            <div class="col-md-8 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm filter-pill btn-primary" data-filter="ALL">Semua</button>
                <button type="button" class="btn btn-sm filter-pill btn-outline-secondary" data-filter="IN">Restock (+)</button>
                <button type="button" class="btn btn-sm filter-pill btn-outline-secondary" data-filter="OUT">Terjual (-)</button>
            </div>
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari produk atau catatan...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="logTable">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Nama Produk</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_log > 0): ?>
                        <?php foreach ($log_list as $log): ?>
                        <tr data-type="<?= $log['type'] === 'IN' ? 'IN' : 'OUT' ?>">
                            <td>
                                <div class="fw-semibold"><?= date('d M Y', strtotime($log['created_at'])) ?></div>
                                <small class="text-muted"><?= date('H:i', strtotime($log['created_at'])) ?> WIB</small>
                            </td>
                            <td class="fw-bold"><?= htmlspecialchars($log['item_name']) ?></td>
                            <td>
                                <?php if ($log['type'] == 'IN'): ?>
                                    <span class="badge bg-success px-2 py-1">RESTOCK (+)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger px-2 py-1">TERJUAL (-)</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold <?= $log['type'] == 'IN' ? 'text-success' : 'text-danger' ?>">
                                <?= $log['type'] == 'IN' ? '+' : '-' ?><?= number_format($log['quantity'], 1) ?>
                                <span class="text-muted small fw-normal"><?= htmlspecialchars($log['unit']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($log['notes'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-5">❄️ Belum ada catatan penjualan atau restock freezer.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Filter tipe + pencarian real-time
let activeType = 'ALL';
const rows = document.querySelectorAll('#logTable tbody tr[data-type]');
const searchInput = document.getElementById('searchInput');

function applyFilter() {
    const keyword = searchInput.value.toLowerCase();
    rows.forEach(row => {
        const okType = activeType === 'ALL' || row.dataset.type === activeType;
        const okText = row.innerText.toLowerCase().includes(keyword);
        row.style.display = (okType && okText) ? '' : 'none';
    });
}

searchInput.addEventListener('keyup', applyFilter);

document.querySelectorAll('[data-filter]').forEach(btn => {
    btn.addEventListener('click', () => {
        activeType = btn.dataset.filter;
        document.querySelectorAll('[data-filter]').forEach(b => {
            b.classList.toggle('btn-primary', b === btn);
            b.classList.toggle('btn-outline-secondary', b !== btn);
        });
        applyFilter();
    });
});
</script>
</body>
</html>
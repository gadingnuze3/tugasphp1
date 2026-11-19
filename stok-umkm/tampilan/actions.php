<?php
require_once 'config.php';

// AKSI 1: Catat Transaksi Stok Masuk / Keluar via Modal
if (isset($_POST['action']) && $_POST['action'] == 'add_transaction') {
    $inventory_id = (int)$_POST['inventory_id'];
    $type         = $_POST['type'];
    $quantity     = (float)$_POST['quantity'];
    $notes        = $conn->real_escape_string($_POST['notes']);

    if ($quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO stock_logs (inventory_id, type, quantity, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $inventory_id, $type, $quantity, $notes);
        $stmt->execute();

        if ($type == 'IN') {
            $conn->query("UPDATE inventory SET stock = stock + $quantity WHERE id = $inventory_id");
        } else {
            $conn->query("UPDATE inventory SET stock = GREATEST(0, stock - $quantity) WHERE id = $inventory_id");
        }
    }
    header("Location: index.php?msg=transaksi_sukses");
    exit();
}

// AKSI 2: Quick Action (+1 / -1 Langsung dari Tabel)
if (isset($_GET['quick_type']) && isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $type = $_GET['quick_type'] === 'IN' ? 'IN' : 'OUT';
    $qty  = 1;
    $note = ($type === 'IN') ? 'Quick Restock (+1)' : 'Penjualan Cepat (-1)';

    $stmt = $conn->prepare("INSERT INTO stock_logs (inventory_id, type, quantity, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $id, $type, $qty, $note);
    $stmt->execute();

    if ($type === 'IN') {
        $conn->query("UPDATE inventory SET stock = stock + 1 WHERE id = $id");
    } else {
        $conn->query("UPDATE inventory SET stock = GREATEST(0, stock - 1) WHERE id = $id");
    }
    header("Location: index.php?msg=quick_sukses");
    exit();
}

// AKSI 3: Tambah Produk Frozen Food Baru
if (isset($_POST['action']) && $_POST['action'] == 'add_item') {
    $item_code    = $conn->real_escape_string($_POST['item_code']);
    $name         = $conn->real_escape_string($_POST['name']);
    $category_id  = (int)$_POST['category_id'];
    $stock        = (float)$_POST['stock'];
    $min_stock    = (float)$_POST['min_stock'];
    $unit         = $conn->real_escape_string($_POST['unit']);
    $avg_usage    = (float)$_POST['avg_daily_usage'];
    $cost         = (float)$_POST['cost_per_unit'];
    $expired_date = !empty($_POST['expired_date']) ? $_POST['expired_date'] : NULL;

    $stmt = $conn->prepare("INSERT INTO inventory (item_code, name, category_id, stock, min_stock, unit, avg_daily_usage, cost_per_unit, expired_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiddsdds", $item_code, $name, $category_id, $stock, $min_stock, $unit, $avg_usage, $cost, $expired_date);
    $stmt->execute();

    header("Location: index.php?msg=item_ditambahkan");
    exit();
}

// AKSI 4: Hapus Produk
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM inventory WHERE id = $id");
    header("Location: index.php?msg=item_dihapus");
    exit();
}
?>
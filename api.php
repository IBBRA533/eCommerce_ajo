<?php
// api.php (complete — original endpoints preserved + sales_summary_range + improved export)
// NOTE: ganti path 'db.php' jika posisi berbeda
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// get PDO from db.php
$pdo = getPDO();

// ----------------------
// helper utilities
// ----------------------
function json_ok($data = [], $msg = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'error' => false, 'message' => $msg, 'data' => $data]);
    exit;
}
function json_err($msg = '', $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => true, 'message' => $msg]);
    exit;
}
function require_admin() {
    if (empty($_SESSION['admin'])) json_err('Unauthorized', 403);
}
function as_str($v){ return trim((string)($v ?? '')); }
function as_int($v){ return (int)$v; }

// ----------------------
// development convenience: ensure tables exist (safe to run repeatedly)
// ----------------------
try {
    // ensure menus table
    $tbl = $pdo->query("SHOW TABLES LIKE 'menus'")->fetch(PDO::FETCH_NUM);
    if (!$tbl) {
        $sql = "CREATE TABLE IF NOT EXISTS `menus` (
            `id` VARCHAR(50) NOT NULL,
            `name` VARCHAR(191) NOT NULL,
            `category` VARCHAR(50) DEFAULT 'lauk',
            `price` INT DEFAULT 0,
            `description` TEXT,
            `image` VARCHAR(255) DEFAULT '',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
        error_log('api.php: created table menus');
    }

    // ensure orders table
    $tbl2 = $pdo->query("SHOW TABLES LIKE 'orders'")->fetch(PDO::FETCH_NUM);
    if (!$tbl2) {
        $sql2 = "CREATE TABLE IF NOT EXISTS `orders` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `order_code` VARCHAR(50) NOT NULL,
            `customer_name` VARCHAR(191) DEFAULT NULL,
            `phone` VARCHAR(30) DEFAULT NULL,
            `items` TEXT,
            `subtotal` INT DEFAULT 0,
            `total` INT DEFAULT 0,
            `note` TEXT,
            `status` VARCHAR(50) DEFAULT 'new',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_order_code` (`order_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql2);
        error_log('api.php: created table orders');
    }
} catch (Exception $e) {
    // Log internal error, but do not expose SQL errors to clients
    error_log('api.php ensure tables failed: ' . $e->getMessage());
}

// ----------------------
// route handling
// ----------------------
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {

    // LIST - public - legacy: return raw array for older JS
    if ($action === 'list') {
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') {
            $stmt = $pdo->query("SELECT * FROM menus ORDER BY id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $like = '%' . $q . '%';
            $stmt = $pdo->prepare("SELECT * FROM menus WHERE CAST(id AS CHAR) LIKE ? OR name LIKE ? ORDER BY id ASC");
            $stmt->execute([$like, $like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // legacy clients expect raw array (not wrapped)
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows);
        exit;
    }

    // GET detail
    if ($action === 'get') {
        $id = as_str($_GET['id'] ?? '');
        if ($id === '') { echo json_encode([]); exit; }
        $stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
        exit;
    }

    // CREATE menu (admin)
    if ($action === 'create') {
        require_admin();
        $d = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = as_str($d['id'] ?? ''); $name = as_str($d['name'] ?? '');
        $category = as_str($d['category'] ?? 'lauk'); $price = as_int($d['price'] ?? 0);
        $description = as_str($d['description'] ?? null); $image = as_str($d['image'] ?? '');
        if ($id === '' || $name === '') json_err('ID dan Nama diperlukan');
        $chk = $pdo->prepare("SELECT COUNT(*) FROM menus WHERE id = ?");
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) json_err('ID sudah ada');
        $stmt = $pdo->prepare("INSERT INTO menus (id,name,category,price,description,image) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$id,$name,$category,$price,$description,$image]);
        json_ok([], 'Menu berhasil dibuat');
    }

    // UPDATE menu (admin)
    if ($action === 'update') {
        require_admin();
        $d = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = as_str($d['id'] ?? ''); if ($id === '') json_err('ID diperlukan');
        $name = as_str($d['name'] ?? ''); $category = as_str($d['category'] ?? 'lauk'); $price = as_int($d['price'] ?? 0);
        $description = as_str($d['description'] ?? null); $image = as_str($d['image'] ?? '');
        $stmt = $pdo->prepare("UPDATE menus SET name=?, category=?, price=?, description=?, image=? WHERE id=?");
        $stmt->execute([$name,$category,$price,$description,$image,$id]);
        json_ok([], 'Menu diperbarui');
    }

    // DELETE menu (admin)
    if ($action === 'delete') {
        require_admin();
        $d = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = $d['id'] ?? null; if (!$id) json_err('ID diperlukan');
        $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
        $stmt->execute([$id]);
        json_ok([], 'Menu dihapus');
    }

    // ORDER - public
    if ($action === 'order') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) json_err('Payload invalid');
        $customer = as_str($data['customer_name'] ?? 'Pengunjung'); $phone = as_str($data['phone'] ?? '');
        $items = $data['items'] ?? []; if (!is_array($items) || count($items)===0) json_err('Keranjang kosong');

        // collect ids and validate
        $ids = [];
        foreach ($items as $it) {
            $id = as_str($it['id'] ?? '');
            if ($id !== '') $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) === 0) json_err('Keranjang kosong');

        $placeholders = implode(',', array_fill(0,count($ids),'?'));
        $stmtMenu = $pdo->prepare("SELECT id, price, name FROM menus WHERE id IN ($placeholders)");
        $stmtMenu->execute($ids);
        $menuRows = $stmtMenu->fetchAll(PDO::FETCH_ASSOC);
        $menuById = []; foreach ($menuRows as $m) $menuById[(string)$m['id']] = $m;

        $validatedItems = []; $subtotal = 0;
        foreach ($items as $it) {
            $id = as_str($it['id'] ?? ''); $qty = max(1, as_int($it['qty'] ?? 1));
            if (!isset($menuById[$id])) json_err("Item tidak ditemukan: $id");
            $serverPrice = as_int($menuById[$id]['price']); $name = $menuById[$id]['name'];
            $validatedItems[] = ['id'=>$id,'name'=>$name,'qty'=>$qty,'price'=>$serverPrice];
            $subtotal += $qty * $serverPrice;
        }

        $total = $subtotal; $note = as_str($data['note'] ?? '');
        $order_code = 'ORD'.date('YmdHis').rand(100,999);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO orders (order_code, customer_name, phone, items, subtotal, total, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$order_code,$customer,$phone,json_encode($validatedItems, JSON_UNESCAPED_UNICODE),$subtotal,$total,$note]);
            $pdo->commit();
            json_ok(['order_code'=>$order_code], 'Pesanan diterima');
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('api.php order insert failed: '.$e->getMessage());
            json_err('Gagal menyimpan order', 500);
        }
    }

    // SALES SUMMARY - admin only (single date) (kept original)
    if ($action === 'sales_summary') {
        // debug log
        file_put_contents(__DIR__.'/debug_sales_summary.log', date('c').' - sales_summary called; session_admin=' . (empty($_SESSION['admin']) ? 'no' : 'yes') . ' GET='.json_encode($_GET).PHP_EOL, FILE_APPEND);
        require_admin();
        $date = as_str($_GET['date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_err('Format tanggal invalid (YYYY-MM-DD)');
        $start = $date.' 00:00:00'; $end = $date.' 23:59:59';

        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total_amount FROM orders WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$start,$end]); $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cnt'=>0,'total_amount'=>0];

        $stmt2 = $pdo->prepare("SELECT id, order_code, customer_name, phone, total, subtotal, items, status, created_at FROM orders WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC");
        $stmt2->execute([$start,$end]); $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) { /* keep as-is; client parses items */ }

        json_ok(['date'=>$date,'summary'=>$summary,'rows'=>$rows]);
    }

    // --- NEW: SALES SUMMARY RANGE (start & end inclusive) ---
    if ($action === 'sales_summary_range') {
        require_admin();
        $startDate = as_str($_GET['start'] ?? '');
        $endDate = as_str($_GET['end'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            json_err('Parameter start & end harus format YYYY-MM-DD');
        }
        $start = $startDate . ' 00:00:00';
        $end = $endDate . ' 23:59:59';
        if ($start > $end) json_err('Rentang tanggal tidak valid');

        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total_amount FROM orders WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$start,$end]); $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cnt'=>0,'total_amount'=>0];

        $stmt2 = $pdo->prepare("SELECT id, order_code, customer_name, phone, total, subtotal, items, status, created_at FROM orders WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC");
        $stmt2->execute([$start,$end]); $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        json_ok(['start'=>$startDate,'end'=>$endDate,'summary'=>$summary,'rows'=>$rows]);
    }

    // EXPORT SALES CSV - admin only (improved: supports date OR start+end)
    if ($action === 'export_sales') {
        require_admin();
        $date = as_str($_GET['date'] ?? '');
        $startDate = as_str($_GET['start'] ?? '');
        $endDate = as_str($_GET['end'] ?? '');

        if ($date !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_err('Format tanggal invalid (YYYY-MM-DD)');
            $start = $date . ' 00:00:00';
            $end = $date . ' 23:59:59';
            $label = $date;
        } else {
            if ($startDate === '' || $endDate === '') json_err('Parameter start & end diperlukan untuk export rentang');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) json_err('Format start & end invalid (YYYY-MM-DD)');
            $start = $startDate . ' 00:00:00';
            $end = $endDate . ' 23:59:59';
            if ($start > $end) json_err('Rentang tanggal tidak valid');
            $label = $startDate . '_to_' . $endDate;
        }

        // get rows and summary
        $stmt = $pdo->prepare("SELECT order_code, customer_name, phone, subtotal, total, items, status, created_at FROM orders WHERE created_at BETWEEN ? AND ? ORDER BY created_at ASC");
        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtSum = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as total_amount FROM orders WHERE created_at BETWEEN ? AND ?");
        $stmtSum->execute([$start, $end]);
        $summary = $stmtSum->fetch(PDO::FETCH_ASSOC) ?: ['cnt'=>0,'total_amount'=>0];

        // ensure no prior output
        if (ob_get_level()) ob_end_clean();

        // CSV headers
        header('Content-Type: text/csv; charset=utf-8');
        $filename = 'sales_' . $label . '.csv';
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // BOM for Excel/UTF-8
        echo "\xEF\xBB\xBF";

        // top meta rows (human friendly)
        fputcsv($out, ['Report start', $start]);
        fputcsv($out, ['Report end', $end]);
        fputcsv($out, ['Generated at', date('Y-m-d H:i:s')]);
        fputcsv($out, ['Total Orders', $summary['cnt']]);
        fputcsv($out, ['Total Amount (Rp)', $summary['total_amount']]);
        fputcsv($out, []); // empty line

        // header
        fputcsv($out, ['Order Code','Customer','Phone','Subtotal','Total','Items(JSON)','Status','Created At']);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['order_code'],
                $r['customer_name'],
                $r['phone'],
                $r['subtotal'],
                $r['total'],
                $r['items'],
                $r['status'],
                $r['created_at']
            ]);
        }

        // footer totals repeated
        fputcsv($out, []);
        fputcsv($out, ['Totals','', '', '', $summary['total_amount'], '', '', 'Total Orders: '.$summary['cnt']]);

        fclose($out);
        exit;
    }

    json_err('Action tidak dikenali', 400);

} catch (Exception $e) {
    error_log('api.php exception: '.$e->getMessage());
    json_err('Server error', 500);
}

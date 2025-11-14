<?php
require_once 'db.php';
$pdo = getPDO();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {

    // ----------------------------
    // LIST (dengan optional search q)
    // ----------------------------
    if ($action === 'list') {
        $q = trim((string)($_GET['q'] ?? ''));

        if ($q === '') {
            $stmt = $pdo->query("SELECT * FROM menus ORDER BY id ASC");
            $rows = $stmt->fetchAll();
        } else {
            // Cari di id (cast ke char kalau id numeric) atau name
            $like = '%' . $q . '%';
            $stmt = $pdo->prepare("SELECT * FROM menus WHERE CAST(id AS CHAR) LIKE ? OR name LIKE ? ORDER BY id ASC");
            $stmt->execute([$like, $like]);
            $rows = $stmt->fetchAll();
        }

        echo json_encode($rows);
        exit;
    }

    // ----------------------------
    // GET DETAIL
    // ----------------------------
    if ($action === 'get') {
        $id = $_GET['id'] ?? '';
        if ($id === '') {
            echo json_encode([]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        echo json_encode($stmt->fetch() ?: []);
        exit;
    }

    // ----------------------------
    // CREATE
    // ----------------------------
    if ($action === 'create') {
        $d = json_decode(file_get_contents("php://input"), true) ?: $_POST;

        // basic validation
        $id = trim((string)($d['id'] ?? ''));
        $name = trim((string)($d['name'] ?? ''));
        $category = trim((string)($d['category'] ?? 'lauk'));
        $price = (int)($d['price'] ?? 0);
        $description = trim((string)($d['description'] ?? null));
        $image = trim((string)($d['image'] ?? ''));

        if ($id === '' || $name === '') {
            throw new Exception('ID dan Nama diperlukan');
        }

        // cek duplikat
        $chk = $pdo->prepare("SELECT COUNT(*) FROM menus WHERE id = ?");
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            throw new Exception('ID sudah ada');
        }

        $stmt = $pdo->prepare("INSERT INTO menus (id,name,category,price,description,image) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$id, $name, $category, $price, $description, $image]);

        echo json_encode(["success" => true, "message" => "Menu berhasil dibuat"]);
        exit;
    }

    // ----------------------------
    // UPDATE
    // ----------------------------
    if ($action === 'update') {
        $d = json_decode(file_get_contents("php://input"), true) ?: $_POST;

        $id = trim((string)($d['id'] ?? ''));
        $name = trim((string)($d['name'] ?? ''));
        $category = trim((string)($d['category'] ?? 'lauk'));
        $price = (int)($d['price'] ?? 0);
        $description = trim((string)($d['description'] ?? null));
        $image = trim((string)($d['image'] ?? ''));

        if ($id === '') throw new Exception('ID diperlukan');

        $stmt = $pdo->prepare("UPDATE menus SET name = ?, category = ?, price = ?, description = ?, image = ? WHERE id = ?");
        $stmt->execute([$name, $category, $price, $description, $image, $id]);

        echo json_encode(["success" => true, "message" => "Menu diperbarui"]);
        exit;
    }

    // ----------------------------
    // DELETE
    // ----------------------------
    if ($action === 'delete') {
        $d = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $id = $d['id'] ?? null;
        if (!$id) throw new Exception('ID diperlukan');

        $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode(["success" => true, "message" => "Menu dihapus"]);
        exit;
    }

    // unknown action
    echo json_encode(["error" => true, "message" => "Action tidak dikenali"]);

} catch (Exception $e) {
    http_response_code(400);
    // log untuk debugging (jangan tampilkan stacktrace di production)
    error_log('api.php error: ' . $e->getMessage());
    echo json_encode(["error" => true, "message" => $e->getMessage()]);
}

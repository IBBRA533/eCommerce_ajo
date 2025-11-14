<?php
// db.php
// Tidak boleh ada spasi / baris kosong sebelum <?php !!!

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi DB
define('DB_HOST', 'localhost');
define('DB_NAME', 'nasi_padang');
define('DB_USER', 'root');
define('DB_PASS', '');

// Function koneksi PDO
function getPDO() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Koneksi database gagal: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Mengecek admin login
function requireAdmin(){
    if (empty($_SESSION['admin'])) {
        header('Location: admin_login.php');
        exit;
    }
}

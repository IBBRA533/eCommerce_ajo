<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php'; // db.php Anda harus memiliki getPDO()

$action = $_POST['action'] ?? null;

// Ambil koneksi PDO
$pdo = getPDO();  

// LOGIN
if ($action === 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password'])) {

            unset($user['password']);  // demi keamanan
            $_SESSION['user'] = $user;

            header("Location: home.html");
            exit;
        } else {
            $_SESSION['error'] = "Password salah!";
            header("Location: index.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "Email tidak ditemukan!";
        header("Location: index.php");
        exit;
    }
}


// REGISTER
if ($action === 'register') {
    $fullName = trim($_POST['fullName']);
    $email = trim($_POST['email']);
    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Cek email sudah terdaftar atau belum
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $exists = $stmt->fetch();

    if ($exists) {
        $_SESSION['error'] = "Email sudah terdaftar!";
        header("Location: Register.php");
        exit;
    }

    // Insert user baru
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$fullName, $email, $passwordHash]);

    $_SESSION['success'] = "Pendaftaran berhasil, silakan login.";
    header("Location: index.php");
    exit;
}


// Jika action tidak dikenali
$_SESSION['error'] = "Aksi tidak valid!";
header("Location: index.php");
exit;

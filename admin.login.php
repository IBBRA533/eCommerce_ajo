<?php

require_once 'db.php'; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}   
if (!empty($_SESSION['admin'])) {
header('Location: admin_dashboard.php');
exit;
}


$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
if (!$username || !$password) $err = 'Username dan password diperlukan.';
else {
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
$admin = $stmt->fetch();
if ($admin && password_verify($password, $admin['password'])){
// sukses
unset($admin['password']);
$_SESSION['admin'] = $admin;
header('Location: admin.dashboard.php');
exit;
} else {
$err = 'Username atau password salah.';
}
}
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md bg-gray-800 p-6 rounded-lg shadow-lg">
<h1 class="text-2xl font-bold mb-4">Masuk Admin</h1>
<?php if($err): ?>
<div class="bg-red-600 text-white p-3 rounded mb-3"><?php echo htmlspecialchars($err) ?></div>
<?php endif; ?>
<form method="post">
<label class="block text-sm text-gray-300">Username</label>
<input name="username" class="w-full p-2 rounded bg-white/5 mb-3" required />


<label class="block text-sm text-gray-300">Password</label>
<input name="password" type="password" class="w-full p-2 rounded bg-white/5 mb-4" required />


<div class="flex justify-between items-center">
<button class="bg-maroon px-4 py-2 rounded text-white">Masuk</button>
<a href="index.php" class="text-sm text-gray-400">Kembali ke situs</a>
</div>
</form>
</div>
</body>
</html>
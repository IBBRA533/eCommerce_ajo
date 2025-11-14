<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Rumah Makan Nasi Padang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
  
</head>
<body class="login-bg">

<div class="login-container d-flex justify-content-center align-items-center py-5">
    <div class="card p-4 shadow-lg" style="width: 400px;">
        <div class="text-center mb-3">
            <i class="fas fa-utensils fs-1 text-danger"></i>
            <h4 class="fw-bold mt-2">Selamat Datang Di Zahra Minang</h4>
            <p class="text-muted mb-0">Daftar untuk melanjutkan</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form action="auth.php" method="POST" id="registerForm" novalidate>
            <input type="hidden" name="action" value="register">

            <!-- Nama Lengkap -->
            <div class="mb-3">
                <label for="fullName" class="form-label fw-semibold">Nama Lengkap</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="fullName" id="fullName" class="form-control" required>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', 'toggleIcon1')">
                        <i class="fas fa-eye" id="toggleIcon1"></i>
                    </button>
                </div>
                <div class="mt-2 small">
                    <div class="check-item" id="lengthCheck"><span>❌</span> 8-12 karakter</div>
                    <div class="check-item" id="uppercaseCheck"><span>❌</span> Huruf besar (A-Z)</div>
                    <div class="check-item" id="lowercaseCheck"><span>❌</span> Huruf kecil (a-z)</div>
                    <div class="check-item" id="numberCheck"><span>❌</span> Angka (0-9)</div>
                    <div class="check-item" id="specialCheck"><span>❌</span> Karakter khusus (@, #, $, !)</div>
                </div>
            </div>

            <!-- Konfirmasi Password -->
            <div class="mb-3">
                <label for="confirmPassword" class="form-label fw-semibold">Konfirmasi Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="confirmPassword" id="confirmPassword" class="form-control" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirmPassword', 'toggleIcon2')">
                        <i class="fas fa-eye" id="toggleIcon2"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-danger w-100 fw-bold mt-2">Daftar</button>
        </form>

        <div class="mt-3 text-center">
            <p class="mb-0">Sudah punya akun?</p>
            <a href="index.php" class="text-danger fw-semibold">Masuk Sekarang</a>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Validasi password real-time
const passwordInput = document.getElementById('password');
const checks = {
    lengthCheck: value => value.length >= 8 && value.length <= 12,
    uppercaseCheck: value => /[A-Z]/.test(value),
    lowercaseCheck: value => /[a-z]/.test(value),
    numberCheck: value => /\d/.test(value),
    specialCheck: value => /[@$!#%*?&]/.test(value)
};

passwordInput.addEventListener('input', () => {
    const value = passwordInput.value;
    let allValid = true;

    for (const [id, test] of Object.entries(checks)) {
        const valid = test(value);
        document.querySelector(`#${id} span`).textContent = valid ? '✅' : '❌';
        if (!valid) allValid = false;
    }

    passwordInput.setCustomValidity(allValid ? "" : "Password tidak sesuai format.");
});

// Konfirmasi password cocok
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const pass = document.getElementById('password').value;
    const confirm = document.getElementById('confirmPassword').value;
    if (pass !== confirm) {
        e.preventDefault();
        alert('Konfirmasi password tidak cocok!');
    }
});
</script>

</body>
</html>

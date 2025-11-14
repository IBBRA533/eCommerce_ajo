<?php
session_start();

// Jika admin yang logout
if (!empty($_SESSION['admin'])) {
    session_unset();
    session_destroy();
    header("Location: admin.login.php");
    exit;
}

// Jika user biasa yang logout
if (!empty($_SESSION['user'])) {
    session_unset();
    session_destroy();
    header("Location: index.php"); // atau index.php
    exit;
}

// Jika tidak ada session (sudah logout)
header("Location: index.php");
exit;

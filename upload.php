<?php
// upload.php
// Pastikan tidak ada output sebelum header JSON (tidak ada spasi/BOM di awal file)
require_once 'db.php';

// Jika Anda ingin membatasi akses hanya admin, aktifkan baris berikut
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => true, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_FILES) || empty($_FILES['file'])) {
        throw new RuntimeException('Tidak ada file yang diupload');
    }

    $file = $_FILES['file'];

    // Periksa error upload PHP
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Parameter upload tidak valid');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('File tidak ditemukan');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Ukuran file terlalu besar');
        default:
            throw new RuntimeException('Error saat upload');
    }

    // Limit ukuran (contoh 3MB)
    $maxSize = 3 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Maksimum ukuran file 3MB');
    }

    // Validasi ekstensi/mime
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'jpg' => 'image/jpeg',
        'jpeg'=> 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    $ext = array_search($mime, $allowed, true);
    if ($ext === false) {
        throw new RuntimeException('Format gambar tidak diizinkan (jpg/png/gif)');
    }

    // Siapkan folder upload
    $uploadDir = __DIR__ . '/uploads/menu/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Gagal membuat folder upload');
        }
    }

    // Buat nama file unik
    $basename = sprintf('menu_%s_%s.%s', time(), bin2hex(random_bytes(4)), $ext);
    $targetPath = $uploadDir . $basename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Gagal memindahkan file upload');
    }

    // Jika berhasil, kembalikan path relatif yang bisa disimpan ke DB
    $fileUrl = 'uploads/menu/' . $basename;

    echo json_encode(['success' => true, 'file_url' => $fileUrl]);
    exit;
} catch (Exception $e) {
    // Jangan echo stacktrace/HTML — log ke error_log dan kembalikan JSON error
    error_log('Upload error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
    exit;
}

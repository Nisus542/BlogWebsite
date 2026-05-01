<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

// Only allow image uploads inside the editor
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

// Validate MIME type (images only)
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(415);
    echo json_encode(['error' => 'Only JPEG, PNG, GIF and WebP images are allowed']);
    exit;
}

// Max 5 MB
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['error' => 'Image must be under 5 MB']);
    exit;
}

// Create uploads directory if needed
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Safe unique filename
$ext      = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$mime];
$filename = 'img_' . uniqid('', true) . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save image']);
    exit;
}

// TinyMCE expects { "location": "<url>" }
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$base     = dirname($_SERVER['SCRIPT_NAME']);   // e.g. /Blog
$url      = $protocol . '://' . $host . rtrim($base, '/') . '/uploads/' . $filename;

echo json_encode(['location' => $url]);

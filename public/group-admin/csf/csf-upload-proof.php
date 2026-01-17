<?php
/**
 * CSF Upload Payment Proof
 * Handles file upload for payment screenshots/proofs
 */

require_once __DIR__ . '/../../../config/config.php';

// Authentication
AuthMiddleware::requireRole('group_admin');

header('Content-Type: application/json');

$communityId = AuthMiddleware::getCommunityId();

// Check if file was uploaded
if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit();
}

$file = $_FILES['proof_image'];

// Validation
$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
$maxFileSize = 5242880; // 5MB

$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid file type. Allowed: JPG, JPEG, PNG, PDF'
    ]);
    exit();
}

if ($file['size'] > $maxFileSize) {
    echo json_encode([
        'success' => false,
        'error' => 'File too large. Maximum size: 5MB'
    ]);
    exit();
}

// Create upload directory if not exists
$uploadDir = __DIR__ . '/../../public/uploads/csf-proof/' . $communityId . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$timestamp = time();
$randomString = bin2hex(random_bytes(8));
$newFilename = "proof_{$timestamp}_{$randomString}.{$fileExtension}";
$uploadPath = $uploadDir . $newFilename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $relativePath = "/public/uploads/csf-proof/{$communityId}/{$newFilename}";

    echo json_encode([
        'success' => true,
        'file_path' => $relativePath,
        'filename' => $newFilename
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save file. Please check directory permissions.'
    ]);
}

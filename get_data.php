<?php
session_start();
include 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json');

$data = [];

// ✅ Fetch latest DB record
$result = $conn->query("SELECT * FROM static_content ORDER BY id DESC LIMIT 1");
if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
}

// ✅ Set action (view/download)
$data['action'] = $_SESSION['action'] ?? 'view';
unset($_SESSION['action']); // Clear session after use

// ✅ Locate latest barcode folder inside cropped_barcodes/run_*
$barcodeDir = __DIR__ . '/cropped_barcodes';
$runFolders = glob($barcodeDir . '/run_*', GLOB_ONLYDIR);

$barcodes = [];
if (!empty($runFolders)) {
    rsort($runFolders); // Sort by latest
    $latestRun = $runFolders[0];

    // Get all .png or .jpg files (adjust extension if needed)
    $files = glob($latestRun . '/*.png');
    if (empty($files)) {
        $files = glob($latestRun . '/*.jpg');
    }

    sort($files); // Ensure they are in order
    foreach ($files as $file) {
        // Return relative paths for HTML use
        $relativePath = 'cropped_barcodes/' . basename($latestRun) . '/' . basename($file);
        $barcodes[] = $relativePath;
    }
}

$data['barcodes'] = $barcodes;

// ✅ Return response
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

<?php
session_start();
include 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json');

$response = [];

// ✅ Fetch latest static content from database
try {
    $stmt = $conn->prepare("SELECT * FROM static_content ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $response = $result->fetch_assoc() ?? [];
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// ✅ Get session-based action ("view" or "download")
$response['action'] = $_SESSION['action'] ?? 'view';
unset($_SESSION['action']); // Clear it after using

// ✅ Locate latest barcode folder from /cropped_barcodes/run_*
$barcodeBaseDir = __DIR__ . '/cropped_barcodes';
$barcodes = [];

if (is_dir($barcodeBaseDir)) {
    $runFolders = glob($barcodeBaseDir . '/run_*', GLOB_ONLYDIR);
    
    if (!empty($runFolders)) {
        rsort($runFolders); // Sort descending (latest first)
        $latestRun = $runFolders[0];

        // ✅ Collect all .png or .jpg files in latest run folder
        $imageFiles = glob($latestRun . '/*.png');
        if (empty($imageFiles)) {
            $imageFiles = glob($latestRun . '/*.jpg');
        }

        sort($imageFiles); // Sort ascending

        foreach ($imageFiles as $img) {
            // Make path relative for frontend use
            $relativePath = 'cropped_barcodes/' . basename($latestRun) . '/' . basename($img);
            $barcodes[] = $relativePath;
        }
    }
}

$response['barcodes'] = $barcodes;

// ✅ Final JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

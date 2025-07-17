<?php
session_start();
include 'db.php';

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json');

// Fetch latest record
$result = $conn->query("SELECT * FROM static_content ORDER BY id DESC LIMIT 1");

$data = $result->fetch_assoc() ?? [];

// Default to view if session action isn't set
$data['action'] = $_SESSION['action'] ?? 'view';

// Output JSON (with error handling)
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

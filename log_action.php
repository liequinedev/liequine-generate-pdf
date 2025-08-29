<?php
// log_action.php
include 'logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? 'unknown';
    $message = $_POST['message'] ?? '';

    switch ($action) {
        case 'info':
            logInfo($message);
            break;
        case 'error':
            logError($message);
            break;
        default:
            logInfo("Unknown action: " . $message);
    }

    echo json_encode(['status' => 'ok']);
}

<?php
include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $action = $_POST['action'];

    // Form fields
    $heading = $_POST['heading'] ?? '';
    $sub_heading = $_POST['sub_heading'] ?? '';    
    $from_us_to_you_title = $_POST['from_us_to_you_title'] ?? '';
    $from_us_to_you_cnt = $_POST['from_us_to_you_cnt'] ?? '';
    $cnt_address_sec = $_POST['cnt_address_sec'] ?? '';
    $in_addition_title = $_POST['in_addition_title'] ?? '';
    $in_addition_cnt = $_POST['in_addition_cnt'] ?? '';
    $footer_cnt = $_POST['footer_cnt'] ?? '';

    // Upload folders
    $uploadDir = __DIR__ . '/uploads/';
    $tempDir = __DIR__ . '/temp/';
    @mkdir($uploadDir, 0777, true);
    @mkdir($tempDir, 0777, true);

    $bannerImagePath = '';
    $contentImagePath = '';
    $csvPath = '';
    $qrPdfPath = '';
    $qrPdfPathFull = '';

    // 📤 Handle uploads
    if (!empty($_FILES['banner_sec_image']['tmp_name'])) {
        $name = uniqid() . '_' . basename($_FILES['banner_sec_image']['name']);
        move_uploaded_file($_FILES['banner_sec_image']['tmp_name'], $uploadDir . $name);
        $bannerImagePath = 'uploads/' . $name;
    }

    if (!empty($_FILES['cnt_sec_image']['tmp_name'])) {
        $name = uniqid() . '_' . basename($_FILES['cnt_sec_image']['name']);
        move_uploaded_file($_FILES['cnt_sec_image']['tmp_name'], $uploadDir . $name);
        $contentImagePath = 'uploads/' . $name;
    }

    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $name = uniqid() . '_' . basename($_FILES['csv_file']['name']);
        move_uploaded_file($_FILES['csv_file']['tmp_name'], $tempDir . $name);
        $csvPath = 'temp/' . $name;
    }

    if (!empty($_FILES['qr_pdf']['tmp_name'])) {
        $name = uniqid() . '_qr.pdf';
        $qrPdfPathFull = $uploadDir . $name;
        move_uploaded_file($_FILES['qr_pdf']['tmp_name'], $qrPdfPathFull);
        $qrPdfPath = 'uploads/' . $name;
    }

    if (empty($qrPdfPathFull) || !file_exists($qrPdfPathFull)) {
        die("❌ Error: QR PDF file is required and was not uploaded properly.");
    }

    // ✅ Validate CSV row count == PDF page count
    $pdfPageCount = 0;
    $csvRowCount = 0;

    $pythonPath = "C:\\Users\\mdm459\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
    $pythonPageScript = __DIR__ . "/get_pdf_page_count.py";
    $cmd = "\"$pythonPath\" " . escapeshellarg($pythonPageScript) . " " . escapeshellarg($qrPdfPathFull);
    exec($cmd, $output, $status);

    if ($status === 0 && isset($output[0]) && is_numeric(trim($output[0]))) {
        $pdfPageCount = (int) trim($output[0]);
    }

    if (!empty($csvPath) && file_exists(__DIR__ . '/' . $csvPath)) {
        $lines = file(__DIR__ . '/' . $csvPath, FILE_SKIP_EMPTY_LINES);
        $csvRowCount = count($lines) - 1;
    }

    if ($pdfPageCount !== $csvRowCount) {
        die("❌ PDF page count ($pdfPageCount) does not match CSV row count ($csvRowCount).");
    }

    $fastApiUrl = "http://localhost:8000/crop-barcodes/";
    $cfile = new CURLFile($qrPdfPathFull, 'application/pdf', basename($qrPdfPathFull));
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $fastApiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['file' => $cfile],
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        die("❌ FastAPI barcode crop failed or returned invalid response: $res");
    }

    $response = json_decode($res, true);

    if (!isset($response['files'])) {
        die("file not found");
    }
    if (!isset($response['folder'])) {
        die("Folder not found");
    }

    session_start();
    $_SESSION['barcode_folder'] = $response['folder'];


    // ✅ Store or update in DB
    $result = $conn->query("SELECT id FROM static_content ORDER BY id DESC LIMIT 1");
    $query = '';
    if ($result->num_rows > 0) {
        $id = $result->fetch_assoc()['id'];
        $query = "UPDATE static_content SET
            heading=?, sub_heading=?, 
            from_us_to_you_title=?, from_us_to_you_cnt=?, cnt_address_sec=?,
            in_addition_title=?, in_addition_cnt=?, footer_cnt=?";

        $params = [
            $heading, $sub_heading,
            $from_us_to_you_title, $from_us_to_you_cnt, $cnt_address_sec,
            $in_addition_title, $in_addition_cnt, $footer_cnt
        ];
        $types = str_repeat('s', count($params));

        if (!empty($bannerImagePath)) {
            $query .= ", banner_sec_image=?";
            $params[] = $bannerImagePath;
            $types .= 's';
        }

        if (!empty($contentImagePath)) {
            $query .= ", cnt_sec_image=?";
            $params[] = $contentImagePath;
            $types .= 's';
        }

        if (!empty($csvPath)) {
            $query .= ", csv_path=?";
            $params[] = $csvPath;
            $types .= 's';
        }

        $query .= " WHERE id=?";
        $params[] = $id;
        $types .= 'i';

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    } else {
        $query = "INSERT INTO static_content (
            heading, sub_heading,
            from_us_to_you_title, from_us_to_you_cnt, cnt_address_sec,
            in_addition_title, in_addition_cnt, footer_cnt,
            banner_sec_image, cnt_sec_image, csv_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);

        $stmt->bind_param(
            "sssssssssss",  // 12 strings
            $heading, $sub_heading,
            $from_us_to_you_title, $from_us_to_you_cnt, $cnt_address_sec,
            $in_addition_title, $in_addition_cnt, $footer_cnt,
            $banner_sec_image, $cnt_sec_image, $csv_path
        );

        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['action'] = $action;
    // ✅ Now pass the action in the URL
    header("Location: template.php?action=view");
    exit;
}
?>

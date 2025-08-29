<?php
include 'db.php';
include_once 'logger.php';
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
        logError('Mailing PDF file is required and was not uploaded properly.');
        die("❌ Error: Mailing PDF file is required and was not uploaded properly.");
    }else{
        logInfo('Mailing PDF file uploaded properly.');
    }

    // ✅ Get PDF page count from FastAPI instead of exec()
    $fastApiPageCountUrl = "http://localhost:8000/count-pages/";
    $cfilePage = new CURLFile($qrPdfPathFull, 'application/pdf', basename($qrPdfPathFull));
    $chPage = curl_init();
    curl_setopt_array($chPage, [
        CURLOPT_URL => $fastApiPageCountUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['file' => $cfilePage],
    ]);
    $pageRes = curl_exec($chPage);
    $pageHttpCode = curl_getinfo($chPage, CURLINFO_HTTP_CODE);
    curl_close($chPage);

    if ($pageHttpCode !== 200) {
        logError('PDF count failed:' . $pageRes);
        die("❌ PDF count failed: $pageRes");
    }else{
        logInfo("PDF Count Success");
    }

    $pageData = json_decode($pageRes, true);
    $pdfPageCount = $pageData['total_pages'] ?? 0;

    // ✅ CSV row count
    $csvRowCount = 0;
    if (!empty($csvPath) && file_exists(__DIR__ . '/' . $csvPath)) {
        $lines = file(__DIR__ . '/' . $csvPath, FILE_SKIP_EMPTY_LINES);
        $csvRowCount = count($lines) - 1;
    }

    // ✅ Compare counts
    if ($pdfPageCount !== $csvRowCount) {
        logError("PDF page count ($pdfPageCount) does not match CSV row count ($csvRowCount).");
        die("❌ PDF page count ($pdfPageCount) does not match CSV row count ($csvRowCount).");
    }else{
        logInfo("PDF and CSV Count Matched");
    }

    // ✅ Call FastAPI barcode crop
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
        logError("FastAPI barcode crop failed or returned invalid response: $res");
        die("❌ FastAPI barcode crop failed or returned invalid response: $res");
    }else{
        logInfo("FastAPI barcode crop Success");
    }

    $response = json_decode($res, true);
    if (!isset($response['files']) || !isset($response['folder'])) {
        logError("Missing files or folder from FastAPI response: $response");
        die("❌ Missing files or folder from FastAPI response: $response");
    }else{
        logInfo("File and Folder Path Available in Response");
    }

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
            "sssssssssss",
            $heading, $sub_heading,
            $from_us_to_you_title, $from_us_to_you_cnt, $cnt_address_sec,
            $in_addition_title, $in_addition_cnt, $footer_cnt,
            $bannerImagePath, $contentImagePath, $csvPath
        );
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['action'] = $action;
    header("Location: template.php?action=view");
    logInfo("Redirected to view PDF page");
    exit;
}
?>

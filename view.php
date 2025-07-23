<?php
include 'db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $action = $_POST['action'];

    // Collect form data
    $heading = $_POST['heading'] ?? '';
    $subheading = $_POST['subheading'] ?? '';
    $address = $_POST['address'] ?? '';

    $futyc = [];
    for ($i = 1; $i <= 6; $i++) {
        $futyc["cnt$i"] = $_POST["from_us_to_you_cnt$i"] ?? '';
    }

    $propAddr = [];
    for ($i = 1; $i <= 8; $i++) {
        $propAddr["cnt$i"] = $_POST["prop_address_cnt$i"] ?? '';
    }

    $inAddition = [];
    for ($i = 1; $i <= 6; $i++) {
        $inAddition["cnt$i"] = $_POST["in_addition_cnt$i"] ?? '';
    }

    $footer = $_POST['footer_cnt'] ?? '';

    // 📁 Upload handling
    $uploadDir = __DIR__ . '/uploads/';
    $tempDir = __DIR__ . '/temp/';
    $barcodeOutputDir = __DIR__ . '/cropped_barcodes/';
    @mkdir($uploadDir, 0777, true);
    @mkdir($tempDir, 0777, true);
    @mkdir($barcodeOutputDir, 0777, true);

    $imagePath = '';
    $csvPath = '';
    $qrPdfPath = '';

    // Handle image upload
    if (!empty($_FILES['image']['tmp_name'])) {
        $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
        $imagePath = 'uploads/' . $imageName;
    }

    // Handle CSV upload
    if (!empty($_FILES['csv']['tmp_name'])) {
        $csvName = uniqid() . '_' . basename($_FILES['csv']['name']);
        $csvPathFull = $tempDir . $csvName;
        move_uploaded_file($_FILES['csv']['tmp_name'], $csvPathFull);
        $csvPath = 'temp/' . $csvName;
    }

    // Handle QR PDF upload
    if (!empty($_FILES['qr_pdf']['tmp_name'])) {
        $pdfName = uniqid() . '_qr.pdf';
        $qrPdfPathFull = $uploadDir . $pdfName;
        move_uploaded_file($_FILES['qr_pdf']['tmp_name'], $qrPdfPathFull);
        $qrPdfPath = 'uploads/' . $pdfName;
    }

    if (empty($qrPdfPathFull) || !file_exists($qrPdfPathFull)) {
        die("❌ Error: QR PDF file is required and was not uploaded properly.");
    }

    // ✅ Validation: PDF pages vs CSV rows
    $pdfPageCount = 0;
    $csvRowCount = 0;

    if (!empty($qrPdfPathFull) && file_exists($qrPdfPathFull)) {
        $pythonPath = "C:\\Users\\mdm459\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
        $pythonPageScript = __DIR__ . "/get_pdf_page_count.py";

        $cmdPageCount = "\"$pythonPath\" " . escapeshellarg($pythonPageScript) . " " . escapeshellarg($qrPdfPathFull) . " 2>&1";
        $output = [];
        exec($cmdPageCount, $output, $pageReturnCode);

        echo "<pre>Return code: $pageReturnCode\nOutput:\n" . implode("\n", $output) . "</pre>";

        // Update $pdfPageCount only if exec succeeded and output is valid
        if ($pageReturnCode === 0 && isset($output[0]) && is_numeric(trim($output[0]))) {
            $pdfPageCount = (int) trim($output[0]);
        } else {
            // Optional: handle error case here, e.g. throw or set to 0
            $pdfPageCount = 0;
        }
    }


    if (!empty($csvPathFull) && file_exists($csvPathFull)) {
        $lines = file($csvPathFull, FILE_SKIP_EMPTY_LINES);
        $csvRowCount = count($lines) - 1; // Exclude header
    }

    if ($pdfPageCount !== $csvRowCount) {
        die("❌ Validation failed: PDF has $pdfPageCount pages, but CSV has $csvRowCount data rows. Operation aborted.");
    }

    // ✅ Barcode extraction
    if (!empty($qrPdfPathFull)) {
        $barcodeRunFolder = $barcodeOutputDir . uniqid('run_');
        @mkdir($barcodeRunFolder, 0777, true);

        $pythonPath = "C:\\Users\\mdm459\\AppData\\Local\\Programs\\Python\\Python313\\python.exe";
        $cmd = "\"$pythonPath\" crop_barcode.py " . escapeshellarg($qrPdfPathFull) . " " . escapeshellarg($barcodeRunFolder);
        exec($cmd . " 2>&1", $output, $returnCode);
        echo "<pre>CMD: $cmd\nOutput:\n" . implode("\n", $output) . "\nReturn Code: $returnCode\n</pre>";

        if ($returnCode !== 0) {
            die("❌ Barcode cropping failed.");
        }
    }

    // ✅ DB Operation
    $result = $conn->query("SELECT id FROM static_content ORDER BY id DESC LIMIT 1");

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];

        $query = "UPDATE static_content SET heading=?, subheading=?, address=?,
            from_us_to_you_cnt1=?, from_us_to_you_cnt2=?, from_us_to_you_cnt3=?, from_us_to_you_cnt4=?, from_us_to_you_cnt5=?, from_us_to_you_cnt6=?,
            prop_address_cnt1=?, prop_address_cnt2=?, prop_address_cnt3=?, prop_address_cnt4=?, prop_address_cnt5=?, prop_address_cnt6=?, prop_address_cnt7=?, prop_address_cnt8=?,
            in_addition_cnt1=?, in_addition_cnt2=?, in_addition_cnt3=?, in_addition_cnt4=?, in_addition_cnt5=?, in_addition_cnt6=?,
            footer_cnt=?";

        $params = [
            $heading, $subheading, $address,
            $futyc['cnt1'], $futyc['cnt2'], $futyc['cnt3'], $futyc['cnt4'], $futyc['cnt5'], $futyc['cnt6'],
            $propAddr['cnt1'], $propAddr['cnt2'], $propAddr['cnt3'], $propAddr['cnt4'], $propAddr['cnt5'], $propAddr['cnt6'], $propAddr['cnt7'], $propAddr['cnt8'],
            $inAddition['cnt1'], $inAddition['cnt2'], $inAddition['cnt3'], $inAddition['cnt4'], $inAddition['cnt5'], $inAddition['cnt6'],
            $footer
        ];
        $types = str_repeat('s', count($params));

        if (!empty($imagePath)) {
            $query .= ", image_path=?";
            $params[] = $imagePath;
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
        // Insert new
        $query = "INSERT INTO static_content (
            heading, subheading, address,
            from_us_to_you_cnt1, from_us_to_you_cnt2, from_us_to_you_cnt3, from_us_to_you_cnt4, from_us_to_you_cnt5, from_us_to_you_cnt6,
            prop_address_cnt1, prop_address_cnt2, prop_address_cnt3, prop_address_cnt4, prop_address_cnt5, prop_address_cnt6, prop_address_cnt7, prop_address_cnt8,
            in_addition_cnt1, in_addition_cnt2, in_addition_cnt3, in_addition_cnt4, in_addition_cnt5, in_addition_cnt6,
            footer_cnt, image_path, csv_path
        ) VALUES (?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            'ssssssssssssssssssssssssss',
            $heading, $subheading, $address,
            $futyc['cnt1'], $futyc['cnt2'], $futyc['cnt3'], $futyc['cnt4'], $futyc['cnt5'], $futyc['cnt6'],
            $propAddr['cnt1'], $propAddr['cnt2'], $propAddr['cnt3'], $propAddr['cnt4'], $propAddr['cnt5'], $propAddr['cnt6'], $propAddr['cnt7'], $propAddr['cnt8'],
            $inAddition['cnt1'], $inAddition['cnt2'], $inAddition['cnt3'], $inAddition['cnt4'], $inAddition['cnt5'], $inAddition['cnt6'],
            $footer, $imagePath, $csvPath
        );
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['action'] = $action;
    echo "<script>window.location.href='template.html';</script>";
    exit;
}
?>

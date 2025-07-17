<?php
include 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action']; // 'view' or 'download'

    // Collect form data
    $heading = $_POST['heading'] ?? '';
    $subheading = $_POST['subheading'] ?? '';
    $address = $_POST['address'] ?? '';

    // From Us To You
    $futyc = [];
    for ($i = 1; $i <= 6; $i++) {
        $futyc["cnt$i"] = $_POST["from_us_to_you_cnt$i"] ?? '';
    }

    // Property Address
    $propAddr = [];
    for ($i = 1; $i <= 8; $i++) {
        $propAddr["cnt$i"] = $_POST["prop_address_cnt$i"] ?? '';
    }

    // In Addition
    $inAddition = [];
    for ($i = 1; $i <= 6; $i++) {
        $inAddition["cnt$i"] = $_POST["in_addition_cnt$i"] ?? '';
    }

    $footer = $_POST['footer_cnt'] ?? '';

    // Handle image upload
    $imagePath = '';
    if (!empty($_FILES['image']['tmp_name'])) {
        $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetPath = 'uploads/' . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);
        $imagePath = $targetPath;
    }

    // Handle CSV upload
    $csvPath = '';
    if (!empty($_FILES['csv']['tmp_name'])) {
        $csvName = uniqid() . '_' . basename($_FILES['csv']['name']);
        $csvPath = 'temp/' . $csvName;
        move_uploaded_file($_FILES['csv']['tmp_name'], $csvPath);
    }

    // Check if a record already exists
    $result = $conn->query("SELECT id FROM static_content ORDER BY id DESC LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];

        // Build dynamic update query
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
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        if (!$stmt->bind_param($types, ...$params)) {
            die("Bind failed: " . $stmt->error);
        }
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

    } else {
        // Insert new row
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
    }

    $stmt->execute();
    $stmt->close();

    session_start();
    $_SESSION['action'] = $action;

    // Redirect to HTML template
    echo "<script>window.location.href='template.html';</script>";
    exit;
}
?>

<?php
include 'db.php';

$uploadDir = __DIR__ . '/uploads/';
$csvPath = '';
$imagePath = '';

if (!empty($_FILES['csv']['tmp_name'])) {
    $csvName = uniqid() . '_' . $_FILES['csv']['name'];
    $csvPath = $uploadDir . $csvName;
    move_uploaded_file($_FILES['csv']['tmp_name'], $csvPath);
}

if (!empty($_FILES['image']['tmp_name'])) {
    $imgName = uniqid() . '_' . $_FILES['image']['name'];
    $imagePath = $uploadDir . $imgName;
    move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
}

$data = [
    'heading'    => $_POST['heading'],
    'subheading' => $_POST['subheading'],
    'address'    => $_POST['address'],
    'content1'   => $_POST['content1'],
    'content2'   => $_POST['content2'],
    'content3'   => $_POST['content3'],
    'image_path' => 'uploads/' . $imgName,
    'csv_path'   => 'uploads/' . $csvName,
    'action'     => $_POST['action']
];

file_put_contents('temp/data.json', json_encode($data));

// Redirect to template
header("Location: template.html");
exit;

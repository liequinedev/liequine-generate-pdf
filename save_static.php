<?php
include 'db.php';

$heading = $_POST['heading'];
$subheading = $_POST['subheading'];
$address = $_POST['address'];
$content1 = $_POST['content1'];
$content2 = $_POST['content2'];
$content3 = $_POST['content3'];

$image_name = $_FILES['image']['name'];
$image_tmp = $_FILES['image']['tmp_name'];
$image_path = "uploads/" . uniqid() . "_" . basename($image_name);
move_uploaded_file($image_tmp, $image_path);

// Clear old entry (only 1 record needed)
$conn->query("DELETE FROM static_content");

$stmt = $conn->prepare("INSERT INTO static_content (heading, subheading, address, image_path, content1, content2, content3) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $heading, $subheading, $address, $image_path, $content1, $content2, $content3);
$stmt->execute();

echo "Saved successfully. <a href='index.php'>Go back</a>";

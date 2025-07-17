<?php
include 'db.php';

// Get latest static content
$result = $conn->query("SELECT * FROM static_content ORDER BY id DESC LIMIT 1");
$static = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF Generator</title>
</head>
<body>
    <h2>Static & CSV Upload</h2>
    <form id="pdfForm" action="view.php" method="POST" enctype="multipart/form-data">
        <input type="text" name="heading" placeholder="Heading" value="<?= htmlspecialchars($static['heading'] ?? '') ?>"><br>
        <input type="text" name="subheading" placeholder="Subheading" value="<?= htmlspecialchars($static['subheading'] ?? '') ?>"><br>
        <textarea name="address" readonly><?= htmlspecialchars($static['address'] ?? 'Smithtown, NY') ?></textarea><br>
        <input type="file" name="image" accept="image/*"><br>

        <?php if (!empty($static['image_path'])): ?>
            <img src="<?= htmlspecialchars($static['image_path']) ?>" alt="Current Image" width="100"><br>
        <?php endif; ?>

        <textarea name="from_us_to_you_cnt1" placeholder="From us to you content1"><?= htmlspecialchars($static['from_us_to_you_cnt1'] ?? '') ?></textarea><br>
        <textarea name="from_us_to_you_cnt2" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt2'] ?? '') ?></textarea><br>
        <textarea name="from_us_to_you_cnt3" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt3'] ?? '') ?></textarea><br>
        <textarea name="from_us_to_you_cnt4" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt4'] ?? '') ?></textarea><br>
        <textarea name="from_us_to_you_cnt5" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt5'] ?? '') ?></textarea><br>
        <textarea name="from_us_to_you_cnt6" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt6'] ?? '') ?></textarea><br>

        <textarea name="prop_address_cnt1" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt1'] ?? '') ?></textarea><br>
        <textarea name="prop_address_cnt2" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt2'] ?? '') ?></textarea><br>
        <textarea name="prop_address_cnt3" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt3'] ?? '') ?></textarea><br>
        <textarea name="prop_address_cnt4" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt4'] ?? '') ?></textarea><br>
        <textarea name="prop_address_cnt5" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt5'] ?? '') ?></textarea><br>
        <textarea name="prop_address_cnt6" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt6'] ?? '') ?></textarea><br>
        <textarea name="prop_address_cnt7" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt7'] ?? '') ?></textarea><br>
        <textarea name="prop_address_cnt8" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt8'] ?? '') ?></textarea><br>

        <textarea name="in_addition_cnt1" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt1'] ?? '') ?></textarea><br>
        <textarea name="in_addition_cnt2" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt2'] ?? '') ?></textarea><br>
        <textarea name="in_addition_cnt3" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt3'] ?? '') ?></textarea><br>
        <textarea name="in_addition_cnt4" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt4'] ?? '') ?></textarea><br>
        <textarea name="in_addition_cnt5" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt5'] ?? '') ?></textarea><br>
        <textarea name="in_addition_cnt6" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt6'] ?? '') ?></textarea><br>

        <textarea name="footer_cnt" placeholder="Footer Content"><?= htmlspecialchars($static['footer_cnt'] ?? '') ?></textarea><br>
        

        <input type="file" name="csv" accept=".csv"><br>

        <?php if (!empty($static['csv_path'])): ?>
            <p>Current CSV: <a href="<?= htmlspecialchars($static['csv_path']) ?>" target="_blank">Download Current CSV</a></p>
        <?php endif; ?>


        <!-- Buttons always enabled -->
        <button type="submit" name="action" value="view">View PDF</button>
        <button type="submit" name="action" value="download">Download PDF</button>
    </form>
</body>
</html>

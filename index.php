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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <!-- <h2>Static & CSV Upload</h2> -->
    <div class="container">    
        <form id="pdfForm" action="view.php" method="POST" enctype="multipart/form-data">
            <div class="form-field-div">
                <input type="text" name="heading" placeholder="Heading" value="<?= htmlspecialchars($static['heading'] ?? '') ?>">
                <input type="text" name="subheading" placeholder="Subheading" value="<?= htmlspecialchars($static['subheading'] ?? '') ?>">
                <textarea name="address" readonly hidden><?= htmlspecialchars($static['address'] ?? 'Smithtown, NY') ?></textarea>
                <div>
                    <input type="file" name="image" accept="image/*">

                    <?php if (!empty($static['image_path'])): ?>
                        <img src="<?= htmlspecialchars($static['image_path']) ?>" alt="Current Image" width="100">
                    <?php endif; ?>
                </div>
                <div>
                    <label>Upload QR PDF:</label>
                    <input type="file" name="qr_pdf" accept="application/pdf">
                </div>        
                <div>
                    <input type="file" name="csv" accept=".csv" >

                   <!-- <?php if (!empty($static['csv_path'])): ?>
                        <p>Current CSV: <a href="<?= htmlspecialchars($static['csv_path']) ?>" target="_blank">Download Current CSV</a></p>
                    <?php endif; ?> -->
                </div>

                <textarea name="from_us_to_you_cnt1" placeholder="From us to you content1"><?= htmlspecialchars($static['from_us_to_you_cnt1'] ?? '') ?></textarea>
                <textarea name="from_us_to_you_cnt2" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt2'] ?? '') ?></textarea>
                <textarea name="from_us_to_you_cnt3" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt3'] ?? '') ?></textarea>
                <textarea name="from_us_to_you_cnt4" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt4'] ?? '') ?></textarea>
                <textarea name="from_us_to_you_cnt5" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt5'] ?? '') ?></textarea>
                <textarea name="from_us_to_you_cnt6" placeholder="From us to you content"><?= htmlspecialchars($static['from_us_to_you_cnt6'] ?? '') ?></textarea>
                        
                <textarea name="prop_address_cnt1" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt1'] ?? '') ?></textarea>
                <textarea name="prop_address_cnt2" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt2'] ?? '') ?></textarea>
                <textarea name="prop_address_cnt3" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt3'] ?? '') ?></textarea>
                <textarea name="prop_address_cnt4" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt4'] ?? '') ?></textarea>
                <textarea name="prop_address_cnt5" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt5'] ?? '') ?></textarea>
                <textarea name="prop_address_cnt6" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt6'] ?? '') ?></textarea>
                <textarea name="prop_address_cnt7" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt7'] ?? '') ?></textarea>
                <textarea name="prop_address_cnt8" placeholder="Property Address content"><?= htmlspecialchars($static['prop_address_cnt8'] ?? '') ?></textarea>

                <textarea name="in_addition_cnt1" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt1'] ?? '') ?></textarea>
                <textarea name="in_addition_cnt2" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt2'] ?? '') ?></textarea>
                <textarea name="in_addition_cnt3" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt3'] ?? '') ?></textarea>
                <textarea name="in_addition_cnt4" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt4'] ?? '') ?></textarea>
                <textarea name="in_addition_cnt5" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt5'] ?? '') ?></textarea>
                <textarea name="in_addition_cnt6" placeholder="In Addition content"><?= htmlspecialchars($static['in_addition_cnt6'] ?? '') ?></textarea>

                <textarea name="footer_cnt" placeholder="Footer Content"><?= htmlspecialchars($static['footer_cnt'] ?? '') ?></textarea>
                        
            </div>

            


            <!-- Buttons always enabled -->
            <div class="pdfform-btn">
                <button type="submit" name="action" value="view">View PDF</button>
                <button type="submit" name="action" value="download">Download PDF</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>

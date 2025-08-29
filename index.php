<?php
include 'db.php';
include_once 'logger.php';

// Get latest static content
$result = $conn->query("SELECT * FROM static_content ORDER BY id DESC LIMIT 1");
$static = $result->fetch_assoc();
if($static == ''){
    logError("Static Data Fetching faild");
}else{
    logInfo("Static Data Fetch Success");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
</head>
<body>
<div class="container">
    <form id="pdfForm" action="view.php" method="POST" enctype="multipart/form-data">
        <div class="form-field-div">
            <div class="pdfForm-file-upload-div">
                <!-- QR PDF -->
                <div>
                    <label>Upload Mailing PDF:</label>
                    <input type="file" name="qr_pdf" accept="application/pdf">
                </div>

                <!-- CSV -->
                <div>
                    <label>Upload CSV File:</label>
                    <input type="file" name="csv_file" accept=".csv">
                </div>
            </div>
            <div class="pdfForm-head-section-div">
                <!-- Heading  -->
                <textarea class="heading" name="heading" placeholder="Heading"><?= htmlspecialchars($static['heading'] ?? '') ?></textarea>
                <textarea class="sub_heading" name="sub_heading" placeholder="Sub Heading"><?= htmlspecialchars($static['sub_heading'] ?? '') ?></textarea>
            </div>

            <div class="pdfForm-bnr-section-div">
                <div class="pdfForm-bnr-address">
                    
                    <p>{{Name}}</p>
                    <p>{{QR Code}}</p>
                    <p>{{Address}}</p>
                </div>

                <!-- Banner Section Image -->
                <div class="pdfForm-bnr-address">
                    <label>Banner Section Image:</label>
                    <input type="file" name="banner_sec_image" accept="image/*">
                    <?php if (!empty($static['banner_sec_image'])): ?>
                        <img src="<?= htmlspecialchars($static['banner_sec_image']) ?>" alt="Banner Image" width="100">
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-body-section">
                <!-- From Us To You -->
                <div class="from_us_to_you_section">
                    <textarea class="from_us_to_you_title" name="from_us_to_you_title" placeholder="From Us To You Title"><?= htmlspecialchars($static['from_us_to_you_title'] ?? '') ?></textarea>
                    <textarea class="from_us_to_you_cnt" name="from_us_to_you_cnt" placeholder="From Us To You Content"><?= htmlspecialchars($static['from_us_to_you_cnt'] ?? '') ?></textarea>
                </div>

                <div class="cnt_address_and_img">
                    <!-- Address Section -->
                    <textarea class="cnt_address_sec" name="cnt_address_sec" placeholder="Address Section Content"><?= htmlspecialchars($static['cnt_address_sec'] ?? '') ?></textarea>

                    <!-- Section Image -->
                    <div class="cnt_sec_image_div">
                        <input type="file" name="cnt_sec_image" accept="image/*">
                        <?php if (!empty($static['cnt_sec_image'])): ?>
                            <img src="<?= htmlspecialchars($static['cnt_sec_image']) ?>" alt="Content Section Image" width="100">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- In Addition -->
                <div class="in_addition_section">
                    <textarea class="in_addition_title" name="in_addition_title" placeholder="In Addition Title"><?= htmlspecialchars($static['in_addition_title'] ?? '') ?></textarea>
                    <textarea class="in_addition_cnt" name="in_addition_cnt" placeholder="In Addition Content"><?= htmlspecialchars($static['in_addition_cnt'] ?? '') ?></textarea>
                </div>

            </div>
            <!-- Footer -->
            <textarea class="footer_cnt" name="footer_cnt" placeholder="Footer Content"><?= htmlspecialchars($static['footer_cnt'] ?? '') ?></textarea>
        </div>

        <!-- Buttons -->
        <div class="pdfform-btn">
            <button type="submit" name="action" value="view">Preview PDF</button>
            <!-- <button type="submit" name="action" value="download">Download PDF</button> -->
        </div>
    </form>
</div>

<!-- Loader -->
<div id="loader-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:#fff8f8e3; z-index:9999; text-align:center; padding-top:20%;">
  <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
    <span class="visually-hidden">Loading...</span>
  </div>
  <p class="mt-3">Processing, please wait...</p>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const form = document.getElementById("pdfForm");
  const loader = document.getElementById("loader-overlay");

  form.addEventListener("submit", function () {
    loader.style.display = "block";  // Show loader
  });
</script>

</body>
</html>

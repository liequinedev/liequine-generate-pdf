<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;

header('Content-Type: application/json');

// Read the raw POST body
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Start capturing HTML content
ob_start();
?>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .user-box { page-break-after: always; }
        img { max-width: 200px; }
        .heading{
            color:"red";
        }
    </style>
</head>
<body>
    

<?php if (!empty($data['users'])): ?>
    <?php foreach ($data['users'] as $user): ?>
        <div class="user-box">
            <!-- Common content for all pages -->
            <h1 style="color:#932313; text-align:center;"><?= htmlspecialchars($data['heading'] ?? '') ?></h1>
            <h2 style="color:#70ad5b; text-align:center;"><?= htmlspecialchars($data['sub_heading'] ?? '') ?></h2>
            

            <!-- User-specific content -->
            <table>
                <tr>
                    <td>
                        <p><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
                        <?php if (!empty($user['barcode_base64'])): ?>
                            <img src="<?= htmlspecialchars($user['barcode_base64']) ?>" alt="Barcode">
                        <?php endif; ?>
                        <p><?= htmlspecialchars($user['mailing_address']) ?></p>
                        <p><?= htmlspecialchars($user['mailing_town']) ?>, <?= htmlspecialchars($user['mailing_zip']) ?>, </strong> <?= htmlspecialchars($user['mailing_carrier_route']) ?>, <?= htmlspecialchars($user['county']) ?>, <?= htmlspecialchars($user['state']) ?></p>
                    </td>
                    <td>
                        <p>Welcome</p>
                    </td>
                </tr>
            </table>
            <table>
                <tr style="border:1px solid black;">
                    <td>
                        <p>row 1 colom 1</p>
                    </td>
                    <td>
                        <p>row 1 colum 2</p>
                    </td>
                    <td>
                        <p>row 1 colum 3</p>
                    </td>
                </tr>
                <tr style="border:1px solid black;">
                    <td>
                        <p>row 2 colom 1</p>
                    </td>
                    <td>
                        <p>row 2 colum 2</p>
                    </td>
                    <td>
                        <p>row 2 colum 3</p>
                    </td>
                </tr>
            </table>
            <p><?= nl2br(htmlspecialchars($data['from_us_to_you_cnt'] ?? '')) ?></p>            

            

            <!-- Common image for all pages -->
            <?php if (!empty($data['cnt_sec_image_base64'])): ?>
                <img src="<?= htmlspecialchars($data['cnt_sec_image_base64']) ?>" alt="Extra Image">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

                   
    
</body>
</html>
<?php
$html = ob_get_clean();

// Generate PDF
$dompdf = new Dompdf(); 
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Clear all output before PDF output
ob_clean();
flush();

// Output the PDF
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=generated.pdf");
echo $dompdf->output();
exit;
?>

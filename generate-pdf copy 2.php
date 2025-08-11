<?php
require 'vendor/autoload.php';

use Mpdf\Mpdf;

// Read POST data
header('Content-Type: application/json');
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Start HTML buffer
ob_start();
?>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .user-box { page-break-after: always; }
        img { max-width: 200px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid black; padding: 5px; }
        h1 { color: #932313; text-align: center; }
        h2 { color: #70ad5b; text-align: center; }
    </style>
</head>
<body>

<?php if (!empty($data['users'])): ?>
    <?php foreach ($data['users'] as $user): ?>
        <div class="user-box">
            <h1><?= htmlspecialchars($data['heading'] ?? '') ?></h1>
            <h2><?= htmlspecialchars($data['sub_heading'] ?? '') ?></h2>

            <table>
                <tr>
                    <td>
                        <p><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
                        <?php if (!empty($user['barcode_base64'])): ?>
                            <img src="<?= $user['barcode_base64'] ?>" alt="Barcode">
                        <?php endif; ?>
                        <p><?= htmlspecialchars($user['mailing_address']) ?></p>
                        <p><?= htmlspecialchars($user['mailing_town']) ?>, 
                           <?= htmlspecialchars($user['mailing_zip']) ?>, 
                           <?= htmlspecialchars($user['mailing_carrier_route']) ?>, 
                           <?= htmlspecialchars($user['county']) ?>, 
                           <?= htmlspecialchars($user['state']) ?></p>
                    </td>
                    <td>
                        <p>Welcome</p>
                    </td>
                </tr>
            </table>

            <table>
                <tr>
                    <td rowspan="1"><p><?= nl2br(htmlspecialchars($data['from_us_to_you_title'] ?? '')) ?></p></td>
                    <td rowspan="2"><p><?= nl2br(htmlspecialchars($data['cnt_address_sec'] ?? '')) ?></p></td>
                    <td rowspan="1"><p><?= nl2br(htmlspecialchars($data['in_addition_title'] ?? '')) ?></p></td>
                </tr>
                <tr>
                    <td rowspan="1"></td>
                    <td rowspan="1"></td>
                </tr>
                <tr>
                    <td rowspan="1"><p><?= nl2br(htmlspecialchars($data['from_us_to_you_cnt'] ?? '')) ?></p></td>
                    <td>
                        <?php if (!empty($data['cnt_sec_image_base64'])): ?>
                            <img src="<?= $data['cnt_sec_image_base64'] ?>" alt="Extra Image">
                        <?php endif; ?>
                    </td>
                    <td rowspan="1"><p><?= nl2br(htmlspecialchars($data['in_addition_cnt'] ?? '')) ?></p></td>
                </tr>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

// Create mPDF instance
$mpdf = new Mpdf(['format' => 'A4']);

// Write HTML to PDF
$mpdf->WriteHTML($html);

// Output PDF to browser
$mpdf->Output('generated.pdf', 'D');
?>

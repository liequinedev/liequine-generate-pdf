<!DOCTYPE html>
<html>
<head>
  <title>Generated PDF</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: Arial, sans-serif;
    }
    /* Your existing CSS unchanged ... */
    .container {
        max-width: 100%;
        width: 43rem;
        margin: 0 auto;
        padding: 30px 0;
        min-height: calc(100vh - 80px);
        page-break-after: always;
    }
    /* ... rest of your styles unchanged */
    h1, h3, p {
        text-align: center;
        margin: 0; 
        color: #932313;               
    }
    p {
        font-weight: 700;
    }
    h3 {
        color: #70ad5b;
    }
    h1 {
        font-size: 34px;
        margin-bottom: 10px;
    }
    .address-sec {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 1rem;
    }
    .address p {
        text-align: left;
    }
    .static-content-section {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
        margin-top: 5px;
    }
    .static-title {
        border: 5px solid #932313;
        padding: 10px;
        text-align: center;
        margin-bottom: 8px;
        font-weight: 700;
        color: #932313;   
    }
    .static-subcnt-div {
        border: 2px solid #932313;
        padding: 10px;
        text-align: center;
        margin-bottom: 8px;
    }
    .static-sub-image {
        border: 2px solid #932313;
        padding: 10px;
        text-align: center;
        margin-bottom: 8px;
    }
    .footer-section {
        border: 5px solid #932313;
        padding: 10px;
        text-align: center;
        font-size: 22px;
        font-weight: 700;
    }
    .static-cnt-div {
        display: grid;
        grid-template-rows: auto 1fr;
    }
    .static-subcnt-div p {
        margin-bottom: 1rem;
    }
    #downloadBtn, #backBtn {
        position: fixed;
        right: 10%;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: 700;
        cursor: pointer;
        z-index: 1000;
    }
    #downloadBtn {
        top: 10%;
        background-color: #8f5a34;
    }
    #downloadBtn:hover {
        background-color: #bf7f51;
    }
    #backBtn {
        top: 20%;
        background-color: #8f5a34;
    }
    #backBtn:hover {
        background-color: #bf7f51;
    }
    .address-section-indiv{
        display:flex;
        justify-content: space-between;
        gap:30px;
    }
    @media print {
        #downloadBtn, #backBtn {
            display: none;
        }
    }
  </style>
</head>
<body>

  <div id="pdf-content"></div>

  <button id="downloadBtn">Download PDF</button>
  <button id="backBtn">Back</button>

  <!-- Loader -->
<div id="loader-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:#fff8f8e3; z-index:9999; text-align:center; padding-top:20%;">
  <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
    <span class="visually-hidden">Loading...</span>
  </div>
  <p class="mt-3">Processing, please wait...</p>
</div>

<script>
    const folderName = "<?php session_start(); echo $_SESSION['barcode_folder'] ?? 'run_12345678'; ?>";

    // Utility: Fetch image from URL and convert to base64 string
    async function getBase64FromUrl(url) {
        if (!url) return null;
        const response = await fetch(url);
        const blob = await response.blob();
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.readAsDataURL(blob);
        });
    }

    async function renderContent() {
        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action') || 'view';

        const response = await fetch('get_data.php');
        const data = await response.json();

        const csvText = await fetch(data.csv_path).then(r => r.text());
        const rows = csvText.trim().split("\n").map(r => r.split(','));
        const headers = rows[0].map(h => h.trim().toLowerCase());
        const users = rows.slice(1).map(row => {
            let obj = {};
            headers.forEach((h, i) => obj[h] = row[i]);
            return obj;
        });

        // Show all users here, or limit if you want
        const limitedUsers = users;

        // Render HTML as before (optional if you want to preview)
        let html = '';
        limitedUsers.forEach((user, index) => {
            const line1 = [user['mailing address']].filter(Boolean).join(' ');
            const line2 = [user['city-state-zip']].filter(Boolean).join(', ');
            const bin_number = [user['presorttrayid']].filter(Boolean).join(', ');
            const date = [user['presortdate']].filter(Boolean).join(', ');
            const fullAddr = `${line1}<br>${line2}`;
            const barcodeImg = data.barcodes && data.barcodes[index] ? data.barcodes[index] : null;

            const bar_code = barcodeImg
                ? `<div class="barcode-sec"><img src="${barcodeImg}" alt="Barcode Image" width="300"></div>`
                : '<p>No Barcode</p>';

            html += `
            <section class="container">
                <h1>${data.heading}</h1>
                <h3>${data.sub_heading}</h3>
                <div class="address-sec">
                    <div class="address">
                        <p style='padding-bottom:5px;'>${user['owner first name'] || ''} ${user['owner last name'] || ''},</p>
                        ${bar_code}
                        <div class="address-section-indiv">
                          <div>
                            <p>${line1}</p>
                            <p>${line2}</p>
                          </div>
                          <div>
                            <p>${bin_number}</p>
                            <p>${date}</p>
                          </div>
                        </div>                        
                    </div>
                    <div class="image-sec">
                        <img src="${data.banner_sec_image}" alt="Image" width="300">
                    </div>
                </div>

                <div class="static-content-section">
                    <div class="static-cnt-div section1">
                        <div class="static-title">${data.from_us_to_you_title}</div>
                        <div class="static-subcnt-div">
                            <p>Hello ${user['owner first name'] || ''}, </p>
                            <p>${data.from_us_to_you_cnt}</p>
                        </div>
                    </div>
                    <div class="static-cnt-div section2">
                        <div class="static-subcnt-div">
                            <p>${data.cnt_address_sec}</p>                        
                        </div>
                        <div class="static-sub-image">
                            <img src="${data.cnt_sec_image}" alt="Sub Image">
                        </div>
                    </div>
                    <div class="static-cnt-div section3">
                        <div class="static-title">${data.in_addition_title}</div>
                        <div class="static-subcnt-div">
                            <p>${data.in_addition_cnt}</p>
                        </div>
                    </div>
                </div>
                <div class="footer-section">${data.footer_cnt}</div>
            </section>`;
        });

        document.getElementById('pdf-content').innerHTML = html;

        if (action === 'download') {
            downloadPDF(limitedUsers, data);
        }
    }
    
    async function downloadPDF(users, data) {
        const loader = document.getElementById('loader-overlay');
        try {            
            loader.style.display = 'block'; // Show loader
            // Convert all barcode images to base64
            const usersWithBase64 = await Promise.all(users.map(async (user, index) => {
                const barcodeUrl = data.barcodes && data.barcodes[index] ? data.barcodes[index] : null;
                const barcode_base64 = barcodeUrl ? await getBase64FromUrl(barcodeUrl) : null;

                return {
                    first_name: user['owner first name'],
                    last_name: user['owner last name'],
                    mailing_address: user['mailing address'],
                    // mailing_town: user['mailing town'],
                    mailing_zip: user['city-state-zip'],
                    // mailing_carrier_route: user['mailing carrier route'],
                    // county: user['county'],
                    // state: user['state'],
                    barcode_base64: barcode_base64,
                    bin_number: user['presorttrayid'],
                    date: user['presortdate']
                };
            }));

            // Convert static images to base64
            const cnt_sec_image_base64 = await getBase64FromUrl(data.cnt_sec_image);
            const banner_sec_image_base64 = await getBase64FromUrl(data.banner_sec_image);

            // Payload matches backend expectations
            const payload = {
                heading: data.heading,
                sub_heading: data.sub_heading,
                banner_sec_image_base64: banner_sec_image_base64,
                from_us_to_you_title: data.from_us_to_you_title,
                from_us_to_you_cnt: data.from_us_to_you_cnt,
                cnt_address_sec: data.cnt_address_sec,                
                cnt_sec_image_base64: cnt_sec_image_base64,
                in_addition_title: data.in_addition_title,
                in_addition_cnt: data.in_addition_cnt,
                footer_cnt: data.footer_cnt,
                users: usersWithBase64
            };

            // Call the API — use /api/generate-pdf for Vercel
            const res = await fetch('https://vercel-pdf-download-nine.vercel.app/api/generate-pdf', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({ message: 'Unknown error' }));
                console.error('PDF error', err);
                alert('PDF generation failed');
                return;
            }else{
                console.log("PDF generation Success");
            }

            // Download the PDF
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'generated.pdf';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
            await fetch('log_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'info',
                    message: 'PDF downloaded successfully'
                })
            });

        } catch (err) {
            await fetch('log_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'error',
                    message: 'PDF download failed: ' + err.message
                })
            });
            alert("Error downloading PDF: " + err.message);
        }
        finally {
            console.log("PDF Downloaded Successfully");
            loader.style.display = 'none'; // Hide loader regardless of success/fail
        }
    }


    document.getElementById('downloadBtn').addEventListener('click', async () => {
        const response = await fetch('get_data.php');
        const data = await response.json();
        const csvText = await fetch(data.csv_path).then(r => r.text());
        const rows = csvText.trim().split("\n").map(r => r.split(','));
        const headers = rows[0].map(h => h.trim().toLowerCase());
        const users = rows.slice(1).map(row => {
            let obj = {};
            headers.forEach((h, i) => obj[h] = row[i]);
            return obj;
        });
        await downloadPDF(users, data);
    });

    document.getElementById('backBtn').addEventListener('click', () => window.location.href = 'index.php');

    renderContent();
</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>



</body>
</html>

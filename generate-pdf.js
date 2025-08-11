// /api/generate-pdf.js
import chromium from "@sparticuz/chromium";
import puppeteer from "puppeteer-core";

export default async function handler(req, res) {
  if (req.method !== "POST") {
    res.setHeader("Allow", "POST");
    return res.status(405).send("Method Not Allowed");
  }

  try {
    const payload = req.body || {};

    // Build HTML from payload (safe minimal example)
    // For large PDFs, prefer building HTML in templates or server-side streaming
    const html = payload.html || generateHtmlFromPayload(payload);

    // Launch Chromium
    const browser = await puppeteer.launch({
      args: chromium.args,
      defaultViewport: chromium.defaultViewport,
      executablePath: await chromium.executablePath(),
      headless: chromium.headless,
    });

    const page = await browser.newPage();

    // Set content and wait until network idle so external fonts/images load
    await page.setContent(html, { waitUntil: "networkidle0" });

    // Optional: set emulated media for print CSS
    await page.emulateMediaType("screen");

    // Create PDF options — tune these to your needs
    const pdfBuffer = await page.pdf({
      format: "A4",
      printBackground: true,
      margin: { top: "15mm", right: "10mm", bottom: "15mm", left: "10mm" },
      preferCSSPageSize: true, // honor @page size if used
    });

    await browser.close();

    // Send PDF
    res.setHeader("Content-Type", "application/pdf");
    res.setHeader(
      "Content-Disposition",
      `attachment; filename="${payload.filename || "generated"}.pdf"`
    );
    res.status(200).send(pdfBuffer);
  } catch (err) {
    console.error("PDF generation error:", err);
    // try to close any running browser instance gracefully
    res.status(500).json({ error: "Failed to generate PDF", details: String(err) });
  }
}

/**
 * Example helper to build HTML from JSON payload.
 * Replace this with your real template. Keep images as data URLs or public URLs.
 */
function generateHtmlFromPayload(data) {
  const users = Array.isArray(data.users) ? data.users : [];
  return `<!doctype html>
  <html>
    <head>
      <meta charset="utf-8" />
      <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; margin:0; padding:10mm; }
        .page { page-break-after: always; padding: 10mm; box-sizing: border-box; }
        h1 { text-align:center; color:#932313; }
        h2 { text-align:center; color:#70ad5b; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; align-items: start; }
        .left { }
        .right { }
        table { border-collapse: collapse; width: 100%; margin-top: 12px; }
        td, th { border: 1px solid #333; padding: 6px; }
        img { max-width: 200px; height: auto; display:block; }
        .footer-image { margin-top: 12px; }
        /* page-break utilities */
        .avoid-break { break-inside: avoid; }
        @page { size: A4; margin: 15mm; }
      </style>
    </head>
    <body>
      ${users
        .map(user => `
        <div class="page">
          <h1>${escapeHtml(data.heading || "")}</h1>
          <h2>${escapeHtml(data.sub_heading || "")}</h2>

          <div class="grid">
            <div class="left">
              <p><strong>${escapeHtml(user.first_name || "")} ${escapeHtml(user.last_name || "")}</strong></p>
              ${user.barcode_base64 ? `<img src="${user.barcode_base64}" alt="barcode" />` : ""}
              <p>${escapeHtml(user.mailing_address || "")}</p>
              <p>${escapeHtml(user.mailing_town || "")}, ${escapeHtml(user.mailing_zip || "")}</p>
              <p>${escapeHtml(user.mailing_carrier_route || "")}, ${escapeHtml(user.county || "")}, ${escapeHtml(user.state || "")}</p>
            </div>
            <div class="right">
              <p>Welcome</p>
            </div>
          </div>

          <table class="avoid-break">
            <tr>
              <td>row 1 column 1</td><td>row 1 column 2</td><td>row 1 column 3</td>
            </tr>
            <tr>
              <td>row 2 column 1</td><td>row 2 column 2</td><td>row 2 column 3</td>
            </tr>
          </table>

          <p>${(data.from_us_to_you_cnt || "").replace(/\n/g, "<br/>")}</p>

          ${data.cnt_sec_image_base64 ? `<div class="footer-image"><img src="${data.cnt_sec_image_base64}" alt="footer" /></div>` : ""}
        </div>
      `).join('')}
    </body>
  </html>`;
}

function escapeHtml(str = "") {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

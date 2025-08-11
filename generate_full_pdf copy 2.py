from fastapi import APIRouter
from fastapi.responses import StreamingResponse
from pydantic import BaseModel
from typing import List, Optional
from io import BytesIO
import base64
import pdfkit
import tempfile
import os

router = APIRouter()

# --------------------------
# Models
# --------------------------
class UserData(BaseModel):
    first_name: Optional[str]
    last_name: Optional[str]
    mailing_address: Optional[str]
    mailing_town: Optional[str]
    mailing_zip: Optional[str]
    mailing_carrier_route: Optional[str]
    county: Optional[str]
    state: Optional[str]
    barcode_base64: Optional[str]

class PdfRequest(BaseModel):
    heading: str
    sub_heading: str
    from_us_to_you_title: str
    from_us_to_you_cnt: str
    cnt_address_sec: str
    cnt_sec_image_base64: Optional[str]
    in_addition_title: str
    in_addition_cnt: str
    footer_cnt: str
    users: List[UserData]

# --------------------------
# Endpoint
# --------------------------
@router.post("/generate-pdf/")
async def generate_pdf(data: PdfRequest):
    all_users_html = ""

    for idx, user in enumerate(data.users):
        barcode_img = f'<img src="data:image/png;base64,{user.barcode_base64}" style="height:40px;">' if user.barcode_base64 else ""
        section_img = f'<img src="{data.cnt_sec_image_base64}" style="max-width:100%;">' if data.cnt_sec_image_base64 else ""

        all_users_html += f"""
        <div class="page">
            <h1>{data.heading}</h1>
            <h2>{data.sub_heading}</h2>

            <div class="banner">
                <strong>{user.first_name or ''} {user.last_name or ''}</strong><br>
                {barcode_img}<br>
                {user.mailing_address or ''}<br>
                {user.mailing_town or ''}, {user.mailing_zip or ''}<br>
                {user.mailing_carrier_route or ''}, {user.county or ''}, {user.state or ''}
            </div>

            <div class="grid">
                <div>
                    <div class="box"><strong>{data.from_us_to_you_title}</strong></div>
                    <div class="box">Hello {user.first_name or ''}, {data.from_us_to_you_cnt}</div>
                </div>

                <div>
                    <div class="box"><strong>{data.cnt_address_sec}</strong></div>
                    <div class="box">{section_img}</div>
                </div>

                <div>
                    <div class="box"><strong>{data.in_addition_title}</strong></div>
                    <div class="box">{data.in_addition_cnt}</div>
                </div>
            </div>

            <div class="footer">{data.footer_cnt}</div>
        </div>
        """

    html_content = f"""
    <html>
    <head>
    <meta charset="UTF-8">
    <style>
        body {{
            font-family: Arial, sans-serif;
            color: #942314;
            margin: 20px;
        }}
        h1 {{
            text-align: center;
            font-size: 22px;
        }}
        h2 {{
            text-align: center;
            font-size: 16px;
            color: #45ae5b;
        }}
        .banner {{
            margin-bottom: 15px;
            font-size: 12px;
        }}
        .grid {{
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 10px;
        }}
        .box {{
            border: 1px solid #000;
            padding: 8px;
            min-height: 100px;
            width: 32%;
        }}
        .footer {{
            border: 1px solid #000;
            padding: 8px;
            margin-top: 10px;
            text-align: center;
        }}
        .page {{
            page-break-after: always;
        }}
        .page:last-child {{
            page-break-after: auto;
        }}
    </style>
    </head>
    <body>
        {all_users_html}
    </body>
    </html>
    """

    # Generate PDF using wkhtmltopdf via pdfkit
    try:
        with tempfile.NamedTemporaryFile(suffix=".pdf", delete=False) as tmp_pdf:
            pdfkit.from_string(html_content, tmp_pdf.name)
            tmp_pdf.seek(0)
            return StreamingResponse(tmp_pdf, media_type="application/pdf", headers={
                "Content-Disposition": "attachment; filename=styled_flyer.pdf"
            })
    finally:
        # Clean up temp file
        if os.path.exists(tmp_pdf.name):
            os.unlink(tmp_pdf.name)

from fastapi import APIRouter
from fastapi.responses import StreamingResponse
from pydantic import BaseModel
from typing import List, Optional
from io import BytesIO
from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import A4
from reportlab.lib.utils import ImageReader
from PIL import Image
import base64

router = APIRouter()

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

@router.post("/generate-pdf/")
async def generate_pdf(data: PdfRequest):
    buffer = BytesIO()
    pdf = canvas.Canvas(buffer, pagesize=A4)
    width, height = A4

    for user in data.users:
        pdf.setFont("Helvetica-Bold", 16)
        pdf.drawCentredString(width / 2, height - 50, data.heading)

        pdf.setFont("Helvetica", 12)
        pdf.drawCentredString(width / 2, height - 70, data.sub_heading)

        # User address
        y = height - 110
        pdf.setFont("Helvetica", 10)
        pdf.drawString(50, y, f"{user.first_name or ''} {user.last_name or ''}")
        y -= 15
        pdf.drawString(50, y, user.mailing_address or "")
        y -= 15
        pdf.drawString(50, y, f"{user.mailing_town or ''}, {user.mailing_zip or ''}")
        y -= 15
        pdf.drawString(50, y, f"{user.mailing_carrier_route or ''}, {user.county or ''}, {user.state or ''}")

        # Barcode image (if present)
        if user.barcode_base64:
            try:
                barcode_bytes = base64.b64decode(user.barcode_base64)
                barcode_img = Image.open(BytesIO(barcode_bytes))
                barcode_img = barcode_img.convert("RGB")
                img_reader = ImageReader(barcode_img)
                pdf.drawImage(img_reader, 400, height - 150, width=150, height=40, preserveAspectRatio=True)
            except Exception:
                pass  # Ignore barcode errors

        # Section: From Us to You
        y -= 50
        pdf.setFont("Helvetica-Bold", 12)
        pdf.drawString(50, y, data.from_us_to_you_title)
        y -= 15
        pdf.setFont("Helvetica", 10)
        pdf.drawString(50, y, f"Hello {user.first_name or ''}, {data.from_us_to_you_cnt}")

        # Section: cnt_address_sec + image
        y -= 50
        pdf.setFont("Helvetica-Bold", 12)
        pdf.drawString(50, y, data.cnt_address_sec)

        if data.cnt_sec_image_base64:
            try:
                img_data = base64.b64decode(data.cnt_sec_image_base64)
                img = Image.open(BytesIO(img_data)).convert("RGB")
                img_reader = ImageReader(img)
                pdf.drawImage(img_reader, 50, y - 100, width=200, height=100, preserveAspectRatio=True)
            except Exception:
                pass

        # Section: In Addition
        y -= 130
        pdf.setFont("Helvetica-Bold", 12)
        pdf.drawString(50, y, data.in_addition_title)
        y -= 15
        pdf.setFont("Helvetica", 10)
        pdf.drawString(50, y, data.in_addition_cnt)

        # Footer
        pdf.setFont("Helvetica", 9)
        pdf.drawString(50, 50, data.footer_cnt)

        pdf.showPage()

    pdf.save()
    buffer.seek(0)

    return StreamingResponse(buffer, media_type="application/pdf", headers={
        "Content-Disposition": "attachment; filename=generated_flyer.pdf"
    })

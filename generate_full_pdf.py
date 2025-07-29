from fastapi import APIRouter
from fastapi.responses import StreamingResponse
from pydantic import BaseModel
from typing import List, Optional
from io import BytesIO
import base64
from reportlab.lib.pagesizes import LETTER
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Image, PageBreak, Table, TableStyle
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib import colors
from reportlab.lib.units import inch
from xml.sax.saxutils import escape

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

def sanitize(text):
    return escape(text or "").replace("  ", "&nbsp;&nbsp;").replace("\n", "<br/>")

def image_from_base64(base64_str: str, width=2*inch, height=1*inch) -> Optional[Image]:
    if not base64_str:
        return None
    img_data = base64.b64decode(base64_str.split(",")[-1])
    return Image(BytesIO(img_data), width, height)

@router.post("/generate-pdf/")
async def generate_pdf(data: PdfRequest):
    buffer = BytesIO()
    doc = SimpleDocTemplate(buffer, pagesize=LETTER, rightMargin=40, leftMargin=40, topMargin=40, bottomMargin=40)
    elements = []

    styles = getSampleStyleSheet()
    title_style = ParagraphStyle("TitleStyle", parent=styles['Title'], alignment=TA_CENTER, fontSize=20, textColor="#942314")
    sub_title_style = ParagraphStyle("SubTitle", parent=styles['Heading2'], alignment=TA_CENTER, fontSize=14, textColor="#45ae5b")    
    banner_cnt = ParagraphStyle("NormalText", parent=styles["BodyText"], fontSize=10, alignment=TA_LEFT, textColor="#942314")
    normal = ParagraphStyle("NormalText", parent=styles["BodyText"], fontSize=10, alignment=TA_CENTER, textColor="#942314")
    section_title = ParagraphStyle("SectionTitle", fontSize=11, textColor="#942314", alignment=TA_CENTER, spaceAfter=4, spaceBefore=2)
    footer_style = ParagraphStyle("Footer", alignment=TA_CENTER, fontSize=10, textColor="#942314", spaceBefore=12)

    for user in data.users:
        # Heading
        elements.append(Paragraph(sanitize(data.heading), title_style))
        elements.append(Paragraph(sanitize(data.sub_heading), sub_title_style))
        elements.append(Spacer(1, 12))

        # Banner Section (Name + Barcode + Address)
        full_name = f"{user.first_name or ''} {user.last_name or ''}".strip()
        elements.append(Paragraph(sanitize(full_name), banner_cnt))

        if user.barcode_base64:
            barcode = image_from_base64(user.barcode_base64, width=2*inch, height=0.5*inch)
            if barcode:
                barcode.hAlign = 'LEFT'
                elements.append(Spacer(1, 4))
                elements.append(barcode)

        address_lines = [
            sanitize(user.mailing_address or ""),
            sanitize(f"{user.mailing_town or ''}, {user.mailing_zip or ''}"),
            sanitize(f"{user.mailing_carrier_route or ''}, {user.county or ''}, {user.state or ''}")
        ]
        for line in address_lines:
            elements.append(Paragraph(line, banner_cnt))

        elements.append(Spacer(1, 12))

        # Left Column - FROM US TO YOU
        left_col = []
        left_col.append(Paragraph(sanitize(data.from_us_to_you_title), section_title))
        left_col.append(Spacer(1, 4))
        left_col.append(Paragraph(sanitize(f"Hello {user.first_name or ''},"), normal))
        left_col.append(Paragraph(sanitize(data.from_us_to_you_cnt), normal))

        # Middle Column - Address Section + Image
        mid_col = []
        mid_col.append(Paragraph(sanitize(data.cnt_address_sec), section_title))
        mid_col.append(Spacer(1, 4))
        if data.cnt_sec_image_base64:
            mid_col.append(Spacer(1, 8))
            img = image_from_base64(data.cnt_sec_image_base64, width=2.5*inch, height=1.5*inch)
            if img:
                img.hAlign = 'CENTER'
                mid_col.append(img)

        # Right Column - IN ADDITION
        right_col = []
        right_col.append(Paragraph(sanitize(data.in_addition_title), section_title))
        right_col.append(Spacer(1, 4))
        right_col.append(Paragraph(sanitize(data.in_addition_cnt), normal))

        # Table Layout
        table = Table([[left_col, mid_col, right_col]], colWidths=[2.3*inch, 2.3*inch, 2.3*inch])
        table.setStyle(TableStyle([
            ('VALIGN', (0, 0), (-1, -1), 'TOP'),
            ('LEFTPADDING', (0, 0), (-1, -1), 8),
            ('RIGHTPADDING', (0, 0), (-1, -1), 8),
            ('TOPPADDING', (0, 0), (-1, -1), 8),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 8),
            ('GRID', (0, 0), (-1, -1), 1, colors.grey),  # optional
        ]))

        elements.append(table)
        elements.append(Spacer(1, 12))

        # Footer
        elements.append(Paragraph(sanitize(data.footer_cnt), footer_style))
        elements.append(PageBreak())

    doc.build(elements)
    buffer.seek(0)
    return StreamingResponse(buffer, media_type="application/pdf", headers={
        "Content-Disposition": "attachment; filename=styled_flyer.pdf"
    })

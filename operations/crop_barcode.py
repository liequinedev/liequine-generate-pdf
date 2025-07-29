from fastapi import APIRouter, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse
import fitz  # PyMuPDF
from PIL import Image
import shutil
import os
import uuid

router = APIRouter()

# Crop settings
CROP_X = 805
CROP_Y = 610
CROP_WIDTH = 925
CROP_HEIGHT = 115

@router.post("/crop-barcodes/")
async def crop_barcodes(file: UploadFile = File(...)):
    if file.content_type != "application/pdf":
        raise HTTPException(status_code=400, detail="Only PDF files are allowed.")

    # Create output folder
    output_folder = "cropped_barcodes"
    os.makedirs(output_folder, exist_ok=True)

    # Generate unique PDF filename
    pdf_filename = os.path.join(output_folder, f"{uuid.uuid4()}.pdf")

    try:
        # Save uploaded PDF file
        with open(pdf_filename, "wb") as f:
            shutil.copyfileobj(file.file, f)

        # Open the saved PDF
        doc = fitz.open(pdf_filename)

        for i, page in enumerate(doc):
            pix = page.get_pixmap(dpi=300)
            img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)

            left = CROP_X
            top = CROP_Y
            right = CROP_X + CROP_WIDTH
            bottom = CROP_Y + CROP_HEIGHT
            cropped = img.crop((left, top, right, bottom))

            output_path = os.path.join(output_folder, f"barcode_page_{i+1}.png")
            cropped.save(output_path)

        return JSONResponse({
            "message": "All barcodes cropped successfully.",
            "total_pages": len(doc),
            "files": sorted(f for f in os.listdir(output_folder) if f.endswith(".png"))
        })

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error cropping barcodes: {str(e)}")

    finally:
        file.file.close()

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

    # Generate unique folder name
    run_folder = f"run_{uuid.uuid4().hex[:8]}"
    output_folder = os.path.join("cropped_barcodes", run_folder)
    os.makedirs(output_folder, exist_ok=True)

    # Save uploaded PDF inside run folder
    pdf_filename = os.path.join(output_folder, f"{uuid.uuid4()}.pdf")

    try:
        with open(pdf_filename, "wb") as f:
            shutil.copyfileobj(file.file, f)

        # Open PDF and crop pages
        doc = fitz.open(pdf_filename)
        for i, page in enumerate(doc):
            pix = page.get_pixmap(dpi=300)
            img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)

            cropped = img.crop((
                CROP_X,
                CROP_Y,
                CROP_X + CROP_WIDTH,
                CROP_Y + CROP_HEIGHT
            ))

            output_path = os.path.join(output_folder, f"barcode_page_{i+1}.png")
            cropped.save(output_path)

        return JSONResponse({
            "message": "All barcodes cropped successfully.",
            "folder": run_folder,
            "files": sorted(f for f in os.listdir(output_folder) if f.endswith(".png"))
        })

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error cropping barcodes: {str(e)}")

    finally:
        file.file.close()


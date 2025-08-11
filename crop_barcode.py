from fastapi import APIRouter, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse
import pypdfium2 as pdfium
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

    # Create unique run folder
    run_folder = f"run_{uuid.uuid4().hex[:8]}"
    output_folder = os.path.join("cropped_barcodes", run_folder)
    os.makedirs(output_folder, exist_ok=True)

    pdf_path = os.path.join(output_folder, f"{uuid.uuid4()}.pdf")

    try:
        # Save uploaded file
        with open(pdf_path, "wb") as f:
            shutil.copyfileobj(file.file, f)

        # Load PDF with pypdfium2
        pdf = pdfium.PdfDocument(pdf_path)

        # Render each page and crop
        for i, page in enumerate(pdf):
            scale = 300 / 72  # Match 300 DPI like PyMuPDF
            bitmap = page.render(scale=scale).to_pil()
            cropped = bitmap.crop((CROP_X, CROP_Y, CROP_X + CROP_WIDTH, CROP_Y + CROP_HEIGHT))
            output_file = os.path.join(output_folder, f"barcode_page_{i+1}.png")
            cropped.save(output_file)

        return JSONResponse({
            "message": "All barcodes cropped successfully.",
            "folder": run_folder,
            "files": sorted(f for f in os.listdir(output_folder) if f.endswith(".png"))
        })

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error cropping barcodes: {str(e)}")

    finally:
        file.file.close()

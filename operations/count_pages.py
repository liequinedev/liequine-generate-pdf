from fastapi import APIRouter, UploadFile, File, HTTPException
from PyPDF2 import PdfReader
import tempfile
import os

router = APIRouter()

@router.post("/count-pages/")
async def count_pdf_pages(file: UploadFile = File(...)):
    if file.content_type != "application/pdf":
        raise HTTPException(status_code=400, detail="Only PDF files are allowed.")

    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as tmp:
            tmp.write(await file.read())
            tmp_path = tmp.name

        reader = PdfReader(tmp_path)
        page_count = len(reader.pages)

        return {"total_pages": page_count}

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Error reading PDF: {str(e)}")

    finally:
        file.file.close()
        if os.path.exists(tmp_path):
            os.remove(tmp_path)

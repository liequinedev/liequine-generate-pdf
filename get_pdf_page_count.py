import sys
from PyPDF2 import PdfReader

pdf_path = sys.argv[1]
reader = PdfReader(pdf_path)
print(len(reader.pages))

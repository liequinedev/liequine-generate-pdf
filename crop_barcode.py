# crop_barcode.py
import sys
import fitz  # PyMuPDF
from PIL import Image
import os

if len(sys.argv) != 3:
    print("Usage: python crop_barcode.py <input_pdf> <output_folder>")
    sys.exit(1)

input_pdf = sys.argv[1]
output_folder = sys.argv[2]

if not os.path.exists(output_folder):
    os.makedirs(output_folder)

# Crop settings (update as needed)
crop_x = 805
crop_y = 610
crop_width = 925
crop_height = 115

try:
    doc = fitz.open(input_pdf)
    for i, page in enumerate(doc):
        pix = page.get_pixmap(dpi=300)
        img = Image.frombytes("RGB", [pix.width, pix.height], pix.samples)

        left = crop_x
        top = crop_y
        right = crop_x + crop_width
        bottom = crop_y + crop_height
        cropped = img.crop((left, top, right, bottom))

        output_path = os.path.join(output_folder, f"barcode_page_{i+1}.png")
        cropped.save(output_path)

    print("All barcodes cropped successfully.")
    sys.exit(0)

except Exception as e:
    print("Python Error:", e)
    sys.exit(2)

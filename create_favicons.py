from PIL import Image, ImageOps
import os

logo_path = r"C:\Users\Mahagedara\.gemini\antigravity-ide\brain\e0e7ab91-ac01-4e5d-80b9-1779c2bb15ff\media__1785864500467.png"

# Load image
img = Image.open(logo_path)
width, height = img.size
print(f"Original dimensions: {width}x{height}")

# Make it square by adding black padding to the top and bottom
max_dim = max(width, height)
square_img = Image.new("RGB", (max_dim, max_dim), (0, 0, 0))
# Center the original image
pad_left = (max_dim - width) // 2
pad_top = (max_dim - height) // 2
square_img.paste(img, (pad_left, pad_top))

# Ensure target directories exist
os.makedirs("img", exist_ok=True)

# Save root favicon.png and favicon.ico
square_img.resize((64, 64), Image.Resampling.LANCZOS).save(os.path.join("img", "favicon.png"), "PNG")
square_img.resize((32, 32), Image.Resampling.LANCZOS).save(os.path.join("img", "favicon.ico"), "ICO")
print("Saved root favicon.png and favicon.ico")

# Overwrite Webflow favicon files
webflow_favicons = [
    ("urth_clone/images/6a1c3ef337c6c9b9b6b4fc66_favicon.jpg", (32, 32)),
    ("urth_clone/images/6a1c3ef308233f09aa0d7f70_favicon.jpg", (48, 48)),
    ("urth_clone/images/6a1c3ef3f96cad36ed74d8cd_favicon.jpg", (180, 180)),
    ("urth_clone/images/6a1c3ef31cd2b4a0088fd18b_favicon.jpg", (192, 192)),
    ("urth_clone/images/6a1c3ef3fc1f9b0c79b148b3_favicon.jpg", (512, 512))
]

for file_path, size in webflow_favicons:
    full_path = os.path.join("c:\\xampp\\htdocs\\urth", file_path)
    if os.path.exists(full_path):
        # Save as JPG since Webflow references them as .jpg
        square_img.resize(size, Image.Resampling.LANCZOS).save(full_path, "JPEG", quality=95)
        print(f"Overwrote Webflow favicon: {file_path} ({size[0]}x{size[1]})")
    else:
        print(f"Webflow favicon path not found: {file_path}")

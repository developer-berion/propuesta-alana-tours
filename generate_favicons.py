import os
from PIL import Image

def generate_favicons(source_path, output_dir):
    if not os.path.exists(source_path):
        print(f"Error: {source_path} not found.")
        return

    if not os.path.exists(output_dir):
        os.makedirs(output_dir)

    img = Image.open(source_path)
    width, height = img.size
    print(f"Original size: {width}x{height}")

    # Crop to square (center)
    min_dim = min(width, height)
    left = (width - min_dim) / 2
    top = (height - min_dim) / 2
    right = (width + min_dim) / 2
    bottom = (height + min_dim) / 2

    img_square = img.crop((left, top, right, bottom))
    print(f"Cropped to square: {img_square.size}")

    # 1. favicon-16x16.png
    img_16 = img_square.resize((16, 16), Image.Resampling.LANCZOS)
    img_16.save(os.path.join(output_dir, "favicon-16x16.png"))

    # 2. favicon-32x32.png
    img_32 = img_square.resize((32, 32), Image.Resampling.LANCZOS)
    img_32.save(os.path.join(output_dir, "favicon-32x32.png"))

    # 3. apple-touch-icon.png (180x180)
    img_180 = img_square.resize((180, 180), Image.Resampling.LANCZOS)
    img_180.save(os.path.join(output_dir, "apple-touch-icon.png"))

    # 4. favicon.ico (multi-size: 16, 32, 48)
    # create 48x48 as well for the ico
    img_48 = img_square.resize((48, 48), Image.Resampling.LANCZOS)
    img_square.save(os.path.join(output_dir, "favicon.ico"), format='ICO', sizes=[(16, 16), (32, 32), (48, 48)])
    
    print("Favicons generated successfully in " + output_dir)

if __name__ == "__main__":
    generate_favicons("Be.png", "assets/favicon")

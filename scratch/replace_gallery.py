import re
import os

gallery_html = """<section class="gallery-section">
  <style>
    .gallery-section {
      padding: 100px 5%;
      background-color: #fff;
    }
    .gallery-container {
      max-width: 1400px;
      margin: 0 auto;
      column-count: 3;
      column-gap: 40px;
    }
    .gallery-item {
      break-inside: avoid;
      margin-bottom: 40px;
    }
    .gallery-item img {
      width: 100%;
      height: auto;
      display: block;
    }
    @media (max-width: 991px) {
      .gallery-container {
        column-count: 2;
      }
    }
    @media (max-width: 767px) {
      .gallery-container {
        column-count: 1;
      }
    }
  </style>
  <div class="gallery-container">
    <div class="gallery-item"><img src="../img/urthprojs/1.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/I.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/2.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/I1.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/7.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/I2.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/8.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/I3.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/9.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/10.jpg" alt="Interior"></div>
    <div class="gallery-item"><img src="../img/urthprojs/I5.jpg" alt="Interior"></div>
  </div>
</section>"""

pages = [
    'poolscape-villa.html',
    'european-lard-station.html',
    'yabroudi-villa.html',
    'cultural-complex-centre.html',
    'dalbourne-villa.html',
    'amman-rotana-hotel.html'
]

for page in pages:
    filepath = f"urth_clone/{page}"
    if not os.path.exists(filepath):
        print(f"Skipping {page}, not found.")
        continue
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Replace the <section id="project" class="section">...</section>
    new_content = re.sub(r'<section id="project" class="section">.*?</section>', gallery_html, content, flags=re.DOTALL)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {page}")
    else:
        print(f"No match found in {page}")

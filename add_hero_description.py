import os
import glob

css_to_add = '''
    .hero-description-box {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      background-color: rgba(20, 20, 20, 0.85);
      padding: 30px 20px;
      text-align: center;
      box-sizing: border-box;
      z-index: 10;
    }
    .hero-description-box .desc-text {
      color: white;
      font-size: 18px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      max-width: 800px;
      margin: 0 auto;
      line-height: 1.5;
    }
    .hero-description-box .desc-location {
      color: #cc6600;
      font-size: 16px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      margin-top: 10px;
    }
'''

box_html_static = '''
    <div class="hero-description-box">
      <div class="desc-text">
        Description added here in white front
      </div>
      <div class="desc-location">
        Location: Canada
      </div>
    </div>
  </header>'''

files = ['amman-rotana-hotel.html', 'cultural-complex-centre.html', 'dalbourne-villa.html', 'european-lard-station.html', 'poolscape-villa.html', 'yabroudi-villa.html']

for f in files:
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    
    # Check if already modified
    if 'hero-description-box' in content:
        continue
    
    # Add CSS
    if '.hero-title {' in content:
        content = content.replace('.hero-title {', css_to_add + '\n    .hero-title {')
    
    # Add HTML
    if '</header>' in content:
        # We want to insert the box just before </header>
        content = content.replace('</header>', box_html_static)
        
    with open(f, 'w', encoding='utf-8') as file:
        file.write(content)
    print(f'Modified {f}')

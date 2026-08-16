import re

with open('c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html', 'r', encoding='utf-8') as f:
    content = f.read()

# Add the style block
style_block = """  <style>
    /* Override projects.css black background for this specific page */
    body {
      background-color: #ffffff !important;
      color: #000000;
    }
    .hero-title, .hero-subtitle, .hero-top-nav a {
      color: #ffffff !important;
    }
    .project-image {
      position: relative;
    }
    .category-tag {
      position: absolute;
      top: 16px;
      left: 16px;
      background: rgba(0, 0, 0, 0.4);
      color: #fff;
      padding: 6px 14px;
      border-radius: 30px;
      font-size: 11px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      z-index: 10;
      font-family: 'Montserrat', sans-serif;
      border: 1px solid rgba(255, 255, 255, 0.3);
      transition: background 0.3s ease;
    }
    .project-item:hover .category-tag {
      background: rgba(0, 0, 0, 0.7);
    }
  </style>
</head>"""

# Replace the existing style block
content = re.sub(r'  <style>\s*/\* Override projects\.css.*?</style>\s*</head>', style_block, content, flags=re.DOTALL)

# Add tags to each project image
# Looking for: <div class="project-image"><img src="../img/poolscape_villa.png" loading="lazy" alt="" class="parallax-image"></div>
# and replacing with <div class="project-image"><div class="category-tag">Architecture</div><img ...>
content = content.replace('<div class="project-image"><img', '<div class="project-image"><div class="category-tag">Architecture</div><img')

with open('c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html', 'w', encoding='utf-8') as f:
    f.write(content)
print("Updated successfully.")

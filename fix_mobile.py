import os
import glob

html_files = glob.glob('*.html') + glob.glob('urth_clone/*.html')
for file in html_files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if '.hero-title' in content and '@media (max-width: 768px)' not in content.split('.hero-title')[1][:200]:
        # Need to append media query before </style> that contains .hero-title
        # Let's find the closing style tag after .hero-title
        parts = content.split('.hero-title {')
        if len(parts) > 1:
            idx = content.find('</style>', content.find('.hero-title {'))
            if idx != -1:
                media_query = """
    @media (max-width: 768px) {
      .hero-title {
        font-size: 40px !important;
      }
      .hero-description-box .desc-text {
        font-size: 15px;
      }
      .hero-content {
        padding: 0 20px;
      }
    }
"""
                new_content = content[:idx] + media_query + content[idx:]
                with open(file, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Updated {file}")

# For project_detail.php
with open('project_detail.php', 'r', encoding='utf-8') as f:
    php_content = f.read()
if 'font-size: 40px;' in php_content and '.hero-description-box .desc-text' not in php_content:
    php_content = php_content.replace(
        '.hero-title {\n        font-size: 40px;\n      }',
        '.hero-title {\n        font-size: 40px;\n      }\n      .hero-description-box .desc-text {\n        font-size: 15px;\n      }\n      .hero-content {\n        padding: 0 20px;\n      }'
    )
    with open('project_detail.php', 'w', encoding='utf-8') as f:
        f.write(php_content)
    print("Updated project_detail.php")


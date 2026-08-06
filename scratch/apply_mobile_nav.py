import os
import re

ROOT_DIR = r"c:\xampp\htdocs\Urth\urth"

def get_relative_path(file_path):
    rel_path = os.path.relpath(ROOT_DIR, os.path.dirname(file_path))
    if rel_path == '.':
        return ''
    return rel_path.replace('\\', '/') + '/'

def process_html_file(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Skip if already injected
    if 'mobile-nav.css' in content:
        return False

    prefix = get_relative_path(file_path)

    # 1. Inject CSS in head
    css_link = f'\n  <link rel="stylesheet" href="{prefix}mobile-nav.css" />'
    content = re.sub(r'(</head>)', f'{css_link}\n\\1', content, flags=re.IGNORECASE)

    # 2. Inject JS before body end
    js_link = f'\n  <script src="{prefix}mobile-nav.js"></script>'
    content = re.sub(r'(</body>)', f'{js_link}\n\\1', content, flags=re.IGNORECASE)

    # 3. Mobile Overlay HTML
    overlay_html = f"""
  <div class="mobile-nav-overlay">
    <nav class="mobile-nav-links">
      <a href="{prefix}index.html">HOME</a>
      <a href="{prefix}urth_clone/index.html">ABOUT</a>
      <a href="{prefix}projects.html">PROJECTS</a>
      <a href="{prefix}contact.html">CONTACT</a>
      <a href="#">SHOP</a>
    </nav>
  </div>
"""
    # Insert right after <body>
    content = re.sub(r'(<body[^>]*>)', f'\\1\n{overlay_html}', content, flags=re.IGNORECASE)

    # 4. Hamburger button logic
    hamburger_btn = """
        <button class="mobile-nav-toggle" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    """
    
    # Check if file has hero-top-nav
    if 'hero-top-nav' in content and 'right-nav' in content:
        # insert into right-nav
        # Match <div class="right-nav"...></div>
        content = re.sub(
            r'(<div[^>]*class="right-nav"[^>]*>)\s*(</div>)', 
            f'\\1\n{hamburger_btn}\n\\2', 
            content
        )
    else:
        # Doesn't have right-nav, likely index.html
        # We inject a mobile header
        mobile_header = f"""
  <header class="mobile-index-header">
    <div class="logo">
      <a href="{prefix}index.html" style="text-decoration: none;">
        <h2>urth.</h2>
      </a>
    </div>
    {hamburger_btn}
  </header>
"""
        content = re.sub(r'(<body[^>]*>)', f'\\1\n{mobile_header}', content, flags=re.IGNORECASE)


    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    return True

def main():
    modified_count = 0
    for root, dirs, files in os.walk(ROOT_DIR):
        if 'node_modules' in dirs:
            dirs.remove('node_modules')
        if '.git' in dirs:
            dirs.remove('.git')
            
        for file in files:
            if file.endswith('.html'):
                file_path = os.path.join(root, file)
                if process_html_file(file_path):
                    print(f"Modified: {file_path}")
                    modified_count += 1
                    
    print(f"Done. Modified {modified_count} files.")

if __name__ == "__main__":
    main()

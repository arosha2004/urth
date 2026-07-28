import os
import glob
import re

def process_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    original_content = content

    # 1. Replace logo image with text
    # The logo tag might have different classes or spacing, so we use regex
    logo_regex = re.compile(r'<img[^>]*src="[^"]*dekora%20logo\.svg"[^>]*>')
    text_logo = '<h2 class="navbar-brand-text" style="color: white; margin: 0; font-size: 28px; font-weight: normal; letter-spacing: -1px; text-transform: lowercase;">urth</h2>'
    content = logo_regex.sub(text_logo, content)

    # 2. Replace 'dekora_clone' with 'urth_clone'
    content = content.replace('dekora_clone', 'urth_clone')
    
    # 3. Replace 'dekora_temp.html' with 'urth_temp.html'
    content = content.replace('dekora_temp.html', 'urth_temp.html')

    # 4. Replace remaining 'dekora' with 'urth' (case-insensitive for domains and other things)
    # But let's be careful about breaking other CDN images. We can just replace 'dekora' with 'urth'
    # generally because there aren't that many. We saw dekora-studio.webflow.io, and dekora home.avif
    # The user wants "no dekora anywhere". We will use regex to replace 'dekora' with 'urth'
    # in any string, but maintain casing if it was Dekora or DEKORA (already done). 
    # Just lowercase dekora to urth.
    content = content.replace('dekora', 'urth')

    if content != original_content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath}")

def main():
    root_dir = r"c:\xampp\htdocs\Urth\urth"
    
    # Process HTML files in root
    for filepath in glob.glob(os.path.join(root_dir, '*.html')):
        process_file(filepath)
        
    # Process HTML files in urth_clone
    for filepath in glob.glob(os.path.join(root_dir, 'urth_clone', '*.html')):
        process_file(filepath)
        
    # Also rename the CSS file in urth_clone/css/
    css_dir = os.path.join(root_dir, 'urth_clone', 'css')
    if os.path.exists(css_dir):
        for filename in os.listdir(css_dir):
            if 'dekora' in filename:
                new_filename = filename.replace('dekora', 'urth')
                os.rename(os.path.join(css_dir, filename), os.path.join(css_dir, new_filename))
                print(f"Renamed CSS file: {filename} to {new_filename}")
                
    # Also CSS files inside urth_clone/css/ could have contents to replace
    for filepath in glob.glob(os.path.join(root_dir, 'urth_clone', 'css', '*.css')):
        process_file(filepath)

if __name__ == '__main__':
    main()

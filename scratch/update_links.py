import os

directory = r'c:\xampp\htdocs\Urth - Copy\urth'

for root, _, files in os.walk(directory):
    for file in files:
        if file.endswith('.html'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            
            new_content = content.replace('urth_clone/index.html', 'urth_clone/about.html')
            new_content = new_content.replace('href="index.html">ABOUT', 'href="about.html">ABOUT')
            new_content = new_content.replace('href="index.html" class="active">ABOUT', 'href="about.html" class="active">ABOUT')
            
            if new_content != content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f'Updated {filepath}')

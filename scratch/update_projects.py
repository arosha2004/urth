import os
import re

html_files = [
    'c:/xampp/htdocs/Urth/urth/urth_clone/the-heritage-pavilion.html',
    'c:/xampp/htdocs/Urth/urth/urth_clone/residence-k.html',
    'c:/xampp/htdocs/Urth/urth/urth_clone/loft-no-07.html',
    'c:/xampp/htdocs/Urth/urth/urth_clone/the-monolith-house.html'
]

# Read the source section from index_pretty.html
with open('c:/xampp/htdocs/Urth/urth/urth_clone/index_pretty.html', 'r', encoding='utf-8') as f:
    index_html = f.read()

# Extract the #project section
match = re.search(r'(<section id="project" class="section">.*?</section>)', index_html, re.DOTALL)
if not match:
    print('Could not find #project section in index_pretty.html')
    exit(1)
project_section = match.group(1)

# The CSS to inject
style_block = '''<style>
  /* Stack effect for project items */
  #project .project-item {
    position: sticky !important;
  }
  #project .project-item:nth-child(1) { top: var(--_spacing---semi-large) !important; }
  #project .project-item:nth-child(2) { top: calc(var(--_spacing---semi-large) + 30px) !important; }
  #project .project-item:nth-child(3) { top: calc(var(--_spacing---semi-large) + 60px) !important; }
  #project .project-item:nth-child(4) { top: calc(var(--_spacing---semi-large) + 90px) !important; }
  #project .project-item:nth-child(5) { top: calc(var(--_spacing---semi-large) + 120px) !important; }
  #project, #project .w-layout-blockcontainer, #project .vertical-wrapper, #project .w-dyn-list, #project .project-list {
    overflow: visible !important;
  }
</style>
</head>'''

for filepath in html_files:
    if not os.path.exists(filepath):
        print(f'File not found: {filepath}')
        continue
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Replace the existing #project section
    content = re.sub(r'<section id="project" class="section[^"]*">.*?</section>', project_section, content, flags=re.DOTALL)
    
    # Inject the style block before </head>
    if '<style>\n  /* Stack effect for project items */' not in content:
        content = content.replace('</head>', style_block)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Updated {filepath}')

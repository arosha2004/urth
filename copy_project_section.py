import os
import re

source_file = 'urth_clone/index.html'

files_to_modify = [
    'amman-rotana-hotel.html',
    'cultural-complex-centre.html',
    'dalbourne-villa.html',
    'european-lard-station.html',
    'poolscape-villa.html',
    'yabroudi-villa.html',
    'urth_clone/the-heritage-pavilion.html',
    'urth_clone/residence-k.html',
    'urth_clone/loft-no-07.html',
    'urth_clone/the-monolith-house.html',
    'urth_clone/index_pretty.html'
]

# Read the source section from urth_clone/index.html
with open(source_file, 'r', encoding='utf-8') as f:
    source_content = f.read()

# Grab everything from <section id="project" class="section"> 
# until the end of that section </section> (which precedes <section id="testimonial")
match = re.search(r'(<section id="project" class="section">.*?</section>)\s*<section id="testimonial"', source_content, flags=re.DOTALL)
if not match:
    print("Could not find the project section.")
    exit(1)

project_section_html = match.group(1)

for filepath in files_to_modify:
    if not os.path.exists(filepath):
        print(f"File not found: {filepath}")
        continue
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # We replace from <section id="project" ... > up to </section> before <section id="testimonial"
    # Wait, some pages might not have testimonial right after, or they do.
    # In index_pretty, it has <section id="testimonial" right after. In individual project pages, let's see.
    # Individual project pages originally had testimonial? Actually yes. Let's just do a clean sub.
    new_content = re.sub(r'<section id="project" class="section">.*?</section>', project_section_html, content, flags=re.DOTALL)
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filepath}")
    else:
        print(f"No changes made to {filepath}")

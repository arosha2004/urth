import re

with open(r"c:\xampp\htdocs\Urth\urth\urth_clone\index.html", "r", encoding="utf-8") as f:
    content = f.read()

# Extract the project section
match = re.search(r'(<section id="project".*?</section>)', content)
if match:
    section_html = match.group(1)
    with open(r"c:\xampp\htdocs\Urth\urth\scratch\project_section.html", "w", encoding="utf-8") as out:
        out.write(section_html)
    print("Extracted successfully.")
else:
    print("Not found.")

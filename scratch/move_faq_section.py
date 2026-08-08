import re

def move_faq_section():
    bak_path = r"c:\xampp\htdocs\Urth - Copy\urth\urth_clone\index.html.bak"
    target_path = r"c:\xampp\htdocs\Urth - Copy\urth\urth_clone\projectsoverall.html"
    about_path = r"c:\xampp\htdocs\Urth - Copy\urth\urth_clone\index.html"

    with open(bak_path, "r", encoding="utf-8") as f:
        bak_content = f.read()

    # Extract the faq section
    # It starts with <section id="faq" class="section"> and goes up to <footer
    match = re.search(r'(<section id="faq" class="section">.*?</section>)(?=<footer)', bak_content, re.DOTALL)
    if not match:
        print("FAQ section not found in index.html.bak")
        return
    
    faq_section = match.group(1)

    # 1. Insert into projectsoverall.html
    with open(target_path, "r", encoding="utf-8") as f:
        target_content = f.read()
    
    if '<section id="faq"' not in target_content:
        # Insert before <footer class="contact-footer">
        new_target = target_content.replace('<footer class="contact-footer">', faq_section + '<footer class="contact-footer">')
        if new_target != target_content:
            with open(target_path, "w", encoding="utf-8") as f:
                f.write(new_target)
            print("Successfully inserted FAQ section into projectsoverall.html")
        else:
            print("Could not find <footer class=\"contact-footer\"> in projectsoverall.html")
    else:
        print("FAQ section already in projectsoverall.html")

    # 2. Remove from about page (index.html)
    with open(about_path, "r", encoding="utf-8") as f:
        about_content = f.read()
    
    if '<section id="faq"' in about_content:
        # Replace the faq section with empty string
        new_about = re.sub(r'<section id="faq" class="section">.*?</section>(?=<footer)', '', about_content, flags=re.DOTALL)
        if new_about != about_content:
            with open(about_path, "w", encoding="utf-8") as f:
                f.write(new_about)
            print("Successfully removed FAQ section from about page (index.html)")
        else:
            print("Could not match the FAQ section for removal in index.html")
    else:
        print("FAQ section already removed from index.html")

if __name__ == "__main__":
    move_faq_section()

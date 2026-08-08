import re

def main():
    bak_path = r"c:\xampp\htdocs\Urth - Copy\urth\urth_clone\index.html.bak"
    target_path = r"c:\xampp\htdocs\Urth - Copy\urth\urth_clone\projectsoverall.html"

    with open(bak_path, "r", encoding="utf-8") as f:
        bak_content = f.read()

    # Extract the service section
    # We look for <section id="service" class="section subtle-grey"> and end right before <section id="why" class="section">
    match = re.search(r'(<section id="service" class="section subtle-grey">.*?</section>)<section id="why" class="section">', bak_content, re.DOTALL)
    if not match:
        print("Could not find the service section in index.html.bak")
        return
    
    service_section = match.group(1)
    print(f"Extracted service section, length: {len(service_section)}")

    with open(target_path, "r", encoding="utf-8") as f:
        target_content = f.read()

    # Insert it into projectsoverall.html right before <footer class="contact-footer">
    if '<footer class="contact-footer">' in target_content:
        new_content = target_content.replace('<footer class="contact-footer">', service_section + '<footer class="contact-footer">')
        
        with open(target_path, "w", encoding="utf-8") as f:
            f.write(new_content)
        print("Successfully updated projectsoverall.html")
    else:
        print("Could not find <footer class='contact-footer'> in projectsoverall.html")

if __name__ == "__main__":
    main()

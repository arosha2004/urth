import re

filepath = 'urth_clone/index.html'
with open(filepath, 'r', encoding='utf-8') as f:
    html = f.read()

# We want to remove the sections:
# <div class="about-image-wrapper"> ... </div>
# <div class="about-wrapper"> ... </div>
# These are the siblings inside <div class="vertical-wrapper"> after <div class="vertical-headline about-headline">

# Find the start of about-image-wrapper
start_idx = html.find('<div class="about-image-wrapper">')

if start_idx != -1:
    # Find the end of about-wrapper
    # Let's just find the text Kimberly Stone and then the closing </div>s
    kimberly_idx = html.find('Kimberly Stone', start_idx)
    if kimberly_idx != -1:
        # Find the next </section> and back up, or just find the closing </div> of about-wrapper
        # Actually it's easier to use a regex to match from start_idx to the end of about-wrapper
        
        # We know about-wrapper ends with </div></div></div> (the last one closes vertical-wrapper or container)
        # Let's just use re to replace the specific chunk we saw earlier:
        # We can extract the chunk using beautifulsoup if we install it, but we can't easily.
        
        # Let's extract the part to remove using string slicing.
        # Find the start of about-wrapper:
        about_wrapper_start = html.find('<div class="about-wrapper">', start_idx)
        
        # The about-wrapper contains:
        # <div fade-in-up-2="" class="about-image-two">...</div>
        # <div class="vertical-headline about-greeting">...</div>
        # </div> (closes about-wrapper)
        
        # Let's find the closing </div> of about-wrapper.
        # It's right after "curate the backdrops to people’s lives.&quot;</div></div></div>"
        
        # Let's just find the next </section> after start_idx
        section_end = html.find('</section>', start_idx)
        
        # We want to keep everything up to start_idx, and then we need to close the tags that were opened before start_idx but closed before </section>
        # Before about-image-wrapper, there is:
        # <section id="about" class="section about-section"><div class="w-layout-blockcontainer container w-container"><div class="vertical-wrapper"><div class="vertical-headline about-headline">...</div>
        # Then about-image-wrapper, then about-wrapper
        # Then </div></div></section>
        
        # So we can just replace everything from <div class="about-image-wrapper"> up to </section> with </div></div></section>
        
        new_html = html[:start_idx] + '</div></div></section>' + html[section_end+10:]
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_html)
        print("Successfully removed the photo section.")
    else:
        print("Could not find Kimberly Stone.")
else:
    print("Could not find about-image-wrapper.")

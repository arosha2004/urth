import re

filepath = 'c:\\xampp\\htdocs\\Urth - Copy\\urth\\urth_clone\\projectsoverall.html'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

# find the cards wrapper
wrapper_match = re.search(r'(<div role="list" class="project-list w-dyn-items">)(.*?)(</div></div></div></div></section><section id="service")', content)

if not wrapper_match:
    print("Could not find the wrapper.")
    exit(1)

prefix = wrapper_match.group(1)
inner_html = wrapper_match.group(2)
suffix = wrapper_match.group(3)

# Extract individual cards
cards = re.findall(r'<div role="listitem" class="project-item w-dyn-item">.*?</a></div></div>', inner_html)

print(f"Found {len(cards)} cards.")

categories = [
    "Architectural Design",
    "Interior Designing",
    "Urban Planning",
    "Landscape Design",
    "Custom Furnitures",
    "Spatial Redesign",
    "Ideas Hub"
]

new_cards = []
for i, cat in enumerate(categories):
    if i < len(cards):
        card = cards[i]
    else:
        card = cards[-1] # duplicate the last card
        
    # Update category-tag
    card = re.sub(r'<div class="category-tag">.*?</div>', f'<div class="category-tag">{cat}</div>', card)
    
    # Update subtitle
    card = re.sub(r'<div class="subtitle">.*?</div>', f'<div class="subtitle">{cat}</div>', card)
    
    # Update title for new cards just to distinguish
    if i >= len(cards):
        card = re.sub(r'<div class="heading-4">.*?</div>', f'<div class="heading-4">{cat} Showcase</div>', card)
        
    new_cards.append(card)

new_inner = "".join(new_cards)

new_content = content[:wrapper_match.start()] + prefix + new_inner + suffix + content[wrapper_match.end():]

with open(filepath, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Successfully updated cards.")

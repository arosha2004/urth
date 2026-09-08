import re

text = open('about.html', 'r', encoding='utf-8', errors='ignore').read()
m1 = re.search(r'href="[^"]*"([^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>Book a Consultation)', text, flags=re.IGNORECASE)
m2 = re.search(r'href="[^"]*"([^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>\s*<div[^>]*>Explore Service)', text, flags=re.IGNORECASE)

print("m1:", m1 is not None)
print("m2:", m2 is not None)

if not m1:
    idx = text.find('Book a Consultation')
    print("Found 'Book a Consultation' at:", idx)
    if idx != -1:
        start = max(0, idx - 150)
        end = min(len(text), idx + 50)
        print("Context around Book a Consultation:\n", repr(text[start:end]))

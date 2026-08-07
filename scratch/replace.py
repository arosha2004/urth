import re

with open('urth_clone/projectsoverall.html', 'r', encoding='utf-8') as f:
    content = f.read()

# Project 1: The Heritage Pavilion -> Poolscape Villa
content = content.replace('https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a23c53cde20611e2fd123b4_PROJECT%20The%20Heritage%20Pavilion.avif', '../img/poolscape_villa.png')
content = content.replace('<div class="heading-4">The Heritage Pavilion</div>', '<div class="heading-4">Poolscape Villa</div>')
content = content.replace('href="the-heritage-pavilion.html"', 'href="poolscape-villa.html"')
content = content.replace('<div class="subtitle">Singapore</div>', '<div class="subtitle">Architecture</div>')

# Project 2: Residence K -> European Lard Station
content = content.replace('https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a23c54d2c253b9e5ee2370e_PROJECT%20Residence%20K.avif', '../img/european_lard_station.png')
content = content.replace('<div class="heading-4">Residence K</div>', '<div class="heading-4">European Lard Station</div>')
content = content.replace('href="residence-k.html"', 'href="european-lard-station.html"')
content = content.replace('<div class="subtitle">Tokyo, Japan</div>', '<div class="subtitle">Architecture</div>')

# Project 3: Loft No. 07 -> Yabroudi Villa
content = content.replace('https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a23c56287aa71f7de6de4d6_PROJECT%20Loft%20No.%2007.avif', '../img/desert_residence.png')
content = content.replace('<div class="heading-4">Loft No. 07</div>', '<div class="heading-4">Yabroudi Villa</div>')
content = content.replace('href="loft-no-07.html"', 'href="yabroudi-villa.html"')
content = content.replace('<div class="subtitle">Melbourne, Australia</div>', '<div class="subtitle">Architecture</div>')

# Project 4: The Monolith House -> Cultural Complex Centre
content = content.replace('https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a23c5798ef0cd3e847e8b58_PROJECT%20The%20Monolith%20House.avif', '../img/american_lard_station.png')
content = content.replace('<div class="heading-4">The Monolith House</div>', '<div class="heading-4">Cultural Complex Centre</div>')
content = content.replace('href="the-monolith-house.html"', 'href="cultural-complex-centre.html"')
content = content.replace('<div class="subtitle">Ubud, Bali</div>', '<div class="subtitle">Architecture</div>')

with open('urth_clone/projectsoverall.html', 'w', encoding='utf-8') as f:
    f.write(content)

print("Done")

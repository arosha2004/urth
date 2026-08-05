import os

html_path = r"c:\xampp\htdocs\Urth\urth\urth_clone\index_pretty.html"

replacements = [
    (
        r"https://prague.foxthemes.me/wp-content/uploads/2017/02/33606f47455433.587b51bef1bd2.jpg",
        r"../img/urthprojs/4plex.png"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a1c/6a2424199401d162eb467553_about%20team.avif",
        r"../img/urthprojs/I.jpg"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a1c/6a2424199ead5e87a019a446_ceo.avif",
        r"../img/urthprojs/I1.jpg"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a24c4a192c518a59f96ec04_Architectural%20Design%20Service.avif",
        r"../img/urthprojs/1.jpg"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a24c4aca18d5d024da935ad_Interior%20Architecture%20Service.avif",
        r"../img/urthprojs/2.jpg"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a24c4bd0786361962bb252c_Custom%20Furniture%20Service.avif",
        r"../img/urthprojs/7.jpg"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a24c53b3546e70bdfab2d91_Spatial%20Redesign%20Service.avif",
        r"../img/urthprojs/8.jpg"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a23c53cde20611e2fd123b4_PROJECT%20The%20Heritage%20Pavilion.avif",
        r"../img/urthprojs/9.jpg"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a23c54d2c253b9e5ee2370e_PROJECT%20Residence%20K.avif",
        r"../img/urthprojs/10.jpg"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a23c56287aa71f7de6de4d6_PROJECT%20Loft%20No.%2007.avif",
        r"../img/urthprojs/img_e4948.webp"
    ),
    (
        r"https://cdn.prod.website-files.com/6a1c3d9d7686060838b27a24/6a23c5798ef0cd3e847e8b58_PROJECT%20The%20Monolith%20House.avif",
        r"../img/urthprojs/I5.jpg"
    )
]

with open(html_path, "r", encoding="utf-8") as f:
    content = f.read()

for old, new in replacements:
    content = content.replace(old, new)

with open(html_path, "w", encoding="utf-8") as f:
    f.write(content)

print("Images replaced in index_pretty.html successfully!")

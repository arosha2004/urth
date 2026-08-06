import os

projects = [
    {
        "file": "poolscape-villa.html",
        "title": "Poolscape Villa",
        "image": "../img/poolscape_villa.png"
    },
    {
        "file": "european-lard-station.html",
        "title": "European Lard Station",
        "image": "../img/european_lard_station.png"
    },
    {
        "file": "yabroudi-villa.html",
        "title": "Yabroudi Villa",
        "image": "../img/desert_residence.png"
    },
    {
        "file": "cultural-complex-centre.html",
        "title": "Cultural Complex Centre",
        "image": "../img/american_lard_station.png"
    },
    {
        "file": "dalbourne-villa.html",
        "title": "Dalbourne Villa",
        "image": "../img/urban_tower.png"
    },
    {
        "file": "amman-rotana-hotel.html",
        "title": "Amman Rotana Hotel",
        "image": "../img/poolscape_villa.png"
    }
]

template = """<header class="hero-section">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600&family=Roboto:wght@300;400;500;700&display=swap');
    .hero-section {
      position: relative;
      width: 100%;
      height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      overflow: hidden;
    }
    .hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
    }
    .hero-bg img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .hero-bg::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.3);
    }
    .hero-top-nav {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      padding: 30px 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 10;
      box-sizing: border-box;
    }
    .hero-top-nav .logo a {
      color: #fff;
      text-decoration: none;
      font-size: 24px;
      font-weight: 500;
      letter-spacing: 4px;
    }
    .hero-top-nav .center-nav {
      display: flex;
      gap: 30px;
    }
    .hero-top-nav .center-nav a {
      color: white !important;
      text-decoration: none;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 2px;
      text-transform: uppercase;
      transition: color 0.3s ease;
      font-family: 'Montserrat', sans-serif;
    }
    .hero-top-nav .center-nav a:hover,
    .hero-top-nav .center-nav a.active {
      color: white !important;
    }
    .hero-content {
      position: relative;
      z-index: 10;
      text-align: center;
      color: white !important;
    }
    .hero-subtitle {
      font-size: 13px;
      font-weight: 500;
      letter-spacing: 3px;
      text-transform: uppercase;
      margin-bottom: 20px;
      opacity: 0.9;
      color: white !important;
      font-family: 'Montserrat', sans-serif;
    }
    .hero-title {
      font-size: 72px;
      font-weight: 700;
      margin: 0;
      letter-spacing: -1px;
      color: white !important;
      font-family: 'Roboto', sans-serif;
    }
    @media (max-width: 768px) {
      .hero-top-nav .center-nav {
        display: none;
      }
      .hero-title {
        font-size: 40px;
      }
    }
  </style>
  <div class="hero-bg">
    <img src="{IMAGE_URL}" alt="Hero background" />
  </div>
  <div class="hero-top-nav">
    <div class="logo">
      <a href="../index.html" style="text-decoration: none;">
        <h2 class="navbar-brand-text" style="color: white; margin: 0; font-size: 28px; font-weight: normal; letter-spacing: -1px; text-transform: lowercase; font-family: 'Montserrat', sans-serif; display: inline-block; vertical-align: middle;">urth.</h2>
      </a>
    </div>
    <nav class="center-nav">
      <a href="../index.html">HOME</a>
      <a href="../urth_clone/about2.html">ABOUT</a>
      <a href="../projects.html" class="active">PROJECTS</a>
      <a href="../contact.html">CONTACT</a>
      <a href="#">SHOP</a>
    </nav>
    <div class="right-nav" style="width: 80px;"></div>
  </div>
  <div class="hero-content">
    <p class="hero-subtitle">URTH. ARCHITECURE</p>
    <h1 class="hero-title">{TITLE}</h1>
  </div>
</header>"""

for p in projects:
    filepath = os.path.join(r"c:\xampp\htdocs\Urth\urth\urth_clone", p["file"])
    if os.path.exists(filepath):
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()
        
        # We need to insert the hero section after <body>.
        html_to_inject = template.replace("{TITLE}", p["title"]).replace("{IMAGE_URL}", p["image"])
        content = content.replace("<body>", "<body>" + html_to_inject)
        
        with open(filepath, "w", encoding="utf-8") as f:
            f.write(content)

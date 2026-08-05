import re

new_nav = '''<style>
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
    color: rgba(255, 255, 255, 0.8);
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
    color: #fff;
  }
  @media (max-width: 768px) {
    .hero-top-nav .center-nav {
      display: none;
    }
  }
</style>
<div class="hero-top-nav">
  <div class="logo">
    <a href="../index.html" style="text-decoration: none;">
      <h2 class="navbar-brand-text" style="color: white; margin: 0; font-size: 28px; font-weight: normal; letter-spacing: -1px; text-transform: lowercase; font-family: 'Montserrat', sans-serif; display: inline-block; vertical-align: middle;">urth.</h2>
    </a>
  </div>
  <nav class="center-nav">
    <a href="../index.html">HOME</a>
    <a href="index.html" class="active">ABOUT</a>
    <a href="../projects.html">PROJECTS</a>
    <a href="../contact.html">CONTACT</a>
    <a href="#">SHOP</a>
  </nav>
  <div class="right-nav" style="width: 80px;"></div>
</div>'''

with open('urth_clone/index.html', 'r', encoding='utf-8') as f:
    content = f.read()

pattern = re.compile(r'<div data-animation="default" class="navbar.*?<div class="sticky-section">', re.DOTALL)
content = pattern.sub(new_nav + '<div class="sticky-section">', content, count=1)

with open('urth_clone/index.html', 'w', encoding='utf-8') as f:
    f.write(content)
print('Done!')

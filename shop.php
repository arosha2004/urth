<?php
$products = [
    [
        'id' => 'cedar-lounge',
        'name' => 'Cedar Lounge',
        'category' => 'Seating',
        'image' => 'img/about_service1.jpg',
        'alt' => 'Warm, sunlit living room with contemporary lounge seating',
    ],
    [
        'id' => 'morrow-chair',
        'name' => 'Morrow Chair',
        'category' => 'Seating',
        'image' => 'img/about_service2.jpg',
        'alt' => 'Calm contemporary living room with a timber coffee table',
    ],
    [
        'id' => 'sola-side-table',
        'name' => 'Sola Side Table',
        'category' => 'Tables',
        'image' => 'img/about_service3.jpg',
        'alt' => 'Minimal living room with a round timber side table',
    ],
    [
        'id' => 'arc-floor-light',
        'name' => 'Arc Floor Light',
        'category' => 'Lighting',
        'image' => 'img/about_featured_villa.jpg',
        'alt' => 'Quiet living space with sculptural floor lighting',
    ],
    [
        'id' => 'form-coffee-table',
        'name' => 'Form Coffee Table',
        'category' => 'Tables',
        'image' => 'img/about_service4.jpg',
        'alt' => 'Minimal interior with a low coffee table and natural timber',
    ],
    [
        'id' => 'stillwater-vase',
        'name' => 'Stillwater Vessel',
        'category' => 'Objects',
        'image' => 'img/about_dark_highlight.jpg',
        'alt' => 'Natural materials and considered details in a modern interior',
    ],
    [
        'id' => 'nara-stool',
        'name' => 'Nara Stool',
        'category' => 'Seating',
        'image' => 'img/about_hero.jpg',
        'alt' => 'Warm, minimal interior with natural finishes',
    ],
    [
        'id' => 'oasis-study',
        'name' => 'Oasis Study Model',
        'category' => 'Objects',
        'image' => 'img/miniature_model.jpg',
        'alt' => 'Architectural model displayed in a bright studio',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Explore considered furniture and objects for thoughtful, everyday living by URTH.">
  <title>Shop — URTH</title>
  <link rel="icon" type="image/png" href="img/favicon-512.png">
  <link rel="stylesheet" href="mobile-nav.css">
  <link rel="stylesheet" href="shop.css">
</head>
<body>
  <header class="shop-hero">
    <div class="shop-hero-image" aria-hidden="true"></div>
    <div class="shop-hero-shade" aria-hidden="true"></div>
    <div class="hero-top-nav shop-nav">
      <a class="shop-wordmark" href="index.php" aria-label="URTH home">URTH<span>.</span></a>
      <nav class="center-nav" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="about.html">About</a>
        <a href="projects.php">Projects</a>
        <a href="contact.html">Contact</a>
        <a href="shop.php" aria-current="page">Shop</a>
      </nav>
      <div class="shop-nav-actions">
        <button class="bag-toggle" type="button" aria-controls="enquiry-panel" aria-expanded="false">
          Enquiry bag <span class="bag-count" aria-live="polite">0</span>
        </button>
        <button class="mobile-nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
    <div class="mobile-nav-overlay" aria-hidden="true">
      <nav class="mobile-nav-links" aria-label="Mobile navigation">
        <a href="index.php">Home</a>
        <a href="about.html">About</a>
        <a href="projects.php">Projects</a>
        <a href="contact.html">Contact</a>
        <a href="shop.php" aria-current="page">Shop</a>
      </nav>
    </div>
    <div class="shop-hero-copy">
      <p class="eyebrow">URTH OBJECTS</p>
      <h1>Considered pieces<br>for everyday living.</h1>
      <p>Quiet forms. Natural materials. Made to belong.</p>
    </div>
  </header>

  <main class="shop-main">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
      <a href="index.php">Home</a><span aria-hidden="true">/</span><span>Shop</span>
    </nav>

    <section class="catalog" aria-labelledby="catalog-title">
      <div class="catalog-heading">
        <div>
          <p class="eyebrow">THE URTH EDIT</p>
          <h2 id="catalog-title">Shop the collection</h2>
          <p class="catalog-intro">Thoughtful furniture and objects, selected for a slower kind of living.</p>
        </div>
      </div>

      <div class="catalog-toolbar">
        <div class="category-filters" role="group" aria-label="Filter products by category">
          <button class="filter-button is-active" type="button" data-filter="All" aria-pressed="true">All pieces</button>
          <button class="filter-button" type="button" data-filter="Seating" aria-pressed="false">Seating</button>
          <button class="filter-button" type="button" data-filter="Tables" aria-pressed="false">Tables</button>
          <button class="filter-button" type="button" data-filter="Lighting" aria-pressed="false">Lighting</button>
          <button class="filter-button" type="button" data-filter="Objects" aria-pressed="false">Objects</button>
        </div>
        <div class="catalog-tools">
          <p class="result-count" aria-live="polite"><span id="visible-count"><?= count($products) ?></span> pieces</p>
          <label class="sort-control">
            <span class="visually-hidden">Sort products</span>
            <select id="product-sort">
              <option value="featured">Featured</option>
              <option value="name-asc">Name: A to Z</option>
              <option value="name-desc">Name: Z to A</option>
            </select>
          </label>
        </div>
      </div>

      <div class="product-grid" id="product-grid">
        <?php foreach ($products as $product): ?>
          <article class="product-card" data-product-id="<?= htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" data-category="<?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="product-image-wrap">
              <img src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['alt'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
              <span class="product-category"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></span>
              <button class="add-to-bag" type="button" aria-label="Add <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> to enquiry bag">Add to enquiry</button>
            </div>
            <div class="product-details">
              <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p>Price on request</p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <p class="empty-results" id="empty-results" hidden>No pieces match this selection.</p>
    </section>
  </main>

  <footer class="shop-footer">
    <a class="shop-wordmark" href="index.php">URTH<span>.</span></a>
    <p>Designed for quiet, considered living.</p>
    <a href="mailto:info@urthspaces.com">Enquire with our studio</a>
    <small>URTH. &copy; 2026 ALL RIGHTS RESERVED</small>
  </footer>

  <div class="panel-backdrop" hidden></div>
  <aside class="enquiry-panel" id="enquiry-panel" aria-labelledby="enquiry-title" aria-hidden="true">
    <div class="panel-heading">
      <div>
        <p class="eyebrow">YOUR SELECTION</p>
        <h2 id="enquiry-title">Enquiry bag <span class="panel-count">0</span></h2>
      </div>
      <button class="panel-close" type="button" aria-label="Close enquiry bag">&times;</button>
    </div>
    <p class="panel-note">Ask us about availability, finishes and pricing for your selected pieces.</p>
    <ul class="bag-items" aria-live="polite"></ul>
    <p class="bag-empty">Your enquiry bag is empty for now.</p>
    <a class="send-enquiry" href="https://wa.me/94768008687" target="_blank" rel="noopener noreferrer">Send enquiry on WhatsApp</a>
  </aside>

  <script src="mobile-nav.js"></script>
  <script src="shop.js"></script>
</body>
</html>

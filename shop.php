<?php
require_once __DIR__ . '/config.php';

// Auto-check table existence and setup if needed
$tblCheck = $conn->query("SHOW TABLES LIKE 'products'");
if (!$tblCheck || $tblCheck->num_rows === 0) {
    require_once __DIR__ . '/setup_shop.php';
}

// Fetch active products from MySQL database
$result = $conn->query("SELECT * FROM products WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
$products = [];
$allProductsData = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $priceFloat = (float)$row['price'];
        $curr = !empty($row['currency']) ? $row['currency'] : '£';
        $salePriceFloat = $row['sale_price'] !== null ? (float)$row['sale_price'] : null;
        $priceOnRequest = (int)($row['price_on_request'] ?? 0) === 1 || ($priceFloat <= 0 && $row['price_on_request'] !== null);
        $availability = !empty($row['availability']) ? strtolower($row['availability']) : 'available';

        $formattedPrice = $priceOnRequest ? 'Price on request' : ($curr . number_format($priceFloat, 2));
        $formattedSalePrice = ($priceOnRequest || $salePriceFloat === null) ? null : ($curr . number_format($salePriceFloat, 2));

        $p = [
            'id' => (string)$row['id'],
            'slug' => $row['slug'] ?? ('product-' . $row['id']),
            'name' => $row['name'],
            'sku' => $row['sku'] ?? ('PR' . str_pad($row['id'], 3, '0', STR_PAD_LEFT)),
            'price' => $priceFloat,
            'sale_price' => $salePriceFloat,
            'formattedPrice' => $formattedPrice,
            'formattedSalePrice' => $formattedSalePrice,
            'currency' => $curr,
            'category' => $row['category'] ?? 'Furniture & Objects',
            'description' => $row['description'] ?? '',
            'image' => $row['image'],
            'popularity' => (int)$row['popularity'],
            'display_order' => (int)$row['display_order'],
            'is_featured' => (int)($row['is_featured'] ?? 0),
            'availability' => $availability,
            'product_label' => $row['product_label'] ?? '',
            'price_on_request' => $priceOnRequest ? 1 : 0,
            'product_type' => $row['product_type'] ?? 'furniture_object',
            'book_format' => $row['book_format'] ?? '',
            'date' => substr($row['created_at'] ?? date('Y-m-d'), 0, 10),
        ];
        $products[] = $p;
        $allProductsData[] = $p;
    }
}
$totalProducts = count($products);
$initialCount = min(8, $totalProducts);

// Fetch categories dynamically
$catResult = $conn->query("
    SELECT c.name, c.slug, c.description, c.display_order,
           COUNT(p.id) AS product_count
    FROM product_categories c
    LEFT JOIN products p ON (p.category = c.name AND p.is_active = 1)
    GROUP BY c.id, c.name, c.slug, c.description, c.display_order
    ORDER BY c.display_order ASC, c.name ASC
");
$categoriesList = [];
if ($catResult) {
    while ($c = $catResult->fetch_assoc()) {
        $categoriesList[] = [
            'name' => $c['name'],
            'slug' => $c['slug'],
            'description' => $c['description'],
            'product_count' => (int)$c['product_count']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="An Amazing Collection Modern Furniture. Explore the curated architecture, furniture, and lighting collection at URTH.">
  <title>Shop — URTH</title>
  <link rel="icon" type="image/png" href="img/favicon-512.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="mobile-nav.css">
  <link rel="stylesheet" href="shop.css?v=<?= time() ?>">
  <link rel="stylesheet" href="shop-info.css">
</head>
<body>

  <!-- ==========================================================================
       HERO TOP NAV (EXACT NAVBAR MATCHING ALL URTH PAGES)
       ========================================================================== -->
  <div class="hero-top-nav">
    <div class="logo">
      <a href="index.php" style="text-decoration: none;">
        <img src="img/URTH (1).png" alt="Urth Logo" class="navbar-brand-image" style="height: 22px; margin: 0; padding: 0; object-fit: contain; vertical-align: middle; transform: translateY(-2px);">
      </a>
    </div>

    <nav class="center-nav">
      <a href="index.php">HOME</a>
      <a href="about.html">ABOUT</a>
      <a href="projects.php">PROJECTS</a>
      <a href="contact.html">CONTACT</a>
      <a href="shop.php" class="active">SHOP</a>
    </nav>

    <div class="right-nav">
      <button type="button" class="cart-trigger-btn" id="cartTrigger" aria-label="View Shopping Cart">
        <svg class="cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <path d="M16 10a4 4 0 0 1-8 0"></path>
        </svg>
        <span class="cart-badge" id="cartBadge">0</span>
      </button>

      <button class="mobile-nav-toggle" aria-label="Toggle Menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </div>

  <div class="mobile-nav-overlay">
    <nav class="mobile-nav-links">
      <a href="index.php">HOME</a>
      <a href="about.html">ABOUT</a>
      <a href="projects.php">PROJECTS</a>
      <a href="contact.html">CONTACT</a>
      <a href="shop.php" class="active">SHOP</a>
    </nav>
  </div>

  <a class="shop-skip-link" href="#catalogHeading">Skip to catalogue</a>

  <!-- ==========================================================================
       HERO BANNER
       ========================================================================== -->
  <section class="top-banner big fullheight light" id="topBanner">
    <span class="overlay"></span>
    <img src="img/shop/shop-hero-gallery-dark.jpg" alt="URTH Studio Shop" class="s-img-switch banner-bg" />
    <div class="content">
      <span class="hero-eyebrow">URTH <span aria-hidden="true">/</span> ARCHITECTURE STUDIO</span>
      <h1 class="title">The studio shop.</h1>
      <p class="description">Furniture and objects, tools for design, and time with the studio.</p>
      <a class="hero-cta" href="#catalogHeading">Explore the collection <span aria-hidden="true">&darr;</span></a>
    </div>
    <span class="hero-note">FIG. 01 &nbsp; STUDIO GALLERY</span>
  </section>

  
  <main class="shop-container" id="shopMain">
    <!-- Main Catalog Section -->
    <header class="woocommerce-products-header" id="catalogHeading">
      <div>
        <span class="catalog-eyebrow">01 &nbsp;/&nbsp; CATALOGUE</span>
        <h2 class="woocommerce-products-header__title page-title">The collection</h2>
      </div>
      <a class="catalog-guide-link" href="#orderingProcess">How to order <span aria-hidden="true">&darr;</span></a>
    </header>

    <!-- Product Search and Toolbar -->
    <div class="shop-toolbar-wrapper">
      <div class="shop-search-bar">
        <svg class="shop-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" id="shopSearchInput" placeholder="Find a piece or SKU" aria-label="Find a piece or SKU">
        <button type="button" class="shop-search-clear" id="shopSearchClear" aria-label="Clear search">&times;</button>
      </div>

      <!-- Dynamic Category Filter Bar -->
      <nav class="shop-category-bar" aria-label="Shop categories" id="categoryNav">
        <button type="button" class="category-pill-btn active" data-category="all">
          <span class="cat-name">All pieces</span>
          <span class="cat-badge" id="catCountAll"><?= $totalProducts ?></span>
        </button>
        <?php foreach ($categoriesList as $catItem): ?>
          <button type="button" class="category-pill-btn" data-category="<?= htmlspecialchars($catItem['slug']) ?>" data-name="<?= htmlspecialchars($catItem['name']) ?>" title="<?= htmlspecialchars($catItem['description'] ?? '') ?>">
            <span class="cat-name"><?= htmlspecialchars($catItem['name']) ?></span>
            <span class="cat-badge"><?= $catItem['product_count'] ?></span>
          </button>
        <?php endforeach; ?>
      </nav>

      <div class="shop-controls-bar">
        <div class="shop-search-tools">
          <p class="woocommerce-result-count" id="resultCount" aria-live="polite">
            <?= $totalProducts > 0 ? "Showing 1–{$initialCount} of {$totalProducts} results" : "No products available" ?>
          </p>
          <div class="select-wrap">
            <select name="orderby" class="orderby" id="orderBySelect" aria-label="Shop order">
              <option value="menu_order" selected="selected">Studio order</option>
              <option value="price-asc">Price · low to high</option>
              <option value="price-desc">Price · high to low</option>
              <option value="name">Name · A–Z</option>
            </select>
            <span class="select-arrow" aria-hidden="true">&#9662;</span>
          </div>
          <details class="advanced-filters">
            <summary>More filters</summary>
            <div class="advanced-filters-panel">
              <label>
                Availability
                <select id="availabilityFilter" class="shop-select-filter">
                  <option value="all">All availability</option>
                  <option value="available">Available now</option>
                  <option value="made_to_order">Made to order</option>
                  <option value="coming_soon">Coming soon</option>
                  <option value="unavailable">Unavailable</option>
                </select>
              </label>
              <label>
                Format
                <select id="labelFilter" class="shop-select-filter">
                  <option value="all">All formats</option>
                  <option value="Digital">Digital</option>
                  <option value="Printed">Printed</option>
                  <option value="Made to order">Made to order</option>
                  <option value="Online course">Online course</option>
                  <option value="Consultation">Consultation</option>
                </select>
              </label>
            </div>
          </details>
        </div>
      </div>
    </div>

    <!-- Added to Cart Notice -->
    <div class="woocommerce-notice" id="cartNotice" role="status" aria-live="polite" style="display: none;" hidden>
      <div class="notice-content">
        <span class="notice-icon">&#10003;</span>
        <span class="notice-message" id="noticeMessage">Product has been added to your cart.</span>
      </div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <a href="cart.php" class="notice-cart-btn" id="noticeCartBtn">View Cart</a>
        <button type="button" id="noticeCloseBtn" aria-label="Close notification" style="background: transparent; border: none; font-size: 18px; line-height: 1; cursor: pointer; color: #888; padding: 4px 6px;">&times;</button>
      </div>
    </div>

    <!-- Products Grid (4 columns) -->
    <ul class="products columns-4" id="productsGrid">
      <?php if (!empty($products)): ?>
        <?php foreach (array_slice($products, 0, 8) as $p): ?>
          <?php
            $avail = strtolower($p['availability'] ?? 'available');
            $availClass = 'avail-' . str_replace('_', '-', $avail);
            $availText = 'Available';
            if ($avail === 'made_to_order') $availText = 'Made to Order';
            elseif ($avail === 'coming_soon') $availText = 'Coming Soon';
            elseif ($avail === 'unavailable') $availText = 'Unavailable';
          ?>
          <li class="product-item" data-id="<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="product-image-box">
              <a href="product.php?id=<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8') ?>" class="product-thumb-link">
                <img src="<?= htmlspecialchars($p['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" width="300" height="300">
              </a>
              <span class="product-ref"><?= htmlspecialchars($p['sku'], ENT_QUOTES, 'UTF-8') ?></span>
              <button type="button" class="product-quick-view" onclick="window.openProductModal('<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8') ?>')">Quick view</button>
              <?php if ($avail === 'coming_soon'): ?>
                <button type="button" class="button btn-coming-soon product-add" disabled aria-disabled="true" title="Coming soon — payment not accepted for upcoming items">
                  +
                </button>
              <?php elseif ($avail === 'unavailable'): ?>
                <button type="button" class="button btn-unavailable product-add" disabled aria-disabled="true">
                  +
                </button>
              <?php elseif (!empty($p['price_on_request'])): ?>
                <button type="button" class="button btn-inquire product-add" onclick="window.inquireProduct('<?= htmlspecialchars($p['id']) ?>')">
                  +
                </button>
              <?php else: ?>
                <button type="button" class="button add_to_cart_button product-add" data-id="<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Add &ldquo;<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>&rdquo; to your bag">
                  +
                </button>
              <?php endif; ?>
            </div>
            <div class="product-card-caption">
              <span class="product-cat-tag"><?= htmlspecialchars($p['category'] ?? 'Furniture & Objects', ENT_QUOTES, 'UTF-8') ?></span>
              <a href="product.php?id=<?= htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8') ?>">
                <h3 class="woocommerce-loop-product__title"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></h3>
              </a>
              <?php if (!empty($p['price_on_request'])): ?>
                <span class="price-on-request">Price on request</span>
              <?php elseif (!empty($p['sale_price'])): ?>
                <span class="price">
                  <del style="opacity: 0.5; margin-right: 5px;"><?= htmlspecialchars($p['formattedPrice'], ENT_QUOTES, 'UTF-8') ?></del>
                  <ins style="text-decoration: none; font-weight: 700;"><?= htmlspecialchars($p['formattedSalePrice'], ENT_QUOTES, 'UTF-8') ?></ins>
                </span>
              <?php else: ?>
                <span class="price"><?= htmlspecialchars($p['formattedPrice'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #888;">
          No products currently available in the shop.
        </li>
      <?php endif; ?>
    </ul>

    <!-- Pagination -->
    <nav class="woocommerce-pagination" aria-label="Product Pagination" id="paginationNav" <?= $totalProducts <= 8 ? 'style="display:none;"' : '' ?>>
      <ul class="page-numbers" id="paginationList">
        <li><span class="page-numbers current" aria-current="page">1</span></li>
        <?php if ($totalProducts > 8): ?>
          <li><button type="button" class="page-numbers" data-page="2">2</button></li>
          <li><button type="button" class="page-numbers next" data-page="next" aria-label="Next page">&rarr;</button></li>
        <?php endif; ?>
      </ul>
    </nav>
    <p class="shop-information-link"><a href="shop-info.php">How to order · Payment and delivery · Returns · Digital licence · Privacy · FAQs &amp; contact</a></p>

    <section class="ordering-process-section" id="orderingProcess" aria-labelledby="orderGuideTitle">
      <div class="guide-heading">
        <span class="catalog-eyebrow">02 &nbsp;/&nbsp; HOW TO ORDER</span>
        <h2 class="section-title" id="orderGuideTitle">Order with the studio.</h2>
        <p>Ask about a finish, a size or delivery to your door. URTH confirms the details and price before you pay.</p>
      </div>
      <div class="order-guide-content">
        <div class="order-steps">
          <article class="order-step"><span>01</span><h3>Choose your pieces</h3></article>
          <article class="order-step"><span>02</span><h3>Ask on WhatsApp</h3></article>
          <article class="order-step"><span>03</span><h3>Confirm and pay</h3></article>
          <article class="order-step"><span>04</span><h3>Receive your order</h3></article>
          <a class="guide-browse" href="#catalogHeading">Browse the collection <span aria-hidden="true">&uarr;</span></a>
        </div>
        <aside class="guide-notes" aria-label="Order details">
          <article class="guide-note guide-example">
            <span class="catalog-eyebrow">01 / EXAMPLE BAG</span>
            <?php if (!empty($products)): ?>
              <p class="guide-example-sku"><?= htmlspecialchars($products[0]['sku'], ENT_QUOTES, 'UTF-8') ?> &middot; QTY 1</p>
              <h3><?= htmlspecialchars($products[0]['name'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="guide-example-price"><?= htmlspecialchars($products[0]['formattedSalePrice'] ?: $products[0]['formattedPrice'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
              <h3>Choose what you love.</h3>
            <?php endif; ?>
            <p>Open Quick view for a closer look, then add the pieces you like to your bag.</p>
          </article>
          <article class="guide-note">
            <span class="catalog-eyebrow">02 / EXAMPLE ENQUIRY</span>
            <h3>A question for the studio.</h3>
            <p>Ask about a finish, size or delivery. URTH will confirm availability, details and the final total.</p>
          </article>
          <article class="guide-note">
            <span class="catalog-eyebrow">03 / BEFORE PAYMENT</span>
            <h3>Know what you’re agreeing to.</h3>
            <p>Piece, quantity and finish. Final price and delivery cost. Payment instructions from URTH.</p>
          </article>
          <article class="guide-note">
            <span class="catalog-eyebrow">04 / AFTER PAYMENT VERIFICATION</span>
            <h3>How it reaches you.</h3>
            <p>Digital files arrive by email. Furniture and objects are delivered as agreed with the studio.</p>
          </article>
        </aside>
      </div>
    </section>
  </main>

  <!-- ==========================================================================
       PRODUCT DETAILS QUICK VIEW MODAL
       ========================================================================== -->
  <div class="product-modal-backdrop" id="productModalBackdrop" hidden>
    <div class="product-modal-dialog" id="productModalDialog" role="dialog" aria-modal="true" aria-labelledby="modalProductTitle">
      <button type="button" class="product-modal-close" id="productModalClose" aria-label="Close product view">&times;</button>
      
      <div class="product-modal-left">
        <img id="modalProductImage" src="img/shop/prod-1.jpg" alt="Product Image">
      </div>

      <div class="product-modal-right">
        <div style="margin-bottom: 8px;">
          <span class="product-modal-cat" id="modalProductCategory">Accessories</span>
        </div>
        <h2 class="product-modal-title" id="modalProductTitle">Product Title</h2>
        <div class="product-modal-price-wrap">
          <span class="product-modal-price" id="modalProductPrice">£0.00</span>
          <span class="product-modal-sku" id="modalProductSku">SKU: PR000</span>
        </div>
        
        <div class="modal-avail-warning" id="modalAvailWarning" role="alert"></div>

        <p class="product-modal-desc" id="modalProductDesc">Curated modern architecture and design pieces handcrafted with premium materials.</p>
        
        <div class="product-modal-actions" id="modalActionsWrap">
          <div class="product-modal-qty" id="modalQtyWrap">
            <button type="button" id="modalQtyMinus" aria-label="Decrease quantity">&minus;</button>
            <input type="number" id="modalQtyInput" value="1" min="1" max="99" aria-label="Quantity">
            <button type="button" id="modalQtyPlus" aria-label="Increase quantity">&plus;</button>
          </div>
          <button type="button" class="product-modal-add-btn" id="modalAddToCartBtn">ADD TO CART</button>
        </div>

        <a href="https://wa.me/94768008687" target="_blank" rel="noopener noreferrer" class="product-modal-whatsapp-btn" id="modalWhatsAppBtn">
          <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Inquire via WhatsApp
        </a>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       FOOTER (EXACT FOOTER USED IN OTHER PAGES)
       ========================================================================== -->
  <footer class="contact-footer" style="position: relative; z-index: 10;">
    <div class="footer-overlay"></div>
    <img src="img/shop/footer-bg.jpg" class="footer-bg" alt="Footer Background" />
    
    <div class="footer-inner">
      <div class="footer-top">
        <div class="footer-left">
          <div class="footer-logo">
            <img src="img/URTH (1).png" alt="Urth Logo" class="footer-brand-image" style="width: auto; height: 32px; margin: 0; padding: 0; object-fit: contain; object-position: left; filter: brightness(0) invert(1);">
          </div>
          <div class="footer-desc">
            <p style="color: rgba(255,255,255,0.7);">The company principle of Architecture-Studio is the collective conception. From the very beginning, the practice has believed in the virtues of exchange, crossing ideas, common effort, shared knowledge and enthusiasm.</p>
          </div>
        </div>

        <div class="footer-right">
          <h6 class="footer-info-title">GET IN TOUCH</h6>
          <div class="footer-info-content">
            <p><a href="https://wa.me/94768008687" target="_blank" style="color: white;">+94768008687</a></p>
            <p><a href="mailto:info@urthspaces.com" style="color: white;">info@urthspaces.com</a></p>
            <p style="color: rgba(255,255,255,0.7);">No.374, Peradeniya Road, Kandy 20000, Sri Lanka</p>
          </div>
        </div>
      </div>

      <div class="footer-divider"></div>

      <div class="footer-bottom">
        <div class="footer-copyright">
          <p style="color: rgba(255,255,255,0.7);">URTH. &copy; 2026 ALL RIGHTS RESERVED &nbsp;&bull;&nbsp; <span style="white-space: nowrap;">MADE BY <a href="https://engagelanka.com" target="_blank" rel="noopener noreferrer" style="color: white; text-decoration: none; font-weight: 300;">ENGAGELANKA</a></span></p>
        </div>
        <div class="footer-socials">
          <a href="https://www.instagram.com/urth.spaces/" target="_blank" class="footer-social__link" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
          <a href="https://wa.me/94768008687" target="_blank" class="footer-social__link" aria-label="WhatsApp"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>
          <a href="https://www.facebook.com/people/Urth/61586496127091/" target="_blank" class="footer-social__link" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
          <a href="https://www.linkedin.com/company/studio-urth/" target="_blank" class="footer-social__link" aria-label="LinkedIn"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
        </div>
      </div>
    </div>
  </footer>

  <!-- ==========================================================================
       CART DRAWER & BACKDROP
       ========================================================================== -->
  <div class="cart-backdrop" id="cartBackdrop" hidden></div>
  <aside class="cart-drawer" id="cartDrawer" aria-labelledby="cartDrawerTitle" aria-hidden="true">
    <div class="cart-drawer-header">
      <h2 id="cartDrawerTitle">SHOPPING CART <span class="cart-drawer-count">(0)</span></h2>
      <button type="button" class="cart-close-btn" id="cartCloseBtn" aria-label="Close cart">&times;</button>
    </div>
    <div class="cart-drawer-body">
      <ul class="cart-items-list" id="cartItemsList"></ul>
      <div class="cart-empty-state" id="cartEmptyState">
        <p>Your shopping cart is currently empty.</p>
        <a href="#productsGrid" class="cart-browse-btn" id="cartBrowseBtn">Browse Products</a>
      </div>
    </div>
    <div class="cart-drawer-footer" id="cartDrawerFooter" hidden>
      <div class="cart-subtotal">
        <span>Subtotal:</span>
        <span class="subtotal-amount" id="cartSubtotalAmount">£0.00</span>
      </div>
      <div class="cart-drawer-actions">
        <a href="cart.php" class="cart-view-full-btn">
          View Full Cart
        </a>
        <a href="cart.php#checkout" class="cart-checkout-btn">
          Proceed to Checkout
        </a>
        <a href="https://wa.me/94768008687" target="_blank" rel="noopener noreferrer" class="cart-whatsapp-order-btn" id="cartCheckoutBtn">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true" style="width: 16px; height: 16px; min-width: 16px; min-height: 16px; max-width: 16px; max-height: 16px; flex-shrink: 0; fill: currentColor;"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Enquire via WhatsApp
        </a>
      </div>
    </div>
  </aside>

  <!-- Pass dynamic products & categories from MySQL to Frontend -->
  <script>
    window.INITIAL_PRODUCTS = <?= json_encode($allProductsData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    window.INITIAL_CATEGORIES = <?= json_encode($categoriesList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="mobile-nav.js"></script>
  <script src="shop.js?v=<?= time() ?>"></script>
</body>
</html>

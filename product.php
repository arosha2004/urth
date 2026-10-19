<?php
/**
 * URTH Architecture Studio — Individual Shareable Product Page
 */
require_once __DIR__ . '/config.php';

// Auto-check table existence
$tblCheck = $conn->query("SHOW TABLES LIKE 'products'");
if (!$tblCheck || $tblCheck->num_rows === 0) {
    require_once __DIR__ . '/setup_shop.php';
}

$idParam = $_GET['id'] ?? '';
$slugParam = $_GET['slug'] ?? '';

$product = null;

if (!empty($idParam)) {
    if (is_numeric($idParam)) {
        $pId = (int)$idParam;
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $pId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM products WHERE slug = ? LIMIT 1");
        $stmt->bind_param("s", $idParam);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
    }
    $stmt->close();
} elseif (!empty($slugParam)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slugParam);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $product = $res->fetch_assoc();
    }
    $stmt->close();
}

// Fallback if not found: redirect to shop or pick first active product
if (!$product) {
    header('Location: shop.php');
    exit;
}

// Price calculations
$priceFloat = (float)$product['price'];
$curr = !empty($product['currency']) ? $product['currency'] : '£';
$salePriceFloat = $product['sale_price'] !== null ? (float)$product['sale_price'] : null;
$priceOnRequest = (int)($product['price_on_request'] ?? 0) === 1 || ($priceFloat <= 0 && $product['price_on_request'] !== null);
$availability = !empty($product['availability']) ? strtolower($product['availability']) : 'available';

$formattedPrice = $priceOnRequest ? 'Price on request' : ($curr . number_format($priceFloat, 2));
$formattedSalePrice = ($priceOnRequest || $salePriceFloat === null) ? null : ($curr . number_format($salePriceFloat, 2));

// Calculate discount percentage
$discountPct = 0;
if ($salePriceFloat && $priceFloat > $salePriceFloat && !$priceOnRequest) {
    $discountPct = round((($priceFloat - $salePriceFloat) / $priceFloat) * 100);
}

// Gallery images
$gallery = [];
if (!empty($product['gallery_images'])) {
    $decoded = json_decode($product['gallery_images'], true);
    if (is_array($decoded)) {
        $gallery = $decoded;
    }
}
if (empty($gallery) && !empty($product['image'])) {
    $gallery = [$product['image']];
}
// Ensure main cover image is at start
if (!empty($product['image']) && !in_array($product['image'], $gallery, true)) {
    array_unshift($gallery, $product['image']);
}

// Available options
$optionsLabel = !empty($product['options_label']) ? $product['options_label'] : 'Option';
$optionsList = [];
if (!empty($product['options_list'])) {
    $rawOpts = explode(',', $product['options_list']);
    foreach ($rawOpts as $o) {
        $trimmed = trim($o);
        if (!empty($trimmed)) {
            $optionsList[] = $trimmed;
        }
    }
}

// What is included
$includedItems = [];
if (!empty($product['what_included'])) {
    $lines = explode("\n", $product['what_included']);
    foreach ($lines as $ln) {
        $clean = trim($ln, " \t\n\r\0\x0B•-*");
        if (!empty($clean)) {
            $includedItems[] = $clean;
        }
    }
}

// Delivery & preparation time
$deliveryTime = !empty($product['delivery_time']) ? $product['delivery_time'] : 'Dispatched within 3–5 business days';
$deliveryInfo = !empty($product['delivery_info']) ? $product['delivery_info'] : 'White-glove UK courier delivery included. Packaged in reinforced, recyclable wooden crate with corner dampening foam. Worldwide freight delivery arranged with trackable sea or air dispatch.';
$cancellationRefund = !empty($product['cancellation_refund']) ? $product['cancellation_refund'] : 'Free cancellation within 24 hours of placing order. 14-day hassle-free return policy for standard models in original packaging.';

// Product Type
$pType = !empty($product['product_type']) ? $product['product_type'] : 'furniture_object';

// Related Products (same category or top active)
$relatedStmt = $conn->prepare("SELECT * FROM products WHERE is_active = 1 AND id != ? AND (category = ? OR 1=1) ORDER BY (category = ?) DESC, popularity DESC, display_order ASC LIMIT 4");
$catName = $product['category'] ?? 'Furniture & Objects';
$prodIdInt = (int)$product['id'];
$relatedStmt->bind_param("iss", $prodIdInt, $catName, $catName);
$relatedStmt->execute();
$relRes = $relatedStmt->get_result();
$relatedProducts = [];
if ($relRes) {
    while ($rRow = $relRes->fetch_assoc()) {
        $relatedProducts[] = $rRow;
    }
}
$relatedStmt->close();

// Page URL for sharing
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $isHttps ? "https://" : "http://";
$currentUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
$cleanShareUrl = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['PHP_SELF'] ?? ''), '/\\') . '/product.php?id=' . $product['id'];

// SEO & Share metadata
$pageTitle = htmlspecialchars($product['name']) . " — URTH Shop";
$metaDesc = !empty($product['description']) ? htmlspecialchars(substr(strip_tags($product['description']), 0, 160)) . '...' : 'Explore handcrafted architecture, objects, and design tools from URTH.';
$shareImage = !empty($product['image']) ? (preg_match('/^https?:\/\//i', $product['image']) ? $product['image'] : ($protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/' . ltrim($product['image'], '/'))) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?></title>
  <meta name="description" content="<?= $metaDesc ?>">
  
  <!-- Canonical & Social Sharing Meta Tags -->
  <link rel="canonical" href="<?= htmlspecialchars($cleanShareUrl) ?>">
  <meta property="og:type" content="product">
  <meta property="og:title" content="<?= htmlspecialchars($product['name']) ?> — URTH">
  <meta property="og:description" content="<?= $metaDesc ?>">
  <meta property="og:url" content="<?= htmlspecialchars($cleanShareUrl) ?>">
  <?php if (!empty($shareImage)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($shareImage) ?>">
  <?php endif; ?>
  <?php if (!$priceOnRequest): ?>
    <meta property="product:price:amount" content="<?= number_format($salePriceFloat ?: $priceFloat, 2, '.', '') ?>">
    <meta property="product:price:currency" content="<?= $curr === '£' ? 'GBP' : ($curr === '$' ? 'USD' : 'EUR') ?>">
  <?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($product['name']) ?> — URTH">
  <meta name="twitter:description" content="<?= $metaDesc ?>">
  <?php if (!empty($shareImage)): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($shareImage) ?>">
  <?php endif; ?>

  <!-- JSON-LD Structured Data for Product -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": <?= json_encode($product['name']) ?>,
    "image": <?= json_encode($shareImage) ?>,
    "description": <?= json_encode(strip_tags($product['description'] ?? '')) ?>,
    "sku": <?= json_encode($product['sku'] ?? ('PR' . $product['id'])) ?>,
    "brand": {
      "@type": "Brand",
      "name": "URTH Studio"
    },
    "offers": {
      "@type": "Offer",
      "url": <?= json_encode($cleanShareUrl) ?>,
      "priceCurrency": <?= json_encode($curr === '£' ? 'GBP' : ($curr === '$' ? 'USD' : 'EUR')) ?>,
      "price": <?= json_encode(number_format($salePriceFloat ?: $priceFloat, 2, '.', '')) ?>,
      "availability": <?= json_encode($availability === 'available' ? 'https://schema.org/InStock' : ($availability === 'made_to_order' ? 'https://schema.org/PreOrder' : 'https://schema.org/OutOfStock')) ?>
    }
  }
  </script>

  <link rel="icon" type="image/png" href="img/favicon-512.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="mobile-nav.css">
  <link rel="stylesheet" href="shop.css?v=<?= time() ?>">
  <link rel="stylesheet" href="product.css?v=<?= time() ?>">
</head>
<body class="product-page-body">

  <!-- ==========================================================================
       HERO TOP NAV (EXACT URTH NAVBAR MATCHING OTHER PAGES)
       ========================================================================== -->
  <div class="hero-top-nav" style="background-color: #111111; position: fixed;">
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

  <!-- ==========================================================================
       MAIN PRODUCT DETAIL VIEW
       ========================================================================== -->
  <main class="product-detail-page">
    <div class="product-detail-container">
      
      <!-- Breadcrumbs Navigation -->
      <nav class="product-breadcrumbs" aria-label="Breadcrumb navigation">
        <a href="index.php">Home</a>
        <span class="sep">/</span>
        <a href="shop.php">Shop Catalog</a>
        <span class="sep">/</span>
        <a href="shop.php?category=<?= urlencode(strtolower(str_replace(' ', '-', $product['category'] ?? ''))) ?>"><?= htmlspecialchars($product['category'] ?? 'Furniture & Objects') ?></a>
        <span class="sep">/</span>
        <span class="current"><?= htmlspecialchars($product['name']) ?></span>
      </nav>

      <!-- Top Notification Bar (Added to cart feedback) -->
      <div class="woocommerce-notice" id="cartNotice" role="status" aria-live="polite" style="display: none; margin-bottom: 25px;">
        <div class="notice-content">
          <span class="notice-icon">&#10003;</span>
          <span class="notice-message" id="noticeMessage">&ldquo;<?= htmlspecialchars($product['name']) ?>&rdquo; has been added to your cart.</span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
          <a href="cart.php" class="notice-cart-btn" id="noticeCartBtn">View Cart</a>
          <button type="button" id="noticeCloseBtn" aria-label="Close notification" style="background: transparent; border: none; font-size: 18px; line-height: 1; cursor: pointer; color: #888; padding: 4px 6px;">&times;</button>
        </div>
      </div>

      <!-- Hero Two-Column Grid -->
      <div class="product-hero-grid">
        
        <!-- Column 1: Image Gallery -->
        <div class="product-gallery-wrap">
          <div class="product-main-image-box" id="mainImageBox">
            <div class="product-gallery-badge-row">
              <?php
                $availBadgeClass = 'badge-avail-' . str_replace('-', '_', $availability);
                $availBadgeText = 'Available';
                if ($availability === 'made_to_order') $availBadgeText = 'Made to Order';
                elseif ($availability === 'coming_soon') $availBadgeText = 'Coming Soon';
                elseif ($availability === 'unavailable') $availBadgeText = 'Sold Out';
              ?>
              <span class="gallery-badge <?= $availBadgeClass ?>"><?= $availBadgeText ?></span>
              <?php if (!empty($product['product_label'])): ?>
                <span class="gallery-badge badge-format"><?= htmlspecialchars($product['product_label']) ?></span>
              <?php endif; ?>
            </div>

            <img id="mainProductImg"
                 src="<?= htmlspecialchars($gallery[0] ?? $product['image']) ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>"
                 loading="eager">
          </div>

          <!-- Thumbnail Navigation Strip -->
          <?php if (count($gallery) > 1): ?>
            <div class="product-thumbnails-strip" aria-label="Product Image Gallery Thumbnails">
              <?php foreach ($gallery as $idx => $imgUrl): ?>
                <button type="button"
                        class="gallery-thumb-btn <?= $idx === 0 ? 'active' : '' ?>"
                        data-img="<?= htmlspecialchars($imgUrl) ?>"
                        aria-label="View photo <?= $idx + 1 ?>">
                  <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($product['name']) ?> thumbnail <?= $idx + 1 ?>" loading="lazy">
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Column 2: Product Info & Purchase Actions -->
        <div class="product-info-wrap">
          <div class="product-meta-top">
            <span class="product-category-eyebrow"><?= htmlspecialchars($product['category'] ?? 'Furniture & Objects') ?></span>
            <span class="product-sku-tag">SKU: <?= htmlspecialchars($product['sku'] ?? ('PR' . $product['id'])) ?></span>
          </div>

          <h1 class="product-title-heading"><?= htmlspecialchars($product['name']) ?></h1>

          <!-- Price Display -->
          <div class="product-price-container">
            <?php if ($priceOnRequest): ?>
              <span class="product-price-on-request">Price on Request</span>
            <?php elseif ($salePriceFloat): ?>
              <div class="product-current-price">
                <span class="currency-symbol"><?= htmlspecialchars($curr) ?></span><?= number_format($salePriceFloat, 2) ?>
                <span class="currency-iso"><?= $curr === '£' ? 'GBP' : ($curr === '$' ? 'USD' : 'EUR') ?></span>
              </div>
              <span class="product-del-price"><?= htmlspecialchars($curr . number_format($priceFloat, 2)) ?></span>
              <?php if ($discountPct > 0): ?>
                <span class="product-discount-pill">Save <?= $discountPct ?>%</span>
              <?php endif; ?>
            <?php else: ?>
              <div class="product-current-price">
                <span class="currency-symbol"><?= htmlspecialchars($curr) ?></span><?= number_format($priceFloat, 2) ?>
                <span class="currency-iso"><?= $curr === '£' ? 'GBP' : ($curr === '$' ? 'USD' : 'EUR') ?></span>
              </div>
            <?php endif; ?>
          </div>

          <!-- Expected Delivery / Preparation Time -->
          <div class="product-leadtime-banner">
            <svg class="leadtime-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <div class="leadtime-text">
              <strong>Expected Delivery / Lead Time:</strong> <?= htmlspecialchars($deliveryTime) ?>
            </div>
          </div>

          <!-- Description Summary -->
          <?php if (!empty($product['description'])): ?>
            <div class="product-desc-lead">
              <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
          <?php endif; ?>

          <!-- Available Options (Size, Finish, Format, Licence, etc.) -->
          <?php if (!empty($optionsList)): ?>
            <div class="product-options-section">
              <div class="options-label-heading">
                <span>Select <?= htmlspecialchars($optionsLabel) ?>:</span>
                <span class="options-label-selected" id="selectedOptionText"><?= htmlspecialchars($optionsList[0]) ?></span>
              </div>
              <div class="options-pills-wrap" id="optionsPillsWrap">
                <?php foreach ($optionsList as $optIndex => $optVal): ?>
                  <button type="button"
                          class="option-pill-btn <?= $optIndex === 0 ? 'active' : '' ?>"
                          data-value="<?= htmlspecialchars($optVal) ?>">
                    <?= htmlspecialchars($optVal) ?>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Action Buttons Form -->
          <div class="product-actions-box">
            <?php if (!$priceOnRequest && $availability === 'available'): ?>
              <!-- Quantity Stepper -->
              <div class="product-qty-stepper">
                <button type="button" class="qty-step-btn" id="qtyMinus" aria-label="Decrease quantity">&minus;</button>
                <input type="number" id="productQty" class="qty-step-input" value="1" min="1" max="99" aria-label="Product quantity">
                <button type="button" class="qty-step-btn" id="qtyPlus" aria-label="Increase quantity">&plus;</button>
              </div>

              <!-- Add to Cart Primary -->
              <button type="button" class="btn-primary-action" id="addToCartBtn">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                  <line x1="3" y1="6" x2="21" y2="6"></line>
                  <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                ADD TO CART
              </button>
            <?php elseif (!$priceOnRequest && $availability === 'made_to_order'): ?>
              <div class="product-qty-stepper">
                <button type="button" class="qty-step-btn" id="qtyMinus" aria-label="Decrease quantity">&minus;</button>
                <input type="number" id="productQty" class="qty-step-input" value="1" min="1" max="99" aria-label="Product quantity">
                <button type="button" class="qty-step-btn" id="qtyPlus" aria-label="Increase quantity">&plus;</button>
              </div>

              <button type="button" class="btn-primary-action" id="addToCartBtn">
                ORDER BESPOKE
              </button>
            <?php elseif ($availability === 'coming_soon'): ?>
              <button type="button" class="btn-primary-action" disabled>
                COMING SOON &mdash; PAYMENT UNAVAILABLE
              </button>
            <?php elseif ($availability === 'unavailable'): ?>
              <button type="button" class="btn-primary-action" disabled>
                CURRENTLY SOLD OUT
              </button>
            <?php else: ?>
              <!-- Price on Request / Consultation -->
              <a href="javascript:void(0)" class="btn-primary-action" id="inquireWhatsAppPrimary">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                INQUIRE ON WHATSAPP
              </a>
            <?php endif; ?>

            <!-- WhatsApp Inquire Link -->
            <a href="javascript:void(0)" class="btn-secondary-whatsapp" id="inquireWhatsAppBtn" title="Direct architectural inquiry on WhatsApp">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
              </svg>
              Ask an Architect
            </a>
          </div>

          <!-- Auxiliary Share & Support Links -->
          <div class="product-auxiliary-actions">
            <button type="button" class="aux-btn" id="shareProductBtn">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="18" cy="5" r="3"></circle>
                <circle cx="6" cy="12" r="3"></circle>
                <circle cx="18" cy="19" r="3"></circle>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
              </svg>
              Share This Piece
            </button>

            <span style="display: inline-flex; align-items: center; gap: 6px;">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              Authentic URTH Studio Craftsmanship
            </span>
          </div>

        </div>
      </div>

      <!-- ==========================================================================
           TABS: SPECIFICATIONS, WHAT IS INCLUDED, DELIVERY & POLICIES
           ========================================================================== -->
      <section class="product-tabs-section">
        <div class="product-tabs-nav" role="tablist">
          <button type="button" class="product-tab-btn active" data-tab="tab-specs" role="tab" aria-selected="true">
            Product Specifications
          </button>
          <?php if (!empty($includedItems)): ?>
            <button type="button" class="product-tab-btn" data-tab="tab-included" role="tab" aria-selected="false">
              What Is Included (<?= count($includedItems) ?>)
            </button>
          <?php endif; ?>
          <button type="button" class="product-tab-btn" data-tab="tab-delivery" role="tab" aria-selected="false">
            Delivery &amp; Preparation
          </button>
          <button type="button" class="product-tab-btn" data-tab="tab-returns" role="tab" aria-selected="false">
            Cancellation &amp; Refund Policy
          </button>
        </div>

        <!-- Tab 1: Product Specifications (Type-dependent details) -->
        <div class="product-tab-panel active" id="tab-specs" role="tabpanel">
          <div class="spec-cards-grid">
            
            <?php if ($pType === 'digital_asset'): ?>
              <!-- Digital Asset details -->
              <?php if (!empty($product['file_formats'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">File Formats</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['file_formats']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['software_compatibility'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Software Compatibility</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['software_compatibility']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['permitted_use'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Permitted Use &amp; License</span>
                  <div class="spec-attr-value"><?= nl2br(htmlspecialchars($product['permitted_use'])) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['preview_images'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Preview Assets &amp; Demos</span>
                  <div class="spec-attr-value"><?= nl2br(htmlspecialchars($product['preview_images'])) ?></div>
                </div>
              <?php endif; ?>

            <?php elseif ($pType === 'book_planner'): ?>
              <!-- Book or Planner details -->
              <?php if (!empty($product['book_format'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Digital / Printed Format</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['book_format']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['book_dimensions'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Dimensions &amp; Physical Scope</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['book_dimensions']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['sample_pages'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Sample Pages &amp; Previews</span>
                  <div class="spec-attr-value"><?= nl2br(htmlspecialchars($product['sample_pages'])) ?></div>
                </div>
              <?php endif; ?>

            <?php elseif ($pType === 'course_workshop'): ?>
              <!-- Course or Workshop details -->
              <?php if (!empty($product['course_topics'])): ?>
                <div class="spec-attribute-card" style="grid-column: 1 / -1;">
                  <span class="spec-attr-label">Curriculum Topics Covered</span>
                  <div class="spec-attr-value"><?= nl2br(htmlspecialchars($product['course_topics'])) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['course_format'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Format</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['course_format']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['course_duration'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Duration</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['course_duration']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['access_period'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Access Period</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['access_period']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['live_dates'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Dates &amp; Time Zone for Live Events</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['live_dates']) ?></div>
                </div>
              <?php endif; ?>

            <?php elseif ($pType === 'design_session'): ?>
              <!-- Design Session details -->
              <?php if (!empty($product['session_scope'])): ?>
                <div class="spec-attribute-card" style="grid-column: 1 / -1;">
                  <span class="spec-attr-label">Consultation Scope</span>
                  <div class="spec-attr-value"><?= nl2br(htmlspecialchars($product['session_scope'])) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['session_duration'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Session Duration</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['session_duration']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['customer_info_needed'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Required Customer Information</span>
                  <div class="spec-attr-value"><?= nl2br(htmlspecialchars($product['customer_info_needed'])) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['deliverables'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Final Deliverables</span>
                  <div class="spec-attr-value"><?= nl2br(htmlspecialchars($product['deliverables'])) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['included_revisions'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Included Revisions</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['included_revisions']) ?></div>
                </div>
              <?php endif; ?>

            <?php else: ?>
              <!-- Furniture or Object details (default) -->
              <?php if (!empty($product['dimensions'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Dimensions</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['dimensions']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['materials'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Materials</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['materials']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['finishes'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Finishes</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['finishes']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['customisation'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Customisation</span>
                  <div class="spec-attr-value"><?= nl2br(htmlspecialchars($product['customisation'])) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['production_time'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Production Time</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['production_time']) ?></div>
                </div>
              <?php endif; ?>

              <?php if (!empty($product['delivery_area'])): ?>
                <div class="spec-attribute-card">
                  <span class="spec-attr-label">Delivery Area</span>
                  <div class="spec-attr-value"><?= htmlspecialchars($product['delivery_area']) ?></div>
                </div>
              <?php endif; ?>

            <?php endif; ?>

            <div class="spec-attribute-card">
              <span class="spec-attr-label">Category &amp; Classification</span>
              <div class="spec-attr-value"><?= htmlspecialchars($product['category'] ?? 'Furniture & Objects') ?></div>
            </div>

            <div class="spec-attribute-card">
              <span class="spec-attr-label">Authenticity Guarantee</span>
              <div class="spec-attr-value">Architect-certified original studio creation with unique catalog serial.</div>
            </div>

          </div>
        </div>

        <!-- Tab 2: What Is Included -->
        <?php if (!empty($includedItems)): ?>
          <div class="product-tab-panel" id="tab-included" role="tabpanel">
            <ul class="included-items-list">
              <?php foreach ($includedItems as $itemText): ?>
                <li class="included-item-row">
                  <span class="included-icon-check">&#10003;</span>
                  <span class="included-text"><?= htmlspecialchars($itemText) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Tab 3: Delivery & Preparation -->
        <div class="product-tab-panel" id="tab-delivery" role="tabpanel">
          <div class="policy-card">
            <h5 class="policy-card-title">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
              Expected Fulfillment &amp; Preparation
            </h5>
            <p class="policy-card-desc"><?= nl2br(htmlspecialchars($deliveryTime)) ?></p>
          </div>

          <div class="policy-card">
            <h5 class="policy-card-title">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
              Shipping, Packaging &amp; Freight Methods
            </h5>
            <p class="policy-card-desc"><?= nl2br(htmlspecialchars($deliveryInfo)) ?></p>
          </div>
        </div>

        <!-- Tab 4: Cancellation & Refund Policy -->
        <div class="product-tab-panel" id="tab-returns" role="tabpanel">
          <div class="policy-card">
            <h5 class="policy-card-title">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
              Cancellation, Return &amp; Refund Terms
            </h5>
            <p class="policy-card-desc"><?= nl2br(htmlspecialchars($cancellationRefund)) ?></p>
          </div>
        </div>

      </section>

      <!-- ==========================================================================
           CURATED COMPANIONS (RELATED PRODUCTS)
           ========================================================================== -->
      <?php if (!empty($relatedProducts)): ?>
        <section class="related-products-section">
          <div class="related-header">
            <span class="related-eyebrow">URTH COLLECTION</span>
            <h3 class="related-title">Curated Companions</h3>
          </div>

          <ul class="products columns-4">
            <?php foreach ($relatedProducts as $relP): ?>
              <?php
                $rPrice = (float)$relP['price'];
                $rCurr = !empty($relP['currency']) ? $relP['currency'] : '£';
                $rSale = $relP['sale_price'] !== null ? (float)$relP['sale_price'] : null;
                $rPor = (int)($relP['price_on_request'] ?? 0) === 1 || ($rPrice <= 0 && $relP['price_on_request'] !== null);
              ?>
              <li class="product-item">
                <a href="product.php?id=<?= $relP['id'] ?>" class="product-thumb-link">
                  <div class="product-image-box">
                    <img src="<?= htmlspecialchars($relP['image']) ?>" alt="<?= htmlspecialchars($relP['name']) ?>" loading="lazy" width="300" height="300">
                  </div>
                  <span class="product-cat-tag"><?= htmlspecialchars($relP['category'] ?? 'Furniture & Objects') ?></span>
                  <h3 class="woocommerce-loop-product__title"><?= htmlspecialchars($relP['name']) ?></h3>
                  <?php if ($rPor): ?>
                    <span class="price-on-request">Price on request</span>
                  <?php elseif ($rSale): ?>
                    <span class="price">
                      <del style="opacity: 0.5; margin-right: 5px;"><?= $rCurr . number_format($rPrice, 2) ?></del>
                      <ins style="text-decoration: none; font-weight: 700;"><?= $rCurr . number_format($rSale, 2) ?></ins>
                    </span>
                  <?php else: ?>
                    <span class="price"><?= $rCurr . number_format($rPrice, 2) ?></span>
                  <?php endif; ?>
                </a>
                <a href="product.php?id=<?= $relP['id'] ?>" class="button" style="text-align: center; text-decoration: none;">
                  VIEW PIECE
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

    </div>
  </main>

  <!-- ==========================================================================
       FOOTER (EXACT PRAGUE / URTH CONTACT FOOTER)
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
          <p style="color: rgba(255,255,255,0.7);">URTH. &copy; <?= date('Y') ?> ALL RIGHTS RESERVED &nbsp;&bull;&nbsp; <span style="white-space: nowrap;">MADE BY <a href="https://engagelanka.com" target="_blank" rel="noopener noreferrer" style="color: white; text-decoration: none; font-weight: 300;">ENGAGELANKA</a></span></p>
        </div>
        <div class="footer-socials">
          <a href="https://www.instagram.com/urth.spaces/" target="_blank" class="footer-social__link" aria-label="Instagram"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
          <a href="https://wa.me/94768008687" target="_blank" class="footer-social__link" aria-label="WhatsApp"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg></a>
          <a href="https://www.facebook.com/people/Urth/61586496127091/" target="_blank" class="footer-social__link" aria-label="Facebook"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
          <a href="https://www.linkedin.com/company/studio-urth/" target="_blank" class="footer-social__link" aria-label="LinkedIn"><svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
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
        <a href="shop.php" class="cart-browse-btn" id="cartBrowseBtn">Browse Products</a>
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
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Enquire via WhatsApp
        </a>
      </div>
    </div>
  </aside>

  <!-- Share Toast Feedback -->
  <div class="share-toast" id="shareToast" role="status" aria-live="polite">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
    <span id="shareToastMessage">Share link copied to clipboard!</span>
  </div>

  <!-- Client-side Interactive Logic -->
  <script>
    window.CURRENT_PRODUCT = <?= json_encode([
      'id' => (string)$product['id'],
      'name' => $product['name'],
      'sku' => $product['sku'] ?? ('PR' . $product['id']),
      'price' => $salePriceFloat ?: $priceFloat,
      'formattedPrice' => $formattedSalePrice ?: $formattedPrice,
      'currency' => $curr,
      'category' => $product['category'] ?? 'Furniture & Objects',
      'image' => $product['image'],
      'availability' => $availability,
      'price_on_request' => $priceOnRequest ? 1 : 0
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="mobile-nav.js"></script>
  <script src="product.js?v=<?= time() ?>"></script>
</body>
</html>

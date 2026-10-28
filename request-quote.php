<?php
require_once __DIR__ . '/config.php';

$productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$product = null;
if ($productId !== false && $productId !== null && $productId > 0) {
    $stmt = $conn->prepare('SELECT id, name, sku, image, category, product_type, options_label, options_list, price_on_request, availability FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Could not prepare quote product lookup.');
    }
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result ? $result->fetch_assoc() : null;
    $stmt->close();
}

$canRequestQuote = $product && (
    (int)($product['price_on_request'] ?? 0) === 1
    || in_array($product['availability'] ?? '', ['quote_only', 'made_to_order'], true)
    || in_array($product['product_type'] ?? '', ['furniture_object', 'design_session'], true)
);
if ($product && in_array($product['availability'], ['coming_soon', 'unavailable', 'fully_booked'], true)) {
    $canRequestQuote = false;
}
if (!$canRequestQuote) {
    http_response_code(404);
}

$options = array_values(array_filter(array_map('trim', explode(',', (string)($product['options_list'] ?? '')))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Request a quote — URTH</title>
  <link rel="stylesheet" href="mobile-nav.css">
  <link rel="stylesheet" href="shop.css">
  <style>
    .quote-page { max-width: 760px; margin: 56px auto; padding: 0 20px; color: #222; }
    .quote-card { padding: 26px; border: 1px solid #ddd; background: #fff; }
    .quote-product { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
    .quote-product img { width: 90px; height: 90px; object-fit: cover; }
    .quote-field { margin: 16px 0; }
    .quote-field label { display: block; margin-bottom: 6px; font-weight: 600; }
    .quote-field input:not([type=checkbox]), .quote-field textarea, .quote-field select { width: 100%; padding: 11px; border: 1px solid #bbb; box-sizing: border-box; }
    .quote-consent { display: flex; align-items: flex-start; gap: 9px; line-height: 1.5; }
    .quote-submit { padding: 13px 22px; background: #222; color: #fff; border: 0; cursor: pointer; }
    .quote-error { color: #9b1c1c; }
  </style>
</head>
<body>
  <header class="hero-top-nav" style="background:#111;position:relative;">
    <div class="logo"><a href="index.php" aria-label="URTH home"><img src="img/URTH (1).png" alt="URTH" class="navbar-brand-image"></a></div>
    <nav class="center-nav" aria-label="Main navigation">
      <a href="index.php">HOME</a><a href="about.html">ABOUT</a><a href="projects.php">PROJECTS</a><a href="contact.html">CONTACT</a><a href="shop.php">SHOP</a>
    </nav>
    <div class="right-nav"><button class="mobile-nav-toggle" aria-label="Toggle Menu"><span></span><span></span><span></span></button></div>
  </header>
  <div class="mobile-nav-overlay"><nav class="mobile-nav-links" aria-label="Mobile navigation">
    <a href="index.php">HOME</a><a href="about.html">ABOUT</a><a href="projects.php">PROJECTS</a><a href="contact.html">CONTACT</a><a href="shop.php">SHOP</a>
  </nav></div>
  <main class="quote-page">
    <?php if (!$canRequestQuote): ?>
      <h1>Quote request unavailable</h1>
      <p>This product is not available for a quote request. <a href="shop.php">Return to the shop</a>.</p>
    <?php else: ?>
      <h1>Request a quote</h1>
      <p>Send your requirements separately from fixed-price orders. URTH will review the request and confirm scope, availability, delivery and pricing before any payment is requested.</p>
      <section class="quote-card">
        <div class="quote-product">
          <?php if (!empty($product['image'])): ?><img src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
          <div>
            <strong><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></strong>
            <?php if (!empty($product['sku'])): ?><div>SKU: <?= htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
          </div>
        </div>
        <form id="quoteRequestForm">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <div class="quote-field">
            <label for="quoteName">Full name *</label>
            <input id="quoteName" name="customer_name" autocomplete="name" required maxlength="255">
          </div>
          <div class="quote-field">
            <label for="quoteEmail">Email address *</label>
            <input id="quoteEmail" name="customer_email" type="email" autocomplete="email" required maxlength="255">
          </div>
          <div class="quote-field">
            <label for="quotePhone">Phone / WhatsApp with country code *</label>
            <input id="quotePhone" name="customer_phone" type="tel" autocomplete="tel" required maxlength="50">
          </div>
          <?php if ($options): ?>
            <div class="quote-field">
              <label for="quoteOption"><?= htmlspecialchars($product['options_label'] ?: 'Option', ENT_QUOTES, 'UTF-8') ?></label>
              <select id="quoteOption" name="selected_option">
                <option value="">Not selected</option>
                <?php foreach ($options as $option): ?><option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
          <div class="quote-field">
            <label for="quoteBrief">Project brief / requirements *</label>
            <textarea id="quoteBrief" name="project_brief" rows="5" required maxlength="10000" placeholder="Include dimensions, materials, delivery location or project goals where relevant."></textarea>
          </div>
          <?php if (in_array($product['product_type'], ['furniture_object', 'design_session'], true)): ?>
            <div class="quote-field">
              <label for="quoteFiles">Reference images or drawings (optional)</label>
              <input id="quoteFiles" type="file" name="customer_files[]" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple>
              <small>Up to 5 files, 10 MB each. Do not upload sensitive information.</small>
            </div>
          <?php endif; ?>
          <div class="quote-field" style="position:absolute;left:-10000px;" aria-hidden="true">
            <label for="quoteWebsite">Leave this field empty</label>
            <input id="quoteWebsite" name="website" tabindex="-1" autocomplete="off">
          </div>
          <div class="quote-field">
            <label class="quote-consent">
              <input type="checkbox" name="terms_accepted" value="1" required>
              <span>I have read the <a href="shop-info.php#returns-refunds" target="_blank" rel="noopener noreferrer">shop terms</a> and <a href="shop-info.php#privacy" target="_blank" rel="noopener noreferrer">privacy notice</a>. I understand this is a quote request, not an accepted order.</span>
            </label>
          </div>
          <p id="quoteRequestError" class="quote-error" role="alert" hidden></p>
          <button type="submit" class="quote-submit">Submit quote request</button>
        </form>
        <div id="quoteRequestSuccess" hidden role="status"></div>
      </section>
    <?php endif; ?>
  </main>
  <script src="mobile-nav.js"></script>
  <?php if ($canRequestQuote): ?>
  <script>
    document.querySelector('#quoteRequestForm').addEventListener('submit', async event => {
      event.preventDefault();
      const form = event.currentTarget;
      if (!form.reportValidity()) return;
      const button = form.querySelector('button[type="submit"]');
      const error = document.querySelector('#quoteRequestError');
      button.disabled = true;
      button.textContent = 'Submitting request…';
      error.hidden = true;
      try {
        const response = await fetch('api/request_quote.php', { method: 'POST', body: new FormData(form) });
        const result = await response.json();
        if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Quote request could not be submitted.');
        form.hidden = true;
        const success = document.querySelector('#quoteRequestSuccess');
        const emailNotice = result.email_status === 'sent'
          ? 'A secure status link was sent to your email.'
          : 'URTH could not send the confirmation email yet; please contact the studio with your reference.';
        success.textContent = `Quote request ${result.order_number} submitted. Payment is not due until URTH confirms a quote. ${emailNotice}`;
        success.hidden = false;
      } catch (requestError) {
        error.textContent = requestError.message || 'Quote request could not be submitted. Please try again.';
        error.hidden = false;
        button.disabled = false;
        button.textContent = 'Try again';
      }
    });
  </script>
  <?php endif; ?>
</body>
</html>

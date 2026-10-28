<?php
require_once __DIR__ . '/config.php';

$token = (string)($_GET['token'] ?? '');
$order = null;
$items = [];
$statusMessage = '';

if (preg_match('/^[A-Za-z0-9_-]{43}$/D', $token)) {
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id, order_number, order_type, customer_name, created_at, currency, subtotal, shipping_cost, total, payment_status, fulfilment_status, payment_method, receipt_url, tracking_number, tracking_url, estimated_delivery, customer_status_token_expires_at, digital_access_link FROM orders WHERE customer_status_token_hash = ? AND customer_status_token_expires_at > NOW() LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException('Could not prepare order status lookup.');
    }
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($order) {
        $itemsStmt = $conn->prepare('SELECT product_name, product_sku, selected_option, price, quantity, total FROM order_items WHERE order_id = ? ORDER BY id ASC');
        if (!$itemsStmt) {
            throw new RuntimeException('Could not prepare order item lookup.');
        }
        $orderId = (int)$order['id'];
        $itemsStmt->bind_param('i', $orderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        while ($itemsResult && ($item = $itemsResult->fetch_assoc())) {
            $items[] = $item;
        }
        $itemsStmt->close();

        if (($_GET['evidence'] ?? '') === 'received') {
            $statusMessage = 'Payment evidence received. URTH will review it and update your order.';
        } elseif (($_GET['evidence'] ?? '') === 'error') {
            $statusMessage = 'We could not accept that file. Upload a PDF, JPG, PNG or WebP under 10 MB.';
        }
    }
}

$currency = htmlspecialchars($order['currency'] ?? '£', ENT_QUOTES, 'UTF-8');
$canSubmitPaymentEvidence = $order
    && in_array($order['payment_status'] ?? 'pending_payment', ['pending_payment', 'deposit_paid', 'balance_pending'], true)
    && (($order['order_type'] ?? 'order') !== 'quote_request'
        || ((float)$order['total'] > 0 && ($order['fulfilment_status'] ?? '') === 'quote_accepted'));
$statusLabels = [
    'pending_payment' => 'Payment pending',
    'deposit_paid' => 'Deposit recorded; balance due',
    'balance_pending' => 'Balance pending',
    'fully_paid' => 'Paid',
    'refund_pending' => 'Refund pending',
    'partially_refunded' => 'Partially refunded',
    'refunded' => 'Refunded',
    'cancelled' => 'Cancelled',
];
$fulfilmentLabels = [
    'enquiry' => 'Under review',
    'quote_prepared' => 'Quote prepared',
    'quote_sent' => 'Quote sent',
    'quote_accepted' => 'Quote accepted',
    'in_queue' => 'In queue',
    'in_production' => 'In production',
    'booking_scheduled' => 'Booking scheduled',
    'digital_ready' => 'Digital delivery ready',
    'dispatched' => 'Dispatched',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Order status — URTH</title>
  <link rel="stylesheet" href="mobile-nav.css">
  <link rel="stylesheet" href="shop.css">
  <style>
    .order-status-page { max-width: 860px; margin: 60px auto; padding: 0 20px; color: #222; }
    .order-status-card { padding: 28px; margin: 18px 0; border: 1px solid #ddd; background: #fff; }
    .order-status-card h2 { margin-top: 0; }
    .order-status-list { padding-left: 20px; line-height: 1.8; }
    .order-status-message { padding: 12px 16px; background: #f3f0e8; }
    .order-status-table { width: 100%; border-collapse: collapse; }
    .order-status-table th, .order-status-table td { padding: 10px 6px; border-bottom: 1px solid #ddd; text-align: left; }
    .order-status-upload { padding: 18px; background: #f7f5ef; }
    .order-status-upload button { margin-top: 12px; padding: 12px 18px; border: 0; background: #222; color: #fff; cursor: pointer; }
    @media (max-width: 600px) { .order-status-card { padding: 18px; overflow-x: auto; } }
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

  <main class="order-status-page">
    <h1>Order status</h1>
    <?php if (!$order): ?>
      <div class="order-status-card">
        <h2>This order link is invalid or has expired</h2>
        <p>For help, contact <a href="mailto:info@urthspaces.com">info@urthspaces.com</a> and include your order number if available.</p>
      </div>
    <?php else: ?>
      <?php if ($statusMessage !== ''): ?><p class="order-status-message" role="status"><?= htmlspecialchars($statusMessage, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
      <section class="order-status-card">
        <h2><?= htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Submitted <?= htmlspecialchars(date('F j, Y', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8') ?>. Status link expires <?= htmlspecialchars(date('F j, Y', strtotime($order['customer_status_token_expires_at'])), ENT_QUOTES, 'UTF-8') ?>.</p>
        <ul class="order-status-list">
          <li><strong>Payment:</strong> <?= htmlspecialchars($statusLabels[$order['payment_status'] ?? 'pending_payment'] ?? 'Pending review', ENT_QUOTES, 'UTF-8') ?></li>
          <li><strong>Fulfilment:</strong> <?= htmlspecialchars($fulfilmentLabels[$order['fulfilment_status'] ?? 'in_queue'] ?? 'Under review', ENT_QUOTES, 'UTF-8') ?></li>
          <li><strong>Preferred payment method:</strong> <?= htmlspecialchars($order['payment_method'] ?? 'To be confirmed', ENT_QUOTES, 'UTF-8') ?></li>
          <?php if (($order['order_type'] ?? 'order') === 'quote_request'
              && ((float)$order['total'] <= 0 || !in_array($order['fulfilment_status'], ['quote_sent', 'quote_accepted'], true))): ?>
            <li><strong>Quote:</strong> Under review. No payment is due until URTH sends a written quote.</li>
          <?php elseif (($order['order_type'] ?? 'order') === 'quote_request'): ?>
            <li><strong>Quote total:</strong> <?= $currency . number_format((float)$order['total'], 2) ?> (confirm the written quote from URTH before paying)</li>
          <?php else: ?>
            <li><strong>Provisional item subtotal:</strong> <?= $currency . number_format((float)$order['subtotal'], 2) ?></li>
            <li><strong>Final total:</strong> To be confirmed by URTH after order review.</li>
          <?php endif; ?>
          <?php if (($order['payment_status'] ?? '') === 'fully_paid' && ($order['fulfilment_status'] ?? '') === 'digital_ready' && !empty($order['digital_access_link']) && preg_match('~^https://~i', $order['digital_access_link'])): ?>
            <li><a href="<?= htmlspecialchars($order['digital_access_link'], ENT_QUOTES, 'UTF-8') ?>">Download your digital product</a> (private link expires after 30 days)</li>
          <?php endif; ?>
          <?php if (!empty($order['estimated_delivery'])): ?><li><strong>Estimated delivery:</strong> <?= htmlspecialchars($order['estimated_delivery'], ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
          <?php if (!empty($order['tracking_number'])): ?><li><strong>Tracking number:</strong> <?= htmlspecialchars($order['tracking_number'], ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
          <?php if (!empty($order['tracking_url']) && preg_match('~^https://~i', $order['tracking_url'])): ?><li><a href="<?= htmlspecialchars($order['tracking_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Track delivery</a></li><?php endif; ?>
        </ul>
      </section>

      <section class="order-status-card">
        <h2>Items</h2>
        <table class="order-status-table">
          <thead><tr><th>Product</th><th>Option</th><th>Qty</th><th>Total</th></tr></thead>
          <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($item['selected_option'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= (int)$item['quantity'] ?></td>
              <td><?= $currency . number_format((float)$item['total'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <?php if ($canSubmitPaymentEvidence): ?>
        <section class="order-status-card order-status-upload">
          <h2>Submit payment evidence</h2>
          <?php if (!empty($order['receipt_url'])): ?>
            <p>Payment evidence has already been submitted and is awaiting staff review.</p>
          <?php else: ?>
            <p>Only submit evidence after paying using instructions confirmed by URTH. Do not upload card details, passwords or account credentials.</p>
            <form action="api/submit_payment_evidence.php" method="post" enctype="multipart/form-data">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
              <label for="paymentEvidence">Receipt or transfer confirmation (PDF, JPG, PNG or WebP; maximum 10 MB)</label><br>
              <input type="file" id="paymentEvidence" name="payment_evidence" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
              <br><button type="submit">Submit payment evidence</button>
            </form>
          <?php endif; ?>
        </section>
      <?php endif; ?>
      <p>Questions? Email <a href="mailto:info@urthspaces.com">info@urthspaces.com</a> or message <a href="https://wa.me/94768008687">URTH on WhatsApp</a>.</p>
    <?php endif; ?>
  </main>
  <script src="mobile-nav.js"></script>
</body>
</html>

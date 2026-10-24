<?php

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/Core/shop_mail.php';

ensure_shop_email_outbox($conn);
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        http_response_code(400);
        $message = 'Security token expired. Reload the page and try again.';
        $messageType = 'danger';
    } else {
        $emailId = filter_var($_POST['email_id'] ?? null, FILTER_VALIDATE_INT);
        if ($emailId === false || $emailId === null || $emailId <= 0) {
            http_response_code(422);
            $message = 'Select a valid failed email to retry.';
            $messageType = 'danger';
        } else {
            try {
                $sent = send_shop_email($conn, '', '', '', $emailId);
                $message = $sent ? 'Email accepted by the configured mail transport.' : 'Email retry failed and has been recorded.';
                $messageType = $sent ? 'success' : 'danger';
            } catch (RuntimeException $e) {
                error_log('URTH failed email retry could not be completed: ' . $e->getMessage());
                http_response_code(500);
                $message = 'The email could not be retried. The error was logged for staff review.';
                $messageType = 'danger';
            }
        }
    }
}

$result = $conn->query("SELECT id, recipient, subject, status, attempts, last_error, last_attempt_at, created_at FROM shop_email_outbox WHERE status = 'failed' ORDER BY created_at DESC LIMIT 100");
$failedEmails = [];
if (!$result) {
    throw new RuntimeException('Could not load failed email records.');
}
while ($row = $result->fetch_assoc()) {
    $failedEmails[] = $row;
}
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Failed Emails — URTH Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .email-log-wrap { width: min(1100px, calc(100% - 32px)); margin: 28px auto; overflow-x: auto; }
        .email-log { width: 100%; border-collapse: collapse; background: #fff; }
        .email-log th, .email-log td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        .email-log th { font-size: 12px; text-transform: uppercase; }
        .email-error { max-width: 340px; overflow-wrap: anywhere; color: #8b1e1e; }
        @media (max-width: 640px) { .email-log th, .email-log td { padding: 9px; font-size: 12px; } }
    </style>
</head>
<body>
<div class="admin-layout">
    <header class="admin-header">
        <a href="index.php" class="header-brand">
            <img src="../urth_clone/images/6a1c3ef3f96cad36ed74d8cd_favicon.jpg" alt="URTH Logo" style="width: 38px; height: 38px; border-radius: var(--radius-sm); object-fit: cover;">
            <div class="brand-text">
                <span class="brand-name">URTH</span>
                <span class="brand-sub">Admin</span>
            </div>
        </a>
        <nav class="admin-nav-tabs">
            <a href="index.php" class="admin-nav-link">Projects</a>
            <a href="products.php" class="admin-nav-link">Products</a>
            <a href="orders.php" class="admin-nav-link">Orders</a>
            <a href="partners.php" class="admin-nav-link">Partners</a>
            <a href="emails.php" class="admin-nav-link active">Failed Emails</a>
        </nav>
        <div class="header-actions">
            <a href="logout.php" class="btn btn-danger btn-sm" title="Log out">Logout</a>
        </div>
    </header>
    <main class="email-log-wrap">
        <h1>Failed Emails</h1>
        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (!$failedEmails): ?>
            <p>No failed emails are waiting for retry.</p>
        <?php else: ?>
            <table class="email-log">
                <thead><tr><th>Created</th><th>Recipient / subject</th><th>Attempts / latest error</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($failedEmails as $email): ?>
                    <tr>
                        <td><?= htmlspecialchars($email['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= htmlspecialchars($email['recipient'], ENT_QUOTES, 'UTF-8') ?></strong><br><?= htmlspecialchars($email['subject'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)$email['attempts'] ?> attempt(s)<br><span class="email-error"><?= htmlspecialchars($email['last_error'] ?? 'No error details recorded.', ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <form method="post" action="emails.php">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="email_id" value="<?= (int)$email['id'] ?>">
                                <button type="submit" class="btn btn-gold btn-sm">Retry email</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

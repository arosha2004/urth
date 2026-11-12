<?php

require_once __DIR__ . '/auth.php';
require_admin_auth();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/Core/shop_mail.php';

$h = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$envPath = dirname(__DIR__) . '/.env';
$settings = [
    'URTH_MAIL_FROM' => getenv('URTH_MAIL_FROM'),
    'URTH_SHOP_ORDER_EMAIL' => getenv('URTH_SHOP_ORDER_EMAIL'),
    'URTH_PUBLIC_BASE_URL' => getenv('URTH_PUBLIC_BASE_URL'),
    'URTH_SMTP_HOST' => getenv('URTH_SMTP_HOST'),
    'URTH_SMTP_PORT' => getenv('URTH_SMTP_PORT'),
    'URTH_SMTP_USER' => getenv('URTH_SMTP_USER'),
    'URTH_SMTP_PASS' => getenv('URTH_SMTP_PASS') ? '(set, ' . strlen((string)getenv('URTH_SMTP_PASS')) . ' characters)' : false,
];

$checks = [];
$checks[] = [file_exists($envPath), '.env file found in the site root'];
$checks[] = [function_exists('shop_smtp_send'), 'New shop_mail.php is uploaded (SMTP support present)'];
$from = trim((string)getenv('URTH_MAIL_FROM'));
$smtpUser = trim((string)getenv('URTH_SMTP_USER'));
$checks[] = [$from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL), 'URTH_MAIL_FROM is a valid address'];
$checks[] = [trim((string)getenv('URTH_SMTP_HOST')) !== '', 'SMTP is switched on (URTH_SMTP_HOST is set)'];
$checks[] = [$smtpUser !== '' && strcasecmp($smtpUser, $from) === 0, 'URTH_MAIL_FROM matches URTH_SMTP_USER'];
$checks[] = [(string)getenv('URTH_SMTP_PASS') !== '', 'URTH_SMTP_PASS is set'];

$message = '';
$messageType = 'success';
$testTo = trim((string)($_POST['test_to'] ?? (getenv('URTH_SHOP_ORDER_EMAIL') ?: '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $message = 'Security token expired. Reload the page and try again.';
        $messageType = 'danger';
    } elseif (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid email address to send the test to.';
        $messageType = 'danger';
    } else {
        try {
            $sent = send_shop_email(
                $conn,
                $testTo,
                'URTH shop email test — ' . date('Y-m-d H:i:s'),
                "This is a test email from the URTH shop.\n\nIf you can read this, order emails to this address will work.\n\nSent at " . date('Y-m-d H:i:s')
            );
            if ($sent) {
                $message = "Sent successfully to {$testTo}. Check that inbox (and Spam) now.";
            } else {
                $row = $conn->query("SELECT last_error FROM shop_email_outbox ORDER BY id DESC LIMIT 1");
                $error = $row ? ($row->fetch_assoc()['last_error'] ?? '') : '';
                $message = 'Sending FAILED: ' . ($error !== '' ? $error : 'no error details recorded.');
                $messageType = 'danger';
            }
        } catch (Throwable $e) {
            $message = 'Sending FAILED: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mail Test — URTH Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .mail-test { width: min(820px, calc(100% - 32px)); margin: 28px auto; }
        .mail-test table { width: 100%; border-collapse: collapse; background: #fff; margin-bottom: 24px; }
        .mail-test th, .mail-test td { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; text-align: left; overflow-wrap: anywhere; }
        .ok { color: #157347; font-weight: 600; }
        .bad { color: #b42318; font-weight: 600; }
        .mail-test form { display: flex; gap: 10px; flex-wrap: wrap; }
        .mail-test input[type=email] { flex: 1 1 260px; padding: 10px; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php if (file_exists(__DIR__ . '/navbar.php')) { require_once __DIR__ . '/navbar.php'; } ?>
    <main class="mail-test">
        <h1>Mail Test</h1>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?= $h($messageType) ?>"><?= $h($message) ?></div>
        <?php endif; ?>

        <h2>Checklist</h2>
        <table>
            <?php foreach ($checks as [$passed, $label]): ?>
                <tr><td class="<?= $passed ? 'ok' : 'bad' ?>"><?= $passed ? '✔' : '✘' ?></td><td><?= $h($label) ?></td></tr>
            <?php endforeach; ?>
        </table>

        <h2>Settings the site can see</h2>
        <table>
            <?php foreach ($settings as $name => $value): ?>
                <tr><th><?= $h($name) ?></th><td><?= $value === false || $value === '' ? '<span class="bad">not set</span>' : $h($value) ?></td></tr>
            <?php endforeach; ?>
        </table>

        <h2>Send a test email</h2>
        <form method="post" action="mail_test.php">
            <input type="hidden" name="csrf_token" value="<?= $h($csrfToken) ?>">
            <input type="email" name="test_to" value="<?= $h($testTo) ?>" placeholder="name@example.com" required>
            <button type="submit" class="btn btn-gold">Send test</button>
        </form>
    </main>
</div>
</body>
</html>

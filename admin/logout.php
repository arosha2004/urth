<?php
/**
 * URTH Admin Panel - Logout Handler
 */

require_once __DIR__ . '/auth.php';

logout_admin();
header('Location: login.php?logout=1');
exit;

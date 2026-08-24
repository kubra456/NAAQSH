<?php
// Admin logout endpoint.
// This destroys the active admin session and prevents any stale authenticated
// state from remaining available after the user leaves the dashboard.
require_once __DIR__ . '/../includes/auth.php';

adminLogout();
header('Location: /NAAQSH/admin/login.php');
exit;


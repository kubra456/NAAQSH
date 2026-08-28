<?php
// Shared public header for NAAQŚĦ.
require_once __DIR__ . '/../includes/session.php';

$projectBase = '/NAAQSH';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$loggedIn = !empty($_SESSION['customer_id']) || !empty($_SESSION['admin_id']);
$userDisplayName = $_SESSION['customer_name'] ?? $_SESSION['admin_name'] ?? 'Account';
$accountHref = !empty($_SESSION['admin_id']) 
    ? $projectBase . '/admin/dashboard.php'
    : ($loggedIn ? $projectBase . '/customer/dashboard.php' : $projectBase . '/customer/login.php');
$accountLabel = $loggedIn ? $userDisplayName : 'Login';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="NAAQŚĦ creates luxury wedding planning, bespoke bridal styling, and editorial photography experiences in Pakistan.">
  <title>NAAQŚĦ — Plan. Style. Capture.</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo htmlspecialchars($projectBase); ?>/public/assets/css/style.css">
</head>
<body class="site-body">
  <a class="skip-link" href="#main-content">Skip to content</a>

  <header class="site-header">
    <div class="container header-inner">
      <a href="<?php echo htmlspecialchars($projectBase); ?>/public/index.php" class="brand-lockup" aria-label="NAAQŚĦ home page">
        <span class="brand-mark">N</span>
        <span class="brand-text">
          <span class="brand-wordmark">NAAQŚĦ</span>
          <span class="brand-tagline">Plan. Style. Capture.</span>
        </span>
      </a>

      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-navigation" aria-label="Toggle navigation">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <nav class="main-nav" id="site-navigation" aria-label="Main navigation">
        <a href="<?php echo htmlspecialchars($projectBase); ?>/public/index.php" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">Home</a>
        <a href="<?php echo htmlspecialchars($projectBase); ?>/public/about.php" class="nav-link <?php echo $currentPage === 'about' ? 'active' : ''; ?>">About</a>
        <a href="<?php echo htmlspecialchars($projectBase); ?>/public/services.php" class="nav-link <?php echo $currentPage === 'services' ? 'active' : ''; ?>">Services</a>
        <a href="<?php echo htmlspecialchars($projectBase); ?>/public/portfolio.php" class="nav-link <?php echo $currentPage === 'portfolio' ? 'active' : ''; ?>">Portfolio</a>
        <a href="<?php echo htmlspecialchars($projectBase); ?>/public/contact.php" class="nav-link <?php echo $currentPage === 'contact' ? 'active' : ''; ?>">Contact</a>

        <div class="nav-actions">
          <a href="<?php echo htmlspecialchars($accountHref); ?>" class="nav-account">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span><?php echo htmlspecialchars($accountLabel); ?></span>
          </a>
          <a href="<?php echo htmlspecialchars($projectBase); ?>/public/plan_event.php" class="btn btn-primary btn-sm">Plan Your Event</a>
        </div>
      </nav>
    </div>
  </header>

  <main id="main-content" class="page-shell">

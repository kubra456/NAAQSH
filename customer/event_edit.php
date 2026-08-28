<?php
// Customer Event Edit Screen for NAAQŚĦ
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['customer_id'])) {
    header('Location: /NAAQSH/customer/login.php');
    exit;
}

$customerId = (int)$_SESSION['customer_id'];
$customerName = $_SESSION['customer_name'] ?? 'Valued Client';
$eventId = (int)($_GET['id'] ?? 0);

if ($eventId <= 0) {
    $_SESSION['flash_error'] = 'Invalid event request.';
    header('Location: /NAAQSH/customer/dashboard.php');
    exit;
}

$pdo = getPDO();

// Strict Ownership Verification: verify events.id = requested ID AND events.user_id = logged-in customer ID
$stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$eventId, $customerId]);
$event = $stmt->fetch();

if (!$event) {
    $_SESSION['flash_error'] = 'Event not found or access denied.';
    header('Location: /NAAQSH/customer/dashboard.php');
    exit;
}

$errors = [];

$eventTypeOptions = [
    'Wedding (Multi-Day)',
    'Nikah Ceremony',
    'Barat Planning & Production',
    'Walima Reception',
    'Mehndi & Sangeet',
    'Engagement & Bridal Suite',
    'Corporate Gala & Launch',
    'Private Dinner & Anniversary',
    'Other Bespoke Gathering'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $eventType = trim($_POST['event_type'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $guestCountRaw = $_POST['guest_count'] ?? '';
    $budgetRaw = $_POST['budget'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($title === '') {
        $errors[] = 'Please provide an event name.';
    }
    if ($eventType === '') {
        $errors[] = 'Please select an event type.';
    }

    if ($eventDate === '') {
        $errors[] = 'Please select an event date.';
    } else {
        $dt = DateTime::createFromFormat('Y-m-d', $eventDate);
        if (!$dt || $dt->format('Y-m-d') !== $eventDate) {
            $errors[] = 'Please enter a valid event date format (YYYY-MM-DD).';
        }
    }

    $guestCount = 0;
    if ($guestCountRaw !== '') {
        if (!is_numeric($guestCountRaw) || (int)$guestCountRaw < 0) {
            $errors[] = 'Guest count must be a non-negative number.';
        } else {
            $guestCount = (int)$guestCountRaw;
        }
    }

    $budget = 0.00;
    if ($budgetRaw !== '') {
        if (!is_numeric($budgetRaw) || (float)$budgetRaw < 0) {
            $errors[] = 'Budget must be a non-negative number.';
        } else {
            $budget = (float)$budgetRaw;
        }
    }

    if (empty($errors)) {
        // Ownership check enforced during UPDATE execution
        $updateStmt = $pdo->prepare('
            UPDATE events
            SET title = ?, event_type = ?, event_date = ?, venue = ?, guest_count = ?, budget = ?, notes = ?
            WHERE id = ? AND user_id = ?
        ');
        $updateStmt->execute([
            $title,
            $eventType,
            $eventDate,
            $venue !== '' ? $venue : null,
            $guestCount,
            $budget,
            $notes !== '' ? $notes : null,
            $eventId,
            $customerId
        ]);

        $_SESSION['flash_success'] = 'Event updated successfully.';
        header('Location: /NAAQSH/customer/dashboard.php');
        exit;
    }
} else {
    // Populate form with existing event values from database
    $title = $event['title'];
    $eventType = $event['event_type'];
    $eventDate = $event['event_date'];
    $venue = $event['venue'] ?? '';
    $guestCountRaw = $event['guest_count'];
    $budgetRaw = $event['budget'];
    $notes = $event['notes'] ?? '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Event — NAAQŚĦ Client Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/NAAQSH/public/assets/css/style.css">
</head>
<body class="site-body">

  <!-- Portal Header Bar -->
  <header class="site-header">
    <div class="container header-inner">
      <a href="/NAAQSH/public/index.php" class="brand-lockup" aria-label="NAAQŚĦ home page">
        <span class="brand-mark">N</span>
        <span class="brand-text">
          <span class="brand-wordmark">NAAQŚĦ</span>
          <span class="brand-tagline">Client Portal</span>
        </span>
      </a>

      <nav class="main-nav" id="site-navigation">
        <a href="/NAAQSH/public/index.php" class="nav-link">Main Website</a>
        <a href="/NAAQSH/customer/dashboard.php" class="nav-link">My Dashboard</a>
        <a href="/NAAQSH/public/services.php" class="nav-link">Services</a>
        <a href="/NAAQSH/public/contact.php" class="nav-link">Contact Studio</a>
        
        <div class="nav-actions">
          <span class="nav-account" style="color: var(--color-champagne);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <?php echo htmlspecialchars($customerName); ?>
          </span>
          <a href="/NAAQSH/customer/logout.php" class="btn btn-secondary btn-sm">Sign Out</a>
        </div>
      </nav>
    </div>
  </header>

  <main class="page-shell" style="padding-bottom: 5rem;">
    <div class="dashboard-header">
      <div class="container">
        <span class="section-kicker">Client Portal &bull; Event Management</span>
        <h1 class="hero-title" style="font-size: clamp(2.2rem, 3.5vw, 3.4rem); margin-bottom: 0.4rem;">
          Edit Event Details
        </h1>
        <p class="lead" style="font-size: 1.05rem;">
          Update celebration parameters for "<?php echo htmlspecialchars($event['title']); ?>" (Event #<?php echo (int)$event['id']; ?>).
        </p>
      </div>
    </div>

    <div class="container" style="max-width: 850px;">
      
      <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;">
          <ul style="margin: 0; padding-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
              <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: clamp(2rem, 4vw, 3rem); box-shadow: var(--shadow-md);">
        
        <form method="post" action="/NAAQSH/customer/event_edit.php?id=<?php echo (int)$eventId; ?>" novalidate>
          
          <div class="form-grid">
            <div class="form-group full-width">
              <label for="title">Event Name *</label>
              <input id="title" name="title" type="text" value="<?php echo htmlspecialchars($title); ?>" required placeholder="e.g. Sana & Areeb's Wedding Celebration">
            </div>

            <div class="form-group">
              <label for="event_type">Event Type *</label>
              <select id="event_type" name="event_type" required>
                <option value="">Select Event Classification</option>
                <?php foreach ($eventTypeOptions as $opt): ?>
                  <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($eventType === $opt) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($opt); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="event_date">Event Date *</label>
              <input id="event_date" name="event_date" type="date" value="<?php echo htmlspecialchars($eventDate); ?>" required>
            </div>

            <div class="form-group full-width">
              <label for="venue">Preferred Venue / Location</label>
              <input id="venue" name="venue" type="text" value="<?php echo htmlspecialchars($venue); ?>" placeholder="e.g. Lahore Grand Hall / Islamabad Farmhouse">
            </div>

            <div class="form-group">
              <label for="guest_count">Estimated Number of Guests</label>
              <input id="guest_count" name="guest_count" type="number" min="0" step="10" value="<?php echo htmlspecialchars($guestCountRaw); ?>" placeholder="e.g. 350">
            </div>

            <div class="form-group">
              <label for="budget">Allocated Budget (PKR)</label>
              <input id="budget" name="budget" type="number" min="0" step="1000" value="<?php echo htmlspecialchars($budgetRaw); ?>" placeholder="e.g. 250000">
            </div>

            <div class="form-group full-width">
              <label for="notes">Notes / Special Requirements</label>
              <textarea id="notes" name="notes" rows="5" placeholder="Special requirements, floral preferences, stage design direction..."><?php echo htmlspecialchars($notes); ?></textarea>
            </div>
          </div>

          <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary" style="min-width: 180px;">Save Changes</button>
            <a href="/NAAQSH/customer/dashboard.php" class="btn btn-secondary">Cancel</a>
          </div>

        </form>

      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-bottom">
      <div class="container">
        <p>&copy; <?php echo date('Y'); ?> NAAQŚĦ. Client Portal. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script src="/NAAQSH/public/assets/js/main.js"></script>
</body>
</html>

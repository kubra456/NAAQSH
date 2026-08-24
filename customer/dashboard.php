<?php
// Luxury Customer Dashboard & Client Portal for NAAQŚĦ.
require_once __DIR__ . '/../config/db.php';
session_start();

if (empty($_SESSION['customer_id'])) {
    header('Location: /NAAQSH/customer/login.php');
    exit;
}

$pdo = getPDO();
$customerId = (int)$_SESSION['customer_id'];
$customerName = $_SESSION['customer_name'] ?? 'Valued Client';

// Handle POST actions (e.g. Delete Event)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete_event') {
    $deleteEventId = (int)($_POST['event_id'] ?? 0);

    if ($deleteEventId > 0) {
        // Enforce ownership check: events.id = ? AND events.user_id = ?
        $verifyStmt = $pdo->prepare('SELECT id FROM events WHERE id = ? AND user_id = ? LIMIT 1');
        $verifyStmt->execute([$deleteEventId, $customerId]);
        $existingEvent = $verifyStmt->fetch();

        if ($existingEvent) {
            try {
                $pdo->beginTransaction();

                // Safely delete linked bookings for this user & event
                $delBookings = $pdo->prepare('DELETE FROM bookings WHERE event_id = ? AND user_id = ?');
                $delBookings->execute([$deleteEventId, $customerId]);

                // Delete event row enforcing strict user_id ownership
                $delEvent = $pdo->prepare('DELETE FROM events WHERE id = ? AND user_id = ?');
                $delEvent->execute([$deleteEventId, $customerId]);

                $pdo->commit();

                $_SESSION['flash_success'] = 'Event deleted successfully.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['flash_error'] = 'An error occurred while deleting the event.';
            }
        } else {
            $_SESSION['flash_error'] = 'Event not found or access denied.';
        }
    } else {
        $_SESSION['flash_error'] = 'Invalid event deletion request.';
    }

    header('Location: /NAAQSH/customer/dashboard.php');
    exit;
}

// Fetch customer events with linked bookings count
$eventsStmt = $pdo->prepare('
    SELECT e.*, (SELECT COUNT(*) FROM bookings b WHERE b.event_id = e.id) AS booking_count
    FROM events e
    WHERE e.user_id = ?
    ORDER BY e.event_date ASC
');
$eventsStmt->execute([$customerId]);
$customerEvents = $eventsStmt->fetchAll();

// Fetch customer bookings with service and event details
$bookingsStmt = $pdo->prepare('
    SELECT b.*, s.title AS service_title, s.price AS unit_price, e.title AS event_title
    FROM bookings b
    INNER JOIN services s ON s.id = b.service_id
    INNER JOIN events e ON e.id = b.event_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
');
$bookingsStmt->execute([$customerId]);
$customerBookings = $bookingsStmt->fetchAll();

// Calculate total budget
$totalBudget = 0.0;
foreach ($customerEvents as $ev) {
    $totalBudget += (float)$ev['budget'];
}

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Client Portal — NAAQŚĦ</title>
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
        <a href="/NAAQSH/customer/dashboard.php" class="nav-link active">My Dashboard</a>
        <a href="/NAAQSH/customer/inspiration_board.php" class="nav-link">Inspiration Board</a>
        <a href="/NAAQSH/public/services.php" class="nav-link">Services</a>
        <a href="/NAAQSH/public/portfolio.php" class="nav-link">Portfolio</a>
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
    <!-- Welcome Header Banner -->
    <div class="dashboard-header">
      <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem;">
          <div>
            <span class="section-kicker">Client Portal Overview</span>
            <h1 class="hero-title" style="font-size: clamp(2.4rem, 4vw, 3.8rem); margin-bottom: 0.4rem;">
              Welcome Back, <?php echo htmlspecialchars($customerName); ?>
            </h1>
            <p class="lead" style="font-size: 1.05rem;">
              Manage your upcoming celebrations, track booked services, and coordinate with the NAAQŚĦ studio.
            </p>
          </div>
          <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="/NAAQSH/customer/inspiration_board.php" class="btn btn-secondary">
              My Inspiration Board
            </a>
            <a href="/NAAQSH/public/plan_event.php" class="btn btn-primary">
              Plan A New Event &rarr;
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success" style="margin-bottom: 2rem;">
          <?php echo htmlspecialchars($flashSuccess); ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($flashError)): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;">
          <?php echo htmlspecialchars($flashError); ?>
        </div>
      <?php endif; ?>

      <!-- Summary Metric Cards -->
      <section class="dashboard-stats-grid" aria-label="Account Summary">
        <article class="dashboard-stat-card">
          <span class="stat-card-label">Total Celebrations</span>
          <div class="stat-card-value"><?php echo count($customerEvents); ?></div>
        </article>

        <article class="dashboard-stat-card">
          <span class="stat-card-label">Active Booked Services</span>
          <div class="stat-card-value"><?php echo count($customerBookings); ?></div>
        </article>

        <article class="dashboard-stat-card">
          <span class="stat-card-label">Total Estimated Budget</span>
          <div class="stat-card-value">PKR <?php echo number_format($totalBudget, 2); ?></div>
        </article>
      </section>

      <!-- Section: My Planned Events -->
      <section style="margin-bottom: 4rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
          <div>
            <span class="section-kicker">Timeline & Schedule</span>
            <h2 style="font-size: 2.2rem; margin-bottom: 0;">My Planned Events</h2>
          </div>
          <a href="/NAAQSH/public/plan_event.php" class="btn btn-primary btn-sm">+ Plan Another Event</a>
        </div>

        <?php if (empty($customerEvents)): ?>
          <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 3rem; text-align: center;">
            <p class="lead" style="margin-bottom: 1.5rem;">You currently have no events planned with NAAQŚĦ.</p>
            <a href="/NAAQSH/public/plan_event.php" class="btn btn-primary">+ Plan Another Event</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Event Name</th>
                  <th>Event Type</th>
                  <th>Event Date</th>
                  <th>Venue</th>
                  <th>Number of Guests</th>
                  <th>Budget</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($customerEvents as $ev): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($ev['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars(ucfirst($ev['event_type'])); ?></td>
                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($ev['event_date']))); ?></td>
                    <td><?php echo htmlspecialchars($ev['venue'] ?? 'To Be Confirmed'); ?></td>
                    <td><?php echo number_format((int)$ev['guest_count']); ?></td>
                    <td>PKR <?php echo number_format((float)$ev['budget'], 2); ?></td>
                    <td>
                      <span class="status-badge status-<?php echo htmlspecialchars(strtolower($ev['status'])); ?>">
                        <?php echo htmlspecialchars(ucfirst($ev['status'])); ?>
                      </span>
                    </td>
                    <td>
                      <span style="color: var(--color-muted); font-size: 0.85rem;">View</span>
                      <span style="color: var(--color-border); margin: 0 0.25rem;">|</span>
                      <a href="/NAAQSH/customer/event_edit.php?id=<?php echo (int)$ev['id']; ?>" style="color: var(--color-charcoal); font-weight: 600; text-decoration: underline; font-size: 0.85rem;">Edit</a>
                      <span style="color: var(--color-border); margin: 0 0.25rem;">|</span>
                      <form method="post" action="/NAAQSH/customer/dashboard.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                        <input type="hidden" name="action" value="delete_event">
                        <input type="hidden" name="event_id" value="<?php echo (int)$ev['id']; ?>">
                        <button type="submit" style="background: none; border: none; padding: 0; color: #dc2626; font-weight: 600; font-size: 0.85rem; cursor: pointer; text-decoration: underline;">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <!-- Section: Your Booked Services -->
      <section>
        <div style="margin-bottom: 1.5rem;">
          <span class="section-kicker">Service Deliverables</span>
          <h2 style="font-size: 2.2rem; margin-bottom: 0;">Booked Studio Services</h2>
        </div>

        <?php if (empty($customerBookings)): ?>
          <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 3rem; text-align: center;">
            <p class="lead" style="margin-bottom: 1.5rem;">No services have been booked for your events yet.</p>
            <a href="/NAAQSH/public/services.php" class="btn btn-secondary">Explore Signature Services</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Service Package</th>
                  <th>Assigned Event</th>
                  <th>Quantity</th>
                  <th>Total Cost</th>
                  <th>Status</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($customerBookings as $b): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($b['service_title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($b['event_title']); ?></td>
                    <td><?php echo (int)$b['quantity']; ?></td>
                    <td>PKR <?php echo number_format((float)$b['total_price'], 2); ?></td>
                    <td>
                      <span class="status-badge status-<?php echo htmlspecialchars(strtolower($b['status'])); ?>">
                        <?php echo htmlspecialchars(ucfirst($b['status'])); ?>
                      </span>
                    </td>
                    <td><small style="color: var(--color-muted);"><?php echo htmlspecialchars($b['notes'] ?? '—'); ?></small></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
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

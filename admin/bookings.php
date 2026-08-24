<?php
// Protected Admin Bookings Management for NAAQŚĦ
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

// Handle POST actions (Update Status, Delete Booking)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $newStatus = trim((string)($_POST['status'] ?? ''));
        $allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

        if ($bookingId > 0 && in_array($newStatus, $allowedStatuses, true)) {
            $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $bookingId]);
            $_SESSION['flash_success'] = "Booking #{$bookingId} status updated to '" . ucfirst($newStatus) . "'.";
        } else {
            $_SESSION['flash_error'] = 'Invalid status update request.';
        }

        header('Location: /NAAQSH/admin/bookings.php');
        exit;
    }

    if ($action === 'delete_booking') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);

        if ($bookingId > 0) {
            $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
            $stmt->execute([$bookingId]);
            $_SESSION['flash_success'] = "Booking #{$bookingId} deleted successfully.";
        } else {
            $_SESSION['flash_error'] = 'Invalid deletion request.';
        }

        header('Location: /NAAQSH/admin/bookings.php');
        exit;
    }
}

// Status Filter parameter
$statusFilter = trim((string)($_GET['status'] ?? ''));
$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];

$sql = '
    SELECT 
        b.id,
        b.user_id,
        b.event_id,
        b.service_id,
        b.quantity,
        b.total_price,
        b.status,
        b.notes,
        b.created_at,
        u.full_name AS customer_name,
        u.email AS customer_email,
        u.phone AS customer_phone,
        e.title AS event_title,
        e.event_type,
        e.event_date,
        e.venue AS event_venue,
        s.title AS service_title,
        s.price AS service_unit_price
    FROM bookings b
    INNER JOIN users u ON u.id = b.user_id
    INNER JOIN events e ON e.id = b.event_id
    INNER JOIN services s ON s.id = b.service_id
';

$params = [];
if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= ' WHERE b.status = ? ';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY b.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookingsList = $stmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Status pill color mapping
function getStatusBadgeStyle($status) {
    switch ($status) {
        case 'confirmed':
            return 'background: var(--color-champagne-soft); color: var(--color-charcoal); border: 1px solid var(--color-champagne-light);';
        case 'completed':
            return 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;';
        case 'cancelled':
            return 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;';
        default: // pending
            return 'background: #fefce8; color: #854d0e; border: 1px solid #fef08a;';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Bookings — NAAQŚĦ Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/NAAQSH/public/assets/css/style.css">
</head>
<body class="site-body">

  <header class="site-header">
    <div class="container header-inner">
      <a href="/NAAQSH/admin/dashboard.php" class="brand-lockup">
        <span class="brand-mark">N</span>
        <span class="brand-text">
          <span class="brand-wordmark">NAAQŚĦ</span>
          <span class="brand-tagline">Admin Management</span>
        </span>
      </a>

      <nav class="main-nav">
        <a href="/NAAQSH/admin/dashboard.php" class="nav-link">Dashboard</a>
        <a href="/NAAQSH/admin/events.php" class="nav-link">Events</a>
        <a href="/NAAQSH/admin/services.php" class="nav-link">Services</a>
        <a href="/NAAQSH/admin/gallery.php" class="nav-link">Gallery</a>
        <a href="/NAAQSH/admin/team.php" class="nav-link">Team</a>
        <a href="/NAAQSH/admin/bookings.php" class="nav-link active">Bookings</a>
        <a href="/NAAQSH/public/index.php" class="nav-link" target="_blank">View Site &nearr;</a>
        
        <div class="nav-actions">
          <a href="/NAAQSH/admin/logout.php" class="btn btn-secondary btn-sm">Sign Out</a>
        </div>
      </nav>
    </div>
  </header>

  <main class="page-shell" style="padding: 3rem 0 5rem;">
    <div class="container">
      
      <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1.5rem;">
        <div>
          <span class="section-kicker">Client Reservations</span>
          <h1 class="hero-title" style="font-size: 2.8rem; margin-bottom: 0;">Manage Bookings</h1>
        </div>

        <!-- Filter Dropdown -->
        <form method="get" action="/NAAQSH/admin/bookings.php" style="display: flex; align-items: center; gap: 0.5rem;">
          <label for="status" style="font-size: 0.85rem; font-weight: 700; color: var(--color-muted);">Filter Status:</label>
          <select id="status" name="status" onchange="this.form.submit()" style="padding: 0.6rem 1rem; font-family: var(--font-sans); border: 1px solid var(--color-border); background: var(--color-surface); border-radius: 4px;">
            <option value="">All Statuses</option>
            <option value="pending" <?php echo ($statusFilter === 'pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="confirmed" <?php echo ($statusFilter === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
            <option value="completed" <?php echo ($statusFilter === 'completed') ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled" <?php echo ($statusFilter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
          </select>
        </form>
      </div>

      <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
          <?php echo htmlspecialchars($flashSuccess); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($flashError)): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
          <?php echo htmlspecialchars($flashError); ?>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Event Details</th>
              <th>Service Package</th>
              <th>Booking Date</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookingsList)): ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--color-muted);">
                  No bookings found <?php echo $statusFilter ? 'matching status "' . htmlspecialchars($statusFilter) . '"' : 'in database'; ?>.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($bookingsList as $b): ?>
                <tr>
                  <td><strong>#<?php echo (int)$b['id']; ?></strong></td>
                  <td>
                    <strong style="display: block; color: var(--color-charcoal);"><?php echo htmlspecialchars($b['customer_name']); ?></strong>
                    <span style="font-size: 0.82rem; color: var(--color-muted);"><?php echo htmlspecialchars($b['customer_email']); ?></span>
                    <?php if (!empty($b['customer_phone'])): ?>
                      <span style="font-size: 0.82rem; color: var(--color-muted); display: block;"><?php echo htmlspecialchars($b['customer_phone']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong style="display: block; color: var(--color-charcoal);"><?php echo htmlspecialchars($b['event_title']); ?></strong>
                    <span style="font-size: 0.82rem; color: var(--color-muted);">
                      <?php echo htmlspecialchars(ucfirst($b['event_type'])); ?> &bull; <?php echo date('d M Y', strtotime($b['event_date'])); ?>
                    </span>
                    <?php if (!empty($b['event_venue'])): ?>
                      <span style="font-size: 0.78rem; color: var(--color-muted); display: block;"><?php echo htmlspecialchars($b['event_venue']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong style="display: block; color: var(--color-charcoal);"><?php echo htmlspecialchars($b['service_title']); ?></strong>
                    <span style="font-size: 0.82rem; color: var(--color-muted);">Qty: <?php echo (int)$b['quantity']; ?></span>
                  </td>
                  <td>
                    <span style="font-size: 0.88rem;"><?php echo date('d M Y', strtotime($b['created_at'])); ?></span>
                    <span style="font-size: 0.78rem; color: var(--color-muted); display: block;"><?php echo date('h:i A', strtotime($b['created_at'])); ?></span>
                  </td>
                  <td>
                    <strong style="font-size: 1rem; color: var(--color-charcoal);">
                      PKR <?php echo number_format((float)$b['total_price'], 2); ?>
                    </strong>
                  </td>
                  <td>
                    <span class="event-type-pill" style="<?php echo getStatusBadgeStyle($b['status']); ?> margin: 0;">
                      <?php echo htmlspecialchars(ucfirst($b['status'])); ?>
                    </span>
                  </td>
                  <td>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem; min-width: 140px;">
                      <!-- Quick Status Change Form -->
                      <form method="post" action="/NAAQSH/admin/bookings.php">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="font-size: 0.78rem; padding: 0.35rem 0.5rem; border: 1px solid var(--color-border); background: var(--color-surface); cursor: pointer; width: 100%;">
                          <option value="pending" <?php echo ($b['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                          <option value="confirmed" <?php echo ($b['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                          <option value="completed" <?php echo ($b['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                          <option value="cancelled" <?php echo ($b['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                      </form>

                      <!-- Delete Booking Form -->
                      <form method="post" action="/NAAQSH/admin/bookings.php" onsubmit="return confirm('Are you sure you want to delete this booking reservation?');">
                        <input type="hidden" name="action" value="delete_booking">
                        <input type="hidden" name="booking_id" value="<?php echo (int)$b['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%; color: #b00020; border-color: rgba(176,0,32,0.2); background: none; font-size: 0.75rem; padding: 0.25rem 0.5rem; cursor: pointer;">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-bottom">
      <div class="container">
        <p>&copy; <?php echo date('Y'); ?> NAAQŚĦ. Admin Management Portal.</p>
      </div>
    </div>
  </footer>

  <script src="/NAAQSH/public/assets/js/main.js"></script>
</body>
</html>

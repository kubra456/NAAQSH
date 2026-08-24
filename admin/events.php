<?php
// Admin: Manage Customer Events (view, edit, status changes, delete)
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();
$errors = [];
$allowedStatuses = ['draft', 'confirmed', 'completed', 'cancelled'];

// Handle quick status change
if (isset($_GET['set_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $newStatus = $_GET['set_status'];
    if (in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE events SET status = ? WHERE id = ?');
        $stmt->execute([$newStatus, $id]);
    }
    header('Location: /NAAQSH/admin/events.php');
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: /NAAQSH/admin/events.php');
    exit;
}

// Handle edit submission
$editEvent = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $eventType = trim($_POST['event_type'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $guestCount = max(0, (int)($_POST['guest_count'] ?? 0));
    $budget = max(0.0, floatval($_POST['budget'] ?? 0));
    $status = $_POST['status'] ?? 'draft';
    $notes = trim($_POST['notes'] ?? '');

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if ($eventDate === '') {
        $errors[] = 'Event date is required.';
    }
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'draft';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE events
             SET title = ?, event_type = ?, event_date = ?, venue = ?, guest_count = ?, budget = ?, status = ?, notes = ?
             WHERE id = ?'
        );
        $stmt->execute([$title, $eventType, $eventDate, $venue ?: null, $guestCount, $budget, $status, $notes ?: null, $id]);
        header('Location: /NAAQSH/admin/events.php');
        exit;
    }
}

// Check if an event is currently being edited
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$editId]);
    $editEvent = $stmt->fetch();
}

// Fetch all events with customer info and linked bookings count
$stmt = $pdo->query('
    SELECT e.*, u.full_name AS customer_name, u.email AS customer_email,
           (SELECT COUNT(*) FROM bookings b WHERE b.event_id = e.id) AS booking_count
    FROM events e
    INNER JOIN users u ON u.id = e.user_id
    ORDER BY e.event_date DESC
');
$events = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Events — NAAQŚĦ Admin</title>
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
        <a href="/NAAQSH/admin/events.php" class="nav-link active">Events</a>
        <a href="/NAAQSH/admin/services.php" class="nav-link">Services</a>
        <a href="/NAAQSH/admin/gallery.php" class="nav-link">Gallery</a>
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
          <span class="section-kicker">Client Celebrations</span>
          <h1 class="hero-title" style="font-size: 2.8rem; margin-bottom: 0;">Manage Events</h1>
        </div>
        <a href="/NAAQSH/admin/dashboard.php" class="btn btn-secondary">&larr; Return to Dashboard</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars(implode(' ', $errors)); ?>
        </div>
      <?php endif; ?>

      <!-- Edit Event Modal/Card -->
      <?php if ($editEvent): ?>
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 2.5rem; box-shadow: var(--shadow-md); margin-bottom: 3rem;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 2rem; margin: 0;">Edit Event #<?php echo (int)$editEvent['id']; ?></h2>
            <a href="/NAAQSH/admin/events.php" style="font-size: 0.85rem; color: var(--color-muted); text-decoration: underline;">Close Edit Mode</a>
          </div>

          <form method="post" action="/NAAQSH/admin/events.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo (int)$editEvent['id']; ?>">

            <div class="form-grid">
              <div class="form-group">
                <label for="title">Event Title *</label>
                <input id="title" name="title" type="text" value="<?php echo htmlspecialchars($editEvent['title']); ?>" required>
              </div>

              <div class="form-group">
                <label for="event_type">Event Type</label>
                <input id="event_type" name="event_type" type="text" value="<?php echo htmlspecialchars($editEvent['event_type']); ?>" placeholder="e.g. wedding, corporate, nikah">
              </div>

              <div class="form-group">
                <label for="event_date">Event Date *</label>
                <input id="event_date" name="event_date" type="date" value="<?php echo htmlspecialchars($editEvent['event_date']); ?>" required>
              </div>

              <div class="form-group">
                <label for="venue">Venue Location</label>
                <input id="venue" name="venue" type="text" value="<?php echo htmlspecialchars($editEvent['venue'] ?? ''); ?>" placeholder="e.g. Lahore Grand Hall">
              </div>

              <div class="form-group">
                <label for="guest_count">Estimated Guests</label>
                <input id="guest_count" name="guest_count" type="number" min="0" value="<?php echo (int)$editEvent['guest_count']; ?>">
              </div>

              <div class="form-group">
                <label for="budget">Allocated Budget (PKR)</label>
                <input id="budget" name="budget" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($editEvent['budget']); ?>">
              </div>

              <div class="form-group">
                <label for="status">Event Status</label>
                <select id="status" name="status">
                  <?php foreach ($allowedStatuses as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo $editEvent['status'] === $st ? 'selected' : ''; ?>>
                      <?php echo ucfirst($st); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group full-width">
                <label for="notes">Planning Notes & Requirements</label>
                <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($editEvent['notes'] ?? ''); ?></textarea>
              </div>
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1.5rem;">
              <button type="submit" class="btn btn-primary">Save Event Changes</button>
              <a href="/NAAQSH/admin/events.php" class="btn btn-secondary">Cancel</a>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <!-- All Events Table -->
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
          <h2 style="font-size: 2rem; margin: 0;">Scheduled Events List (<?php echo count($events); ?>)</h2>
        </div>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Client / Account</th>
                <th>Type</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Guests</th>
                <th>Budget</th>
                <th>Status</th>
                <th>Bookings</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($events)): ?>
                <tr><td colspan="11" style="text-align: center; padding: 2rem;">No events scheduled.</td></tr>
              <?php else: ?>
                <?php foreach ($events as $ev): ?>
                  <tr>
                    <td><?php echo (int)$ev['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($ev['title']); ?></strong></td>
                    <td>
                      <?php echo htmlspecialchars($ev['customer_name']); ?><br>
                      <small style="color: var(--color-muted);"><?php echo htmlspecialchars($ev['customer_email']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars(ucfirst($ev['event_type'])); ?></td>
                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($ev['event_date']))); ?></td>
                    <td><?php echo htmlspecialchars($ev['venue'] ?? '—'); ?></td>
                    <td><?php echo number_format((int)$ev['guest_count']); ?></td>
                    <td>PKR <?php echo number_format((float)$ev['budget'], 2); ?></td>
                    <td>
                      <span class="status-badge status-<?php echo htmlspecialchars(strtolower($ev['status'])); ?>">
                        <?php echo htmlspecialchars(ucfirst($ev['status'])); ?>
                      </span>
                      <div style="font-size: 0.72rem; margin-top: 4px; display: flex; gap: 4px; flex-wrap: wrap;">
                        <?php foreach ($allowedStatuses as $st): ?>
                          <?php if ($st !== $ev['status']): ?>
                            <a href="/NAAQSH/admin/events.php?id=<?php echo (int)$ev['id']; ?>&set_status=<?php echo $st; ?>" style="text-decoration: underline; color: var(--color-charcoal);"><?php echo ucfirst($st); ?></a>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    </td>
                    <td style="text-align: center;">
                      <strong><?php echo (int)$ev['booking_count']; ?></strong>
                    </td>
                    <td>
                      <div style="display: flex; gap: 0.5rem;">
                        <a href="/NAAQSH/admin/events.php?edit=<?php echo (int)$ev['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="/NAAQSH/admin/events.php?delete=<?php echo (int)$ev['id']; ?>" class="btn btn-secondary btn-sm" onclick="return confirm('WARNING: Deleting this event will permanently delete all linked bookings. Proceed?')" style="color: #b00020; border-color: rgba(176,0,32,0.2);">Delete</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="footer-bottom">
      <div class="container">
        <p>&copy; <?php echo date('Y'); ?> NAAQŚĦ. Admin Portal.</p>
      </div>
    </div>
  </footer>

  <script src="/NAAQSH/public/assets/js/main.js"></script>
</body>
</html>

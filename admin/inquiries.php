<?php
// Protected Admin Inquiries Management for NAAQŚĦ
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

// Handle POST actions (Update Status, Delete Inquiry)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $inquiryId = (int)($_POST['inquiry_id'] ?? 0);
        $newStatus = trim((string)($_POST['status'] ?? ''));
        $allowedStatuses = ['new', 'replied', 'closed'];

        if ($inquiryId > 0 && in_array($newStatus, $allowedStatuses, true)) {
            $stmt = $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $inquiryId]);
            $_SESSION['flash_success'] = "Inquiry #{$inquiryId} status updated to '" . ucfirst($newStatus) . "'.";
        } else {
            $_SESSION['flash_error'] = 'Invalid status update request.';
        }

        header('Location: /NAAQSH/admin/inquiries.php');
        exit;
    }

    if ($action === 'delete_inquiry') {
        $inquiryId = (int)($_POST['inquiry_id'] ?? 0);

        if ($inquiryId > 0) {
            $stmt = $pdo->prepare('DELETE FROM inquiries WHERE id = ?');
            $stmt->execute([$inquiryId]);
            $_SESSION['flash_success'] = "Inquiry #{$inquiryId} deleted successfully.";
        } else {
            $_SESSION['flash_error'] = 'Invalid deletion request.';
        }

        header('Location: /NAAQSH/admin/inquiries.php');
        exit;
    }
}

// Read Full Message parameter
$viewInquiry = null;
if (isset($_GET['view'])) {
    $viewId = (int)$_GET['view'];
    $viewStmt = $pdo->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
    $viewStmt->execute([$viewId]);
    $viewInquiry = $viewStmt->fetch();
}

// Status Filter parameter
$statusFilter = trim((string)($_GET['status'] ?? ''));
$allowedStatuses = ['new', 'replied', 'closed'];

$sql = 'SELECT * FROM inquiries';
$params = [];

if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= ' WHERE status = ? ';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inquiriesList = $stmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Status badge helper
function getInquiryBadgeStyle($status) {
    switch ($status) {
        case 'replied':
            return 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;';
        case 'closed':
            return 'background: var(--color-bg-alt); color: var(--color-muted); border: 1px solid var(--color-border);';
        default: // new
            return 'background: #fefce8; color: #854d0e; border: 1px solid #fef08a;';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Inquiries — NAAQŚĦ Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/NAAQSH/public/assets/css/style.css">
  <style>
    .message-modal {
      display: <?php echo $viewInquiry ? 'flex' : 'none'; ?>;
      position: fixed;
      z-index: 9999;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(4px);
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .message-modal-card {
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      max-width: 650px;
      width: 100%;
      padding: 2rem;
      box-shadow: var(--shadow-lg);
      border-radius: 4px;
      position: relative;
    }
  </style>
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
        <a href="/NAAQSH/admin/bookings.php" class="nav-link">Bookings</a>
        <a href="/NAAQSH/admin/inquiries.php" class="nav-link active">Inquiries</a>
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
          <span class="section-kicker">Client Communications</span>
          <h1 class="hero-title" style="font-size: 2.8rem; margin-bottom: 0;">Contact Inquiries</h1>
        </div>

        <!-- Status Filter Dropdown -->
        <form method="get" action="/NAAQSH/admin/inquiries.php" style="display: flex; align-items: center; gap: 0.5rem;">
          <label for="status" style="font-size: 0.85rem; font-weight: 700; color: var(--color-muted);">Filter Status:</label>
          <select id="status" name="status" onchange="this.form.submit()" style="padding: 0.6rem 1rem; font-family: var(--font-sans); border: 1px solid var(--color-border); background: var(--color-surface); border-radius: 4px;">
            <option value="">All Inquiries</option>
            <option value="new" <?php echo ($statusFilter === 'new') ? 'selected' : ''; ?>>New</option>
            <option value="replied" <?php echo ($statusFilter === 'replied') ? 'selected' : ''; ?>>Replied</option>
            <option value="closed" <?php echo ($statusFilter === 'closed') ? 'selected' : ''; ?>>Closed</option>
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

      <!-- Inquiries Data Table -->
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Subject / Service</th>
              <th>Message Snippet</th>
              <th>Received</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($inquiriesList)): ?>
              <tr>
                <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--color-muted);">
                  No inquiries found <?php echo $statusFilter ? 'matching status "' . htmlspecialchars($statusFilter) . '"' : 'in database'; ?>.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($inquiriesList as $inq): ?>
                <tr>
                  <td><strong>#<?php echo (int)$inq['id']; ?></strong></td>
                  <td>
                    <strong style="display: block; color: var(--color-charcoal);"><?php echo htmlspecialchars($inq['full_name']); ?></strong>
                    <a href="mailto:<?php echo htmlspecialchars($inq['email']); ?>" style="font-size: 0.82rem; color: var(--color-muted); text-decoration: underline;">
                      <?php echo htmlspecialchars($inq['email']); ?>
                    </a>
                    <?php if (!empty($inq['phone'])): ?>
                      <span style="font-size: 0.82rem; color: var(--color-muted); display: block;"><?php echo htmlspecialchars($inq['phone']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?php echo htmlspecialchars($inq['subject'] ?: 'General Inquiry'); ?></strong>
                  </td>
                  <td style="max-width: 250px; font-size: 0.85rem; color: var(--color-muted);">
                    <?php echo htmlspecialchars(mb_strimwidth($inq['message'], 0, 75, '...')); ?>
                  </td>
                  <td>
                    <span style="font-size: 0.88rem;"><?php echo date('d M Y', strtotime($inq['created_at'])); ?></span>
                    <span style="font-size: 0.78rem; color: var(--color-muted); display: block;"><?php echo date('h:i A', strtotime($inq['created_at'])); ?></span>
                  </td>
                  <td>
                    <span class="event-type-pill" style="<?php echo getInquiryBadgeStyle($inq['status']); ?> margin: 0;">
                      <?php echo htmlspecialchars(ucfirst($inq['status'])); ?>
                    </span>
                  </td>
                  <td>
                    <div style="display: flex; flex-direction: column; gap: 0.4rem; min-width: 140px;">
                      <!-- Open / Read Message Button -->
                      <a href="/NAAQSH/admin/inquiries.php?view=<?php echo (int)$inq['id']; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-secondary btn-sm" style="text-align: center; font-size: 0.75rem; padding: 0.25rem 0.5rem;">Read Message</a>

                      <!-- Status Dropdown Form -->
                      <form method="post" action="/NAAQSH/admin/inquiries.php">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="inquiry_id" value="<?php echo (int)$inq['id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="font-size: 0.78rem; padding: 0.35rem 0.5rem; border: 1px solid var(--color-border); background: var(--color-surface); cursor: pointer; width: 100%;">
                          <option value="new" <?php echo ($inq['status'] === 'new') ? 'selected' : ''; ?>>New</option>
                          <option value="replied" <?php echo ($inq['status'] === 'replied') ? 'selected' : ''; ?>>Replied</option>
                          <option value="closed" <?php echo ($inq['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
                        </select>
                      </form>

                      <!-- Delete Inquiry Form -->
                      <form method="post" action="/NAAQSH/admin/inquiries.php" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
                        <input type="hidden" name="action" value="delete_inquiry">
                        <input type="hidden" name="inquiry_id" value="<?php echo (int)$inq['id']; ?>">
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

  <!-- Full Message Modal View -->
  <?php if ($viewInquiry): ?>
    <div class="message-modal">
      <div class="message-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--color-border); padding-bottom: 1rem; margin-bottom: 1.25rem;">
          <div>
            <span class="section-kicker" style="font-size: 0.72rem;">Inquiry #<?php echo (int)$viewInquiry['id']; ?></span>
            <h2 style="font-size: 1.6rem; margin: 0; color: var(--color-charcoal);"><?php echo htmlspecialchars($viewInquiry['subject'] ?: 'General Inquiry'); ?></h2>
          </div>
          <a href="/NAAQSH/admin/inquiries.php<?php echo $statusFilter ? '?status=' . urlencode($statusFilter) : ''; ?>" style="font-size: 1.6rem; line-height: 1; text-decoration: none; color: var(--color-muted);">&times;</a>
        </div>

        <div style="background: var(--color-bg); border: 1px solid var(--color-border); padding: 1.25rem; margin-bottom: 1.25rem; font-size: 0.9rem;">
          <div style="margin-bottom: 0.4rem;">
            <strong style="color: var(--color-muted);">From:</strong>
            <span style="font-weight: 700; color: var(--color-charcoal);"><?php echo htmlspecialchars($viewInquiry['full_name']); ?></span>
          </div>
          <div style="margin-bottom: 0.4rem;">
            <strong style="color: var(--color-muted);">Email:</strong>
            <a href="mailto:<?php echo htmlspecialchars($viewInquiry['email']); ?>" style="color: var(--color-charcoal); text-decoration: underline;"><?php echo htmlspecialchars($viewInquiry['email']); ?></a>
          </div>
          <?php if (!empty($viewInquiry['phone'])): ?>
            <div style="margin-bottom: 0.4rem;">
              <strong style="color: var(--color-muted);">Phone:</strong>
              <span><?php echo htmlspecialchars($viewInquiry['phone']); ?></span>
            </div>
          <?php endif; ?>
          <div>
            <strong style="color: var(--color-muted);">Received:</strong>
            <span><?php echo date('d F Y \a\t h:i A', strtotime($viewInquiry['created_at'])); ?></span>
          </div>
        </div>

        <div style="margin-bottom: 1.75rem;">
          <h4 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-muted); margin-bottom: 0.5rem;">Message Content</h4>
          <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 1.25rem; font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap; color: var(--color-charcoal);">
            <?php echo htmlspecialchars($viewInquiry['message']); ?>
          </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px solid var(--color-border); padding-top: 1.25rem;">
          <a href="mailto:<?php echo htmlspecialchars($viewInquiry['email']); ?>?subject=Re: <?php echo urlencode($viewInquiry['subject'] ?: 'NAAQSH Inquiry'); ?>" class="btn btn-primary" style="font-size: 0.8rem;">Reply via Email &rarr;</a>
          <a href="/NAAQSH/admin/inquiries.php<?php echo $statusFilter ? '?status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-secondary" style="font-size: 0.8rem;">Close Modal</a>
        </div>
      </div>
    </div>
  <?php endif; ?>

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

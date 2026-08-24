<?php
// Protected admin dashboard.
// This page reads live statistics from the database and presents them in a
// premium, responsive dashboard layout. The page requires admin auth before any
// content is rendered.
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

// Dashboard counters are derived from the existing live database tables so the
// numbers reflect the actual application state without hardcoded placeholders.
$stats = [
    'services' => 0,
    'events' => 0,
    'gallery' => 0,
    'team_members' => 0,
    'bookings' => 0,
    'inquiries' => 0,
];

foreach ($stats as $tableName => $value) {
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ' . $tableName);
    $stmt->execute();
    $row = $stmt->fetch();
    $stats[$tableName] = (int)($row['total'] ?? 0);
}

// Recent activity pulls the newest records from the tables that already contain
// timestamps or date fields, keeping the dashboard grounded in real data.
$recentBookings = $pdo->query(
    'SELECT b.id, b.total_price, b.status, b.created_at, u.full_name
     FROM bookings b
     INNER JOIN users u ON u.id = b.user_id
     ORDER BY b.created_at DESC
     LIMIT 4'
)->fetchAll();

$recentInquiries = $pdo->query(
    'SELECT id, full_name, subject, status, created_at
     FROM inquiries
     ORDER BY created_at DESC
     LIMIT 4'
)->fetchAll();

$recentEvents = $pdo->query(
    'SELECT id, title, event_type, event_date
     FROM events
     ORDER BY event_date DESC
     LIMIT 4'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>NAAQŚĦ Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f5efe8;
      --panel: rgba(255,255,255,0.76);
      --panel-strong: #fffdfb;
      --border: rgba(27, 27, 26, 0.10);
      --charcoal: #1b1b1a;
      --muted: #5f5a57;
      --rose: #b7887d;
      --rose-soft: rgba(183, 136, 125, 0.14);
      --success: #466e5a;
      --warning: #9f7d42;
      --danger: #8f4d4d;
      --shadow: 0 18px 40px rgba(35,30,27,0.08);
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: 'Manrope', Arial, sans-serif;
      background: linear-gradient(180deg, #f7f3ee 0%, #f2ebdf 100%);
      color: var(--charcoal);
    }

    a { color: inherit; text-decoration: none; }
    table { border-collapse: collapse; width: 100%; }
    th, td { text-align: left; }

    .admin-shell {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: 280px;
      background: rgba(27, 27, 26, 0.97);
      color: rgba(255,255,255,0.9);
      padding: 1.5rem 1.2rem;
      position: sticky;
      top: 0;
      height: 100vh;
    }

    .brand-box {
      display: flex;
      align-items: center;
      gap: 0.9rem;
      padding-bottom: 1.25rem;
      border-bottom: 1px solid rgba(255,255,255,0.12);
      margin-bottom: 1.5rem;
    }

    .brand-mark {
      width: 2.7rem;
      height: 2.7rem;
      display: grid;
      place-items: center;
      background: rgba(255,255,255,0.08);
      font-family: 'Cormorant Garamond', serif;
      font-size: 2rem;
      font-weight: 700;
    }

    .brand-wordmark {
      font-family: 'Cormorant Garamond', serif;
      font-size: 2rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
    }

    .sidebar-nav {
      display: flex;
      flex-direction: column;
      gap: 0.45rem;
    }

    .nav-link {
      padding: 0.8rem 0.9rem;
      border-radius: 10px;
      color: rgba(255,255,255,0.78);
      transition: background 0.2s ease, color 0.2s ease;
      font-weight: 600;
    }

    .nav-link:hover,
    .nav-link.active {
      background: rgba(255,255,255,0.08);
      color: var(--white);
    }

    .user-box {
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid rgba(255,255,255,0.12);
      color: rgba(255,255,255,0.85);
    }

    .content {
      flex: 1;
      padding: 2rem;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .page-title {
      margin: 0;
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.5rem, 2vw + 1.2rem, 3.4rem);
      line-height: 1;
    }

    .header-actions {
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: var(--panel);
      border: 1px solid var(--border);
      padding: 0.7rem 1rem;
      box-shadow: var(--shadow);
      font-size: 0.8rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-height: 42px;
      padding: 0.75rem 1.2rem;
      border: 1px solid var(--border);
      background: var(--charcoal);
      color: white;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      font-size: 0.7rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--panel);
      border: 1px solid var(--border);
      padding: 1.2rem 1.3rem;
      box-shadow: var(--shadow);
    }

    .stat-label {
      color: var(--muted);
      font-size: 0.74rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      margin-bottom: 0.7rem;
      display: block;
    }

    .stat-value {
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.2rem, 2vw + 1rem, 3rem);
      line-height: 1;
      margin-bottom: 0.2rem;
    }

    .board-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1rem;
    }

    .panel {
      background: var(--panel-strong);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      padding: 1.2rem;
    }

    .panel h3 {
      margin: 0 0 1rem;
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2rem, 1.5vw + 1rem, 2.6rem);
      line-height: 1;
    }

    .list {
      display: flex;
      flex-direction: column;
      gap: 0.9rem;
    }

    .list-item {
      padding: 0.9rem 0;
      border-bottom: 1px solid var(--border);
    }

    .list-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .item-head {
      display: flex;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 0.25rem;
      align-items: center;
    }

    .status-badge {
      display: inline-flex;
      padding: 0.3rem 0.55rem;
      font-size: 0.68rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      background: var(--rose-soft);
      border: 1px solid rgba(183, 136, 125, 0.15);
      color: var(--charcoal);
    }

    .muted {
      color: var(--muted);
      font-size: 0.82rem;
    }

    @media (max-width: 980px) {
      .admin-shell { display: block; }
      .sidebar {
        width: 100%;
        height: auto;
        position: static;
      }
      .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .board-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
      .content { padding: 1rem; }
      .stats-grid { grid-template-columns: 1fr; }
      .topbar { flex-direction: column; align-items: flex-start; }
      .header-actions { width: 100%; justify-content: space-between; }
    }
  </style>
</head>
<body>
  <div class="admin-shell">
    <aside class="sidebar">
      <div class="brand-box">
        <div class="brand-mark">N</div>
        <div class="brand-wordmark">NAAQŚĦ</div>
      </div>

      <nav class="sidebar-nav" aria-label="Admin menu">
        <a class="nav-link active" href="/NAAQSH/admin/dashboard.php">Dashboard</a>
        <a class="nav-link" href="/NAAQSH/admin/services.php">Services</a>
        <a class="nav-link" href="/NAAQSH/admin/events.php">Events</a>
        <a class="nav-link" href="/NAAQSH/admin/gallery.php">Gallery</a>
        <a class="nav-link" href="/NAAQSH/admin/team.php">Team</a>
        <a class="nav-link" href="/NAAQSH/admin/bookings.php">Bookings</a>
        <a class="nav-link" href="/NAAQSH/admin/inquiries.php">Inquiries</a>
        <a class="nav-link" href="/NAAQSH/admin/logout.php">Logout</a>
      </nav>

      <div class="user-box">
        <div>Logged in</div>
        <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong>
      </div>
    </aside>

    <main class="content">
      <div class="topbar">
        <h1 class="page-title">Admin Dashboard</h1>
        <div class="header-actions">
          <div class="pill">WELCOME NAAQSH ADMIN</div>
          <a class="button" href="/NAAQSH/admin/logout.php">Logout</a>
        </div>
      </div>

      <section class="stats-grid" aria-label="Dashboard statistics">
        <article class="stat-card">
          <span class="stat-label">Total Services</span>
          <div class="stat-value"><?php echo number_format($stats['services']); ?></div>
        </article>

        <article class="stat-card">
          <span class="stat-label">Total Events</span>
          <div class="stat-value"><?php echo number_format($stats['events']); ?></div>
        </article>

        <article class="stat-card">
          <span class="stat-label">Total Gallery Items</span>
          <div class="stat-value"><?php echo number_format($stats['gallery']); ?></div>
        </article>

        <article class="stat-card">
          <span class="stat-label">Total Team Members</span>
          <div class="stat-value"><?php echo number_format($stats['team_members']); ?></div>
        </article>

        <article class="stat-card">
          <span class="stat-label">Total Bookings</span>
          <div class="stat-value"><?php echo number_format($stats['bookings']); ?></div>
        </article>

        <article class="stat-card">
          <span class="stat-label">Total Inquiries</span>
          <div class="stat-value"><?php echo number_format($stats['inquiries']); ?></div>
        </article>
      </section>

      <section class="board-grid">
        <article class="panel">
          <h3>Recent Bookings</h3>
          <div class="list">
            <?php if (empty($recentBookings)): ?>
              <div class="muted">No recent booking records found.</div>
            <?php else: ?>
              <?php foreach ($recentBookings as $booking): ?>
                <div class="list-item">
                  <div class="item-head">
                    <strong><?php echo htmlspecialchars($booking['full_name']); ?></strong>
                    <span class="status-badge"><?php echo htmlspecialchars($booking['status']); ?></span>
                  </div>
                  <div class="muted">PKR <?php echo number_format((float)$booking['total_price'], 2); ?> · <?php echo htmlspecialchars(date('d M Y', strtotime($booking['created_at']))); ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </article>

        <article class="panel">
          <h3>Recent Inquiries</h3>
          <div class="list">
            <?php if (empty($recentInquiries)): ?>
              <div class="muted">No recent inquiries found.</div>
            <?php else: ?>
              <?php foreach ($recentInquiries as $inquiry): ?>
                <div class="list-item">
                  <div class="item-head">
                    <strong><?php echo htmlspecialchars($inquiry['full_name']); ?></strong>
                    <span class="status-badge"><?php echo htmlspecialchars($inquiry['status']); ?></span>
                  </div>
                  <div class="muted"><?php echo htmlspecialchars($inquiry['subject'] ?: 'General inquiry'); ?> · <?php echo htmlspecialchars(date('d M Y', strtotime($inquiry['created_at']))); ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </article>

        <article class="panel">
          <h3>Upcoming Events</h3>
          <div class="list">
            <?php if (empty($recentEvents)): ?>
              <div class="muted">No upcoming events found.</div>
            <?php else: ?>
              <?php foreach ($recentEvents as $event): ?>
                <div class="list-item">
                  <div class="item-head">
                    <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                    <span class="status-badge"><?php echo htmlspecialchars($event['event_type']); ?></span>
                  </div>
                  <div class="muted"><?php echo htmlspecialchars(date('d M Y', strtotime($event['event_date']))); ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </article>

        <article class="panel">
          <h3>Quick Actions</h3>
          <div class="list">
            <div class="list-item"><a href="/NAAQSH/admin/services.php">Manage Services</a></div>
            <div class="list-item"><a href="/NAAQSH/admin/service_create.php">Create a New Service</a></div>
            <div class="list-item"><a href="/NAAQSH/admin/inquiries.php">Review Inquiries</a></div>
            <div class="list-item"><a href="/NAAQSH/admin/logout.php">Log Out</a></div>
          </div>
        </article>
      </section>
    </main>
  </div>
</body>
</html>

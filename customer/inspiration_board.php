<?php
// Luxury Inspiration Board Display for NAAQŚĦ Client Portal
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['customer_id'])) {
    header('Location: /NAAQSH/customer/login.php');
    exit;
}

$pdo = getPDO();
$customerId = (int)$_SESSION['customer_id'];
$customerName = $_SESSION['customer_name'] ?? 'Valued Client';

// Handle POST Delete Action for Inspiration Images
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete_inspiration') {
    $inspirationId = (int)($_POST['inspiration_id'] ?? 0);
    $redirectEventId = (int)($_POST['event_id'] ?? 0);

    if ($inspirationId > 0) {
        // Enforce strict ownership verification via event relationship:
        // Verify event_inspirations.id = ? AND related events.user_id = logged-in customer ID
        $findStmt = $pdo->prepare('
            SELECT i.id, i.image_path, i.event_id
            FROM event_inspirations i
            INNER JOIN events e ON e.id = i.event_id
            WHERE i.id = ? AND e.user_id = ?
            LIMIT 1
        ');
        $findStmt->execute([$inspirationId, $customerId]);
        $inspirationRecord = $findStmt->fetch();

        if ($inspirationRecord) {
            $eventIdForRedirect = (int)$inspirationRecord['event_id'];
            $relPath = $inspirationRecord['image_path'];
            $physicalPath = __DIR__ . '/../' . $relPath;

            // 1. Delete database record
            $delStmt = $pdo->prepare('DELETE FROM event_inspirations WHERE id = ?');
            $delStmt->execute([$inspirationId]);

            // 2. Delete physical image file safely
            if (!empty($relPath) && file_exists($physicalPath)) {
                @unlink($physicalPath);
            }

            $_SESSION['flash_success'] = 'Inspiration image removed successfully.';
            header('Location: /NAAQSH/customer/inspiration_board.php?event_id=' . $eventIdForRedirect);
            exit;
        } else {
            $_SESSION['flash_error'] = 'Inspiration image not found or access denied.';
        }
    } else {
        $_SESSION['flash_error'] = 'Invalid deletion request.';
    }

    $redirectUrl = '/NAAQSH/customer/inspiration_board.php' . ($redirectEventId > 0 ? '?event_id=' . $redirectEventId : '');
    header('Location: ' . $redirectUrl);
    exit;
}

// Fetch events belonging strictly to logged-in customer
$eventsStmt = $pdo->prepare('
    SELECT id, title, event_type, event_date, venue
    FROM events
    WHERE user_id = ?
    ORDER BY event_date ASC
');
$eventsStmt->execute([$customerId]);
$customerEvents = $eventsStmt->fetchAll();

$error = '';
$currentEvent = null;
$selectedEventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($selectedEventId > 0) {
    // STRICT SECURITY: Verify events.id = requested ID AND events.user_id = logged-in customer ID
    $eventStmt = $pdo->prepare('
        SELECT * FROM events
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ');
    $eventStmt->execute([$selectedEventId, $customerId]);
    $currentEvent = $eventStmt->fetch();

    if (!$currentEvent) {
        $error = 'Event not found or access denied.';
    }
} elseif (!empty($customerEvents)) {
    // Default to customer's first event if no ID selected
    $currentEvent = $customerEvents[0];
}

// Retrieve inspiration photos ONLY if event ownership is verified
$inspirations = [];
if ($currentEvent) {
    $inspStmt = $pdo->prepare('
        SELECT * FROM event_inspirations
        WHERE event_id = ?
        ORDER BY created_at DESC
    ');
    $inspStmt->execute([(int)$currentEvent['id']]);
    $inspirations = $inspStmt->fetchAll();
}

// Define supported categories & metadata
$categories = [
    'Venue' => ['label' => 'Venue Inspiration', 'desc' => 'Architecture, layout, lighting, and venue aesthetics.'],
    'Decoration' => ['label' => 'Decoration Inspiration', 'desc' => 'Floral styling, centerpieces, mandap, and backdrops.'],
    'Food & Catering' => ['label' => 'Food & Catering Inspiration', 'desc' => 'Menu concepts, culinary stations, and dessert displays.'],
    'Dress & Bridal' => ['label' => 'Dress & Bridal Inspiration', 'desc' => 'Bridal couture, jewelry, color swatches, and attire.'],
    'Photography' => ['label' => 'Photography Inspiration', 'desc' => 'Editorial moodboard, pose concepts, and portrait lighting.'],
    'Makeup & Styling' => ['label' => 'Makeup & Styling Inspiration', 'desc' => 'Hair design, bridal glam, and aesthetic references.'],
    'Theme & Colors' => ['label' => 'Theme & Colors', 'desc' => 'Color palettes, moodboard swatches, and motif ideas.'],
    'Other' => ['label' => 'Other Inspiration', 'desc' => 'Custom bespoke details, favors, and additional ideas.']
];

// Group images into categories
$groupedInspirations = [];
foreach ($categories as $catKey => $meta) {
    $groupedInspirations[$catKey] = [];
}

foreach ($inspirations as $item) {
    $cat = $item['category'] ?? 'Other';
    if (!isset($groupedInspirations[$cat])) {
        $groupedInspirations['Other'][] = $item;
    } else {
        $groupedInspirations[$cat][] = $item;
    }
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
  <title>Inspiration Board — NAAQŚĦ Client Portal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/NAAQSH/public/assets/css/style.css">
  <style>
    .inspiration-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.5rem;
    }
    .inspiration-card {
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      border-radius: 4px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      display: flex;
      flex-direction: column;
    }
    .inspiration-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
      border-color: var(--color-champagne);
    }
    .inspiration-img-wrap {
      width: 100%;
      height: 220px;
      overflow: hidden;
      background: var(--color-bg-alt);
      position: relative;
      cursor: pointer;
    }
    .inspiration-img-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }
    .inspiration-card:hover .inspiration-img-wrap img {
      transform: scale(1.05);
    }
    .inspiration-card-body {
      padding: 0.85rem 1.15rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--color-surface);
      border-top: 1px solid var(--color-border-light);
    }
    .inspiration-badge {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--color-charcoal);
      background: var(--color-champagne-soft);
      padding: 0.3rem 0.55rem;
      border-radius: 2px;
    }
    .empty-category-box {
      background: var(--color-bg);
      border: 1px dashed var(--color-border);
      padding: 1.75rem;
      text-align: center;
      color: var(--color-muted);
      font-size: 0.9rem;
    }
    /* Lightbox Modal */
    .lightbox-modal {
      display: none;
      position: fixed;
      z-index: 9999;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(5px);
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .lightbox-modal.active {
      display: flex;
    }
    .lightbox-content {
      max-width: 90vw;
      max-height: 85vh;
      border-radius: 4px;
      box-shadow: var(--shadow-lg);
      object-fit: contain;
    }
    .lightbox-close {
      position: absolute;
      top: 1.5rem;
      right: 2rem;
      color: #ffffff;
      font-size: 2.5rem;
      cursor: pointer;
      line-height: 1;
    }
  </style>
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
        <a href="/NAAQSH/customer/inspiration_board.php" class="nav-link active">Inspiration Board</a>
        <a href="/NAAQSH/public/services.php" class="nav-link">Services</a>
        
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
            <span class="section-kicker">Client Portal &bull; Visual Moodboard</span>
            <h1 class="hero-title" style="font-size: clamp(2.2rem, 3.8vw, 3.6rem); margin-bottom: 0.4rem;">
              Event Inspiration Board
            </h1>
            <p class="lead" style="font-size: 1.05rem;">
              Curated visual references, venue decor, bridal concepts, and aesthetic direction for your celebrations.
            </p>
          </div>
          <div>
            <a href="/NAAQSH/customer/dashboard.php" class="btn btn-secondary">
              &larr; Return to Dashboard
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="container">

      <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success" style="margin-bottom: 2.5rem;">
          <?php echo htmlspecialchars($flashSuccess); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($flashError)): ?>
        <div class="alert alert-error" style="margin-bottom: 2.5rem;">
          <?php echo htmlspecialchars($flashError); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="margin-bottom: 2.5rem;">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <?php if (empty($customerEvents)): ?>
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 4rem 2rem; text-align: center; margin-top: 2rem;">
          <h2 style="font-size: 2rem; margin-bottom: 1rem;">No Events Found</h2>
          <p class="lead" style="margin-bottom: 2rem; color: var(--color-muted);">
            You have not registered any event plans with NAAQŚĦ yet. Plan an event to build your visual moodboard.
          </p>
          <a href="/NAAQSH/public/plan_event.php" class="btn btn-primary">Plan Your First Event &rarr;</a>
        </div>
      <?php else: ?>

        <!-- Event Selection Dropdown -->
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 1.75rem 2rem; margin-bottom: 3.5rem; box-shadow: var(--shadow-sm);">
          <form method="get" action="/NAAQSH/customer/inspiration_board.php" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.25rem;">
            <div>
              <label for="event_id" style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.12em; font-weight: 700; color: var(--color-muted); margin-bottom: 0.4rem;">
                Select an Event
              </label>
              <div style="position: relative; min-width: 300px;">
                <select id="event_id" name="event_id" onchange="this.form.submit()" style="width: 100%; padding: 0.85rem 1.25rem; font-family: var(--font-sans); font-size: 1rem; font-weight: 600; color: var(--color-charcoal); background: var(--color-bg); border: 1px solid var(--color-border); cursor: pointer; border-radius: 4px;">
                  <?php foreach ($customerEvents as $ev): ?>
                    <option value="<?php echo (int)$ev['id']; ?>" <?php echo ($currentEvent && $currentEvent['id'] == $ev['id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($ev['title']); ?> &mdash; <?php echo date('d F Y', strtotime($ev['event_date'])); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            
            <?php if ($currentEvent): ?>
              <div style="text-align: right;">
                <span class="section-kicker" style="font-size: 0.72rem; color: var(--color-champagne-gold);">Active Celebration</span>
                <div style="font-size: 1.15rem; font-family: var(--font-serif); font-weight: 700; color: var(--color-charcoal);">
                  <?php echo htmlspecialchars($currentEvent['title']); ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--color-muted);">
                  <?php echo htmlspecialchars(ucfirst($currentEvent['event_type'])); ?> &bull; <?php echo htmlspecialchars($currentEvent['venue'] ?? 'TBD'); ?>
                </div>
              </div>
            <?php endif; ?>
          </form>
        </div>

        <?php if ($currentEvent): ?>
          
          <!-- Category Sections -->
          <?php foreach ($categories as $catKey => $meta): ?>
            <section style="margin-bottom: 4rem;">
              <div style="border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem; margin-bottom: 1.75rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                  <span class="section-kicker" style="font-size: 0.72rem;"><?php echo htmlspecialchars($meta['label']); ?></span>
                  <h2 style="font-size: 1.9rem; margin: 0; color: var(--color-charcoal);"><?php echo htmlspecialchars($catKey); ?></h2>
                </div>
                <span style="font-size: 0.82rem; color: var(--color-muted);"><?php echo count($groupedInspirations[$catKey]); ?> Image(s)</span>
              </div>

              <?php if (empty($groupedInspirations[$catKey])): ?>
                <div class="empty-category-box">
                  <p style="margin: 0;">No inspiration images uploaded yet for <strong><?php echo htmlspecialchars($catKey); ?></strong>.</p>
                </div>
              <?php else: ?>
                <div class="inspiration-grid">
                  <?php foreach ($groupedInspirations[$catKey] as $img): ?>
                    <div class="inspiration-card">
                      <div class="inspiration-img-wrap" onclick="openLightbox('/NAAQSH/<?php echo htmlspecialchars($img['image_path']); ?>')">
                        <img src="/NAAQSH/<?php echo htmlspecialchars($img['image_path']); ?>" alt="<?php echo htmlspecialchars($catKey); ?> Inspiration" loading="lazy">
                      </div>
                      <div class="inspiration-card-body">
                        <span class="inspiration-badge"><?php echo htmlspecialchars($catKey); ?></span>
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                          <span onclick="openLightbox('/NAAQSH/<?php echo htmlspecialchars($img['image_path']); ?>')" style="font-size: 0.78rem; color: var(--color-muted); cursor: pointer;" title="Click to Enlarge">&#128065; Enlarge</span>
                          <span style="color: var(--color-border);">|</span>
                          <form method="post" action="/NAAQSH/customer/inspiration_board.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this inspiration image?');" onclick="event.stopPropagation();">
                            <input type="hidden" name="action" value="delete_inspiration">
                            <input type="hidden" name="inspiration_id" value="<?php echo (int)$img['id']; ?>">
                            <input type="hidden" name="event_id" value="<?php echo (int)$currentEvent['id']; ?>">
                            <button type="submit" style="background: none; border: none; padding: 0; color: #dc2626; font-weight: 600; font-size: 0.78rem; cursor: pointer; text-decoration: underline;">Delete</button>
                          </form>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </section>
          <?php endforeach; ?>

        <?php endif; ?>

      <?php endif; ?>

    </div>
  </main>

  <!-- Lightbox Modal -->
  <div id="lightboxModal" class="lightbox-modal" onclick="closeLightbox(event)">
    <span class="lightbox-close" onclick="closeLightbox(event)">&times;</span>
    <img id="lightboxImg" class="lightbox-content" src="" alt="Enlarged Inspiration Reference">
  </div>

  <footer class="site-footer">
    <div class="footer-bottom">
      <div class="container">
        <p>&copy; <?php echo date('Y'); ?> NAAQŚĦ. Client Portal. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script>
    function openLightbox(src) {
      document.getElementById('lightboxImg').src = src;
      document.getElementById('lightboxModal').classList.add('active');
    }
    function closeLightbox(event) {
      if (event.target !== document.getElementById('lightboxImg')) {
        document.getElementById('lightboxModal').classList.remove('active');
        document.getElementById('lightboxImg').src = '';
      }
    }
  </script>
  <script src="/NAAQSH/public/assets/js/main.js"></script>
</body>
</html>

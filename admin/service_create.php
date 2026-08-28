<?php
// Admin: Create a new service package
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();
$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC')->fetchAll();

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);

    if ($categoryId <= 0) {
        $errors[] = 'Please select an event category.';
    }
    if ($title === '') {
        $errors[] = 'Service title is required.';
    }

    $filename = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/services/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.';
        } else {
            $filename = bin2hex(random_bytes(8)) . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
        }
    }

    if (empty($errors)) {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'service';
        $stmtSlug = $pdo->prepare('SELECT COUNT(*) FROM services WHERE slug = ?');
        $stmtSlug->execute([$slug]);
        if ((int)$stmtSlug->fetchColumn() > 0) {
            $slug .= '-' . time();
        }

        $stmt = $pdo->prepare('INSERT INTO services (category_id, title, slug, description, price, image, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
        $stmt->execute([$categoryId, $title, $slug, $description, $price, $filename]);
        header('Location: /NAAQSH/admin/services.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add Service Package — NAAQŚĦ Admin</title>
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
        <a href="/NAAQSH/admin/services.php" class="nav-link active">Services</a>
        <a href="/NAAQSH/admin/gallery.php" class="nav-link">Gallery</a>
        <a href="/NAAQSH/admin/team.php" class="nav-link">Team</a>
        <a href="/NAAQSH/admin/bookings.php" class="nav-link">Bookings</a>
        <a href="/NAAQSH/admin/inquiries.php" class="nav-link">Inquiries</a>
        <a href="/NAAQSH/public/index.php" class="nav-link" target="_blank">View Site &nearr;</a>
        
        <div class="nav-actions">
          <a href="/NAAQSH/admin/logout.php" class="btn btn-secondary btn-sm">Sign Out</a>
        </div>
      </nav>
    </div>
  </header>

  <main class="page-shell" style="padding: 3rem 0 5rem;">
    <div class="container" style="max-width: 720px;">
      <div style="margin-bottom: 2rem;">
        <span class="section-kicker">Catalog Expansion</span>
        <h1 class="hero-title" style="font-size: 2.6rem; margin-bottom: 0.5rem;">Add New Service Package</h1>
        <p class="lead" style="font-size: 1rem;">Create a signature offering for client bookings.</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars(implode(' ', $errors)); ?>
        </div>
      <?php endif; ?>

      <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 2.5rem; box-shadow: var(--shadow-sm);">
        <form method="post" enctype="multipart/form-data">
          <div class="form-group">
            <label for="category_id">Service Category *</label>
            <select id="category_id" name="category_id" required>
              <option value="">Select an event category</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo (int)$category['id']; ?>" <?php echo ((int)($_POST['category_id'] ?? 0) === (int)$category['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($category['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="title">Service Title *</label>
            <input id="title" name="title" type="text" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required placeholder="e.g. Luxury Stage & Floral Styling">
          </div>

          <div class="form-group">
            <label for="price">Starting Price (PKR) *</label>
            <input id="price" name="price" type="number" step="0.01" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required placeholder="e.g. 85000">
          </div>

          <div class="form-group">
            <label for="description">Detailed Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Describe the inclusions, duration, staffing, and deliverables..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
          </div>

          <div class="form-group">
            <label for="image">Cover Image (JPG, JPEG, PNG, WEBP)</label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
          </div>

          <div style="display: flex; gap: 1rem; align-items: center; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">Create Service Package</button>
            <a href="/NAAQSH/admin/services.php" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
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

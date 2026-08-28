<?php
// Admin: Edit an existing service package
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($id <= 0) {
    header('Location: /NAAQSH/admin/services.php');
    exit;
}

// Fetch existing service
$stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    header('Location: /NAAQSH/admin/services.php');
    exit;
}

// Fetch categories for dropdown
$categories = $pdo->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC')->fetchAll();

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = max(0.0, floatval($_POST['price'] ?? 0));
    $durationHours = !empty($_POST['duration_hours']) ? max(1, (int)$_POST['duration_hours']) : null;
    $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if ($categoryId <= 0) {
        $errors[] = 'Please select an event category.';
    } else {
        $catCheck = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $catCheck->execute([$categoryId]);
        if (!$catCheck->fetch()) {
            $errors[] = 'Selected category does not exist.';
        }
    }

    if ($title === '') {
        $errors[] = 'Service title is required.';
    }

    $newFilename = null;
    $oldImageToDelete = null;

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
            $newFilename = bin2hex(random_bytes(8)) . '.' . $ext;
            $destination = $uploadDir . $newFilename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                // Keep reference to delete the old image after successful DB update
                $oldImageToDelete = $service['image'];
            } else {
                $errors[] = 'Failed to upload image file to server.';
                $newFilename = null;
            }
        }
    }

    if (empty($errors)) {
        // Generate safe unique slug
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) ?: 'service';
        $stmtSlug = $pdo->prepare('SELECT COUNT(*) FROM services WHERE slug = ? AND id != ?');
        $stmtSlug->execute([$slug, $id]);
        if ((int)$stmtSlug->fetchColumn() > 0) {
            $slug .= '-' . $id;
        }

        $finalImage = ($newFilename !== null) ? $newFilename : $service['image'];

        $updateStmt = $pdo->prepare('
            UPDATE services
            SET category_id = ?, title = ?, slug = ?, description = ?, price = ?, image = ?, duration_hours = ?, is_featured = ?, is_active = ?
            WHERE id = ?
        ');
        $updateStmt->execute([
            $categoryId,
            $title,
            $slug,
            $description !== '' ? $description : null,
            $price,
            $finalImage,
            $durationHours,
            $isFeatured,
            $isActive,
            $id
        ]);

        // Delete old image only if a new one was successfully uploaded and saved
        if ($oldImageToDelete && $newFilename !== null) {
            $oldPath1 = __DIR__ . '/../uploads/services/' . $oldImageToDelete;
            $oldPath2 = __DIR__ . '/../uploads/' . $oldImageToDelete;
            if (file_exists($oldPath1)) {
                @unlink($oldPath1);
            } elseif (file_exists($oldPath2)) {
                @unlink($oldPath2);
            }
        }

        header('Location: /NAAQSH/admin/services.php?updated=' . $id);
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Service Package #<?php echo (int)$service['id']; ?> — NAAQŚĦ Admin</title>
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
        <a href="/NAAQSH/public/index.php" class="nav-link" target="_blank">View Site &nearr;</a>
        
        <div class="nav-actions">
          <a href="/NAAQSH/admin/logout.php" class="btn btn-secondary btn-sm">Sign Out</a>
        </div>
      </nav>
    </div>
  </header>

  <main class="page-shell" style="padding: 3rem 0 5rem;">
    <div class="container" style="max-width: 760px;">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
          <span class="section-kicker">Catalog Management</span>
          <h1 class="hero-title" style="font-size: 2.6rem; margin-bottom: 0.4rem;">Edit Service Package</h1>
          <p class="lead" style="font-size: 1rem; margin-bottom: 0;">Update details, pricing, coverage, or cover image for service #<?php echo (int)$service['id']; ?>.</p>
        </div>
        <a href="/NAAQSH/admin/services.php" class="btn btn-secondary btn-sm">&larr; Back to Services</a>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars(implode(' ', $errors)); ?>
        </div>
      <?php endif; ?>

      <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 2.5rem; box-shadow: var(--shadow-sm);">
        <form method="post" action="/NAAQSH/admin/service_edit.php?id=<?php echo (int)$service['id']; ?>" enctype="multipart/form-data">
          <input type="hidden" name="id" value="<?php echo (int)$service['id']; ?>">

          <div class="form-grid">
            <div class="form-group full-width">
              <label for="category_id">Service Category *</label>
              <select id="category_id" name="category_id" required>
                <option value="">Select an event category</option>
                <?php 
                  $currentCategory = (int)($_POST['category_id'] ?? $service['category_id']);
                  foreach ($categories as $category): 
                ?>
                  <option value="<?php echo (int)$category['id']; ?>" <?php echo ($currentCategory === (int)$category['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group full-width">
              <label for="title">Service Title *</label>
              <input id="title" name="title" type="text" value="<?php echo htmlspecialchars($_POST['title'] ?? $service['title']); ?>" required placeholder="e.g. Luxury Stage & Floral Styling">
            </div>

            <div class="form-group">
              <label for="price">Starting Price (PKR) *</label>
              <input id="price" name="price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['price'] ?? $service['price']); ?>" required placeholder="e.g. 85000">
            </div>

            <div class="form-group">
              <label for="duration_hours">Estimated Duration (Hours)</label>
              <input id="duration_hours" name="duration_hours" type="number" min="1" value="<?php echo htmlspecialchars($_POST['duration_hours'] ?? ($service['duration_hours'] ?? '')); ?>" placeholder="e.g. 48">
            </div>

            <div class="form-group full-width">
              <label for="description">Detailed Description</label>
              <textarea id="description" name="description" rows="4" placeholder="Describe the inclusions, duration, staffing, and deliverables..."><?php echo htmlspecialchars($_POST['description'] ?? ($service['description'] ?? '')); ?></textarea>
            </div>

            <div class="form-group full-width">
              <label>Current Cover Image</label>
              <div style="display: flex; align-items: center; gap: 1.5rem; padding: 1rem; background: var(--color-bg-alt); border: 1px solid var(--color-border); margin-bottom: 0.75rem;">
                <?php if (!empty($service['image'])): ?>
                  <img 
                    src="<?php 
                      $filename = ltrim(str_replace('services/', '', $service['image']), '/');
                      echo htmlspecialchars(file_exists(__DIR__ . '/../uploads/services/' . $filename) ? '/NAAQSH/uploads/services/' . $filename : '/NAAQSH/uploads/' . $service['image']); 
                    ?>" 
                    alt="Current cover image" 
                    style="width: 100px; height: 75px; object-fit: cover; border: 1px solid var(--color-border);"
                  >
                  <div>
                    <strong><?php echo htmlspecialchars($service['image']); ?></strong>
                    <p style="margin: 0.25rem 0 0; font-size: 0.8rem; color: var(--color-muted);">Currently displayed across the website catalog.</p>
                  </div>
                <?php else: ?>
                  <div style="width: 100px; height: 75px; background: var(--color-surface); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; color: var(--color-muted); border: 1px solid var(--color-border);">No Image</div>
                  <div>
                    <span style="color: var(--color-muted); font-size: 0.85rem;">No image uploaded yet.</span>
                  </div>
                <?php endif; ?>
              </div>

              <label for="image">Replace Cover Image (JPG, JPEG, PNG, WEBP)</label>
              <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
              <small style="color: var(--color-muted); font-size: 0.8rem; display: block; margin-top: 0.25rem;">Leave empty if you wish to keep the existing image.</small>
            </div>

            <div class="form-group">
              <label for="is_active">Catalog Visibility</label>
              <select id="is_active" name="is_active">
                <?php $currentActive = (int)($_POST['is_active'] ?? $service['is_active']); ?>
                <option value="1" <?php echo $currentActive === 1 ? 'selected' : ''; ?>>Active (Visible on Website)</option>
                <option value="0" <?php echo $currentActive === 0 ? 'selected' : ''; ?>>Inactive (Hidden)</option>
              </select>
            </div>

            <div class="form-group" style="display: flex; align-items: center; padding-top: 1.8rem;">
              <label style="display: inline-flex; align-items: center; gap: 0.6rem; cursor: pointer; text-transform: none; font-size: 0.92rem; font-weight: 600;">
                <?php $currentFeatured = !empty($_POST['is_featured']) || (!isset($_POST['is_featured']) && !empty($service['is_featured'])); ?>
                <input name="is_featured" type="checkbox" value="1" <?php echo $currentFeatured ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                Feature on Homepage
              </label>
            </div>
          </div>

          <div style="display: flex; gap: 1rem; align-items: center; margin-top: 2.5rem; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
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

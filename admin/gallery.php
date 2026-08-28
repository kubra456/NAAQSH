<?php
// Admin: Manage Gallery Items (view, upload, edit, toggle featured, delete)
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();
$errors = [];

// Handle toggle featured status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare('UPDATE gallery SET is_featured = IF(is_featured = 1, 0, 1) WHERE id = ?');
    $stmt->execute([$id]);
    $_SESSION['flash_success'] = 'Featured status updated successfully.';
    header('Location: /NAAQSH/admin/gallery.php');
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('SELECT image_path FROM gallery WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && !empty($row['image_path'])) {
        $fullPath = __DIR__ . '/../uploads/' . $row['image_path'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
    $del = $pdo->prepare('DELETE FROM gallery WHERE id = ?');
    $del->execute([$id]);
    $_SESSION['flash_success'] = 'Gallery item deleted successfully.';
    header('Location: /NAAQSH/admin/gallery.php');
    exit;
}

// Handle POST requests (Create & Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add_gallery_item';

    if ($action === 'edit_gallery_item') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;

        if ($id <= 0) {
            $errors[] = 'Invalid gallery item ID.';
        } elseif ($title === '') {
            $errors[] = 'Title is required.';
        } else {
            $stmtFetch = $pdo->prepare('SELECT id, image_path FROM gallery WHERE id = ? LIMIT 1');
            $stmtFetch->execute([$id]);
            $existing = $stmtFetch->fetch();

            if (!$existing) {
                $errors[] = 'Gallery item not found in database.';
            } else {
                $dbPath = $existing['image_path'];

                // Handle replacement image upload if provided
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $tmpPath = $_FILES['image']['tmp_name'];
                    $size = $_FILES['image']['size'];

                    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmpPath);
                    finfo_close($finfo);

                    if (!isset($allowedMimes[$mime])) {
                        $errors[] = 'Invalid image format. Allowed: JPG, PNG, WEBP.';
                    } elseif ($size > 5 * 1024 * 1024) {
                        $errors[] = 'Image size exceeds maximum limit of 5MB.';
                    } else {
                        $uploadDir = __DIR__ . '/../uploads/gallery/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $ext = $allowedMimes[$mime];
                        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                        $destination = $uploadDir . $filename;

                        if (move_uploaded_file($tmpPath, $destination)) {
                            // Safely delete old physical image file
                            if (!empty($existing['image_path'])) {
                                $oldFullPath = __DIR__ . '/../uploads/' . $existing['image_path'];
                                if (file_exists($oldFullPath)) {
                                    @unlink($oldFullPath);
                                }
                            }
                            $dbPath = 'gallery/' . $filename;
                        } else {
                            $errors[] = 'Failed to save new image file to server.';
                        }
                    }
                }

                if (empty($errors)) {
                    $stmtUpdate = $pdo->prepare(
                        'UPDATE gallery
                         SET title = ?, caption = ?, is_featured = ?, image_path = ?, updated_at = CURRENT_TIMESTAMP
                         WHERE id = ?'
                    );
                    $stmtUpdate->execute([$title, $caption ?: null, $isFeatured, $dbPath, $id]);
                    $_SESSION['flash_success'] = "Gallery item #{$id} ('{$title}') updated successfully.";
                    header('Location: /NAAQSH/admin/gallery.php');
                    exit;
                }
            }
        }
    } elseif ($action === 'add_gallery_item') {
        $title = trim($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
        $uploadedBy = !empty($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;

        if ($title === '') {
            $errors[] = 'Title is required.';
        }

        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please select a valid image file to upload.';
        } else {
            $tmpPath = $_FILES['image']['tmp_name'];
            $size = $_FILES['image']['size'];

            $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            if (!isset($allowedMimes[$mime])) {
                $errors[] = 'Invalid image format. Allowed: JPG, PNG, WEBP.';
            } elseif ($size > 5 * 1024 * 1024) {
                $errors[] = 'Image size exceeds maximum limit of 5MB.';
            } else {
                $uploadDir = __DIR__ . '/../uploads/gallery/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $ext = $allowedMimes[$mime];
                $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                $destination = $uploadDir . $filename;

                if (move_uploaded_file($tmpPath, $destination)) {
                    $dbPath = 'gallery/' . $filename;
                    $stmt = $pdo->prepare(
                        'INSERT INTO gallery (title, image_path, caption, uploaded_by, is_featured)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$title, $dbPath, $caption ?: null, $uploadedBy, $isFeatured]);
                    $_SESSION['flash_success'] = "Gallery item '{$title}' added successfully.";
                    header('Location: /NAAQSH/admin/gallery.php');
                    exit;
                } else {
                    $errors[] = 'Failed to upload image file to server.';
                }
            }
        }
    }
}

// Handle GET Edit mode
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editStmt = $pdo->prepare('SELECT * FROM gallery WHERE id = ? LIMIT 1');
    $editStmt->execute([$editId]);
    $editItem = $editStmt->fetch();
}

// Fetch all gallery items with admin uploader name
$stmt = $pdo->query(
    'SELECT g.id, g.title, g.image_path, g.caption, g.is_featured, g.created_at, a.full_name AS admin_name
     FROM gallery g
     LEFT JOIN admins a ON a.id = g.uploaded_by
     ORDER BY g.created_at DESC'
);
$items = $stmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

// Helper for image preview URL
function getGalleryAdminImage($storedPath) {
    if (!empty($storedPath)) {
        $fullPath = __DIR__ . '/../uploads/' . $storedPath;
        if (file_exists($fullPath)) {
            return '/NAAQSH/uploads/' . $storedPath;
        }
    }
    return 'https://images.unsplash.com/photo-1520854221256-17451cc331bf?auto=format&fit=crop&w=200&q=80';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Gallery — NAAQŚĦ Admin</title>
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
        <a href="/NAAQSH/admin/gallery.php" class="nav-link active">Gallery</a>
        <a href="/NAAQSH/public/portfolio.php" class="nav-link" target="_blank">View Portfolio &nearr;</a>
        
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
          <span class="section-kicker">Visual Showcase</span>
          <h1 class="hero-title" style="font-size: 2.8rem; margin-bottom: 0;">Manage Gallery & Portfolio</h1>
        </div>
        <a href="/NAAQSH/public/portfolio.php" target="_blank" class="btn btn-secondary">Public Portfolio &nearr;</a>
      </div>

      <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
          <?php echo htmlspecialchars($flashSuccess); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
          <?php echo htmlspecialchars(implode(' ', $errors)); ?>
        </div>
      <?php endif; ?>

      <!-- Upload / Edit Gallery Item Form Panel -->
      <div style="background: var(--color-surface); border: 1px solid var(--color-border); padding: 2rem; box-shadow: var(--shadow-sm); margin-bottom: 3rem;">
        <span class="section-kicker"><?php echo $editItem ? 'Update Showcase Record' : 'Curate New Asset'; ?></span>
        <h2 style="font-size: 1.8rem; margin-bottom: 1.25rem;">
          <?php echo $editItem ? 'Edit Portfolio Item #' . (int)$editItem['id'] : 'Upload New Portfolio Work'; ?>
        </h2>
        
        <form method="post" action="/NAAQSH/admin/gallery.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?php echo $editItem ? 'edit_gallery_item' : 'add_gallery_item'; ?>">
          <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editItem['id']; ?>">
          <?php endif; ?>

          <div class="form-grid">
            <div class="form-group">
              <label for="title">Title *</label>
              <input id="title" name="title" type="text" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required placeholder="e.g. Royal Barat Stage & Floral Suite">
            </div>

            <div class="form-group">
              <label for="image">
                Photograph File <?php echo $editItem ? '(Leave blank to keep existing image)' : '(JPG, JPEG, PNG, WEBP) *'; ?>
              </label>
              <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" <?php echo $editItem ? '' : 'required'; ?>>
            </div>

            <?php if ($editItem && !empty($editItem['image_path'])): ?>
              <div class="form-group full-width" style="margin-bottom: 0.5rem;">
                <label style="font-weight: 700; color: var(--color-muted); display: block; margin-bottom: 0.4rem;">Current Photograph Preview</label>
                <div style="display: flex; align-items: center; gap: 1rem;">
                  <img src="<?php echo htmlspecialchars(getGalleryAdminImage($editItem['image_path'])); ?>" alt="" style="max-width: 140px; height: 90px; object-fit: cover; border: 1px solid var(--color-border); border-radius: 4px;">
                  <span style="font-size: 0.85rem; color: var(--color-muted);"><?php echo htmlspecialchars($editItem['image_path']); ?></span>
                </div>
              </div>
            <?php endif; ?>

            <div class="form-group full-width">
              <label for="caption">Editorial Caption / Story</label>
              <textarea id="caption" name="caption" rows="2" placeholder="Brief notes on styling concept, venue, or photographic direction..."><?php echo htmlspecialchars($editItem['caption'] ?? ''); ?></textarea>
            </div>

            <div class="form-group full-width" style="margin-bottom: 0.5rem;">
              <label style="display: inline-flex; align-items: center; gap: 0.6rem; cursor: pointer; text-transform: none; font-size: 0.92rem; font-weight: 600;">
                <input name="is_featured" type="checkbox" value="1" <?php echo (($editItem['is_featured'] ?? 0) == 1) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                Feature on Homepage Selected Work Showcase
              </label>
            </div>
          </div>

          <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary">
              <?php echo $editItem ? 'Save Changes &rarr;' : 'Upload & Publish to Gallery'; ?>
            </button>
            <?php if ($editItem): ?>
              <a href="/NAAQSH/admin/gallery.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Gallery Records Table -->
      <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
          <h2 style="font-size: 2rem; margin: 0;">Published Gallery Items (<?php echo count($items); ?>)</h2>
        </div>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Thumbnail</th>
                <th>Title</th>
                <th>Caption</th>
                <th>Featured</th>
                <th>Uploaded By</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="8" style="text-align:center; padding: 2rem;">No gallery items found.</td></tr>
              <?php else: ?>
                <?php foreach ($items as $it): ?>
                  <tr>
                    <td><?php echo (int)$it['id']; ?></td>
                    <td style="width: 90px;">
                      <?php if (!empty($it['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars(getGalleryAdminImage($it['image_path'])); ?>" alt="" style="width: 75px; height: 50px; object-fit: cover; border: 1px solid var(--color-border);" onerror="this.src='https://images.unsplash.com/photo-1520854221256-17451cc331bf?auto=format&fit=crop&w=200&q=80';">
                      <?php else: ?>
                        <span style="color: var(--color-muted); font-size: 0.75rem;">No image</span>
                      <?php endif; ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($it['title']); ?></strong></td>
                    <td><small style="color: var(--color-muted);"><?php echo nl2br(htmlspecialchars($it['caption'] ?? '—')); ?></small></td>
                    <td>
                      <?php if ($it['is_featured']): ?>
                        <span class="status-badge status-confirmed">Featured</span>
                      <?php else: ?>
                        <span class="status-badge status-draft">Standard</span>
                      <?php endif; ?>
                      <div style="margin-top: 4px;">
                        <a href="/NAAQSH/admin/gallery.php?toggle=<?php echo (int)$it['id']; ?>" style="font-size: 0.75rem; text-decoration: underline; color: var(--color-charcoal);">Toggle Status</a>
                      </div>
                    </td>
                    <td><?php echo htmlspecialchars($it['admin_name'] ?? 'System/Admin'); ?></td>
                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($it['created_at']))); ?></td>
                    <td>
                      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="/NAAQSH/admin/gallery.php?edit=<?php echo (int)$it['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="/NAAQSH/admin/gallery.php?delete=<?php echo (int)$it['id']; ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Delete this gallery item?')" style="color: #b00020; border-color: rgba(176,0,32,0.2);">Delete</a>
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

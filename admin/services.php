<?php
// Admin: Manage Services (view, create, delete)
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('SELECT image FROM services WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && $row['image']) {
        @unlink(__DIR__ . '/../uploads/services/' . $row['image']);
        @unlink(__DIR__ . '/../uploads/' . $row['image']);
    }
    $del = $pdo->prepare('DELETE FROM services WHERE id = ?');
    $del->execute([$id]);
    header('Location: /NAAQSH/admin/services.php');
    exit;
}

$stmt = $pdo->query('
    SELECT s.id, s.title, s.price, s.image, s.is_active, s.created_at, c.name AS category_name
    FROM services s
    LEFT JOIN categories c ON c.id = s.category_id
    ORDER BY s.created_at DESC
');
$items = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Services — NAAQŚĦ Admin</title>
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
    <div class="container">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1.5rem;">
        <div>
          <span class="section-kicker">Offerings Catalog</span>
          <h1 class="hero-title" style="font-size: 2.8rem; margin-bottom: 0;">Manage Services</h1>
        </div>
        <a href="/NAAQSH/admin/service_create.php" class="btn btn-primary">+ Add New Service</a>
      </div>

      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
          Service package #<?php echo (int)$_GET['updated']; ?> was updated successfully.
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Thumbnail</th>
              <th>Service Title</th>
              <th>Category</th>
              <th>Base Price</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr><td colspan="7" style="text-align: center; padding: 2rem;">No services found in database.</td></tr>
            <?php else: ?>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?php echo (int)$it['id']; ?></td>
                  <td style="width: 80px;">
                    <?php if (!empty($it['image'])): ?>
                      <img src="/NAAQSH/uploads/services/<?php echo htmlspecialchars($it['image']); ?>" alt="" style="width: 60px; height: 45px; object-fit: cover; border: 1px solid var(--color-border);" onerror="this.src='https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=200&q=80';">
                    <?php else: ?>
                      <div style="width: 60px; height: 45px; background: var(--color-bg-alt); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: var(--color-muted);">No Img</div>
                    <?php endif; ?>
                  </td>
                  <td><strong><?php echo htmlspecialchars($it['title']); ?></strong></td>
                  <td><span class="service-category" style="margin: 0;"><?php echo htmlspecialchars($it['category_name'] ?? 'General'); ?></span></td>
                  <td><strong>PKR <?php echo number_format((float)$it['price'], 2); ?></strong></td>
                  <td><?php echo htmlspecialchars(date('d M Y', strtotime($it['created_at'] ?? 'now'))); ?></td>
                  <td>
                    <div style="display: flex; gap: 0.5rem;">
                      <a href="/NAAQSH/admin/service_edit.php?id=<?php echo (int)$it['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                      <a href="/NAAQSH/admin/services.php?delete=<?php echo (int)$it['id']; ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Delete this service package?')" style="color: #b00020; border-color: rgba(176,0,32,0.2);">Delete</a>
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
        <p>&copy; <?php echo date('Y'); ?> NAAQŚĦ. Admin Portal.</p>
      </div>
    </div>
  </footer>

  <script src="/NAAQSH/public/assets/js/main.js"></script>
</body>
</html>

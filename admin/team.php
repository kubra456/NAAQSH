<?php
// Protected Admin Team Management for NAAQŚĦ
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../config/db.php';

$pdo = getPDO();

$uploadDir = __DIR__ . '/../uploads/team/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Handle POST actions (Create, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_team_member') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $role = trim((string)($_POST['role'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($fullName === '' || $role === '') {
            $_SESSION['flash_error'] = 'Please provide both Full Name and Role.';
        } else {
            $dbImagePath = null;

            // Handle image upload if provided
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $tmpPath = $_FILES['image']['tmp_name'];
                $origName = $_FILES['image']['name'];
                $size = $_FILES['image']['size'];

                $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);

                if (isset($allowedMimes[$mime]) && $size <= 5 * 1024 * 1024) {
                    $ext = $allowedMimes[$mime];
                    $filename = 'team_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $targetPath = $uploadDir . $filename;

                    if (is_uploaded_file($tmpPath) ? move_uploaded_file($tmpPath, $targetPath) : copy($tmpPath, $targetPath)) {
                        $dbImagePath = 'team/' . $filename;
                    }
                }
            }

            $stmt = $pdo->prepare('
                INSERT INTO team_members (full_name, role, bio, image_path, is_active)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$fullName, $role, $bio !== '' ? $bio : null, $dbImagePath, $isActive]);
            $_SESSION['flash_success'] = "Team member '{$fullName}' added successfully.";
        }

        header('Location: /NAAQSH/admin/team.php');
        exit;
    }

    if ($action === 'edit_team_member') {
        $id = (int)($_POST['id'] ?? 0);
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $role = trim((string)($_POST['role'] ?? ''));
        $bio = trim((string)($_POST['bio'] ?? ''));
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($id <= 0 || $fullName === '' || $role === '') {
            $_SESSION['flash_error'] = 'Invalid team member data provided.';
        } else {
            $stmtFetch = $pdo->prepare('SELECT id, image_path FROM team_members WHERE id = ? LIMIT 1');
            $stmtFetch->execute([$id]);
            $existing = $stmtFetch->fetch();

            if ($existing) {
                $dbImagePath = $existing['image_path'];

                // Handle profile image replacement if uploaded
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $tmpPath = $_FILES['image']['tmp_name'];
                    $size = $_FILES['image']['size'];

                    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmpPath);
                    finfo_close($finfo);

                    if (isset($allowedMimes[$mime]) && $size <= 5 * 1024 * 1024) {
                        $ext = $allowedMimes[$mime];
                        $filename = 'team_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $targetPath = $uploadDir . $filename;

                        if (is_uploaded_file($tmpPath) ? move_uploaded_file($tmpPath, $targetPath) : copy($tmpPath, $targetPath)) {
                            // Unlink old physical image file safely
                            if (!empty($existing['image_path'])) {
                                $oldPhys = __DIR__ . '/../uploads/' . $existing['image_path'];
                                if (file_exists($oldPhys)) {
                                    @unlink($oldPhys);
                                }
                            }
                            $dbImagePath = 'team/' . $filename;
                        }
                    }
                }

                $stmtUpdate = $pdo->prepare('
                    UPDATE team_members
                    SET full_name = ?, role = ?, bio = ?, image_path = ?, is_active = ?
                    WHERE id = ?
                ');
                $stmtUpdate->execute([$fullName, $role, $bio !== '' ? $bio : null, $dbImagePath, $isActive, $id]);
                $_SESSION['flash_success'] = "Team member '{$fullName}' updated successfully.";
            } else {
                $_SESSION['flash_error'] = 'Team member not found.';
            }
        }

        header('Location: /NAAQSH/admin/team.php');
        exit;
    }

    if ($action === 'delete_team_member') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmtFetch = $pdo->prepare('SELECT id, full_name, image_path FROM team_members WHERE id = ? LIMIT 1');
            $stmtFetch->execute([$id]);
            $member = $stmtFetch->fetch();

            if ($member) {
                // Delete physical image file if exists
                if (!empty($member['image_path'])) {
                    $physPath = __DIR__ . '/../uploads/' . $member['image_path'];
                    if (file_exists($physPath)) {
                        @unlink($physPath);
                    }
                }

                $delStmt = $pdo->prepare('DELETE FROM team_members WHERE id = ?');
                $delStmt->execute([$id]);
                $_SESSION['flash_success'] = "Team member '{$member['full_name']}' deleted successfully.";
            } else {
                $_SESSION['flash_error'] = 'Team member not found.';
            }
        } else {
            $_SESSION['flash_error'] = 'Invalid deletion request.';
        }

        header('Location: /NAAQSH/admin/team.php');
        exit;
    }
}

// Handle GET edit mode
$editMember = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editStmt = $pdo->prepare('SELECT * FROM team_members WHERE id = ? LIMIT 1');
    $editStmt->execute([$editId]);
    $editMember = $editStmt->fetch();
}

// Fetch all team members for table display
$teamStmt = $pdo->query('SELECT * FROM team_members ORDER BY created_at DESC');
$teamList = $teamStmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Helper function for team member images in admin view
function getAdminTeamImage($storedPath) {
    if (!empty($storedPath)) {
        $physPath = __DIR__ . '/../uploads/' . $storedPath;
        if (file_exists($physPath)) {
            return '/NAAQSH/uploads/' . $storedPath;
        }
    }
    return 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Manage Team — NAAQŚĦ Admin</title>
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
        <a href="/NAAQSH/admin/team.php" class="nav-link active">Team</a>
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
          <span class="section-kicker">Studio Leadership</span>
          <h1 class="hero-title" style="font-size: 2.8rem; margin-bottom: 0;">Manage Team Members</h1>
        </div>
        <div>
          <button id="toggleFormBtn" onclick="toggleForm()" class="btn btn-primary">
            <?php echo $editMember ? '&larr; Back to Member List' : '+ Add Team Member'; ?>
          </button>
        </div>
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

      <!-- Add / Edit Team Member Form Panel -->
      <div id="formPanel" style="display: <?php echo $editMember ? 'block' : 'none'; ?>; background: var(--color-surface); border: 1px solid var(--color-border); padding: 2rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
          <?php echo $editMember ? 'Edit Team Member #' . (int)$editMember['id'] : 'Add New Team Member'; ?>
        </h2>

        <form method="post" action="/NAAQSH/admin/team.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?php echo $editMember ? 'edit_team_member' : 'add_team_member'; ?>">
          <?php if ($editMember): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editMember['id']; ?>">
          <?php endif; ?>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.25rem;">
            <div class="form-group">
              <label for="full_name" style="font-weight: 700; margin-bottom: 0.35rem; display: block;">Full Name *</label>
              <input id="full_name" name="full_name" type="text" value="<?php echo htmlspecialchars($editMember['full_name'] ?? ''); ?>" required placeholder="e.g. Ayesha Noor" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border);">
            </div>

            <div class="form-group">
              <label for="role" style="font-weight: 700; margin-bottom: 0.35rem; display: block;">Role / Designation *</label>
              <input id="role" name="role" type="text" value="<?php echo htmlspecialchars($editMember['role'] ?? ''); ?>" required placeholder="e.g. Creative Director" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border);">
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="bio" style="font-weight: 700; margin-bottom: 0.35rem; display: block;">Biography & Editorial Focus</label>
            <textarea id="bio" name="bio" rows="3" placeholder="Brief statement about experience and role..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); font-family: var(--font-sans);"><?php echo htmlspecialchars($editMember['bio'] ?? ''); ?></textarea>
          </div>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; align-items: center;">
            <div class="form-group">
              <label for="image" style="font-weight: 700; margin-bottom: 0.35rem; display: block;">
                Profile Photo <?php echo $editMember ? '(Leave blank to keep existing)' : '(Optional)'; ?>
              </label>
              <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" style="font-size: 0.85rem; width: 100%;">
            </div>

            <div class="form-group">
              <label for="is_active" style="font-weight: 700; margin-bottom: 0.35rem; display: block;">Status</label>
              <select id="is_active" name="is_active" style="width: 100%; padding: 0.75rem; border: 1px solid var(--color-border); background: var(--color-surface);">
                <option value="1" <?php echo (($editMember['is_active'] ?? 1) == 1) ? 'selected' : ''; ?>>Active (Visible on Site)</option>
                <option value="0" <?php echo (($editMember['is_active'] ?? 1) == 0) ? 'selected' : ''; ?>>Inactive (Hidden)</option>
              </select>
            </div>
          </div>

          <?php if ($editMember && !empty($editMember['image_path'])): ?>
            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
              <span style="font-size: 0.85rem; font-weight: 700; color: var(--color-muted);">Current Photo:</span>
              <img src="<?php echo htmlspecialchars(getAdminTeamImage($editMember['image_path'])); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid var(--color-border);">
            </div>
          <?php endif; ?>

          <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">
              <?php echo $editMember ? 'Save Changes &rarr;' : 'Add Team Member &rarr;'; ?>
            </button>
            <?php if ($editMember): ?>
              <a href="/NAAQSH/admin/team.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Team Members Data Table -->
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Photo</th>
              <th>Full Name</th>
              <th>Role</th>
              <th>Bio Statement</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($teamList)): ?>
              <tr>
                <td colspan="7" style="text-align: center; padding: 2.5rem; color: var(--color-muted);">
                  No team members registered yet. Click "+ Add Team Member" to create one.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($teamList as $m): ?>
                <tr>
                  <td><?php echo (int)$m['id']; ?></td>
                  <td style="width: 70px;">
                    <img src="<?php echo htmlspecialchars(getAdminTeamImage($m['image_path'])); ?>" 
                         alt="" 
                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%; border: 1px solid var(--color-border);">
                  </td>
                  <td><strong><?php echo htmlspecialchars($m['full_name']); ?></strong></td>
                  <td><span class="service-category" style="margin: 0;"><?php echo htmlspecialchars($m['role']); ?></span></td>
                  <td style="max-width: 280px; font-size: 0.85rem; color: var(--color-muted);">
                    <?php echo htmlspecialchars(mb_strimwidth($m['bio'] ?? '', 0, 80, '...')); ?>
                  </td>
                  <td>
                    <?php if ($m['is_active']): ?>
                      <span class="event-type-pill" style="background: var(--color-champagne-soft); color: var(--color-charcoal); margin: 0;">Active</span>
                    <?php else: ?>
                      <span class="event-type-pill" style="background: var(--color-bg-alt); color: var(--color-muted); margin: 0;">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                      <a href="/NAAQSH/admin/team.php?edit=<?php echo (int)$m['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                      <form method="post" action="/NAAQSH/admin/team.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this team member?');">
                        <input type="hidden" name="action" value="delete_team_member">
                        <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" style="color: #b00020; border-color: rgba(176,0,32,0.2); background: none; cursor: pointer;">Delete</button>
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

  <script>
    function toggleForm() {
      const panel = document.getElementById('formPanel');
      const btn = document.getElementById('toggleFormBtn');
      if (panel.style.display === 'none') {
        panel.style.display = 'block';
        btn.innerHTML = '&larr; Close Form';
      } else {
        panel.style.display = 'none';
        btn.innerHTML = '+ Add Team Member';
      }
    }
  </script>
  <script src="/NAAQSH/public/assets/js/main.js"></script>
</body>
</html>

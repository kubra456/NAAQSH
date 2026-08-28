<?php
// Luxury Customer Login Screen for NAAQŚĦ.
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['customer_id'])) {
    header('Location: /NAAQSH/customer/dashboard.php');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT id, full_name, password_hash, status FROM users WHERE LOWER(TRIM(email)) = LOWER(?) LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'banned') {
                $error = 'Your account has been deactivated. Please contact studio support.';
            } else {
                if (!headers_sent()) {
                    session_regenerate_id(true);
                }
                $_SESSION['customer_id'] = (int)$user['id'];
                $_SESSION['customer_name'] = $user['full_name'];
                header('Location: /NAAQSH/customer/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid email address or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Customer Login — NAAQŚĦ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/NAAQSH/public/assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, rgba(251, 248, 243, 0.95), rgba(244, 236, 225, 0.95)); min-height: 100vh;">

  <div class="auth-page-wrap">
    <div class="auth-card">
      <div class="auth-header">
        <a href="/NAAQSH/public/index.php" style="display: inline-flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
          <span class="brand-mark" style="width: 2.3rem; height: 2.3rem; font-size: 1.5rem;">N</span>
          <span style="font-family: var(--font-serif); font-size: 1.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--color-charcoal);">NAAQŚĦ</span>
        </a>
        <h1 style="font-size: 2.1rem; margin-bottom: 0.35rem;">Welcome Back</h1>
        <p>Sign in to access your event dashboard & bookings.</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="post" action="/NAAQSH/customer/login.php" novalidate>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="your.name@example.com" autocomplete="email">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required placeholder="••••••••" autocomplete="current-password">
        </div>

        <div style="margin-top: 1.75rem;">
          <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
        </div>
      </form>

      <div class="auth-footer">
        <p>Don't have an account? <a href="/NAAQSH/customer/register.php">Create an account</a></p>
        <p style="margin-top: 0.75rem;"><a href="/NAAQSH/public/index.php" style="text-decoration: none; font-size: 0.82rem; color: var(--color-muted);">&larr; Return to Homepage</a></p>
      </div>
    </div>
  </div>

  <script src="/NAAQSH/public/assets/js/main.js"></script>
</body>
</html>

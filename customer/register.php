<?php
// Luxury Customer Registration Screen for NAAQŚĦ.
require_once __DIR__ . '/../config/db.php';
session_start();

if (!empty($_SESSION['customer_id'])) {
    header('Location: /NAAQSH/customer/dashboard.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullname === '' || $email === '' || $password === '') {
        $errors[] = 'All fields are required to create your client account.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters in length.';
    } elseif ($confirmPassword !== '' && $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match. Please re-enter your password.';
    }

    if (empty($errors)) {
        $pdo = getPDO();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, status) VALUES (?, ?, ?, "active")');
            $stmt->execute([$fullname, $email, $hash]);

            $_SESSION['customer_id'] = (int)$pdo->lastInsertId();
            $_SESSION['customer_name'] = $fullname;
            header('Location: /NAAQSH/customer/dashboard.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'An account with this email address is already registered.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Your Account — NAAQŚĦ</title>
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
        <h1 style="font-size: 2.1rem; margin-bottom: 0.35rem;">Create Your Account</h1>
        <p>Join NAAQŚĦ to plan, coordinate, and track your events.</p>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <?php echo htmlspecialchars(implode(' ', $errors)); ?>
        </div>
      <?php endif; ?>

      <form method="post" action="/NAAQSH/customer/register.php" novalidate>
        <div class="form-group">
          <label for="fullname">Full Name *</label>
          <input id="fullname" name="fullname" type="text" value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>" required placeholder="e.g. Sana Khan" autocomplete="name">
        </div>

        <div class="form-group">
          <label for="email">Email Address *</label>
          <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="your.name@example.com" autocomplete="email">
        </div>

        <div class="form-group">
          <label for="password">Password *</label>
          <input id="password" name="password" type="password" required placeholder="Minimum 6 characters" autocomplete="new-password">
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" autocomplete="new-password">
        </div>

        <div style="margin-top: 1.75rem;">
          <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
        </div>
      </form>

      <div class="auth-footer">
        <p>Already have an account? <a href="/NAAQSH/customer/login.php">Sign in</a></p>
        <p style="margin-top: 0.75rem;"><a href="/NAAQSH/public/index.php" style="text-decoration: none; font-size: 0.82rem; color: var(--color-muted);">&larr; Return to Homepage</a></p>
      </div>
    </div>
  </div>

  <script src="/NAAQSH/public/assets/js/main.js"></script>
</body>
</html>

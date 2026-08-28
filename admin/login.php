<?php
// Admin login page.
// This page uses the existing admins table and the shared auth helper to
// validate credentials before allowing access to protected admin routes.
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: /NAAQSH/admin/dashboard.php');
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please provide both your username and password.';
    } else {
        // Since the schema already contains the hashed admin credentials, we verify
        // them here without changing the database structure.
        if (adminLogin($username, $password)) {
            header('Location: /NAAQSH/admin/dashboard.php');
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>NAAQŚĦ Admin Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #f6f1ea;
      --panel: rgba(255,255,255,0.72);
      --border: rgba(27, 27, 26, 0.12);
      --charcoal: #1c1b1a;
      --muted: #655f5c;
      --rose: #b88a7d;
      --white: #ffffff;
      --shadow: 0 18px 40px rgba(34, 28, 25, 0.08);
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, rgba(246,241,234,0.95), rgba(238, 228, 215, 0.92));
      color: var(--charcoal);
      font-family: 'Manrope', Arial, sans-serif;
    }

    .login-shell {
      width: min(460px, calc(100% - 2rem));
      background: var(--panel);
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      padding: 2rem;
    }

    .brand {
      margin-bottom: 1rem;
      font-family: 'Cormorant Garamond', serif;
      font-size: clamp(2.2rem, 2vw + 1rem, 3rem);
      letter-spacing: 0.08em;
      text-transform: uppercase;
      text-align: center;
    }

    .brand-sub {
      text-align: center;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--muted);
      font-size: 0.72rem;
      margin-bottom: 1.5rem;
    }

    .field {
      margin-bottom: 1rem;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.4rem;
      color: var(--charcoal);
    }

    input {
      width: 100%;
      min-height: 48px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,0.7);
      padding: 0.8rem 0.9rem;
      font: inherit;
      color: var(--charcoal);
    }

    input:focus {
      outline: 2px solid rgba(184, 138, 125, 0.5);
      outline-offset: 2px;
    }

    .button {
      width: 100%;
      min-height: 48px;
      border: none;
      background: var(--charcoal);
      color: var(--white);
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      cursor: pointer;
      transition: opacity 0.2s ease;
    }

    .button:hover { opacity: 0.96; }

    .error {
      background: rgba(176, 42, 34, 0.06);
      border: 1px solid rgba(176, 42, 34, 0.18);
      color: #8a1f1a;
      padding: 0.85rem 1rem;
      margin-bottom: 1rem;
      font-size: 0.92rem;
    }
  </style>
</head>
<body>
  <main class="login-shell">
    <div class="brand">NAAQŚĦ</div>
    <div class="brand-sub">Admin Access</div>

    <?php if ($error !== ''): ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="/NAAQSH/admin/login.php" novalidate>
      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
      </div>

      <button class="button" type="submit">Sign In</button>
    </form>
  </main>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PharmaTrack — Login</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

<style>
/* ====== ALL YOUR CSS SAME (NO CHANGE) ====== */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg: #0a0f1e;
  --surface: #111827;
  --card: #1a2235;
  --accent: #00d4aa;
  --accent2: #0ea5e9;
  --text: #f0f4ff;
  --muted: #6b7a99;
  --danger: #ff4d6d;
  --border: #1e2d47;
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

/* (সব CSS তুমি যেমন ছিল তেমনই রাখো — এখানে shorten করা হলো) */
</style>

</head>
<body>

<div class="bg-glow"></div>
<div class="grid-bg"></div>

<div class="login-container">
  <div class="logo-area">
    <div class="logo-icon">💊</div>
    <div class="logo-text">Pharma<span>Track</span></div>
    <div class="logo-sub">Pharmacy Management System</div>
  </div>

  <div class="card">
    <h2>Welcome back</h2>
    <p>Sign in to access your pharmacy dashboard</p>

    <?php if (!empty($error)): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="joy.php">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               required>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>

      <button type="submit" class="btn-login">Sign In →</button>
    </form>

    <div class="hint">
      Default: <span>admin</span> / <span>admin123</span>
    </div>
  </div>
</div>

</body>
</html>

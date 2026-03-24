<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';
if (Auth::check() && Auth::role() === 'admin') { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $admin = Database::getInstance()->fetchOne("SELECT * FROM admins WHERE email = ? LIMIT 1", [strtolower($email)]);
        if ($admin && Auth::verifyPassword($password, $admin['password'])) {
            Auth::login($admin);
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Login — MediBook</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<link rel="stylesheet" href="../../../core/ui/parts/_icons.css">
<style>
  :root { --dc-accent:#0ea5e9; --dc-accent-2:#38bdf8; --dc-accent-glow:rgba(14,165,233,0.2); }
  .login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
  .login-card { width:100%; max-width:420px; }
  .logo-icon {
    width:64px; height:64px; border-radius:16px;
    background:var(--dc-accent-glow); border:1px solid rgba(14,165,233,0.3);
    display:flex; align-items:center; justify-content:center; margin:0 auto 16px;
  }
  .logo-icon .dc-icon { color:var(--dc-accent-2); }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card dc-animate-fade-up">

    <div style="text-align:center;margin-bottom:32px">
      <div class="logo-icon">
        <i class="dc-icon dc-icon-hospital dc-icon-xl"></i>
      </div>
      <div class="dc-h2">Medi<span style="color:var(--dc-accent)">Book</span></div>
      <p class="dc-body" style="margin-top:4px;color:var(--dc-text-2)">Admin Portal</p>
    </div>

    <div class="dc-card-solid">
      <?php if ($error): ?>
      <div class="dc-card" style="background:rgba(255,92,106,0.08);border-color:rgba(255,92,106,0.3);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
        <i class="dc-icon dc-icon-alert-triangle dc-icon-sm" style="color:var(--dc-danger);flex-shrink:0"></i>
        <span style="color:var(--dc-danger);font-size:0.9rem"><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="dc-form-group">
          <label class="dc-label-field">Email Address</label>
          <input type="email" name="email" class="dc-input"
                 placeholder="admin@clinic.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 autocomplete="email" required autofocus>
        </div>
        <div class="dc-form-group" style="margin-top:16px">
          <label class="dc-label-field">Password</label>
          <input type="password" name="password" class="dc-input"
                 placeholder="Enter your password"
                 autocomplete="current-password" required>
        </div>
        <button type="submit" class="dc-btn dc-btn-primary dc-btn-full"
                style="margin-top:24px;background:var(--dc-accent);border-color:var(--dc-accent)">
          <i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i> Sign In to Dashboard
        </button>
      </form>

      <div style="text-align:center;margin-top:20px">
        <a href="../index.php" class="dc-caption" style="color:var(--dc-text-3);display:inline-flex;align-items:center;gap:4px">
          <i class="dc-icon dc-icon-arrow-right dc-icon-xs" style="transform:rotate(180deg)"></i>
          Back to Public Site
        </a>
      </div>
    </div>

    <p class="dc-caption" style="text-align:center;margin-top:16px;color:var(--dc-text-3)">
      DevCore Portfolio Suite &middot; Medical Booking Platform
    </p>
  </div>
</div>
</body>
</html>
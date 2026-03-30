<?php
require_once __DIR__ . '/../auth.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (attemptLogin($username, $password)) {
        header('Location: /admin/index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LUXE Admin — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0e0d0b;--bg2:#141310;--bg3:#1c1a17;--border:rgba(255,255,255,0.07);--gold:#c9a96e;--gold-light:#e8c98a;--text:#f0ebe3;--text-dim:#8a8070;--red:#e05c5c;--radius:4px;--font-display:'Cormorant Garamond',serif;--font-body:'DM Sans',sans-serif; }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{background:var(--bg);color:var(--text);font-family:var(--font-body);min-height:100vh;display:flex;align-items:center;justify-content:center;}
  .login-wrap{width:380px;max-width:95vw;}
  .login-logo{font-family:var(--font-display);font-size:2rem;letter-spacing:0.18em;color:var(--gold);text-align:center;margin-bottom:8px;}
  .login-sub{text-align:center;color:var(--text-dim);font-size:13px;margin-bottom:2rem;letter-spacing:0.06em;}
  .login-box{background:var(--bg2);border:1px solid var(--border);border-radius:6px;padding:2rem;}
  .form-group{margin-bottom:1.2rem;}
  .form-group label{display:block;font-size:11px;letter-spacing:0.1em;text-transform:uppercase;color:var(--text-dim);margin-bottom:6px;}
  .form-group input{width:100%;background:var(--bg3);border:1px solid var(--border);color:var(--text);padding:10px 12px;border-radius:var(--radius);font-family:var(--font-body);font-size:14px;outline:none;transition:border-color .2s;}
  .form-group input:focus{border-color:var(--gold);}
  .error{background:rgba(224,92,92,0.12);border:1px solid rgba(224,92,92,0.3);color:var(--red);padding:10px 14px;border-radius:var(--radius);font-size:13px;margin-bottom:1.2rem;}
  .login-btn{width:100%;background:var(--gold);color:var(--bg);border:none;padding:12px;font-size:13px;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;font-family:var(--font-body);font-weight:500;border-radius:var(--radius);transition:background .2s;}
  .login-btn:hover{background:var(--gold-light);}
  .back-link{text-align:center;margin-top:1.2rem;font-size:12px;color:var(--text-dim);}
  .back-link a{color:var(--gold);text-decoration:none;}
  .hint{text-align:center;color:var(--text-dim);font-size:11px;margin-top:1rem;opacity:0.6;}
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-logo">LUXE</div>
  <p class="login-sub">Admin Panel</p>
  <div class="login-box">
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="admin" autocomplete="username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
      </div>
      <button type="submit" class="login-btn">Sign In</button>
    </form>
  </div>
  <p class="back-link"><a href="/">← Back to Shop</a></p>
  <p class="hint">Default: admin / admin123</p>
</div>
</body>
</html>

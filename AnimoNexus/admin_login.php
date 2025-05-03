<?php
session_start();
require 'db.php'; // your existing DB connection

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Look up admin
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = [
            'id'       => $admin['id'],
            'username' => $admin['username']
        ];
        header('Location: admin.php');
        exit;
    }
    $message = 'Invalid credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>AnimoNexus – Admin Login</title>
  <link rel="icon" href="static/images/animonexus_logo.png">
  <style>
    :root {
      --green: #006633;
      --green-light: #007a33;
      --green-lighter: #00cc66;
      --page-bg: #e6f2e6;
    }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: var(--page-bg);
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      color: #004d00;
    }

    /* -------- header -------- */
    .page-heading {
      background: var(--green);
      color: #fff;
      display: flex;
      align-items: center;       /* vertically center logo + text */
      justify-content: space-between;
      padding: 15px 100px;
    }
    .page-heading .branding {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .page-heading .branding img.logo {
      width: 40px;      /* match login.php size */
      height: 40px;
      object-fit: cover;
      object-position: center center;
    }
    .page-heading .branding h1 {
      margin: 0;
      font-size: 1.8rem;
    }
    .page-heading a.home-btn {
      background: linear-gradient(to right, var(--green-light), var(--green-lighter));
      color: #fff;
      text-decoration: none;
      padding: 8px 30px;
      border-radius: 5px;
      font-weight: bold;
      transition: .2s;
    }
    .page-heading a.home-btn:hover {
      box-shadow: 0 0 10px var(--green-lighter);
    }

    /* -------- card -------- */
    .card {
      background: rgba(0,0,0,.35);
      margin: auto;
      padding: 40px 50px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,.15);
      width: 320px;
      text-align: center;
    }
    .card h2 { color: #fff; margin: 0 0 25px; }
    .card input {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      border: 1px solid #606060;
      border-radius: 4px;
      background: rgba(255,255,255,.1);
      color: #fff;
    }
    .card button {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border: none;
      border-radius: 4px;
      background: var(--green-light);
      color: #fff;
      font-weight: bold;
      cursor: pointer;
      transition: .15s;
    }
    .card button:hover { background: var(--green); }

    .err {
      color: #ff6b6b;
      margin: 0 0 12px;
      font-size: .9rem;
    }
    .mini-btn {
      display: inline-block;
      margin-top: 12px;
      background: var(--green-light);
      color: #fff;
      text-decoration: none;
      padding: 6px 24px;
      border-radius: 4px;
      font-size: .9rem;
      font-weight: bold;
      transition: .15s;
    }
    .mini-btn:hover { background: var(--green); }
  </style>
</head>
<body>

  <header class="page-heading">
    <div class="branding">
      <img
        src="static/images/animonexus_logo.png?<?= filemtime(__DIR__ . '/static/images/animonexus_logo.png') ?>"
        alt="AnimoNexus logo"
        class="logo"
      >
      <h1>AnimoNexus</h1>
    </div>
    <a href="index.php" class="home-btn">Home</a>
  </header>

  <form class="card" method="post">
    <h2>Login as Admin</h2>
    <?php if ($message): ?>
      <p class="err"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Log In</button>
  </form>

</body>
</html>

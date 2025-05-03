<?php
// faculty_login.php – faculty sign-in
session_start();
require 'db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = $_POST['email']      ?? '';
    $password   = $_POST['password']   ?? '';
    $department = $_POST['department'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM faculty WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'             => $user['id'],
                'email'          => $user['email'],
                'first_name'     => $user['first_name'],
                'middle_initial' => $user['middle_initial'],
                'last_name'      => $user['last_name'],
                'dept'           => $user['department'],
                'type'           => 'faculty'
            ];
            header("Location: dashboard.php");
            exit;
        }
        $message = "Incorrect password.";
    } else {
        $message = "Faculty account not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>AnimoNexus – Faculty Login</title>
  <style>
    :root {
      --green: #006633;
      --green-light: #007a33;
      --green-lighter: #00cc66;
      --page-bg: #e6f2e6;
    }
    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      background: var(--page-bg);
      color: #004d00;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    /* -------- header -------- */
    .page-heading {
      background: var(--green);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px 100px;
    }
    .page-heading .branding {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .page-heading .branding img.logo {
      width: 40px;
      height: 40px;
      object-fit: cover;
      object-position: center;
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

    /* -------- login card -------- */
    .login-card {
      background: rgba(0,0,0,.35);
      margin: auto;
      padding: 40px 50px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,.15);
      width: 320px;
      text-align: center;
    }
    .login-card h2 {
      color: #fff;
      margin: 0 0 25px;
    }
    .login-card input {
      width: 100%;
      padding: 10px;
      margin: 8px 0;
      border: 1px solid #606060;
      border-radius: 4px;
      background: rgba(255,255,255,.1);
      color: #fff;
    }
    .login-card button {
      width: auto;
      padding: 6px 20px;
      font-size: 0.95rem;
      margin-top: 12px;
      border: none;
      border-radius: 4px;
      background: var(--green-light);
      color: #fff;
      font-weight: bold;
      cursor: pointer;
      transition: .15s;
      display: inline-block;
    }
    .login-card button:hover {
      background: var(--green);
    }
    .login-card .register-line {
      margin-top: 18px;
      color: #fff;
      font-size: 0.95rem;
    }
    .login-card .register-line a {
      color: #fff;
      text-decoration: underline;
      margin-left: 6px;
      font-weight: bold;
      transition: color .15s;
    }
    .login-card .register-line a:hover {
      color: var(--green-light);
      text-decoration: none;
    }
    .err {
      color: #ff6767;
      margin-bottom: 12px;
    }
  </style>
</head>
<body>
  <header class="page-heading">
    <div class="branding">
      <img src="static/images/animonexus_logo.png?<?= filemtime(__DIR__ . '/static/images/animonexus_logo.png') ?>" alt="AnimoNexus logo" class="logo">
      <h1>AnimoNexus</h1>
    </div>
    <a href="index.php" class="home-btn">Home</a>
  </header>

  <form class="login-card" method="POST" action="faculty_login.php">
    <h2>Login as Faculty</h2>
    <?php if ($message): ?>
      <p class="err"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <input
      type="email"
      name="email"
      placeholder="Enter DLSU-D Email"
      required
    >
    <input
      type="password"
      name="password"
      placeholder="Password"
      required
    >
    <input
      type="text"
      name="department"
      placeholder="Department"
      required
    >

    <button type="submit">Log In</button>

    <p class="register-line">
      Don’t have an account?
      <a href="faculty_register.php">Register</a>
    </p>
  </form>
</body>
</html>

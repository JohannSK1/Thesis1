<?php
// register.php – student sign-up
require 'db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studnum = trim($_POST['student_number'] ?? '');
    $first   = trim($_POST['first_name']     ?? '');
    $middle  = trim($_POST['middle_initial'] ?? '');
    $last    = trim($_POST['last_name']      ?? '');
    $email   = trim($_POST['email']          ?? '');
    $pass    = $_POST['password']            ?? '';

    if ($studnum === '') {
        $message = 'Please enter your student number.';
    } elseif ($first === '' || $last === '') {
        $message = 'Please enter at least first and last name.';
    } elseif (!preg_match('/@dlsud\.edu\.ph$/i', $email)) {
        $message = 'Email must end with @dlsud.edu.ph';
    } elseif (strlen($pass) < 6) {
        $message = 'Password must be at least 6 characters.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE student_number = ? OR email = ?");
        $stmt->bind_param('ss', $studnum, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows) {
            $message = 'That student number or email is already registered.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users 
                  (student_number, first_name, middle_initial, last_name, email, password, role)
                 VALUES (?, ?, ?, ?, ?, ?, 'user')"
            );
            $stmt->bind_param('ssssss',
                $studnum, $first, $middle, $last, $email, $hash
            );

            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            }
            $message = "Registration failed: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AnimoNexus – Register</title>
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
    /* ---------- header ---------- */
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
      object-position: center center;
    }
    .page-heading .branding h1 {
      margin: 0;
      font-size: 1.8rem;
      /* flexbox vertical-align handles centering */
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

    /* ---------- form card ---------- */
    .card {
      background: rgba(0,0,0,.35);
      margin: auto;
      padding: 40px 50px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,.15);
      width: 320px;
      text-align: center;
    }
    .card h2 {
      color: #fff;
      margin: 0 0 25px;
    }
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
    .card button:hover {
      background: var(--green);
    }

    /* inline login link under form */
    .card .register-line {
      margin-top: 18px;
      color: #fff;
      font-size: 0.95rem;
    }
    .card .register-line a {
      color: #fff;
      text-decoration: underline;
      margin-left: 6px;
      font-weight: bold;
      transition: color .15s;
    }
    .card .register-line a:hover {
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
    <img
  src="static/images/animonexus_logo.png?<?=filemtime(__DIR__ . '/static/images/animonexus_logo.png')?>"
  alt="AnimoNexus logo"
  class="logo"
/>
      <h1>AnimoNexus</h1>
    </div>
    <a href="index.php" class="home-btn">Home</a>
  </header>

  <div class="card">
    <h2>Create an account</h2>
    <?php if ($message): ?>
      <p class="err"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <input type="text"     name="student_number" placeholder="Student Number" required>
      <input type="text"     name="first_name"     placeholder="First Name"      required>
      <input type="text"     name="middle_initial" placeholder="Middle Initial"  
             maxlength="1">
      <input type="text"     name="last_name"      placeholder="Last Name"       required>
      <input type="email"    name="email"          placeholder="DLSU-D Email"    required>
      <input type="password" name="password"       placeholder="Password"        required>
      <button type="submit">Register</button>
    </form>

    <p class="register-line">
      Already have an account?
      <a href="login.php">Login</a>
    </p>
  </div>
</body>
</html>

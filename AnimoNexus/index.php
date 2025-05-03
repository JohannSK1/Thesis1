<?php
/* detect logged-in user */
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>AnimoNexus</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="icon" type="image/png" href="static/images/animonexus_logo.png">

  <style>
    :root{
      --green:#006633;
      --green-light:#007a33;
      --green-lighter:#00cc66;
      --page-bg:#e6f2e6;
    }
    body{
      margin:0;
      font-family:Arial,Helvetica,sans-serif;
      background:var(--page-bg);
      color:#004d00;
      display:flex;
      flex-direction:column;
      min-height:100vh;
    }

    /* --------- header (login.php style) --------- */
    .page-heading {
      background: var(--green);
      color: #fff;
      display: flex;
      align-items: center;      /* vertical centering */
      justify-content: space-between;
      padding: 15px 100px;
    }
    .page-heading .branding {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .page-heading .branding img.logo {
      width: 40px;         /* match login.php */
      height: 40px;
      object-fit: cover;
      object-position: center;
      /* leave PNG transparency intact */
    }
    .page-heading .branding h1 {
      margin: 0;
      font-size: 1.8rem;
      /* flexbox takes care of vertical centering */
    }

    /* ------------ nav / dropdown / rest stays the same ------------ */
    nav.nav{display:flex;align-items:center;gap:28px;font-size:1.05rem}
    nav a.link{
      color:#e3f5e3;text-decoration:none;transition:.15s;
    }
    nav a.link:hover{color:#fff;text-shadow:0 0 4px rgba(255,255,255,.5)}
    nav a.btn{
      background:linear-gradient(to right,var(--green-light),var(--green-lighter));
      color:#fff;text-decoration:none;font-weight:bold;
      padding:8px 30px;border-radius:5px;font-size:1rem;
      transition:.2s;
    }
    nav a.btn:hover{box-shadow:0 0 10px var(--green-lighter)}

    .dropdown{position:relative}
    .drop-toggle{
      cursor:pointer;display:flex;align-items:center;gap:6px;
      color:#e3f5e3;background:none;border:none;font:inherit;
    }
    .drop-toggle:hover{
      color:#fff;text-shadow:0 0 4px rgba(255,255,255,.5);
    }
    .caret{
      border:4px solid transparent;border-top-color:#e3f5e3;
      margin-top:3px;transition:.25s;
    }
    .dropdown.open .caret{
      transform:rotate(180deg);border-top-color:#fff;
    }

    .drop-menu{
      position:absolute;right:0;top:calc(100% + 6px);
      background:#fff;color:#004d00;border-radius:6px;
      box-shadow:0 6px 12px rgba(0,0,0,.15);
      min-width:200px;padding:8px 0;display:none;z-index:100;
    }
    .dropdown.open .drop-menu{display:block}
    .drop-menu a{
      display:block;padding:10px 20px;text-decoration:none;
      color:#004d00;font-size:.95rem;transition:.12s;
    }
    .drop-menu a:hover{background:var(--page-bg)}

    /* -------- main area & hero -------- */
    main{
      flex:1;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:28px;
    }
    .repo-card{
      background:#fff;border:1px solid #c7e0c7;
      border-radius:8px;width:260px;padding:30px;
      text-align:center;box-shadow:0 4px 12px rgba(0,0,0,.06)
    }
    .repo-card h3{margin:0 0 8px;color:var(--green)}
    .repo-card p{margin:0 0 20px;font-size:.9rem;color:#316b31}
    .repo-card a.btn{
      display:inline-block;
      background:linear-gradient(to right,var(--green-light),var(--green-lighter));
      color:#fff;text-decoration:none;font-weight:bold;
      padding:10px 34px;border-radius:5px;font-size:.95rem;
      transition:.18s;
    }
    .repo-card a.btn:hover{box-shadow:0 0 10px var(--green-lighter)}
    .tagline{
      font-size:1.45rem;color:var(--green);
      text-align:center;max-width:900px;line-height:1.4;
    }

    .campus-hero {
      position: relative;
      width: 100%;
      height: 50vh;
      background: url('static/images/dlsud.png') center/cover no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 2rem;
    }
    .campus-hero .campus-overlay {
      background: rgba(0,0,0,0.5);
      padding: 2rem;
      border-radius: 8px;
      text-align: center;
      color: #fff;
      max-width: 800px;
    }
    .campus-hero h2 { margin: 0 0 1rem; font-size: 2rem; }
    .campus-hero p  { margin: 0; font-size: 1rem; line-height: 1.5; }
  </style>
</head>
<body>

  <!-- ============== HEADER ============== -->
  <header class="page-heading">
    <div class="branding">
      <img
        src="static/images/animonexus_logo.png?<?=filemtime(__DIR__ . '/static/images/animonexus_logo.png')?>"
        alt="AnimoNexus logo"
        class="logo"
      >
      <h1>AnimoNexus</h1>
    </div>

    <nav class="nav">
<?php if (isset($_SESSION['user'])): ?>
      <a href="logout.php" class="btn">Logout</a>
<?php else: ?>
      <div class="dropdown" id="acctDrop">
        <button class="drop-toggle" type="button">
          Sign-In<span class="caret"></span>
        </button>
        <div class="drop-menu">
          <a href="login.php">Sign-In as Student</a>
          <a href="faculty_login.php">Sign-In as Faculty</a>
          <a href="admin_login.php">Sign-In as Admin</a>
        </div>
      </div>
<?php endif; ?>
    </nav>
  </header>

  <!-- ============== CENTRE ============== -->
  <main>
    <div class="repo-card">
      <h3>Browse Research Papers</h3>
      <p>Dive into the repository’s growing collection.</p>
<?php if (isset($_SESSION['user'])): ?>
      <a class="btn" href="dashboard.php">Open Repository</a>
<?php else: ?>
      <a class="btn" href="login.php">Open Repository</a>
<?php endif; ?>
    </div>

    <div class="tagline">
      Preserving Academic Contributions, Fueling Intellectual Discovery…
    </div>

    <section class="campus-hero">
      <div class="campus-overlay">
        <h2>Welcome to AnimoNexus</h2>
        <p>
          AnimoNexus is the Online Repository for approved research papers of De La Salle University – Dasmariñas Campus.
          It identifies, acquires, maintains, preserves, and provides access to the university’s approved
          digital research paper records.
        </p>
      </div>
    </section>
  </main>

  <script>
    document.getElementById('acctDrop')?.addEventListener('click', function(e){
      this.classList.toggle('open');
      e.stopPropagation();
      document.addEventListener('click', () => this.classList.remove('open'), { once: true });
    });
  </script>
</body>
</html>

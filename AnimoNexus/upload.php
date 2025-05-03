<?php
session_start();
require 'db.php';

// Only admins
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}

/**
 * Returns an array of reasons why the paper failed rules (empty = passed).
 */
function getFailures(int $id, mysqli $conn): array {
    $fails = [];
    // fetch abstract & file path
    $stmt = $conn->prepare("SELECT abstract, file_path FROM papers WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($abstract, $filePath);
    $stmt->fetch();
    $stmt->close();

    // extract PDF text
    $text = @shell_exec("pdftotext " . escapeshellarg(__DIR__ . "/uploads/$filePath") . " -");

    // Rule 1: abstract header + length
    if (stripos($abstract, 'Abstract') === false) {
        $fails[] = "Missing 'Abstract' header.";
    }
    if (str_word_count(strip_tags($abstract)) <= 100) {
        $fails[] = "Abstract ≤ 100 words.";
    }
    // Rule 2: full paper length
    if (str_word_count($text) <= 1500) {
        $fails[] = "Full paper ≤ 1500 words.";
    }
    // Rule 3: keywords
    foreach (['research','methodology','conclusion'] as $kw) {
        if (stripos($text, $kw) === false) {
            $fails[] = "Missing keyword: $kw.";
        }
    }
    // Rule 4: placeholder text
    if (stripos($text, 'Lorem ipsum') !== false) {
        $fails[] = "Contains 'Lorem ipsum'.";
    }
    // Rule 5: sections
    if (stripos($text, 'Introduction') === false) {
        $fails[] = "Missing 'Introduction' section.";
    }
    if (stripos($text, 'References') === false) {
        $fails[] = "Missing 'References' section.";
    }
    return $fails;
}

// Handle "Check for Approval"
$checked = false;
$fails   = [];
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'check') {
    $checked = true;
    $id      = (int) $_GET['id'];
    $fails   = getFailures($id, $conn);
    $status  = empty($fails) ? 'approved' : 'rejected';
    $stmt    = $conn->prepare("UPDATE papers SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute();
}

// Determine active tab
$activeSection = $checked || ($_GET['section'] ?? '') === 'uploads' ? 'uploadsSection' : 'uploadSection';

// Handle file upload
$message = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = trim($_POST['title']);
    $author         = trim($_POST['author']);
    $date_published = date('Y-m-d', strtotime(trim($_POST['date_published'])));
    $department     = trim($_POST['department']);
    $abstract       = trim($_POST['abstract']);

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $error = 'Only PDF files allowed.';
        } else {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newFilename = time() . '_' . bin2hex(random_bytes(5)) . '.pdf';
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $newFilename)) {
                $stmt = $conn->prepare(
                  "INSERT INTO papers
                    (title,author,abstract,date_published,department,file_path,status)
                   VALUES(?,?,?,?,?,?,'pending')"
                );
                $stmt->bind_param('ssssss', $title, $author, $abstract, $date_published, $department, $newFilename);
                if ($stmt->execute()) {
                    $message = 'Paper uploaded & pending approval.';
                } else {
                    $error = 'DB error: ' . $conn->error;
                }
            } else {
                $error = 'Failed to move uploaded file.';
            }
        }
    } else {
        $error = 'No file uploaded.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AnimoNexus – Admin Console</title>
<style>
  :root { --green:#006633; --green-light:#00a654; --green-lighter:#00cc66; --bg:#e6f2e6; --white:#fff; --gray-light:#f6faf6; --text-dark:#004d00; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { display:flex; height:100vh; font-family:Arial,sans-serif; background:var(--bg); color:var(--text-dark); overflow:hidden; }
  .sidebar { width:220px; background:var(--green); color:var(--white); display:flex; flex-direction:column; }
  .sidebar h2 { padding:20px; font-size:1.4rem; }
  .nav-btn { padding:14px 20px; color:var(--white); text-decoration:none; cursor:pointer; transition:.15s; }
  .nav-btn:hover, .nav-btn.active { background:var(--green-light); }
  .main { flex:1; display:flex; flex-direction:column; overflow:auto; }
  .topbar { background:var(--green); color:var(--white); padding:15px 35px; display:flex; justify-content:space-between; align-items:center; }
  .fail-list { background:#f8d7da; color:#721c24; padding:12px; border-radius:4px; margin-bottom:16px; }
  .fail-list ul { margin:8px 0 0 1.2em; }
  .fail-list li { margin-bottom:4px; }
  .content-section { display:none; padding:30px 40px; overflow:auto; }
  .content-section.active { display:block; }
  .upload-card { background:var(--white); margin:20px auto; padding:24px; border-radius:8px; max-width:600px; box-shadow:0 4px 12px rgba(0,0,0,.08); }
  .upload-card h2 { margin-bottom:16px; color:var(--green); }
  .upload-card form { display:grid; grid-template-columns:1fr 1fr; gap:16px 24px; align-items:center; }
  .upload-card form label { display:flex; flex-direction:column; font-weight:bold; }
  .upload-card form input, .upload-card form textarea { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; background:#fafafa; margin-top:4px; }
  .upload-card form textarea { grid-column:1/ span 2; min-height:100px; }
  .upload-card form button { grid-column:2; padding:10px 20px; background:var(--green-light); color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold; transition:.15s; margin-top:8px; }
  .upload-card form button:hover { background:var(--green); }
  .uploads-list { background:var(--white); margin:20px auto; padding:20px; border-radius:8px; max-width:800px; box-shadow:0 4px 12px rgba(0,0,0,.08); }
  .uploads-list table { width:100%; border-collapse:collapse; }
  .uploads-list th, .uploads-list td { padding:12px; border:1px solid var(--gray-light); text-align:left; }
  .check-btn { background:linear-gradient(to right,var(--green-light),var(--green-lighter)); color:#fff; padding:6px 14px; border-radius:4px; text-decoration:none; font-weight:bold; transition:.15s; }
  .check-btn:hover { box-shadow:0 0 6px var(--green-lighter); }
</style>
</head>
<body>
<nav class="sidebar">
  <h2>Admin</h2>
  <a href="#" class="nav-btn <?= $activeSection==='uploadSection'?'active':'' ?>" data-section="uploadSection">Upload a Paper</a>
  <a href="#" class="nav-btn <?= $activeSection==='uploadsSection'?'active':'' ?>" data-section="uploadsSection">Manage Papers</a>
</nav>
<section class="main">
  <div class="topbar">
    <h1>AnimoNexus – Admin Console</h1>
    <button onclick="location.href='logout.php'">Logout</button>
  </div>
  <!-- Upload -->
  <div id="uploadSection" class="content-section <?= $activeSection==='uploadSection'?'active':'' ?>">
    <div class="upload-card">
      <h2>Upload Approved Research Paper</h2>
      <?php if ($message): ?><p class="success"><?= htmlspecialchars($message) ?></p><?php elseif ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <label>Title<input type="text" name="title" required></label>
        <label>Author<input type="text" name="author" required></label>
        <label>Date Published<input type="date" name="date_published" required></label>
        <label>Department<input type="text" name="department" required></label>
        <label>Abstract<textarea name="abstract" required></textarea></label>
        <label>File<input type="file" name="file" accept="application/pdf" required></label>
        <button type="submit">Submit</button>
      </form>
    </div>
  </div>
  <!-- Manage -->
  <div id="uploadsSection" class="content-section <?= $activeSection==='uploadsSection'?'active':'' ?>">
    <div class="uploads-list">
      <?php if ($checked && !empty($fails)): ?>
        <div class="fail-list">
          <strong>Rejected because:</strong>
          <ul><?php foreach ($fails as $msg): ?><li><?= htmlspecialchars($msg) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
      <h2>Manage Pending Papers</h2>
      <?php $res=$conn->query("SELECT id,title,author,file_path FROM papers WHERE status='pending'");
      if ($res && $res->num_rows): ?>
        <table>
          <thead><tr><th>Title</th><th>Author</th><th>File</th><th>Action</th></tr></thead>
          <tbody><?php while ($row=$res->fetch_assoc()): ?><tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['author']) ?></td>
            <td><a href="uploads/<?= urlencode($row['file_path']) ?>" download>Download</a></td>
            <td><a href="?action=check&id=<?= $row['id'] ?>" class="check-btn">Check for Approval</a></td>
          </tr><?php endwhile; ?></tbody>
        </table>
      <?php else: ?><p>No pending papers.</p><?php endif; ?>
    </div>
  </div>
</section>
<script>
  document.querySelectorAll('.nav-btn').forEach(btn=>btn.addEventListener('click',e=>{
    e.preventDefault();
    document.querySelectorAll('.nav-btn').forEach(x=>x.classList.remove('active'));
    document.querySelectorAll('.content-section').forEach(s=>s.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.section).classList.add('active');
  }));
</script>
</body>
</html>
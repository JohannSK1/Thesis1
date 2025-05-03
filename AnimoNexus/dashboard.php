<?php
/* dashboard.php  – repository view for students & faculty + comments */
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
// ensure 'type' is always defined (students default to 'student')
if (!isset($user['type'])) {
    $user['type'] = 'student';
}

// Handle faculty comment submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'], $_POST['paper_id'])) {
    $paper_id = (int)$_POST['paper_id'];
    $comment  = trim($_POST['comment']);
    if ($comment !== '') {
        $stmtC = $conn->prepare("
            INSERT INTO comments (paper_id, faculty_id, comment_text, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmtC->bind_param('iis', $paper_id, $user['id'], $comment);
        if ($stmtC->execute()) {
            $message = 'Comment added successfully.';
        } else {
            $message = 'Failed to add comment: ' . $conn->error;
        }
    }
}

$search     = trim($_GET['q']           ?? '');
$filterDept = trim($_GET['filter_dept'] ?? '');

/* --------------------------- build query --------------------------- */
$sql = "SELECT id, title, author, abstract, date_published,
               department, file_path, url
          FROM papers
         WHERE status='approved'";

$params = [];
$types  = '';

if ($filterDept !== '') {
    $sql      .= " AND department = ?";
    $params[]  = $filterDept;
    $types    .= 's';
}
if ($search !== '') {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR department LIKE ?)";
    $wild = "%$search%";
    array_push($params, $wild, $wild, $wild);
    $types .= 'sss';
}

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$departments = [
  'College of Information and Computer Studies',
  'College of Education',
  'College of Engineering, Architecture and Technology',
  'College of Liberal Arts and Communication',
  'College of Tourism and Hospitality Management',
  'College of Science',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AnimoNexus – Dashboard</title>
<style>
:root{
  --green:#006633;--green-light:#007a33;--green-lighter:#00cc66;
  --page-bg:#e6f2e6;
}
body{margin:0;font-family:Arial,Helvetica,sans-serif;background:var(--page-bg);color:#004d00}
.page-heading{background:var(--green);color:#fff;display:flex;justify-content:space-between;align-items:center;padding:15px 100px}
.branding{display:flex;align-items:center;gap:14px}
.branding img.logo{height:48px;filter:invert(1)}
.branding h1{margin:0;font-size:2rem}
.nav{display:flex;gap:28px;align-items:center;font-size:1.05rem}
.nav a.link{color:#e3f5e3;text-decoration:none;transition:.15s}
.nav a.link:hover{color:#fff;text-shadow:0 0 4px rgba(255,255,255,.5)}
.nav a.btn{background:linear-gradient(to right,var(--green-light),var(--green-lighter));color:#fff;text-decoration:none;font-weight:bold;padding:8px 30px;border-radius:5px;transition:.2s}
.nav a.btn:hover{box-shadow:0 0 10px var(--green-lighter)}

.wrap{padding:40px 8%}
.controls{margin:10px 0 28px;display:flex;gap:8px;max-width:600px}
.controls select,.controls input{border:1px solid #999;border-radius:4px;padding:8px;font-size:1rem}
.controls select{width:200px;background:#fff}
.controls input{flex:1}
.controls button{border:none;border-radius:4px;padding:8px 22px;background:var(--green-light);color:#fff;font-weight:bold;cursor:pointer;transition:.15s}
.controls button:hover{background:var(--green)}

table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #ccc}
thead tr{background:#d9ead9}
th,td{padding:12px;border:1px solid #bbb;text-align:left;vertical-align:top}
tbody tr:nth-child(even){background:#f6faf6}
a.preview-link,a.ext-link{color:var(--green-light);text-decoration:none}
a.preview-link:hover,a.ext-link:hover{text-decoration:underline}
.no-results{text-align:center;color:#666}

.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:1000;align-items:center;justify-content:center}
.modal-inner{width:85%;height:90%;background:#fff;box-shadow:0 0 18px rgba(0,0,0,.4);position:relative;border-radius:6px;overflow:hidden}
.modal-inner iframe{width:100%;height:100%;border:none}
.modal-close{position:absolute;top:8px;right:12px;font-size:1.7rem;line-height:1;color:#fff;background:var(--green-light);border:none;border-radius:50%;width:34px;height:34px;cursor:pointer}
.modal-close:hover{background:var(--green)}
@media (max-width:768px){.modal-inner{width:95%;height:90%}}

.success { color: #006633; margin-bottom: 16px; font-weight: bold; }
</style>
</head>
<body>

<header class="page-heading">
  <div class="branding">
    <img src="static/images/animonexus_logo.png" class="logo" alt="logo">
    <h1>AnimoNexus</h1>
  </div>
  <nav class="nav">
    <a href="index.php" class="link">Home</a>
    <a href="logout.php" class="btn">Logout</a>
  </nav>
</header>

<div class="wrap">
  <?php if ($message): ?>
    <p class="success"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <h2>
    Welcome,
    <?= htmlspecialchars(
         $user['first_name']
         . (!empty($user['middle_initial']) ? ' '.$user['middle_initial'].'. ' : ' ')
         . $user['last_name']
    ) ?>!
  </h2>

  <!-- search & filter controls -->
  <form class="controls" method="get">
    <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
    <select name="filter_dept" onchange="this.form.submit()">
      <option value="">All Departments</option>
      <?php foreach ($departments as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= $filterDept === $d ? 'selected' : '' ?>>
          <?= htmlspecialchars($d) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="q" placeholder="Search papers…" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>

  <?php if ($search !== ''): ?>
    <p><em>Showing results for “<?= htmlspecialchars($search) ?>”</em></p>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>Title</th><th>Author</th><th>Abstract</th>
        <th>Date Published</th><th>Department</th>
        <th>Link&nbsp;/ URL</th><th>Preview PDF</th>
        <?php if ($user['type'] === 'faculty'): ?>
          <th>Comment</th>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
    <?php if ($result->num_rows): ?>
      <?php while ($p = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($p['title']) ?></td>
          <td><?= htmlspecialchars($p['author']) ?></td>
          <td><?= nl2br(htmlspecialchars($p['abstract'])) ?></td>
          <td><?= htmlspecialchars($p['date_published']) ?></td>
          <td><?= htmlspecialchars($p['department']) ?></td>
          <td>
            <?php if (!empty($p['url'])): ?>
              <a class="ext-link" href="<?= htmlspecialchars($p['url']) ?>" target="_blank" rel="noopener">Visit&nbsp;link</a>
            <?php else: ?>&mdash;<?php endif; ?>
          </td>
          <td>
            <a href="#"
               class="preview-link"
               data-file="/AnimoNexus/uploads/<?= rawurlencode($p['file_path']) ?>">
              View
            </a>
          </td>
          <?php if ($user['type'] === 'faculty'): ?>
          <td>
            <form method="post">
              <input type="hidden" name="paper_id" value="<?= $p['id'] ?>">
              <textarea name="comment" required
                placeholder="Add a comment…"
                style="width:100%;height:60px;margin-bottom:6px"></textarea><br>
              <button type="submit">Submit</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr>
        <td colspan="<?= $user['type']==='faculty'?8:7 ?>" class="no-results">
          No papers found.
        </td>
      </tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal viewer -->
<div class="modal" id="pdfModal">
  <div class="modal-inner">
    <button class="modal-close" title="Close">&times;</button>
    <iframe id="pdfFrame" allowfullscreen></iframe>
  </div>
</div>

<script>
document.querySelectorAll('.preview-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const raw = link.dataset.file;
    const url = raw + '#toolbar=0&navpanes=0&scrollbar=0';
    document.getElementById('pdfFrame').src = url;
    document.getElementById('pdfModal').style.display = 'flex';
  });
});
const modal = document.getElementById('pdfModal');
modal.addEventListener('click', e => {
  if (e.target.classList.contains('modal') || e.target.classList.contains('modal-close')) {
    document.getElementById('pdfFrame').src = '';
    modal.style.display = 'none';
  }
});
</script>
</body>
</html>

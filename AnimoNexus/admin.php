<?php
// admin.php ─ Admin Dashboard with comment display
session_start();
require 'db.php';

/* ───────────────────────── auth guard ─────────────────────────────── */
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit;
}

/* ───────────────────────────── delete handler ─────────────────────── */
if (isset($_GET['delete_id'])) {
    $del = (int)$_GET['delete_id'];
    $p = $conn->prepare("SELECT file_path FROM papers WHERE id=? AND status='approved'");
    $p->bind_param('i', $del);
    $p->execute();
    if ($row = $p->get_result()->fetch_assoc()) {
        @unlink(__DIR__ . '/uploads/' . $row['file_path']);
    }
    $d = $conn->prepare("DELETE FROM papers WHERE id=? AND status='approved'");
    $d->bind_param('i', $del);
    $_SESSION['flash'] = $d->execute()
        ? ['success' => "Paper #{$del} deleted."]
        : ['error'   => "Failed to delete: " . $conn->error];
    header('Location: admin.php?section=uploadsSection');
    exit;
}

/* ───────────────────────── upload handler ─────────────────────────── */
$message = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_paper'])) {
    $title          = trim($_POST['title']);
    $author         = trim($_POST['author']);
    $date_published = date('Y-m-d', strtotime($_POST['date_published']));
    $department     = trim($_POST['department']);
    $abstract       = trim($_POST['abstract']);
    $url            = trim($_POST['url'] ?? '');
    $uploaded_by    = $_SESSION['admin']['id'];

    if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $error = 'Only PDF files are allowed.';
        } else {
            $uploaddir = __DIR__ . '/uploads/';
            if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
            $newName = time() . '_' . bin2hex(random_bytes(5)) . '.pdf';

            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploaddir . $newName)) {
                $ins = $conn->prepare("
                  INSERT INTO papers
                    (title,author,abstract,date_published,department,
                     file_path,url,uploaded_by,status)
                  VALUES (?,?,?,?,?,?,?,?,'pending')
                ");
                $ins->bind_param(
                    'sssssssi',
                    $title,
                    $author,
                    $abstract,
                    $date_published,
                    $department,
                    $newName,
                    $url,
                    $uploaded_by
                );

                if ($ins->execute()) {
                    $message = 'Paper uploaded successfully and is pending approval.';
                    header('Location: admin.php?section=uploadsSection');
                    exit;
                } else {
                    $error = 'DB error: ' . $conn->error;
                }
            } else {
                $error = 'Failed to move uploaded file.';
            }
        }
    } else {
        $error = 'No file uploaded or upload error.';
    }
}

/* ─────────────────────────── check-paper handler ──────────────────── */
if (isset($_GET['check_id'])) {
    $id   = (int)$_GET['check_id'];
    $stmt = $conn->prepare("SELECT file_path,title FROM papers WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $python = 'C:/Users/jiket/AppData/Local/Programs/Python/Python313/python.exe';
        $script = __DIR__ . '/check_papers.py';
        $pdf    = __DIR__ . '/uploads/' . $row['file_path'];
        $cmd    = "\"$python\" \"$script\" \"$pdf\" 2>&1";
        $notes  = preg_split('/\r?\n/', trim(shell_exec($cmd)));
        $failed    = array_filter($notes, fn($l) => str_starts_with($l, 'FAIL:'));
        $newStatus = $failed ? 'rejected' : 'approved';

        $u = $conn->prepare("UPDATE papers SET status=?, review_notes=? WHERE id=?");
        $joined = implode("\n", $notes);
        $u->bind_param('ssi', $newStatus, $joined, $id);
        $u->execute();

        $_SESSION['flash'] = [
            'success' => 'The paper “' . $row['title'] . '” ' . (
                $newStatus === 'approved'
                    ? 'passed all checks and is now approved.'
                    : 'failed some checks and has been rejected.'
            ),
            'notes'   => $notes
        ];
    }
    header('Location: admin.php?section=uploadsSection');
    exit;
}

/* ───────────────────────────────── edit flow ──────────────────────── */
$editPaper = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $q   = $conn->prepare("SELECT * FROM papers WHERE id=? AND status='approved'");
    $q->bind_param('i', $eid);
    $q->execute();
    $editPaper = $q->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_paper'])) {
    $eid        = (int)$_POST['paper_id'];
    $title      = trim($_POST['title']);
    $author     = trim($_POST['author']);
    $date_pub   = date('Y-m-d', strtotime($_POST['date_published']));
    $department = trim($_POST['department']);
    $abstract   = trim($_POST['abstract']);
    $url        = trim($_POST['url']);

    $u = $conn->prepare("
      UPDATE papers
         SET title=?,author=?,date_published=?,department=?,abstract=?,url=?
       WHERE id=?
    ");
    $u->bind_param('ssssssi',
        $title,
        $author,
        $date_pub,
        $department,
        $abstract,
        $url,
        $eid
    );

    $_SESSION['flash'] = $u->execute()
        ? ['success' => "Paper #{$eid} updated."]
        : ['error'   => 'Update failed: ' . $conn->error];

    header('Location: admin.php?section=uploadsSection');
    exit;
}

/* ─────────────────────────────── page vars ────────────────────────── */
$section    = $_GET['section']     ?? 'uploadSection';
$filterDept = $_GET['filter_dept'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AnimoNexus - Admin Dashboard</title>
  <style>
    :root {
      --green:#006633;--green-light:#00a654;--green-lighter:#00cc66;
      --bg:#e6f2e6;--white:#fff;--gray-light:#f6faf6;--text-dark:#004d00;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{display:flex;height:100vh;font-family:Arial,Helvetica,sans-serif;
         background:var(--bg);color:var(--text-dark);overflow:hidden}
    .sidebar{width:220px;background:var(--green);color:#fff;display:flex;flex-direction:column}
    .sidebar h2{padding:25px 20px;font-size:1.4rem}
    .nav-btn{padding:14px 20px;color:#fff;text-decoration:none;transition:.15s}
    .nav-btn:hover,.nav-btn.active{background:var(--green-light)}
    .main{flex:1;display:flex;flex-direction:column;overflow:auto}
    .topbar{background:var(--green);color:#fff;display:flex;
            justify-content:space-between;align-items:center;padding:15px 35px}
    .topbar h1{font-size:1.2rem}
    .logout{background:linear-gradient(to right,var(--green-light),var(--green-lighter));
            border:none;padding:8px 26px;border-radius:5px;color:#fff;cursor:pointer}
    .logout:hover{box-shadow:0 0 10px var(--green-lighter)}
    .content-section{display:none;flex:1;padding:30px 40px;overflow:auto}
    .content-section.active{display:block}
    /* limit upload & edit cards to 900px */
    .upload-card,.edit-card {
        background:#fff;border-radius:8px;padding:24px;
        max-width:900px;margin:0 auto 24px;box-shadow:0 4px 12px rgba(0,0,0,.08);
    }
    /* allow all uploads-list (incl. approved papers) to span full width */
    .uploads-list {
        background:#fff;border-radius:8px;padding:24px;
        margin:0 auto 24px;box-shadow:0 4px 12px rgba(0,0,0,.08);
        max-width:none; /* remove 900px cap */
        width:auto;
    }
    /* make tables inside uploads-list fill their container */
    .uploads-list table {
        width:100%;
        table-layout:auto;
    }
    .upload-card h2,.uploads-list h2{margin-bottom:16px;color:var(--green);font-size:1.3rem}
    label{display:block;margin:12px 0 6px;font-weight:bold}
    input[type=text],input[type=date],select,textarea{
        width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;
        background:var(--gray-light);font-size:1rem}
    textarea{min-height:120px;resize:vertical}
    button{margin-top:16px;padding:10px 20px;border:none;border-radius:4px;
           background:var(--green);color:#fff;font-weight:bold;cursor:pointer}
    button:hover{background:var(--green-light)}
    .msg{max-width:900px;margin:0 auto 24px;padding:16px;border-radius:6px}
    .success{background:#e6f9e6;color:#006633}.error{background:#f9e6e6;color:#633333}
    .msg .main-msg{font-weight:bold;margin-bottom:.75rem}
    .msg ul{list-style:none;margin:0;padding-left:0}
    .msg li{padding-left:1.6rem;position:relative}
    .msg li::before{content:attr(data-icon);position:absolute;left:0;font-weight:bold}
    table{border-collapse:separate;border-spacing:0 8px}
    th,td{padding:12px 16px;text-align:left;vertical-align:top;word-wrap:break-word}
    thead tr{background:var(--gray-light)}tbody tr{background:#fff}
    tbody tr:hover{background:var(--bg)}
    .btn{display:inline-block;padding:6px 12px;margin-right:6px;
         background:var(--green);color:#fff;text-decoration:none;border-radius:4px}
    .btn:hover{background:var(--green-light)}
  </style>
</head>
<body>

<nav class="sidebar">
  <h2>AnimoNexus</h2>
  <a class="nav-btn <?= $section==='uploadSection'?'active':'' ?>"
     href="?section=uploadSection">Upload a Paper</a>
  <a class="nav-btn <?= $section==='uploadsSection'?'active':'' ?>"
     href="?section=uploadsSection">Manage Papers</a>
</nav>

<section class="main">
  <div class="topbar">
    <h1>Admin Dashboard</h1>
    <button class="logout" onclick="location.href='logout.php'">Logout</button>
  </div>

  <!-- UPLOAD SECTION -->
  <div id="uploadSection" class="content-section <?= $section==='uploadSection'?'active':'' ?>">
    <div class="upload-card">
      <h2>Upload Published Research Paper</h2>
      <?php if ($message): ?>
        <div class="msg success"><div class="main-msg"><?= htmlspecialchars($message) ?></div></div>
      <?php elseif ($error): ?>
        <div class="msg error"><div class="main-msg"><?= htmlspecialchars($error) ?></div></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="upload_paper" value="1">
        <label>Title:<input type="text" name="title" required></label>
        <label>Author:<input type="text" name="author" required></label>
        <label>Date Published:<input type="date" name="date_published" required></label>
        <label>Department:
          <select name="department" required>
            <option value="" disabled selected>-- Select Department --</option>
            <?php foreach ([
              'College of Information and Computer Studies',
              'College of Education',
              'College of Engineering, Architecture and Technology',
              'College of Liberal Arts and Communication',
              'College of Tourism and Hospitality Management',
              'College of Science'
            ] as $d): ?>
              <option><?= htmlspecialchars($d) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Abstract:<textarea name="abstract" required></textarea></label>
        <label>Link / URL:<input type="text" name="url" placeholder="https://…"></label>
        <label>PDF:<input type="file" name="file" accept="application/pdf" required></label>
        <button type="submit">Submit Paper</button>
      </form>
    </div>
  </div>

  <!-- PENDING PAPERS -->
  <div id="uploadsSection" class="content-section <?= $section==='uploadsSection'?'active':'' ?>">
    <?php if (!empty($_SESSION['flash'])):
      $f = $_SESSION['flash'];
      $fails=$passes=[];
      if (!empty($f['notes']) && is_array($f['notes'])) {
        foreach ($f['notes'] as $n) {
          if (str_starts_with($n,'FAIL:')) $fails[]=$n;
          elseif (str_starts_with($n,'PASS:')) $passes[]=$n;
        }
      }
    ?>
      <div class="msg <?= isset($f['error'])?'error':'success' ?>">
        <div class="main-msg"><?= htmlspecialchars($f['error'] ?? $f['success']) ?></div>
        <?php if ($fails): ?>
          <p>Failed to meet the following:</p><ul><?php foreach ($fails as $ln): ?><li data-icon="✗"><?= htmlspecialchars($ln) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if ($passes): ?>
          <p><?= $fails?'but meets the following requirements:':'Met the following requirements:' ?></p><ul><?php foreach ($passes as $ln): ?><li data-icon="✓"><?= htmlspecialchars($ln) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
      </div>
      <?php unset($_SESSION['flash']); endif; ?>

    <div class="uploads-list">
      <h2>Pending Papers</h2>
      <?php $pending = $conn->query("SELECT id,title,author,file_path,review_notes FROM papers WHERE status='pending'"); ?>
      <?php if ($pending && $pending->num_rows): ?>
        <table>
          <thead><tr><th>Title</th><th>Author</th><th>File</th><th>Notes</th><th>Action</th></tr></thead>
          <tbody>
            <?php while($r=$pending->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['author']) ?></td>
                <td><a href="uploads/<?= urlencode($r['file_path']) ?>" download>Download</a></td>
                <td><?= nl2br(htmlspecialchars($r['review_notes'] ?? '')) ?></td>
                <td><a class="btn" href="?section=uploadsSection&check_id=<?= $r['id'] ?>">Check</a></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No pending papers.</p>
      <?php endif; ?>
    </div>

    <!-- EDIT FORM -->
    <?php if ($editPaper): ?>
      <div class="edit-card uploads-list">
        <h2>Edit Paper #<?= $editPaper['id'] ?></h2>
        <form method="post">
          <input type="hidden" name="edit_paper" value="1">
          <input type="hidden" name="paper_id"  value="<?= $editPaper['id'] ?>">
          <label>Title:<input type="text" name="title" value="<?= htmlspecialchars($editPaper['title']) ?>" required></label>
          <label>Author:<input type="text" name="author" value="<?= htmlspecialchars($editPaper['author']) ?>" required></label>
          <label>Date Published:<input type="date" name="date_published" value="<?= htmlspecialchars($editPaper['date_published']) ?>" required></label>
          <label>Department:
            <select name="department" required>
              <option value="" disabled>-- Select Department --</option>
              <?php foreach ([
                'College of Information and Computer Studies',
                'College of Education',
                'College of Engineering, Architecture and Technology',
                'College of Liberal Arts and Communication',
                'College of Tourism and Hospitality Management',
                'College of Science'
              ] as $d): ?>
                <option <?= $editPaper['department']===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Abstract:<textarea name="abstract" required><?= htmlspecialchars($editPaper['abstract']) ?></textarea></label>
          <label>Public Link / URL:<input type="text" name="url" value="<?= htmlspecialchars($editPaper['url']) ?>"></label>
          <button type="submit">Save Changes</button>
          <a class="btn" href="?section=uploadsSection">Cancel</a>
        </form>
      </div>
    <?php endif; ?>

    <!-- APPROVED PAPERS -->
    <div class="uploads-list">
      <h2>Manage Approved Papers</h2>
      <form method="get" style="max-width:400px;margin:16px 0;">
        <input type="hidden" name="section" value="uploadsSection">
        <label for="filter_dept" style="font-weight:bold;display:block;margin-bottom:6px;">
          Filter by Department:
        </label>
        <select id="filter_dept" name="filter_dept" onchange="this.form.submit()" style="width:100%;padding:6px 12px;">
          <option value="" <?= $filterDept===''?'selected':'' ?>>-- All Departments --</option>
          <?php foreach ([
            'College of Information and Computer Studies',
            'College of Education',
            'College of Engineering, Architecture and Technology',
            'College of Liberal Arts and Communication',
            'College of Tourism and Hospitality Management',
            'College of Science'
          ] as $d): ?>
            <option value="<?= htmlspecialchars($d) ?>" <?= $filterDept===$d?'selected':'' ?>>
              <?= htmlspecialchars($d) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <?php
        $sql = "SELECT id,title,author,date_published,department,abstract,url
                  FROM papers WHERE status='approved'";
        if ($filterDept) {
            $sql .= " AND department='" . $conn->real_escape_string($filterDept) . "'";
        }
        $app = $conn->query($sql);
      ?>
      <?php if ($app && $app->num_rows): ?>
        <table>
          <thead>
            <tr>
              <th>#</th><th>Title</th><th>Author</th><th>Date</th>
              <th>Dept.</th><th>Abstract</th><th>URL</th><th>Actions</th><th>Comments</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($a = $app->fetch_assoc()): ?>
              <tr>
                <td><?= $a['id'] ?></td>
                <td><?= htmlspecialchars($a['title']) ?></td>
                <td><?= htmlspecialchars($a['author']) ?></td>
                <td><?= htmlspecialchars($a['date_published']) ?></td>
                <td><?= htmlspecialchars($a['department']) ?></td>
                <td><?= nl2br(htmlspecialchars($a['abstract'])) ?></td>
                <td>
                  <?php if (trim($a['url']) !== ''): ?>
                    <a href="<?= htmlspecialchars($a['url']) ?>" target="_blank">open&nbsp;link</a>
                  <?php endif; ?>
                </td>
                <td>
                  <a class="btn" href="?section=uploadsSection&edit_id=<?= $a['id'] ?>">Edit</a>
                  <a class="btn" href="?section=uploadsSection&delete_id=<?= $a['id'] ?>"
                     onclick="return confirm('Delete paper #<?= $a['id'] ?>?')">Delete</a>
                </td>
                <td>
                  <?php
                    $stmtCom = $conn->prepare("
                      SELECT c.comment_text, c.created_at,
                             f.first_name, f.last_name
                        FROM comments c
                        JOIN faculty f ON c.faculty_id=f.id
                       WHERE c.paper_id = ?
                       ORDER BY c.created_at DESC
                    ");
                    $stmtCom->bind_param('i',$a['id']);
                    $stmtCom->execute();
                    $resCom = $stmtCom->get_result();
                    if ($resCom && $resCom->num_rows):
                      while ($c = $resCom->fetch_assoc()):
                  ?>
                      <p>
                        <strong>
                          <?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?>
                          (<?= htmlspecialchars($c['created_at']) ?>):
                        </strong>
                        <?= nl2br(htmlspecialchars($c['comment_text'])) ?>
                      </p>
                  <?php
                      endwhile;
                    else:
                      echo '&mdash;';
                    endif;
                  ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p><?= $filterDept ? 'No papers are uploaded for this department.' : 'No approved papers.' ?></p>
      <?php endif; ?>
    </div>

  </div><!-- /#uploadsSection -->
</section>
</body>
</html>

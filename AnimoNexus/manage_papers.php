<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';

if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE papers SET status = 'approved' WHERE id = ?");
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE papers SET status = 'rejected' WHERE id = ?");
    }

    if (isset($stmt)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Paper successfully " . ($action === 'approve' ? "approved" : "rejected") . ".";
        } else {
            $message = "Action failed.";
        }
    }
}

$result = $conn->query("SELECT p.*, u.username FROM papers p JOIN users u ON p.uploaded_by = u.id WHERE p.status = 'pending'");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Papers - Admin - DataNest</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="background-color:#1e1e1e; color:white; font-family:Arial, sans-serif;">
    <div style="padding: 20px;">
        <h1>Manage Pending Papers</h1>
        <p><a href="upload.php" style="color:lightblue;">Upload New Paper</a> | <a href="dashboard.php" style="color:lightblue;">Dashboard</a> | <a href="../logout.php" style="color:lightblue;">Logout</a></p>

        <?php if (!empty($message)): ?>
            <p style="color: lightgreen;"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if ($result->num_rows > 0): ?>
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color:#2a2a2a;">
                        <th style="padding:10px; border:1px solid #444;">Title</th>
                        <th style="padding:10px; border:1px solid #444;">Author</th>
                        <th style="padding:10px; border:1px solid #444;">Uploaded By</th>
                        <th style="padding:10px; border:1px solid #444;">File</th>
                        <th style="padding:10px; border:1px solid #444;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="padding:10px; border:1px solid #444;"><?php echo htmlspecialchars($row['title']); ?></td>
                            <td style="padding:10px; border:1px solid #444;"><?php echo htmlspecialchars($row['author']); ?></td>
                            <td style="padding:10px; border:1px solid #444;"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td style="padding:10px; border:1px solid #444;">
                                <a href="../uploads/<?php echo $row['file_path']; ?>" style="color:lightblue;" download>Download</a>
                            </td>
                            <td style="padding:10px; border:1px solid #444;">
                                <a href="?action=approve&id=<?php echo $row['id']; ?>" style="color:lightgreen;">Approve</a> | 
                                <a href="?action=reject&id=<?php echo $row['id']; ?>" style="color:red;">Reject</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending papers.</p>
        <?php endif; ?>
    </div>
</body>
</html>

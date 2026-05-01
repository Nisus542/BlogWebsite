<?php
require_once 'config.php';
requireLogin();
if (!isAdmin()) { header('Location: index.php'); exit; }

// Approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['approved','rejected'], true)) {
        $stmt = $mysqli->prepare("UPDATE posts SET status=? WHERE id=?");
        $stmt->bind_param('si', $action, $id);
        $stmt->execute();
    }
    header('Location: admin_dashboard.php');
    exit;
}

// Lists
$pending = $mysqli->query("SELECT posts.id, title, users.name, posts.created_at
                           FROM posts JOIN users ON users.id=posts.user_id
                           WHERE status='pending' ORDER BY posts.created_at ASC");
$approved = $mysqli->query("SELECT posts.id, title, users.name, posts.created_at
                            FROM posts JOIN users ON users.id=posts.user_id
                            WHERE status='approved' ORDER BY posts.created_at DESC");

include 'header.php';
?>
<div class="container my-5">
  <h2>Admin — Post approvals</h2>

  <h5 class="mt-4">Pending</h5>
  <table class="table">
    <thead><tr><th>Title</th><th>Author</th><th>Submitted</th><th>Actions</th></tr></thead>
    <tbody>
      <?php while ($p = $pending->fetch_assoc()): ?>
      <tr>
        <td><?= sanitize($p['title']) ?></td>
        <td><?= sanitize($p['name']) ?></td>
        <td><?= $p['created_at'] ?></td>
        <td>
          <form method="post" class="d-inline">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="hidden" name="action" value="approved">
            <button class="btn btn-sm btn-success">Approve</button>
          </form>
          <form method="post" class="d-inline ms-2">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="hidden" name="action" value="rejected">
            <button class="btn btn-sm btn-danger">Reject</button>
          </form>
          <a class="btn btn-sm btn-secondary ms-2" href="view_post.php?id=<?= $p['id'] ?>">Preview</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <h5 class="mt-4">Approved</h5>
  <ul class="list-group">
    <?php while ($p = $approved->fetch_assoc()): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <span><strong><?= sanitize($p['title']) ?></strong> by <?= sanitize($p['name']) ?></span>
      <a class="btn btn-sm btn-outline-secondary" href="view_post.php?id=<?= $p['id'] ?>">View</a>
    </li>
    <?php endwhile; ?>
  </ul>
</div>
<?php include 'footer.php'; ?>
<?php
require_once 'config.php';
requireLogin();
if (!isAdmin()) { 
    header('Location: dashboard.php'); 
    exit; 
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['approved','rejected'], true)) {
        $stmt = $mysqli->prepare("UPDATE posts SET status=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('si', $action, $id);
        $stmt->execute();
        $message = ($action === 'approved') ? 'Post approved successfully!' : 'Post rejected!';
    }
}

// Fetch statistics
$statsQuery = "SELECT 
    COUNT(*) as total_posts,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_posts,
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_posts,
    SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected_posts
    FROM posts";
$stats = $mysqli->query($statsQuery)->fetch_assoc();

$totalUsers = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role='user'")->fetch_assoc()['count'];

// Fetch pending posts
$pending = $mysqli->query("SELECT posts.id, posts.title, posts.content, users.name, posts.created_at
                           FROM posts 
                           JOIN users ON users.id=posts.user_id
                           WHERE status='pending' 
                           ORDER BY posts.created_at ASC");

// Fetch recent approved posts
$approved = $mysqli->query("SELECT posts.id, posts.title, users.name, posts.created_at
                            FROM posts 
                            JOIN users ON users.id=posts.user_id
                            WHERE status='approved' 
                            ORDER BY posts.created_at DESC 
                            LIMIT 10");

// Fetch recent rejected posts
$rejected = $mysqli->query("SELECT posts.id, posts.title, users.name, posts.created_at
                            FROM posts 
                            JOIN users ON users.id=posts.user_id
                            WHERE status='rejected' 
                            ORDER BY posts.created_at DESC 
                            LIMIT 5");

include 'header.php';
?>
<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2>Admin Dashboard</h2>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="admin_categories.php"> Categories</a>
      <a class="btn btn-outline-primary" href="dashboard.php">My Posts</a>
    </div>
  </div>

  <?php if (isset($message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= sanitize($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Statistics Cards -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card text-white bg-primary">
        <div class="card-body">
          <h5 class="card-title">Total Posts</h5>
          <h2><?= $stats['total_posts'] ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-warning">
        <div class="card-body">
          <h5 class="card-title">Pending Posts</h5>
          <h2><?= $stats['pending_posts'] ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-success">
        <div class="card-body">
          <h5 class="card-title">Approved Posts</h5>
          <h2><?= $stats['approved_posts'] ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-danger">
        <div class="card-body">
          <h5 class="card-title">Rejected Posts</h5>
          <h2><?= $stats['rejected_posts'] ?></h2>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-3">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Total Users</h5>
          <h2><?= $totalUsers ?></h2>
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Posts Section -->
  <div class="card mb-4">
    <div class="card-header bg-warning">
      <h5 class="mb-0">Pending Posts (Require Approval)</h5>
    </div>
    <div class="card-body">
      <?php if ($pending->num_rows === 0): ?>
        <p class="text-muted">No pending posts to review.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Submitted</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($p = $pending->fetch_assoc()): ?>
              <tr>
                <td>
                  <strong><?= sanitize($p['title']) ?></strong>
                  <br>
                  <small class="text-muted"><?= sanitize(substr($p['content'], 0, 100)) ?>...</small>
                </td>
                <td><?= sanitize($p['name']) ?></td>
                <td><small><?= date('M d, Y g:i A', strtotime($p['created_at'])) ?></small></td>
                <td>
                  <div class="btn-group" role="group">
                    <a class="btn btn-sm btn-info" href="view_post.php?id=<?= $p['id'] ?>" target="_blank">
                      👁️ Preview
                    </a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Approve this post?')">
                      <input type="hidden" name="id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="action" value="approved">
                      <button class="btn btn-sm btn-success">Approve</button>
                    </form>
                    <form method="post" class="d-inline" onsubmit="return confirm('Reject this post?')">
                      <input type="hidden" name="id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="action" value="rejected">
                      <button class="btn btn-sm btn-danger">Reject</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Approved Posts -->
  <div class="card mb-4">
    <div class="card-header bg-success text-white">
      <h5 class="mb-0">Recently Approved Posts</h5>
    </div>
    <div class="card-body">
      <?php if ($approved->num_rows === 0): ?>
        <p class="text-muted">No approved posts yet.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php while ($p = $approved->fetch_assoc()): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong><?= sanitize($p['title']) ?></strong>
              <br>
              <small class="text-muted">by <?= sanitize($p['name']) ?> • <?= date('M d, Y', strtotime($p['created_at'])) ?></small>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="view_post.php?id=<?= $p['id'] ?>">View</a>
          </li>
          <?php endwhile; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Rejected Posts -->
  <div class="card mb-4">
    <div class="card-header bg-danger text-white">
      <h5 class="mb-0">Recently Rejected Posts</h5>
    </div>
    <div class="card-body">
      <?php if ($rejected->num_rows === 0): ?>
        <p class="text-muted">No rejected posts.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php while ($p = $rejected->fetch_assoc()): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong><?= sanitize($p['title']) ?></strong>
              <br>
              <small class="text-muted">by <?= sanitize($p['name']) ?> • <?= date('M d, Y', strtotime($p['created_at'])) ?></small>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="view_post.php?id=<?= $p['id'] ?>">View</a>
          </li>
          <?php endwhile; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>

<?php
require_once 'config.php';
requireLogin();

// Redirect admin to admin dashboard
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

// Fetch own posts
$userId = $_SESSION['user_id'];
$mine = $mysqli->prepare("SELECT id, title, status, created_at FROM posts WHERE user_id=? ORDER BY created_at DESC");
$mine->bind_param('i', $userId);
$mine->execute();
$resMine = $mine->get_result();

include 'header.php';
?>
<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Dashboard</h2>
    <a class="btn btn-success" href="create_post.php"> Create New Post</a>
  </div>

  <!-- Welcome Message -->
  <div class="alert alert-info">
    <h5>Welcome, <?= sanitize($_SESSION['name']) ?>!</h5>
    <p class="mb-0">Create and manage your blog posts. Posts must be approved by admin before they appear publicly.</p>
  </div>

  <!-- User's Posts -->
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">Your Blog Posts</h5>
    </div>
    <div class="card-body">
      <?php if ($resMine->num_rows === 0): ?>
        <p class="text-muted text-center py-4">
          You haven't created any posts yet. Click "Create New Post" to get started!
        </p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($p = $resMine->fetch_assoc()): ?>
              <tr>
                <td><strong><?= sanitize($p['title']) ?></strong></td>
                <td>
                  <?php if ($p['status'] == 'approved'): ?>
                    <span class="badge bg-success">Approved</span>
                  <?php elseif ($p['status'] == 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending Review</span>
                  <?php else: ?>
                    <span class="badge bg-danger">Rejected</span>
                  <?php endif; ?>
                </td>
                <td><small><?= date('M d, Y', strtotime($p['created_at'])) ?></small></td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <a class="btn btn-primary" href="edit_post.php?id=<?= $p['id'] ?>"> Edit</a>

                    <?php if ($p['status'] == 'approved'): ?>
                      <a class="btn btn-info" href="view_post.php?id=<?= $p['id'] ?>"> View</a>
                    <?php endif; ?>

                    <a class="btn btn-danger" href="delete_post.php?id=<?= $p['id'] ?>"
                       onclick="return confirm('Are you sure you want to delete this post?')"> Delete</a>
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

  <!-- All Approved Posts Section -->
  <div class="card mt-4">
    <div class="card-header bg-success text-white">
      <h5 class="mb-0">All Published Posts</h5>
    </div>
    <div class="card-body">
      <?php
      $res = $mysqli->query("SELECT posts.id, title, users.name, posts.created_at 
                             FROM posts 
                             JOIN users ON users.id=posts.user_id 
                             WHERE status='approved' 
                             ORDER BY posts.created_at DESC");
      if ($res->num_rows === 0): ?>
        <p class="text-muted text-center py-3">No published posts yet.</p>
      <?php else: ?>
        <div class="list-group">
          <?php while ($row = $res->fetch_assoc()): ?>
            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
               href="view_post.php?id=<?= $row['id'] ?>">
              <div>
                <strong><?= sanitize($row['title']) ?></strong>
                <br>
                <small class="text-muted">by <?= sanitize($row['name']) ?> • <?= date('M d, Y', strtotime($row['created_at'])) ?></small>
              </div>
              <span class="badge bg-primary rounded-pill">Read →</span>
            </a>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

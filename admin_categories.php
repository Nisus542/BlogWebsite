<?php
require_once 'config.php';
requireLogin();
if (!isAdmin()) { header('Location: dashboard.php'); exit; }

$message = '';
$error   = '';

// Add category
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));

    if ($name && $slug) {
        $s = $mysqli->prepare("INSERT INTO categories(name,slug,description) VALUES(?,?,?)");
        $s->bind_param('sss', $name, $slug, $desc);
        if ($s->execute()) {
            $message = "Category \"$name\" added!";
        } else {
            $error = 'Name already exists or DB error.';
        }
    } else {
        $error = 'Name is required.';
    }
}

// Delete category
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $cid = (int)($_POST['cat_id'] ?? 0);
    $del = $mysqli->prepare("DELETE FROM categories WHERE id=?");
    $del->bind_param('i', $cid);
    $del->execute();
    $message = 'Category deleted.';
}

$cats = $mysqli->query("
    SELECT categories.*, COUNT(posts.id) AS post_count
    FROM categories
    LEFT JOIN posts ON posts.category_id = categories.id
    GROUP BY categories.id
    ORDER BY categories.name
");

include 'header.php';
?>
<div class="container my-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2> Manage Categories</h2>
    <a class="btn btn-outline-secondary" href="admin_dashboard.php">← Back to Dashboard</a>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= sanitize($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Add Form -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-header fw-bold" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-radius:14px 14px 0 0;">
           Add New Category
        </div>
        <div class="card-body p-4">
          <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
              <label class="form-label fw-semibold">Name *</label>
              <input name="name" class="form-control" required style="border-radius:8px;">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Description</label>
              <textarea name="description" rows="3" class="form-control" style="border-radius:8px;"></textarea>
            </div>
            <button class="btn btn-gradient w-100">Add Category</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Category List -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-header fw-bold bg-white" style="border-radius:14px 14px 0 0;border-bottom:1px solid #e2e8f0;">
          All Categories
        </div>
        <div class="card-body p-0">
          <table class="table mb-0">
            <thead style="background:#f8fafc;">
              <tr>
                <th class="ps-4">Name</th>
                <th>Slug</th>
                <th>Posts</th>
                <th>Description</th>
                <th class="pe-4">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($c = $cats->fetch_assoc()): ?>
              <tr>
                <td class="ps-4 fw-semibold"><?= sanitize($c['name']) ?></td>
                <td><code><?= sanitize($c['slug']) ?></code></td>
                <td><span class="badge bg-secondary"><?= $c['post_count'] ?></span></td>
                <td class="text-muted" style="font-size:.85rem;"><?= sanitize(substr($c['description'] ?? '', 0, 50)) ?></td>
                <td class="pe-4">
                  <form method="post" class="d-inline"
                        onsubmit="return confirm('Delete category? Posts will become uncategorised.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="cat_id" value="<?= $c['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>

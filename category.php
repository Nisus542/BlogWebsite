<?php
require_once 'config.php';

$slug = trim($_GET['slug'] ?? '');
$cat = null;

if ($slug) {
    $stmt = $mysqli->prepare("SELECT * FROM categories WHERE slug=? LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $cat = $stmt->get_result()->fetch_assoc();
}

if (!$cat) {
    header('Location: index.php');
    exit;
}

// All approved posts in this category
$posts = $mysqli->prepare("
    SELECT posts.id, posts.title, posts.created_at, users.name AS author,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id=posts.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE comments.post_id=posts.id) AS comment_count
    FROM posts
    JOIN users ON users.id = posts.user_id
    WHERE posts.category_id = ? AND posts.status = 'approved'
    ORDER BY posts.created_at DESC
");
$posts->bind_param('i', $cat['id']);
$posts->execute();
$resPosts = $posts->get_result();

// All categories for sidebar
$allCats = $mysqli->query("
    SELECT categories.*, COUNT(posts.id) AS post_count
    FROM categories
    LEFT JOIN posts ON posts.category_id = categories.id AND posts.status='approved'
    GROUP BY categories.id
    ORDER BY categories.name
");

include 'header.php';
?>
<div class="container my-5">
  <div class="row">
    <div class="col-lg-8">
      <!-- Category Header -->
      <div class="d-flex align-items-center mb-4 gap-3">
        <div>
          <h2 class="fw-bold mb-1" style="color:#2d3748;"> <?= sanitize($cat['name']) ?></h2>
          <?php if ($cat['description']): ?>
            <p class="text-muted mb-0"><?= sanitize($cat['description']) ?></p>
          <?php endif; ?>
        </div>
        <span class="badge bg-primary ms-auto fs-6"><?= $resPosts->num_rows ?> posts</span>
      </div>

      <?php if ($resPosts->num_rows === 0): ?>
        <div class="text-center py-5">
          <div style="font-size:3rem;opacity:.3;">📭</div>
          <p class="text-muted mt-3">No posts in this category yet.</p>
        </div>
      <?php else: ?>
        <div class="list-group">
          <?php while ($p = $resPosts->fetch_assoc()): ?>
            <a class="custom-list-group-item" href="view_post.php?id=<?= $p['id'] ?>">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <strong><?= sanitize($p['title']) ?></strong>
                  <div class="text-muted mt-1" style="font-size:.88rem;">
                    by <?= sanitize($p['author']) ?> &bull; <?= date('M d, Y', strtotime($p['created_at'])) ?>
                  </div>
                  <div class="mt-1" style="font-size:.85rem; color:#667eea;">
                    ❤️ <?= $p['like_count'] ?> &nbsp;&nbsp; 💬 <?= $p['comment_count'] ?>
                  </div>
                </div>
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16" style="color:#667eea;flex-shrink:0;margin-top:4px;">
                  <path fill-rule="evenodd" d="M4 8a.5.5 0 0 1 .5-.5h5.793L8.146 5.354a.5.5 0 1 1 .708-.708l3 3a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708-.708L10.293 8.5H4.5A.5.5 0 0 1 4 8z"/>
                </svg>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4 mt-4 mt-lg-0">
      <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-header fw-bold" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-radius:12px 12px 0 0;">
          📚 All Categories
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            <?php while ($c = $allCats->fetch_assoc()): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                <a href="category.php?slug=<?= sanitize($c['slug']) ?>"
                   style="text-decoration:none;color:<?= $c['slug']===$slug ? '#667eea' : '#2d3748' ?>;font-weight:<?= $c['slug']===$slug ? '700' : '400' ?>;">
                  <?= sanitize($c['name']) ?>
                </a>
                <span class="badge rounded-pill bg-secondary"><?= $c['post_count'] ?></span>
              </li>
            <?php endwhile; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include 'footer.php'; ?>

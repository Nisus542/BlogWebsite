<?php include 'header.php'; ?>

<section class="hero-section">
  <div class="container">
    <h1>Blog with the Best</h1>
    <p class="lead">More bloggers and independent creators choose Blogger.<br>Start sharing your stories today.</p>
    <a href="<?= isLoggedIn() ? 'dashboard.php' : 'register.php' ?>" class="btn btn-hero">
      <?= isLoggedIn() ? 'Go to Dashboard' : 'Start Blogging Free' ?>
    </a>
  </div>
</section>

<div class="container my-5">

  <!-- Feature Cards -->
  <div class="row g-4 mb-5">
    <div class="col-md-4">
      <div class="card feature-card">
        <div class="card-body">
          <h5 class="card-title">Simple, meet Flexible</h5>
          <p class="card-text">Publish anywhere, build your audience, and keep full control over your content.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card feature-card">
        <div class="card-body">
          <h5 class="card-title">Rich Text Editor</h5>
          <p class="card-text">Format posts beautifully with headings, images, lists, and more using our built-in editor.</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card feature-card">
        <div class="card-body">
          <h5 class="card-title">Grow Together</h5>
          <p class="card-text">Like and comment on posts. Categories keep content organised and discoverable.</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-12">
      <h3 class="fw-bold mb-4" style="color:#2d3748;">Latest Published Posts</h3>
      <?php
      require_once 'config.php';

      // Optional category filter
      $filterCatId   = (int)($_GET['cat'] ?? 0);
      $filterCatName = '';
      if ($filterCatId) {
          $fc = $mysqli->prepare("SELECT name FROM categories WHERE id=? LIMIT 1");
          $fc->bind_param('i', $filterCatId);
          $fc->execute();
          $fcRow = $fc->get_result()->fetch_assoc();
          $filterCatName = $fcRow['name'] ?? '';
      }

      $sql = "SELECT posts.id, posts.title, posts.created_at, posts.cover_image, users.name AS author,
                     categories.name AS cat_name, categories.slug AS cat_slug,
                     (SELECT COUNT(*) FROM likes WHERE likes.post_id=posts.id) AS like_count,
                     (SELECT COUNT(*) FROM comments WHERE comments.post_id=posts.id) AS comment_count
              FROM posts
              JOIN users ON users.id = posts.user_id
              LEFT JOIN categories ON categories.id = posts.category_id
              WHERE posts.status = 'approved'";
      if ($filterCatId) $sql .= " AND posts.category_id = $filterCatId";
      $sql .= " ORDER BY posts.created_at DESC LIMIT 10";

      $res = $mysqli->query($sql);

      if ($filterCatName): ?>
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="badge" style="background:linear-gradient(135deg,#667eea,#764ba2);font-size:.9rem;padding:.4rem .9rem;">
            📂 <?= sanitize($filterCatName) ?>
          </span>
          <a href="index.php" class="btn btn-sm btn-outline-secondary">✕ Clear</a>
        </div>
      <?php endif;

      if ($res->num_rows === 0): ?>
        <div class="text-center py-5">
          <div style="font-size:4rem;opacity:.3;">📭</div>
          <h5 class="text-muted mt-3">No posts yet</h5>
          <p class="text-muted">Be the first to share your story!</p>
          <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-gradient mt-3">Get Started</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="list-group">
          <?php while ($row = $res->fetch_assoc()): ?>
            <a class="custom-list-group-item" href="view_post.php?id=<?= $row['id'] ?>">
              <div class="d-flex gap-3 align-items-start">
                <?php if (!empty($row['cover_image'])): ?>
                  <img src="<?= sanitize($row['cover_image']) ?>" alt=""
                       style="width:72px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0;">
                <?php endif; ?>
                <div class="flex-grow-1">
                  <?php if ($row['cat_name']): ?>
                    <span class="badge mb-1"
                          style="background:rgba(102,126,234,.15);color:#667eea;font-size:.75rem;">
                      <?= sanitize($row['cat_name']) ?>
                    </span>
                  <?php endif; ?>
                  <div><strong><?= sanitize($row['title']) ?></strong></div>
                  <div class="text-muted mt-1" style="font-size:.88rem;">
                    by <?= sanitize($row['author']) ?> &bull;
                    <?= date('M d, Y', strtotime($row['created_at'])) ?>
                  </div>
                  <div class="mt-1" style="font-size:.82rem;color:#718096;">
                    ❤️ <?= $row['like_count'] ?> &nbsp; 💬 <?= $row['comment_count'] ?>
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
    </div><!-- end col-12 -->
  </div><!-- end row -->
</div>

<?php include 'footer.php'; ?>

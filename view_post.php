<?php
require_once 'config.php';
$id = (int)($_GET['id'] ?? 0);

// Fetch post with author, category, like count
$stmt = $mysqli->prepare("
    SELECT posts.*, users.name AS author,
           categories.name AS category_name, categories.slug AS category_slug,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
           posts.cover_image
    FROM posts
    JOIN users ON users.id = posts.user_id
    LEFT JOIN categories ON categories.id = posts.category_id
    WHERE posts.id = ? LIMIT 1
");
$stmt->bind_param('i', $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

$canView = $post
  && ($post['status'] === 'approved'
      || (isLoggedIn() && (isAdmin() || $_SESSION['user_id'] == $post['user_id'])));

if (!$canView) { header('Location: index.php'); exit; }

// Has the current user liked this post?
$userLiked = false;
if (isLoggedIn() && !isAdmin()) {
    $lk = $mysqli->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=?");
    $lk->bind_param('ii', $id, $_SESSION['user_id']);
    $lk->execute();
    $userLiked = $lk->get_result()->num_rows > 0;
}

// Comments (non-admin only)
$resComments = null;
if (!isAdmin()) {
    $comments = $mysqli->prepare("
        SELECT comments.id, comments.content, comments.created_at, users.name,
               comments.user_id
        FROM comments
        JOIN users ON users.id = comments.user_id
        WHERE post_id = ? ORDER BY comments.created_at ASC
    ");
    $comments->bind_param('i', $id);
    $comments->execute();
    $resComments = $comments->get_result();
}

include 'header.php';
?>
<div class="container my-5" style="max-width:820px;">

  <!-- Category badge -->
  <?php if ($post['category_name']): ?>
    <div class="mb-3">
      <a href="category.php?slug=<?= sanitize($post['category_slug']) ?>"
         class="badge text-decoration-none"
         style="background:linear-gradient(135deg,#667eea,#764ba2);font-size:.85rem;padding:.45rem .85rem;border-radius:20px;">
        📂 <?= sanitize($post['category_name']) ?>
      </a>
    </div>
  <?php endif; ?>

  <h2 class="fw-bold mb-2" style="color:#1a202c;font-size:1.9rem;"><?= sanitize($post['title']) ?></h2>

  <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <span class="text-muted" style="font-size:.9rem;">
      ✍️ <?= sanitize($post['author']) ?> &nbsp;&bull;&nbsp;
      🗓️ <?= date('M d, Y', strtotime($post['created_at'])) ?>
    </span>
    <?php if ($post['status'] !== 'approved'): ?>
      <span class="badge bg-warning text-dark"><?= ucfirst($post['status']) ?></span>
    <?php endif; ?>
  </div>

  <!-- Post content styles -->
  <style>
    .post-content { line-height:1.8; font-size:1rem; color:#2d3748; }
    .post-content h1,.post-content h2,.post-content h3 { margin:1.4rem 0 .6rem; font-weight:700; color:#1a202c; }
    .post-content p { margin-bottom:1rem; }
    .post-content ul,.post-content ol { margin:0 0 1rem 1.5rem; }
    .post-content blockquote { border-left:4px solid #667eea; margin:1rem 0; padding:.5rem 1rem; background:#f0f1ff; border-radius:0 8px 8px 0; color:#4a5568; }
    .post-content pre { background:#1a202c; color:#e2e8f0; padding:1rem; border-radius:8px; overflow-x:auto; }
    .post-content code { background:#edf2f7; padding:.15rem .35rem; border-radius:4px; font-size:.9em; }
    .post-content img { max-width:100%; border-radius:8px; margin:.5rem 0; }
    .post-content a { color:#667eea; }
  </style>

  <!-- Cover Image -->
  <?php if (!empty($post['cover_image'])): ?>
    <div class="mb-4" style="border-radius:14px;overflow:hidden;max-height:420px;">
      <img src="<?= sanitize($post['cover_image']) ?>" alt="Cover image"
           style="width:100%;max-height:420px;object-fit:cover;border-radius:14px;">
    </div>
  <?php endif; ?>

  <!-- Post Content -->
  <div class="card border-0 shadow-sm mb-4 p-4 post-content" style="border-radius:14px;">
    <?= $post['content'] ?>
  </div>

  <!-- ── Like Button ── -->
  <?php if (!isAdmin() && isLoggedIn()): ?>
    <div class="mb-4">
      <button id="likeBtn"
              class="btn <?= $userLiked ? 'btn-danger' : 'btn-outline-danger' ?> d-inline-flex align-items-center gap-2"
              style="border-radius:50px;padding:.5rem 1.4rem;"
              data-post-id="<?= $id ?>"
              data-liked="<?= $userLiked ? '1' : '0' ?>">
        <span id="likeIcon"><?= $userLiked ? '❤️' : '🤍' ?></span>
        <span id="likeLabel"><?= $userLiked ? 'Liked' : 'Like' ?></span>
        <span class="badge <?= $userLiked ? 'bg-light text-danger' : 'bg-secondary' ?>" id="likeCount"
              style="font-size:.85rem;">
          <?= $post['like_count'] ?>
        </span>
      </button>
    </div>
  <?php elseif (!isAdmin()): ?>
    <div class="mb-4">
      <span class="text-muted" style="font-size:.92rem;">
        ❤️ <?= $post['like_count'] ?> likes &nbsp;—&nbsp;
        <a href="login.php" style="color:#667eea;">Login to like</a>
      </span>
    </div>
  <?php else: ?>
    <div class="mb-4 text-muted" style="font-size:.92rem;">❤️ <?= $post['like_count'] ?> likes</div>
  <?php endif; ?>

  <!-- ── Comments ── -->
  <?php if (!isAdmin()): ?>
    <hr class="my-4">
    <h5 class="fw-bold mb-3">💬 Comments
      <span class="badge bg-secondary ms-2" style="font-size:.78rem;"><?= $resComments->num_rows ?></span>
    </h5>

    <?php if ($resComments->num_rows === 0): ?>
      <p class="text-muted">No comments yet. Be the first!</p>
    <?php else: ?>
      <div class="d-flex flex-column gap-3 mb-4" id="commentList">
        <?php while ($c = $resComments->fetch_assoc()): ?>
          <div class="d-flex gap-3" id="comment-<?= $c['id'] ?>">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:40px;height:40px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;font-weight:700;font-size:1rem;">
              <?= strtoupper(mb_substr($c['name'], 0, 1)) ?>
            </div>
            <div class="flex-grow-1">
              <div class="card border-0 shadow-sm p-3" style="border-radius:12px;background:#f8fafc;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong style="font-size:.92rem;"><?= sanitize($c['name']) ?></strong>
                  <small class="text-muted"><?= date('M d, Y g:i A', strtotime($c['created_at'])) ?></small>
                </div>
                <p class="mb-0" style="font-size:.95rem;line-height:1.6;"><?= nl2br(sanitize($c['content'])) ?></p>
              </div>
              <?php if (isLoggedIn() && $_SESSION['user_id'] == $c['user_id']): ?>
                <form method="post" action="comment_delete.php" class="mt-1"
                      onsubmit="return confirm('Delete this comment?')">
                  <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="post_id" value="<?= $id ?>">
                  <button class="btn btn-sm btn-link text-danger p-0" style="font-size:.8rem;">🗑 Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

    <?php if (isLoggedIn()): ?>
      <div class="card border-0 shadow-sm p-4 mt-3" style="border-radius:14px;">
        <h6 class="fw-semibold mb-3">💬 Leave a Comment</h6>
        <form method="post" action="comment_add.php" id="commentForm">
          <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
          <div class="mb-3">
            <textarea name="content" id="commentEditor" rows="4" class="form-control"
                      placeholder="Share your thoughts…" required maxlength="1000"
                      style="border-radius:10px;resize:vertical;font-size:.95rem;line-height:1.6;
                             border:1.5px solid #cbd5e0;transition:border-color .2s;"
                      onfocus="this.style.borderColor='#667eea'"
                      onblur="this.style.borderColor='#cbd5e0'"
                      oninput="document.getElementById('charCount').textContent=this.value.length"></textarea>
            <div class="text-end text-muted mt-1" style="font-size:.78rem;">
              <span id="charCount">0</span>/1000
            </div>
          </div>
          <button type="submit" class="btn btn-gradient px-4">Post Comment</button>
        </form>
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        <a href="login.php" style="color:#667eea;font-weight:600;">Login</a> to leave a comment.
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="alert alert-secondary mt-4">
      You are viewing this post as admin. Comments and likes are visible to regular users.
    </div>
  <?php endif; ?>

</div>

<?php if (isLoggedIn() && !isAdmin()): ?>
<script>
// ── Like button AJAX ──────────────────────────────────────────────────────────
const likeBtn = document.getElementById('likeBtn');
if (likeBtn) {
  likeBtn.addEventListener('click', function () {
    const fd = new FormData();
    fd.append('post_id', this.dataset.postId);
    fetch('like_toggle.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.error) return;
        const icon  = document.getElementById('likeIcon');
        const label = document.getElementById('likeLabel');
        const count = document.getElementById('likeCount');
        if (data.liked) {
          likeBtn.classList.replace('btn-outline-danger', 'btn-danger');
          icon.textContent  = '❤️';
          label.textContent = 'Liked';
          count.classList.replace('bg-secondary', 'bg-light');
          count.classList.add('text-danger');
        } else {
          likeBtn.classList.replace('btn-danger', 'btn-outline-danger');
          icon.textContent  = '🤍';
          label.textContent = 'Like';
          count.classList.replace('bg-light', 'bg-secondary');
          count.classList.remove('text-danger');
        }
        count.textContent = data.count;
      });
  });
}
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>

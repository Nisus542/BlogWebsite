<?php
require_once 'config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $mysqli->prepare("SELECT id,title,content,user_id,status,category_id,cover_image FROM posts WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post || $post['user_id'] !== $_SESSION['user_id']) {
    header('Location: dashboard.php');
    exit;
}

$cats  = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title'] ?? '');
    $content    = $_POST['content'] ?? '';
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;
    $coverImage = $post['cover_image'];

    // Remove cover?
    if (!empty($_POST['remove_cover'])) {
        if ($coverImage && file_exists(__DIR__ . '/' . $coverImage)) @unlink(__DIR__ . '/' . $coverImage);
        $coverImage = null;
    }

    // New cover?
    if (!empty($_FILES['cover_image']['name'])) {
        $cf    = $_FILES['cover_image'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($cf['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
        if ($cf['error'] !== UPLOAD_ERR_OK) {
            $error = 'Cover upload error (code ' . $cf['error'] . ').';
        } elseif (!isset($allowed[$mime])) {
            $error = 'Cover must be JPEG, PNG, GIF or WebP.';
        } elseif ($cf['size'] > 5 * 1024 * 1024) {
            $error = 'Cover must be under 5 MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'cover_' . uniqid('', true) . '.' . $allowed[$mime];
            if (move_uploaded_file($cf['tmp_name'], $uploadDir . $filename)) {
                if ($post['cover_image'] && file_exists(__DIR__ . '/' . $post['cover_image']))
                    @unlink(__DIR__ . '/' . $post['cover_image']);
                $coverImage = 'uploads/' . $filename;
            } else {
                $error = 'Could not save cover image.';
            }
        }
    }

    if (!$error && $title && strip_tags($content)) {
        // If the post was already approved, keep it approved (no re-review needed).
        // Only reset to pending if it was pending or rejected.
        $newStatus = ($post['status'] === 'approved') ? 'approved' : 'pending';

        $stmt2 = $mysqli->prepare(
            "UPDATE posts SET title=?,content=?,category_id=?,cover_image=?,status=?,updated_at=NOW() WHERE id=?"
        );
        $stmt2->bind_param('ssissi', $title, $content, $categoryId, $coverImage, $newStatus, $id);
        if ($stmt2->execute()) {
            header('Location: dashboard.php?updated=1');
            exit;
        }
        $error = 'Database error.';
    } elseif (!$error) {
        $error = 'Title and content required.';
    }
}

include 'header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<style>
  .ql-toolbar.ql-snow {
    border-radius: 10px 10px 0 0;
    border: 1.5px solid #cbd5e0;
    background: #f8fafc;
  }
  .ql-container.ql-snow {
    border-radius: 0 0 10px 10px;
    border: 1.5px solid #cbd5e0;
    border-top: none;
    font-family: Inter, sans-serif;
    font-size: 15px;
    min-height: 280px;
  }
  .ql-editor { min-height: 280px; line-height: 1.75; padding: 14px 16px; }
  .ql-editor.ql-blank::before { color: #a0aec0; font-style: normal; }
  .cover-upload-area {
    border: 2px dashed #cbd5e0; border-radius: 12px; padding: 1.5rem;
    text-align: center; cursor: pointer; transition: border-color .2s, background .2s;
    position: relative; background: #fafbfc;
  }
  .cover-upload-area:hover { border-color: #667eea; background: #f0f1ff; }
  .cover-upload-area input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width:100%; height:100%;
  }
</style>

<div class="container my-5" style="max-width:780px;">
  <h2 class="fw-bold mb-4">✏️ Edit Post</h2>
  <?php if ($post['status'] === 'approved'): ?>
    <div class="alert alert-success">✅ This post is <strong>live</strong>. Your edits will be saved and published immediately — no re-approval needed.</div>
  <?php elseif ($post['status'] === 'pending'): ?>
    <div class="alert alert-warning">⏳ This post is awaiting approval. Saving will keep it in the review queue.</div>
  <?php else: ?>
    <div class="alert alert-info">ℹ️ Saving will re-submit this post for admin approval.</div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error) ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-body p-4">
      <form method="post" enctype="multipart/form-data" id="editForm">

        <!-- Title -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Title</label>
          <input name="title" class="form-control form-control-lg"
                 value="<?= sanitize($post['title']) ?>" required style="border-radius:10px;">
        </div>

        <!-- Category -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Category</label>
          <select name="category_id" class="form-select" style="border-radius:10px;">
            <option value="">— Select a category —</option>
            <?php while ($c = $cats->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>" <?= $c['id'] == $post['category_id'] ? 'selected' : '' ?>>
                <?= sanitize($c['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Cover Image -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Cover / Featured Image
            <small class="text-muted fw-normal">(optional · max 5 MB)</small>
          </label>

          <?php if ($post['cover_image']): ?>
            <div class="mb-2">
              <img src="<?= sanitize($post['cover_image']) ?>" alt="Current cover"
                   style="max-height:160px;border-radius:10px;object-fit:cover;">
              <div class="mt-2">
                <label class="d-flex align-items-center gap-2" style="font-size:.88rem;cursor:pointer;">
                  <input type="checkbox" name="remove_cover" value="1" id="removeCover">
                  <span class="text-danger">Remove current cover image</span>
                </label>
              </div>
              <div class="mt-1 text-muted" style="font-size:.85rem;">Or upload a replacement below:</div>
            </div>
          <?php endif; ?>

          <div class="cover-upload-area" id="coverArea">
            <input type="file" name="cover_image" id="coverInput" accept="image/*">
            <div id="coverPlaceholder">
              <div style="font-size:2rem;opacity:.35;margin-bottom:.4rem;">🖼️</div>
              <p class="mb-0 text-muted" style="font-size:.88rem;">Click or drag a new image here</p>
            </div>
            <div id="coverPreviewWrap" style="display:none;position:relative;">
              <img id="coverPreview" src="" alt="Preview"
                   style="max-height:180px;max-width:100%;border-radius:8px;object-fit:cover;">
              <button type="button" id="coverRemove"
                      style="position:absolute;top:-8px;right:-8px;background:#e53e3e;color:white;border:none;
                             border-radius:50%;width:26px;height:26px;font-size:.75rem;cursor:pointer;">✕</button>
            </div>
          </div>
        </div>

        <!-- Content — Quill -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Content</label>
          <div id="quillEditor"></div>
          <textarea name="content" id="contentInput" style="display:none;"></textarea>
        </div>

        <!-- Inline image insert -->
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:.88rem;color:#667eea;">
            📎 Insert image into content
          </label>
          <div class="d-flex gap-2 align-items-center">
            <input type="file" id="inlineImageInput" accept="image/*" class="form-control form-control-sm"
                   style="border-radius:8px;max-width:320px;">
            <button type="button" id="inlineImageBtn" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
              Insert
            </button>
            <span id="inlineImageStatus" class="text-muted" style="font-size:.82rem;"></span>
          </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <button type="submit" class="btn btn-primary px-4">
            <?= $post['status'] === 'approved' ? '💾 Save Changes' : '💾 Save & Resubmit' ?>
          </button>
          <a href="dashboard.php" class="btn btn-outline-secondary px-4">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
// Existing content (HTML from DB)
const existingContent = <?= json_encode($post['content']) ?>;

const quill = new Quill('#quillEditor', {
  theme: 'snow',
  modules: {
    toolbar: [
      [{ header: [1, 2, 3, false] }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ color: [] }, { background: [] }],
      [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
      [{ align: [] }],
      ['blockquote', 'code-block'],
      ['link', 'image'],
      ['clean']
    ]
  }
});

// Load existing content into Quill
if (existingContent) {
  quill.clipboard.dangerouslyPasteHTML(existingContent);
}

document.getElementById('editForm').addEventListener('submit', function (e) {
  const html = quill.getSemanticHTML();
  document.getElementById('contentInput').value = html;
  if (!quill.getText().trim()) {
    e.preventDefault();
    alert('Content is required.');
  }
});

// Cover preview
const coverInput       = document.getElementById('coverInput');
const coverPreview     = document.getElementById('coverPreview');
const coverPreviewWrap = document.getElementById('coverPreviewWrap');
const coverPlaceholder = document.getElementById('coverPlaceholder');
const coverRemoveBtn   = document.getElementById('coverRemove');
const coverArea        = document.getElementById('coverArea');
const removeCb         = document.getElementById('removeCover');

coverInput.addEventListener('change', function () {
  if (this.files && this.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      coverPreview.src               = e.target.result;
      coverPreviewWrap.style.display = 'block';
      coverPlaceholder.style.display = 'none';
      coverArea.style.borderColor    = '#667eea';
      if (removeCb) removeCb.checked = false;
    };
    reader.readAsDataURL(this.files[0]);
  }
});

if (coverRemoveBtn) {
  coverRemoveBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    coverInput.value               = '';
    coverPreview.src               = '';
    coverPreviewWrap.style.display = 'none';
    coverPlaceholder.style.display = 'block';
    coverArea.style.borderColor    = '#cbd5e0';
  });
}

coverArea.addEventListener('dragover',  () => coverArea.style.borderColor = '#667eea');
coverArea.addEventListener('dragleave', () => coverArea.style.borderColor = '#cbd5e0');

// Inline image via button
function uploadAndInsert(file, statusEl) {
  statusEl.textContent = '⏳ Uploading…';
  const fd = new FormData();
  fd.append('file', file);
  fetch('upload_image.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.location) {
        const range = quill.getSelection(true);
        quill.insertEmbed(range ? range.index : quill.getLength(), 'image', data.location);
        statusEl.textContent = '✅ Inserted!';
        setTimeout(() => statusEl.textContent = '', 3000);
      } else {
        statusEl.textContent = '❌ ' + (data.error || 'Upload failed');
      }
    })
    .catch(() => { statusEl.textContent = '❌ Network error'; });
}

document.getElementById('inlineImageBtn').addEventListener('click', function () {
  const fileInput = document.getElementById('inlineImageInput');
  const status    = document.getElementById('inlineImageStatus');
  if (!fileInput.files || !fileInput.files[0]) {
    status.textContent = 'Please select an image first.'; return;
  }
  uploadAndInsert(fileInput.files[0], status);
  fileInput.value = '';
});

quill.getModule('toolbar').addHandler('image', function () {
  const input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/*');
  input.click();
  input.addEventListener('change', function () {
    if (!this.files || !this.files[0]) return;
    const status = document.getElementById('inlineImageStatus');
    uploadAndInsert(this.files[0], status);
  });
});
</script>

<?php include 'footer.php'; ?>

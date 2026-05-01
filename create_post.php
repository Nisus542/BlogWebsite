<?php
require_once 'config.php';
requireLogin();

$error = '';
$cats  = $mysqli->query("SELECT id, name FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title'] ?? '');
    $content    = $_POST['content'] ?? '';
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;

    // Cover image upload
    $coverImage = null;
    if (!empty($_FILES['cover_image']['name'])) {
        $cf    = $_FILES['cover_image'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($cf['tmp_name']);
        $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];

        if ($cf['error'] !== UPLOAD_ERR_OK) {
            $error = 'Cover image upload failed (error code ' . $cf['error'] . ').';
        } elseif (!isset($allowed[$mime])) {
            $error = 'Cover image must be JPEG, PNG, GIF or WebP.';
        } elseif ($cf['size'] > 5 * 1024 * 1024) {
            $error = 'Cover image must be under 5 MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename   = 'cover_' . uniqid('', true) . '.' . $allowed[$mime];
            if (move_uploaded_file($cf['tmp_name'], $uploadDir . $filename)) {
                $coverImage = 'uploads/' . $filename;
            } else {
                $error = 'Could not save cover image.';
            }
        }
    }

    if (!$error && $title && strip_tags($content)) {
        $check = $mysqli->prepare("SELECT id FROM users WHERE id=?");
        $check->bind_param('i', $_SESSION['user_id']);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            $error = 'Invalid user account. Please log in again.';
        } else {
            $stmt   = $mysqli->prepare(
                "INSERT INTO posts(user_id,category_id,title,content,cover_image,status,created_at)
                 VALUES(?,?,?,?,?,?,NOW())"
            );
            $status = 'pending';
            $stmt->bind_param('iissss', $_SESSION['user_id'], $categoryId, $title, $content, $coverImage, $status);
            if ($stmt->execute()) {
                header('Location: dashboard.php?created=1');
                exit;
            }
            $error = 'Database error: ' . sanitize($mysqli->error);
        }
    } elseif (!$error) {
        $error = 'Title and content are required.';
    }
}

include 'header.php';
?>

<!-- Quill CSS -->
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

  /* Cover upload area */
  .cover-upload-area {
    border: 2px dashed #cbd5e0;
    border-radius: 12px;
    padding: 1.8rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    position: relative;
    background: #fafbfc;
  }
  .cover-upload-area:hover { border-color: #667eea; background: #f0f1ff; }
  .cover-upload-area input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
  }
</style>

<div class="container my-5" style="max-width:780px;">
  <h2 class="fw-bold mb-4">✍️ Create New Post</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error) ?></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-body p-4">
      <form method="post" enctype="multipart/form-data" id="postForm">

        <!-- Title -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Post Title <span class="text-danger">*</span></label>
          <input name="title" class="form-control form-control-lg"
                 placeholder="Enter a compelling title…" required style="border-radius:10px;">
        </div>

        <!-- Category -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Category</label>
          <select name="category_id" class="form-select" style="border-radius:10px;">
            <option value="">— Select a category —</option>
            <?php while ($c = $cats->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Cover Image -->
        <div class="mb-3">
          <label class="form-label fw-semibold">
            Cover / Featured Image
            <small class="text-muted fw-normal">(optional · max 5 MB · JPEG/PNG/GIF/WebP)</small>
          </label>
          <div class="cover-upload-area" id="coverArea">
            <input type="file" name="cover_image" id="coverInput" accept="image/*">
            <div id="coverPlaceholder">
              <div style="font-size:2.2rem;opacity:.35;margin-bottom:.5rem;">🖼️</div>
              <p class="mb-0 text-muted" style="font-size:.9rem;">Click or drag an image here</p>
            </div>
            <div id="coverPreviewWrap" style="display:none;position:relative;">
              <img id="coverPreview" src="" alt="Cover preview"
                   style="max-height:200px;max-width:100%;border-radius:8px;object-fit:cover;">
              <button type="button" id="coverRemove"
                      style="position:absolute;top:-8px;right:-8px;background:#e53e3e;color:white;border:none;
                             border-radius:50%;width:26px;height:26px;font-size:.75rem;cursor:pointer;line-height:1;">✕</button>
            </div>
          </div>
        </div>

        <!-- Content — Quill editor -->
        <div class="mb-3">
          <label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
          <!-- Quill attaches here -->
          <div id="quillEditor"></div>
          <!-- Hidden textarea receives HTML on submit -->
          <textarea name="content" id="contentInput" style="display:none;"></textarea>
        </div>

        <!-- Image inside content -->
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
          <small class="text-muted">Upload an image to embed it at the cursor position in your content.</small>
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <button type="submit" class="btn btn-gradient px-4">🚀 Submit for Approval</button>
          <a href="dashboard.php" class="btn btn-outline-secondary px-4">Cancel</a>
        </div>
      </form>
      <p class="text-muted mt-3 mb-0" style="font-size:.85rem;">
        Your post will be reviewed by an admin before it goes public.
      </p>
    </div>
  </div>
</div>

<!-- Quill JS -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
// ── Quill setup ───────────────────────────────────────────────────────────────
const quill = new Quill('#quillEditor', {
  theme: 'snow',
  placeholder: 'Write your post here…',
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

// ── Sync Quill → hidden textarea on submit ────────────────────────────────────
document.getElementById('postForm').addEventListener('submit', function (e) {
  const html = quill.getSemanticHTML();
  document.getElementById('contentInput').value = html;
  if (!quill.getText().trim()) {
    e.preventDefault();
    alert('Content is required.');
  }
});

// ── Cover image preview ───────────────────────────────────────────────────────
const coverInput       = document.getElementById('coverInput');
const coverPreview     = document.getElementById('coverPreview');
const coverPreviewWrap = document.getElementById('coverPreviewWrap');
const coverPlaceholder = document.getElementById('coverPlaceholder');
const coverRemove      = document.getElementById('coverRemove');
const coverArea        = document.getElementById('coverArea');

coverInput.addEventListener('change', function () {
  if (this.files && this.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      coverPreview.src = e.target.result;
      coverPreviewWrap.style.display = 'block';
      coverPlaceholder.style.display = 'none';
      coverArea.style.borderColor    = '#667eea';
    };
    reader.readAsDataURL(this.files[0]);
  }
});

coverRemove.addEventListener('click', function (e) {
  e.stopPropagation();
  coverInput.value               = '';
  coverPreview.src               = '';
  coverPreviewWrap.style.display = 'none';
  coverPlaceholder.style.display = 'block';
  coverArea.style.borderColor    = '#cbd5e0';
});

coverArea.addEventListener('dragover',  () => coverArea.style.borderColor = '#667eea');
coverArea.addEventListener('dragleave', () => coverArea.style.borderColor = '#cbd5e0');

// ── Inline image upload → Quill ───────────────────────────────────────────────
document.getElementById('inlineImageBtn').addEventListener('click', function () {
  const fileInput = document.getElementById('inlineImageInput');
  const status    = document.getElementById('inlineImageStatus');
  if (!fileInput.files || !fileInput.files[0]) {
    status.textContent = 'Please select an image first.';
    return;
  }
  status.textContent = '⏳ Uploading…';

  const fd = new FormData();
  fd.append('file', fileInput.files[0]);

  fetch('upload_image.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.location) {
        const range = quill.getSelection(true);
        quill.insertEmbed(range ? range.index : quill.getLength(), 'image', data.location);
        status.textContent = '✅ Image inserted!';
        fileInput.value = '';
        setTimeout(() => status.textContent = '', 3000);
      } else {
        status.textContent = '❌ ' + (data.error || 'Upload failed');
      }
    })
    .catch(() => { status.textContent = '❌ Network error'; });
});

// ── Also allow clicking image button inside Quill toolbar to trigger upload ──
quill.getModule('toolbar').addHandler('image', function () {
  const input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/*');
  input.click();
  input.addEventListener('change', function () {
    if (!this.files || !this.files[0]) return;
    const status = document.getElementById('inlineImageStatus');
    status.textContent = '⏳ Uploading…';
    const fd = new FormData();
    fd.append('file', this.files[0]);
    fetch('upload_image.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.location) {
          const range = quill.getSelection(true);
          quill.insertEmbed(range ? range.index : quill.getLength(), 'image', data.location);
          status.textContent = '✅ Inserted!';
          setTimeout(() => status.textContent = '', 3000);
        } else {
          status.textContent = '❌ ' + (data.error || 'Upload failed');
        }
      })
      .catch(() => { status.textContent = '❌ Network error'; });
  });
});
</script>

<?php include 'footer.php'; ?>

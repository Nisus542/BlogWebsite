<?php
require_once 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $mysqli->prepare("SELECT id, name, password, role FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($user = $res->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        }
    }
    $error = 'Invalid credentials.';
}
include 'header.php';
?>

<div class="container my-5 py-5">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card shadow border-0" style="border-radius: 16px;">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <h2 class="fw-bold" style="color: #2d3748;">Welcome Back</h2>
            <p class="text-muted">Login to continue to your account</p>
          </div>
          
          <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success" role="alert">
              <strong>✓ Success!</strong> Account created. Please login.
            </div>
          <?php endif; ?>
          
          <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
              <strong>✕ Error!</strong> <?= sanitize($error) ?>
            </div>
          <?php endif; ?>
          
          <form method="post">
            <div class="mb-4">
              <label class="form-label fw-semibold" style="color: #2d3748;">Email Address</label>
              <input name="email" type="email" class="form-control form-control-lg" 
                     style="border-radius: 10px; padding: 0.75rem 1rem;"
                     placeholder="email" required>
            </div>
            
            <div class="mb-4">
              <label class="form-label fw-semibold" style="color: #2d3748;">Password</label>
              <input name="password" type="password" class="form-control form-control-lg" 
                     style="border-radius: 10px; padding: 0.75rem 1rem;"
                     placeholder="password" required>
            </div>
            
            <button type="submit" class="btn btn-gradient btn-lg w-100" style="border-radius: 10px; padding: 0.75rem;">
              Login
            </button>
          </form>
          
          <div class="text-center mt-4">
            <p class="text-muted mb-0">
              Don't have an account? 
              <a href="register.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                Create one
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>

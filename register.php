<?php
require_once 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 6) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users(name,email,password) VALUES (?,?,?)");
        $stmt->bind_param('sss', $name, $email, $hashed);
        if ($stmt->execute()) {
            header('Location: login.php?registered=1');
            exit;
        } else {
            $error = 'Email already exists or DB error.';
        }
    } else {
        $error = 'Please provide name, valid email, and password (min 6 chars).';
    }
}
include 'header.php';
?>

<div class="container my-5 py-5">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card shadow border-0" style="border-radius: 16px;">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <h2 class="fw-bold" style="color: #2d3748;">Create Account</h2>
            <p class="text-muted">Join our blogging community</p>
          </div>
          
          <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
              <strong>✕ Error!</strong> <?= sanitize($error) ?>
            </div>
          <?php endif; ?>
          
          <form method="post">
            <div class="mb-4">
              <label class="form-label fw-semibold" style="color: #2d3748;">Full Name</label>
              <input name="name" type="text" class="form-control form-control-lg" 
                     style="border-radius: 10px; padding: 0.75rem 1rem;"
                     placeholder="Name" required>
            </div>
            
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
                     placeholder="password" minlength="6" required>
              <small class="text-muted">Must be at least 6 characters</small>
            </div>
            
            <button type="submit" class="btn btn-gradient btn-lg w-100" style="border-radius: 10px; padding: 0.75rem;">
              Create Account
            </button>
          </form>
          
          <div class="text-center mt-4">
            <p class="text-muted mb-0">
              Already have an account? 
              <a href="login.php" style="color: #667eea; text-decoration: none; font-weight: 600;">
                Login
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

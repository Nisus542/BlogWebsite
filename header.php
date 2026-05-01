<?php require_once 'config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Blogger.com - Share Your Stories</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background-color: #fafbfc;
      color: #1a202c;
    }
    
    /* Navigation Bar */
    .custom-navbar {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      padding: 1rem 0;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    
    .navbar-brand {
      font-size: 1.5rem;
      font-weight: 700;
      color: white !important;
      letter-spacing: -0.5px;
      transition: transform 0.2s ease;
    }
    
    .navbar-brand:hover {
      transform: scale(1.05);
    }
    
    .navbar-toggler {
      border: 2px solid rgba(255,255,255,0.5);
      padding: 0.5rem;
    }
    
    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }
    
    .nav-link {
      color: rgba(255,255,255,0.95) !important;
      font-weight: 500;
      padding: 0.6rem 1.2rem !important;
      border-radius: 8px;
      transition: all 0.3s ease;
      margin: 0 0.25rem;
      font-size: 0.95rem;
    }
    
    .nav-link:hover {
      background-color: rgba(255,255,255,0.2);
      color: white !important;
      transform: translateY(-1px);
    }
    
    .nav-link.user-badge {
      background-color: rgba(255,255,255,0.2);
      cursor: default;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .nav-link.user-badge:hover {
      transform: none;
    }
    
    .btn-get-started {
      background-color: white !important;
      color: #667eea !important;
      border: none;
      padding: 0.6rem 1.5rem;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .btn-get-started:hover {
      background-color: #f7f7f7 !important;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    /* Hero Section */
    .hero-section {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 4rem 0;
      text-align: center;
    }
    
    .hero-section h1 {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 1rem;
    }
    
    .hero-section .lead {
      font-size: 1.25rem;
      opacity: 0.95;
      margin-bottom: 2rem;
    }
    
    .btn-hero {
      background-color: white;
      color: #667eea;
      padding: 1rem 2.5rem;
      border-radius: 50px;
      font-weight: 600;
      border: none;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .btn-hero:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0,0,0,0.25);
      background-color: #f8f9fa;
    }
    
    /* Cards */
    .feature-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      height: 100%;
      background: white;
    }
    
    .feature-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
    }
    
    .feature-card .card-body {
      padding: 2rem;
    }
    
    .feature-card .card-title {
      font-weight: 700;
      color: #2d3748;
      margin-bottom: 1rem;
      font-size: 1.25rem;
    }
    
    .feature-card .card-text {
      color: #4a5568;
      line-height: 1.6;
    }
    
    /* List Group */
    .custom-list-group-item {
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      margin-bottom: 0.75rem;
      padding: 1.25rem;
      transition: all 0.3s ease;
      background: white;
      text-decoration: none;
      color: inherit;
      display: block;
    }
    
    .custom-list-group-item:hover {
      border-color: #667eea;
      transform: translateX(5px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
    }
    
    .custom-list-group-item strong {
      color: #2d3748;
      font-weight: 600;
      font-size: 1.1rem;
    }
    
    /* Page Container */
    .page-container {
      min-height: calc(100vh - 200px);
      padding-bottom: 3rem;
    }
    
    /* Buttons */
    .btn-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      transition: all 0.3s ease;
    }
    
    .btn-gradient:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
      color: white;
    }
    
    /* Form Controls */
    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    /* Footer */
    .custom-footer {
      background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
      color: white;
      padding: 2rem 0;
      margin-top: 4rem;
    }
    
    .custom-footer a {
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      transition: color 0.3s ease;
    }
    
    .custom-footer a:hover {
      color: white;
    }
    
    /* Badges */
    .badge {
      padding: 0.5rem 0.75rem;
      font-weight: 500;
      border-radius: 6px;
    }
    
    /* Tables */
    .table-modern {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .table-modern thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }
    
    .table-modern th {
      font-weight: 600;
      border: none;
      padding: 1rem;
    }
    
    .table-modern td {
      padding: 1rem;
      vertical-align: middle;
      border-color: #e2e8f0;
    }
    
    /* Alert Cards */
    .alert {
      border-radius: 10px;
      border: none;
    }
    
    .alert-info {
      background-color: #e6f7ff;
      color: #0c5460;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }
    
    .alert-warning {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .alert-danger {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
      .hero-section h1 {
        font-size: 2rem;
      }
      
      .navbar-brand {
        font-size: 1.25rem;
      }
    }
    /* Category dropdown */
    .cat-dropdown-menu {
      border: none !important;
      border-radius: 12px !important;
      box-shadow: 0 8px 28px rgba(0,0,0,0.13) !important;
      padding: .4rem !important;
      min-width: 210px !important;
      margin-top: .4rem !important;
    }
    .cat-dropdown-menu .dropdown-item {
      border-radius: 8px;
      padding: .5rem .9rem;
      font-size: .9rem;
      color: #2d3748;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background .15s;
    }
    .cat-dropdown-menu .dropdown-item:hover,
    .cat-dropdown-menu .dropdown-item:focus {
      background: #f0f1ff;
      color: #667eea;
    }
    .cat-dropdown-menu .dropdown-item .cat-count {
      background: #e2e8f0;
      color: #718096;
      border-radius: 20px;
      font-size: .72rem;
      padding: .1rem .55rem;
      font-weight: 600;
      min-width: 22px;
      text-align: center;
    }
    .cat-dropdown-menu .dropdown-divider { margin: .3rem .5rem; }
    .cat-dropdown-menu .view-all {
      color: #667eea !important;
      font-weight: 600;
      justify-content: center;
      font-size: .88rem;
    }
    .cat-dropdown-menu .view-all:hover { background: #eef0ff !important; }
    .nav-item.dropdown > .nav-link::after { margin-left: .35em; }
  </style>
</head>
<body>
<?php
/* Fetch categories once for the navbar dropdown — available on every page */
$_navCats = $mysqli->query("
    SELECT categories.name, categories.slug,
           COUNT(posts.id) AS post_count
    FROM categories
    LEFT JOIN posts ON posts.category_id = categories.id AND posts.status = 'approved'
    GROUP BY categories.id
    ORDER BY categories.name
");
?>
<nav class="navbar navbar-expand-lg custom-navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">✍️ Blogger</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">

        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

        <?php if ($current_page !== 'index.php'): ?>
          <li class="nav-item">
            <a class="nav-link" href="index.php">Home</a>
          </li>
        <?php endif; ?>

        <!-- Categories dropdown (visible to everyone) -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button"
             data-bs-toggle="dropdown" aria-expanded="false">
            Categories
          </a>
          <ul class="dropdown-menu cat-dropdown-menu">
            <?php
            $_navCats->data_seek(0);
            while ($__c = $_navCats->fetch_assoc()):
            ?>
              <li>
                <a class="dropdown-item" href="category.php?slug=<?= sanitize($__c['slug']) ?>">
                  <span><?= sanitize($__c['name']) ?></span>
                  <span class="cat-count"><?= (int)$__c['post_count'] ?></span>
                </a>
              </li>
            <?php endwhile; ?>
            <li><hr class="dropdown-divider cat-dropdown-menu"></li>
            <li>
              <a class="dropdown-item view-all" href="index.php">View all posts →</a>
            </li>
          </ul>
        </li>

        <?php if (isLoggedIn()): ?>
          <?php if ($current_page !== 'admin_dashboard.php'): ?>
            <li class="nav-item">
              <a class="nav-link" href="dashboard.php">
                <?= isAdmin() ? 'Admin Panel' : 'Dashboard' ?>
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <span class="nav-link user-badge">
              <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
              </svg>
              <?= sanitize($_SESSION['name']) ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-get-started ms-lg-2 mt-2 mt-lg-0" href="register.php">Get Started</a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
<div class="page-container">

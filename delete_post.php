<?php
require_once 'config.php';
requireLogin();
$id = (int)($_GET['id'] ?? 0);
$stmt = $mysqli->prepare("DELETE FROM posts WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
header('Location: dashboard.php?deleted=1');
exit;
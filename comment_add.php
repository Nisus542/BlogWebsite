<?php
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    header("Location: view_post.php?id=" . (int)($_POST['post_id'] ?? 0));
    exit;
}

$postId  = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($postId && strlen($content) > 0) {
    // Limit to 1000 chars, strip any HTML tags for safety
    $content = substr(strip_tags($content), 0, 1000);
    $stmt = $mysqli->prepare("INSERT INTO comments(post_id,user_id,content) VALUES(?,?,?)");
    $stmt->bind_param('iis', $postId, $_SESSION['user_id'], $content);
    $stmt->execute();
}

header("Location: view_post.php?id=$postId");
exit;

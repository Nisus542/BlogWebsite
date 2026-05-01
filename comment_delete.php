<?php
require_once 'config.php';
requireLogin();

$commentId = (int)($_POST['comment_id'] ?? 0);
$postId    = (int)($_POST['post_id'] ?? 0);

if ($commentId && $postId) {
    // Users can only delete their own comments; admins can delete any
    if (isAdmin()) {
        $stmt = $mysqli->prepare("DELETE FROM comments WHERE id=?");
        $stmt->bind_param('i', $commentId);
    } else {
        $stmt = $mysqli->prepare("DELETE FROM comments WHERE id=? AND user_id=?");
        $stmt->bind_param('ii', $commentId, $_SESSION['user_id']);
    }
    $stmt->execute();
}

header("Location: view_post.php?id=$postId");
exit;

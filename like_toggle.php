<?php
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    echo json_encode(['error' => 'Admins cannot like posts']);
    exit;
}

header('Content-Type: application/json');

$postId = (int)($_POST['post_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$postId) {
    echo json_encode(['error' => 'Invalid post']);
    exit;
}

// Check if already liked
$check = $mysqli->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=?");
$check->bind_param('ii', $postId, $userId);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing) {
    // Unlike
    $del = $mysqli->prepare("DELETE FROM likes WHERE post_id=? AND user_id=?");
    $del->bind_param('ii', $postId, $userId);
    $del->execute();
    $liked = false;
} else {
    // Like
    $ins = $mysqli->prepare("INSERT INTO likes(post_id,user_id) VALUES(?,?)");
    $ins->bind_param('ii', $postId, $userId);
    $ins->execute();
    $liked = true;
}

// Get updated count
$cnt = $mysqli->prepare("SELECT COUNT(*) as c FROM likes WHERE post_id=?");
$cnt->bind_param('i', $postId);
$cnt->execute();
$count = $cnt->get_result()->fetch_assoc()['c'];

echo json_encode(['liked' => $liked, 'count' => $count]);

<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?mode=login');
    exit;
}

$currentUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_post') {
    $body = trim($_POST['body'] ?? '');
    if ($body !== '' && strlen($body) <= 1000) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, body) VALUES (?, ?)");
        $stmt->execute([$currentUserId, $body]);
    }
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $post_id = (int) ($_POST['post_id'] ?? 0);
    $body = trim($_POST['body'] ?? '');
    if ($post_id > 0 && $body !== '' && strlen($body) <= 500) {
        $check = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
        $check->execute([$post_id]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, body) VALUES (?, ?, ?)");
            $stmt->execute([$post_id, $currentUserId, $body]);
        }
    }
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.id, p.body, p.edited, p.created_at, u.name AS author_name, p.user_id
     FROM posts p
     INNER JOIN users u ON u.id = p.user_id
     ORDER BY p.created_at DESC"
);
$stmt->execute();
$posts = $stmt->fetchAll();

$commentStmt = $pdo->prepare(
    "SELECT c.id, c.post_id, c.user_id, c.body, c.edited, c.created_at, u.name AS author_name
     FROM comments c
     INNER JOIN users u ON u.id = c.user_id
     ORDER BY c.created_at ASC"
);
$commentStmt->execute();
$allComments = $commentStmt->fetchAll();

$commentsByPost = [];
foreach ($allComments as $c) {
    $commentsByPost[$c['post_id']][] = $c;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>News Feed</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Blog Feed</h1>
        <nav>
            <span>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="auth.php?mode=logout">Logout</a>
        </nav>
    </header>

    <div class="container">
        <section class="add-post">
            <h2>Write a new post</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_post">
                <textarea name="body" required minlength="1" maxlength="1000" rows="3" placeholder="Post here!"></textarea>
                <button type="submit">Post</button>
            </form>
        </section>

        <section class="feed">
            <h2>Latest posts</h2>

            <?php if (empty($posts)): ?>
                <p>No posts yet. Be the first to post!</p>
            <?php endif; ?>

            <?php foreach ($posts as $post): ?>
                <article class="post">
                    <div class="post-header">
                        <strong><?= htmlspecialchars($post['author_name']) ?></strong>
                        <small>
                            <?= htmlspecialchars($post['created_at']) ?>
                            <?php if ($post['edited']): ?>
                                <span class="edited-tag">(edited)</span>
                            <?php endif; ?>
                        </small>
                    </div>

                    <p class="post-body"><?= nl2br(htmlspecialchars($post['body'])) ?></p>

                    <?php if ($post['user_id'] == $currentUserId): ?>
                        <div class="post-actions">
                            <a href="actions.php?type=post&op=edit&id=<?= $post['id'] ?>">Edit</a>
                            <form method="POST" action="actions.php" onsubmit="return confirm('Delete this post?');">
                                <input type="hidden" name="type" value="post">
                                <input type="hidden" name="op" value="delete">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="comments">
                        <?php if (!empty($commentsByPost[$post['id']])): ?>
                            <?php foreach ($commentsByPost[$post['id']] as $c): ?>
                                <div class="comment">
                                    <strong><?= htmlspecialchars($c['author_name']) ?></strong>
                                    <small>
                                        <?= htmlspecialchars($c['created_at']) ?>
                                        <?php if ($c['edited']): ?>
                                            <span class="edited-tag">(edited)</span>
                                        <?php endif; ?>
                                    </small>
                                    <p><?= nl2br(htmlspecialchars($c['body'])) ?></p>

                                    <?php if ($c['user_id'] == $currentUserId): ?>
                                        <div class="comment-actions">
                                            <a href="actions.php?type=comment&op=edit&id=<?= $c['id'] ?>">Edit</a>
                                            <form method="POST" action="actions.php" onsubmit="return confirm('Delete this comment?');">
                                                <input type="hidden" name="type" value="comment">
                                                <input type="hidden" name="op" value="delete">
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" class="danger">Delete</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <form method="POST" class="add-comment">
                            <input type="hidden" name="action" value="add_comment">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <input type="text" name="body" placeholder="Write a comment..." required maxlength="500">
                            <button type="submit">Comment</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>
</body>
</html>
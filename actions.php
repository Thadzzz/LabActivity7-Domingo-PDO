<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?mode=login');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$type = $_GET['type'] ?? $_POST['type'] ?? '';
$op = $_GET['op'] ?? $_POST['op'] ?? '';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!in_array($type, ['post', 'comment']) || !in_array($op, ['edit', 'delete']) || $id <= 0) {
    header('Location: index.php');
    exit;
}

$table = $type === 'post' ? 'posts' : 'comments';

$stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item || $item['user_id'] != $currentUserId) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($op === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $currentUserId]);
    header('Location: index.php');
    exit;
}

if ($op === 'edit') {
    $maxLength = $type === 'post' ? 1000 : 500;
    $label = $type === 'post' ? 'Post' : 'Comment';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = trim($_POST['body'] ?? '');
        if ($body === '' || strlen($body) > $maxLength) {
            $error = "$label must be between 1 and $maxLength characters.";
        } else {
            $stmt = $pdo->prepare("UPDATE $table SET body = ?, edited = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$body, $id, $currentUserId]);
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit <?= $type === 'post' ? 'Post' : 'Comment' ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Edit <?= $type === 'post' ? 'Post' : 'Comment' ?></h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="type" value="<?= $type ?>">
            <input type="hidden" name="op" value="edit">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <textarea name="body" required minlength="1" maxlength="<?= $type === 'post' ? 1000 : 500 ?>" rows="5"><?= htmlspecialchars($item['body']) ?></textarea>
            <button type="submit">Save changes</button>
            <a href="index.php">Cancel</a>
        </form>
    </div>
</body>
</html>
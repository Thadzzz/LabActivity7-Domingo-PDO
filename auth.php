<?php
session_start();
require 'db.php';

$mode = $_GET['mode'] ?? 'login';
$error = '';

if ($mode === 'logout') {
    $_SESSION = [];
    session_destroy();
    header('Location: auth.php?mode=login');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($mode === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || strlen($name) < 2) {
        $error = 'Name must be at least 2 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'Sorry, but that email has already been used.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hash]);

            header('Location: auth.php?mode=login');
            exit;
        }
    }
}

if ($mode === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $error = 'Password is required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $mode === 'register' ? 'Register' : 'Login' ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1><?= $mode === 'register' ? 'Register' : 'Login' ?></h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($mode === 'register'): ?>
            <form method="POST">
                <label>Name:
                    <input type="text" name="name" required minlength="2" maxlength="100">
                </label>
                <label>Email:
                    <input type="email" name="email" required maxlength="150">
                </label>
                <label>Password:
                    <input type="password" name="password" required>
                </label>
                <button type="submit">Register</button>
            </form>
            <p>Already have an account? <a href="auth.php?mode=login">Log in instead.</a></p>
        <?php else: ?>
            <form method="POST">
                <label>Email:
                    <input type="email" name="email" required>
                </label>
                <label>Password:
                    <input type="password" name="password" required>
                </label>
                <button type="submit">Log in</button>
            </form>
            <p>Don't have an account? <a href="auth.php?mode=register">Register</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
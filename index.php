<?php
require_once "config.php";

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $uname = trim($_POST['uname'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($uname) || empty($password)) {
        $errors[] = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role, municipality, status FROM users WHERE username = ?");
        $stmt->bind_param('s', $uname);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && $password === $user['password']) {
            if ($user['status'] !== "approved") {
                $errors[] = "Your account status is '" . h($user['status']) . "'. Please wait for admin approval.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['municipality'] = $user['municipality'];

                header('Location: dashboard.php');
                exit;
            }
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Bulacan Find & Matching System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Bulacan Find & Match</h1>
                <h2>Local Matching & Finder Portal</h2>
            </div>

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= h($e) ?></div>
            <?php endforeach; ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="uname">Username</label>
                    <input type="text" class="form-control" name="uname" id="uname" placeholder="Enter username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn">Sign In</button>
            </form>

            <div class="auth-footer">
                <p>No account yet? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
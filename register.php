<?php
require_once "config.php";

function h($str){
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

$mess = [];
$errors = [];

$municipalities = [
    "Angat", "Balagtas", "Baliwag", "Bocaue", "Bulakan", "Bustos", 
    "Calumpit", "Dona Remedios Trinidad", "Guiguinto", "Hagonoy", 
    "Marilao", "Meycauayan", "Norzagaray", "Obando", "Pandi", 
    "Paombong", "Plaridel", "Pulilan", "San Ildefonso", "San Jose del Monte", 
    "San Miguel", "San Rafael", "Santa Maria", "City of Malolos"
];

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $uname = trim($_POST['uname'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $municipality = $_POST['municipality'] ?? 'City of Malolos';

    if (empty($uname) || empty($password) || empty($password2)) {
        $errors[] = "All fields are required.";
    } elseif ($password !== $password2) {
        $errors[] = "Passwords do not match.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $uname);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Username is already taken. Please choose another.";
            $stmt->close();
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO users (username, password, municipality, status, role) VALUES (?, ?, ?, 'pending', 'user')");
            $stmt->bind_param('sss', $uname, $password, $municipality);

            if ($stmt->execute()) {
                $mess[] = "Registration successful! Your account is pending admin approval.";
            } else {
                $errors[] = "Registration failed. Please try again later.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bulacan Find & Matching System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Bulacan Find & Match</h1>
                <h2>Create Your Account</h2>
            </div>

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= h($e) ?></div>
            <?php endforeach;?>

            <?php foreach ($mess as $m): ?>
                <div class="alert alert-success"><?= h($m) ?></div>
            <?php endforeach;?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="uname">Username</label>
                    <input type="text" class="form-control" name="uname" id="uname" placeholder="Enter username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="municipality">Bulacan Municipality / City</label>
                    <select name="municipality" id="municipality" class="form-control" required>
                        <?php foreach ($municipalities as $muni): ?>
                            <option value="<?= h($muni) ?>"><?= h($muni) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" name="password" id="password" placeholder="Enter password" required>
                </div>
                <div class="form-group">
                    <label for="password2">Confirm Password</label>
                    <input type="password" class="form-control" name="password2" id="password2" placeholder="Re-enter password" required>
                </div>
                <button type="submit" class="btn">Register</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="index.php">Sign In</a></p>
            </div>
        </div>
    </div>
</body>
</html>
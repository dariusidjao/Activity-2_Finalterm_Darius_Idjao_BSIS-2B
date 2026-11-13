<?php
require_once "config.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

if (!isset($_GET['itemid']) || !filter_var($_GET['itemid'], FILTER_VALIDATE_INT)) {
    header("Location: dashboard.php");
    exit();
}

$itemid = (int) $_GET['itemid'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
$stmt->bind_param("i", $itemid);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: dashboard.php?msg=" . urlencode("Item not found."));
    exit();
}

if ($item['created_by'] != $user_id && $user_role !== 'admin') {
    header("Location: dashboard.php?msg=" . urlencode("Unauthorized access."));
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['savebtn'])) {
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? 'lost';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if (empty($title) || empty($category) || empty($description) || empty($contact)) {
        $errors[] = "All fields are required.";
    } else {
        $update = $conn->prepare("UPDATE items SET title = ?, type = ?, category = ?, description = ?, contact_info = ?, status = ? WHERE id = ?");
        $update->bind_param("ssssssi", $title, $type, $category, $description, $contact, $status, $itemid);

        if ($update->execute()) {
            $update->close();
            header("Location: dashboard.php?msg=" . urlencode("Report updated successfully!"));
            exit();
        } else {
            $errors[] = "Failed to update report.";
            $update->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report - Bulacan Find & Matching System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">Bulacan Match System</div>
        <div class="nav-user">
            <a href="dashboard.php" class="btn" style="background-color: var(--secondary); width: auto; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Back to Dashboard</a>
        </div>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 600px;">
            <div class="auth-header">
                <h1>Edit Report #<?= $item['id'] ?></h1>
                <h2>Update details or mark item as matched/resolved</h2>
            </div>

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= h($e) ?></div>
            <?php endforeach; ?>

            <form action="" method="post">
                <div class="form-group">
                    <label>Item Title</label>
                    <input type="text" name="title" class="form-control" value="<?= h($item['title']) ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select name="type" class="form-control" required>
                            <option value="lost" <?= $item['type'] === 'lost' ? 'selected' : '' ?>>Lost Item</option>
                            <option value="found" <?= $item['type'] === 'found' ? 'selected' : '' ?>>Found Item</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="active" <?= $item['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="matched" <?= $item['status'] === 'matched' ? 'selected' : '' ?>>Matched</option>
                            <option value="resolved" <?= $item['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" class="form-control" value="<?= h($item['category']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Contact Details</label>
                    <input type="text" name="contact_info" class="form-control" value="<?= h($item['contact_info']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Description & Location</label>
                    <textarea name="description" class="form-control" rows="3" required><?= h($item['description']) ?></textarea>
                </div>

                <button type="submit" name="savebtn" class="btn">Save Changes</button>
            </form>
        </div>
    </div>
</body>
</html>
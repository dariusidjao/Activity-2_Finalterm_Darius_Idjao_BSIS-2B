<?php
require_once "config.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit;
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_role = $_SESSION['role'];
$user_muni = $_SESSION['municipality'] ?? 'City of Malolos';
$mess = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['post_item'])) {
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? 'lost';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');

    if (empty($title) || empty($category) || empty($description) || empty($contact)) {
        $errors[] = "All report fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO items (created_by, type, title, category, municipality, description, contact_info, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param('issssss', $user_id, $type, $title, $category, $user_muni, $description, $contact);

        if ($stmt->execute()) {
            $mess = "Item report posted successfully!";
        } else {
            $errors[] = "Failed to post report. Please try again.";
        }
        $stmt->close();
    }
}

if ($user_role === 'admin') {
    $stmt = $conn->prepare("
        SELECT items.*, users.username 
        FROM items 
        JOIN users ON items.created_by = users.id 
        ORDER BY items.created_at DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT items.*, users.username 
        FROM items 
        JOIN users ON items.created_by = users.id 
        WHERE items.municipality = ? 
        ORDER BY items.created_at DESC
    ");
    $stmt->bind_param('s', $user_muni);
}

$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bulacan Find & Matching System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">Bulacan Match System</div>
        <div class="nav-user">
            <span>Welcome, <strong><?= h($username) ?></strong> (<?= h($user_muni) ?>)</span>
            <?php if ($user_role === 'admin'): ?>
                <a href="users.php" class="btn" style="background-color: var(--secondary); width: auto; padding: 0.4rem 0.8rem; font-size: 0.85rem;">User Management</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-danger" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2><?= $user_role === 'admin' ? 'All Province Reports (Admin View)' : 'Local Reports in ' . h($user_muni) ?></h2>
        </div>

        <?php if ($mess): ?>
            <div class="alert alert-success"><?= h($mess) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><?= h($_GET['msg']) ?></div>
        <?php endif; ?>

        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?= h($e) ?></div>
        <?php endforeach; ?>

        <div class="auth-card" style="max-width: 100%; margin-bottom: 2rem;">
            <h3>Post a Lost or Found Item</h3>
            <form action="" method="post" style="margin-top: 1rem;">
                <input type="hidden" name="post_item" value="1">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label>Item Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Black Wallet, Keys" required>
                    </div>
                    <div class="form-group">
                        <label>Report Type</label>
                        <select name="type" class="form-control" required>
                            <option value="lost">Lost Item</option>
                            <option value="found">Found Item</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" class="form-control" placeholder="e.g. Personal Electronics, Documents" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Details</label>
                        <input type="text" name="contact_info" class="form-control" placeholder="Phone Number / Social Link" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description & Specific Location</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Detail landmarks, colors, date lost/found..." required></textarea>
                </div>
                <button type="submit" class="btn" style="width: auto; padding: 0.6rem 2rem;">Submit Post</button>
            </form>
        </div>

        <h3>Recent Reports</h3>
        <br>
        <?php if (empty($items)): ?>
            <p style="color: var(--text-muted);">No reports posted yet.</p>
        <?php else: ?>
            <div class="item-grid">
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div>
                            <div class="item-card-header">
                                <span class="item-card-title"><?= h($item['title']) ?></span>
                                <div>
                                    <span class="badge badge-<?= h($item['type']) ?>"><?= h($item['type']) ?></span>
                                    <span class="badge" style="background: #e2e8f0; color: #334155;"><?= h($item['status']) ?></span>
                                </div>
                            </div>
                            <div class="item-meta">
                                <?= h($item['municipality']) ?> • <?= h($item['category']) ?> • By <?= h($item['username']) ?>
                            </div>
                            <p class="item-description"><?= h($item['description']) ?></p>
                        </div>
                        <div class="item-card-footer">
                            <div>Contact: <strong><?= h($item['contact_info']) ?></strong></div>
                            <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                Posted: <?= date("M d, Y g:i A", strtotime($item['created_at'])) ?>
                            </div>

                            <?php if ($item['created_by'] == $user_id || $user_role === 'admin'): ?>
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                                    <a href="edititem.php?itemid=<?= $item['id'] ?>" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">Edit</a>
                                    <a href="deleteitem.php?itemid=<?= $item['id'] ?>" onclick="return confirm('Delete this report permanently?');" class="btn btn-danger" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">Delete</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
</html>
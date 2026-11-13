<?php
require_once "config.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['toggle_role']) && filter_var($_GET['toggle_role'], FILTER_VALIDATE_INT)) {
    $target_id = (int) $_GET['toggle_role'];
    if ($target_id !== (int) $_SESSION['user_id']) {
        $stmt = $conn->prepare("UPDATE users SET role = IF(role = 'admin', 'user', 'admin') WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: users.php?msg=" . urlencode("User role updated."));
    exit();
}

$result = mysqli_query($conn, "SELECT id, username, municipality, status, role, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Bulacan Find & Matching System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .table-card { background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color); overflow-x: auto; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.9rem; }
        th { background-color: #f8fafc; font-weight: 600; color: var(--primary); }
        .action-btns { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8rem; width: auto; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">Bulacan Match System — Admin</div>
        <div class="nav-user">
            <a href="dashboard.php" class="btn" style="background-color: var(--secondary); width: auto; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Dashboard</a>
            <a href="logout.php" class="btn btn-danger" style="width: auto; padding: 0.4rem 0.8rem; font-size: 0.85rem;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2>User Access Control & Approvals</h2>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><?= h($_GET['msg']) ?></div>
        <?php endif; ?>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Municipality</th>
                        <th>Status</th>
                        <th>Role</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong><?= h($user['username']) ?></strong></td>
                                <td><?= h($user['municipality']) ?></td>
                                <td>
                                    <span class="badge <?= $user['status'] === 'approved' ? 'badge-found' : 'badge-lost' ?>">
                                        <?= h($user['status']) ?>
                                    </span>
                                </td>
                                <td><?= h(strtoupper($user['role'])) ?></td>
                                <td><?= date("M d, Y", strtotime($user['created_at'])) ?></td>
                                <td>
                                    <div class="action-btns">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <?php if ($user['status'] !== 'approved'): ?>
                                                <a href="approveduser.php?userid=<?= $user['id'] ?>" class="btn btn-sm" style="background-color: var(--success);">Approve</a>
                                            <?php else: ?>
                                                <a href="disableuser.php?userid=<?= $user['id'] ?>" class="btn btn-danger btn-sm">Disable</a>
                                            <?php endif; ?>
                                            <a href="users.php?toggle_role=<?= $user['id'] ?>" class="btn btn-sm" style="background-color: #475569;">
                                                Make <?= $user['role'] === 'admin' ? 'User' : 'Admin' ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">Current Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
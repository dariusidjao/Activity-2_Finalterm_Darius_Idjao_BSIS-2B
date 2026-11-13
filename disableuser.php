<?php
require_once "config.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

if (!isset($_GET['userid']) || !filter_var($_GET['userid'], FILTER_VALIDATE_INT)) {
    header("Location: users.php");
    exit();
}

$userid = (int) $_GET['userid'];

if ($userid === (int) $_SESSION['user_id']) {
    header("Location: users.php?msg=" . urlencode("You cannot disable your own account."));
    exit();
}

$stmt = $conn->prepare("UPDATE users SET status = 'pending' WHERE id = ?");
$stmt->bind_param("i", $userid);
$msg = $stmt->execute() ? "User account disabled (set to pending)." : "Failed to disable user.";
$stmt->close();

header("Location: users.php?msg=" . urlencode($msg));
exit();
?>
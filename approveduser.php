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

$stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ?");
$stmt->bind_param("i", $userid);
$msg = $stmt->execute() ? "User has been approved!" : "Failed to approve user.";
$stmt->close();

header("Location: users.php?msg=" . urlencode($msg));
exit();
?>
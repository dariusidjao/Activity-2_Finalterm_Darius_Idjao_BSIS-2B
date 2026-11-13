<?php
require_once "config.php";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['itemid']) || !filter_var($_GET['itemid'], FILTER_VALIDATE_INT)) {
    header("Location: dashboard.php");
    exit();
}

$itemid = (int) $_GET['itemid'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if ($user_role === 'admin') {
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
    $stmt->bind_param("i", $itemid);
} else {
    $stmt = $conn->prepare("DELETE FROM items WHERE id = ? AND created_by = ?");
    $stmt->bind_param("ii", $itemid, $user_id);
}

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $msg = "Report deleted successfully!";
} else {
    $msg = "Unable to delete report or unauthorized action.";
}

$stmt->close();
header("Location: dashboard.php?msg=" . urlencode($msg));
exit();
?>
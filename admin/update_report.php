<?php
require_once __DIR__ . "/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"] ?? "")) {
    header("Location: dashboard.php?error=1");
    exit;
}

$reportId = filter_input(INPUT_POST, "report_id", FILTER_VALIDATE_INT);
$status = $_POST["status"] ?? "";
$adminNote = trim($_POST["admin_note"] ?? "");
$allowedStatuses = ["Reported", "In review", "In progress", "Resolved"];
if (!$reportId || !in_array($status, $allowedStatuses, true) || mb_strlen($adminNote) > 5000) {
    header("Location: dashboard.php?error=1");
    exit;
}

$statement = $conn->prepare("UPDATE reports SET status = ?, admin_note = ? WHERE id = ?");
$statement->bind_param("ssi", $status, $adminNote, $reportId);
$statement->execute();
$updated = $statement->affected_rows >= 0;
$statement->close();
header("Location: dashboard.php?" . ($updated ? "updated=1" : "error=1"));
exit;
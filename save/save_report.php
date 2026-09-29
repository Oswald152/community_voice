<?php
session_start();
require_once "../config/db_connect.php";

if (empty($_SESSION["user_id"])) {
	header("Location: ../account.php?mode=login&next=report.php");
	exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_SESSION["report_csrf"]) || !hash_equals($_SESSION["report_csrf"], $_POST["csrf_token"] ?? "")) {
	header("Location: ../report.php?error=1");
	exit;
}

$allowedCategories = ["Roads & walkways", "Waste & cleanliness", "Street lighting", "Parks & public spaces", "Water & drainage", "Safety", "Other"];
$category = trim($_POST["category"] ?? "");
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$location = trim($_POST["location"] ?? "");
$accountStatement = $conn->prepare("SELECT display_name, phone FROM users WHERE id = ? LIMIT 1");
$accountStatement->bind_param("i", $_SESSION["user_id"]);
$accountStatement->execute();
$account = $accountStatement->get_result()->fetch_assoc();
$accountStatement->close();
if (!$account || !in_array($category, $allowedCategories, true) || $title === "" || mb_strlen($title) > 150 || $description === "" || mb_strlen($description) > 5000 || $location === "" || mb_strlen($location) > 255) {
	header("Location: ../report.php?error=1");
	exit;
}

try {
	$reportCode = "CV-" . date("Ymd") . "-" . strtoupper(bin2hex(random_bytes(3)));
	$userId = (int) $_SESSION["user_id"];
	$reporterName = $account["display_name"];
	$reporterPhone = $account["phone"] ?? "";
	$statement = $conn->prepare("INSERT INTO reports (user_id, report_code, category, title, description, location, reporter_name, reporter_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
	$statement->bind_param("isssssss", $userId, $reportCode, $category, $title, $description, $location, $reporterName, $reporterPhone);
	$statement->execute();
	$statement->close();
	$_SESSION["report_csrf"] = bin2hex(random_bytes(32));
	header("Location: ../report.php?submitted=" . urlencode($reportCode));
	exit;
} catch (Throwable $exception) {
	error_log("Community report save failed: " . $exception->getMessage());
	header("Location: ../report.php?error=1");
	exit;
}

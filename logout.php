<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_SESSION["account_csrf"]) || !hash_equals($_SESSION["account_csrf"], $_POST["csrf_token"] ?? "")) {
	header("Location: account.php");
	exit;
}

unset($_SESSION["user_id"], $_SESSION["user_name"], $_SESSION["user_email"], $_SESSION["account_csrf"]);
header("Location: account.php?mode=login");
exit;
<?php
require_once __DIR__ . "/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"] ?? "")) {
    header("Location: dashboard.php");
    exit;
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $parameters = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $parameters["path"], $parameters["domain"], $parameters["secure"], $parameters["httponly"]);
}
session_destroy();
header("Location: login.php");
exit;
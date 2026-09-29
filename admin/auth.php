<?php
session_start();
require_once __DIR__ . "/../config/db_connect.php";

if (empty($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$profileStatement = $conn->prepare("SELECT username, display_name FROM admins WHERE id = ? LIMIT 1");
$profileStatement->bind_param("i", $_SESSION["admin_id"]);
$profileStatement->execute();
$leaderProfile = $profileStatement->get_result()->fetch_assoc();
$profileStatement->close();
if (!$leaderProfile) {
    unset($_SESSION["admin_id"], $_SESSION["admin_username"], $_SESSION["admin_display_name"]);
    header("Location: login.php");
    exit;
}
$_SESSION["admin_username"] = $leaderProfile["username"];
$_SESSION["admin_display_name"] = $leaderProfile["display_name"] ?: $leaderProfile["username"];
$leaderInitial = mb_strtoupper(mb_substr($_SESSION["admin_display_name"], 0, 1));

if (empty($_SESSION["admin_csrf"])) {
    $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function status_class($status)
{
    return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $status), '-'));
}
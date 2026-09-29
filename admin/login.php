<?php
session_start();
require_once __DIR__ . "/../config/db_connect.php";

if (!empty($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}
if (empty($_SESSION["login_csrf"])) {
    $_SESSION["login_csrf"] = bin2hex(random_bytes(32));
}

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $token = $_POST["csrf_token"] ?? "";
    if (!hash_equals($_SESSION["login_csrf"], $token)) {
        $error = "Your session expired. Please refresh and try again.";
    } else {
        $statement = $conn->prepare("SELECT id, username, display_name, password FROM admins WHERE username = ? LIMIT 1");
        $statement->bind_param("s", $username);
        $statement->execute();
        $admin = $statement->get_result()->fetch_assoc();
        $statement->close();
        if ($admin && password_verify($password, $admin["password"])) {
            session_regenerate_id(true);
            $_SESSION["admin_id"] = (int) $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];
            $_SESSION["admin_display_name"] = $admin["display_name"] ?: $admin["username"];
            $_SESSION["admin_csrf"] = bin2hex(random_bytes(32));
            header("Location: dashboard.php");
            exit;
        }
        $error = "The username or password is incorrect.";
    }
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Leader sign in | Community Voice</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
    <header class="site-header"><a class="brand" href="../index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a><div class="header-actions"><button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode"><span class="theme-icon" aria-hidden="true">◐</span></button></div></header>
    <main class="auth-wrap"><div class="page-intro"><p class="eyebrow">COMMUNITY LEADERS</p><h1>Welcome back</h1><p>Sign in to review reports and share progress with your community.</p></div><section class="form-panel">
        <?php if ($error !== ""): ?><div class="notice notice-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div><?php endif; ?>
        <form method="post" action="login.php"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["login_csrf"], ENT_QUOTES, "UTF-8") ?>"><div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required></div><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button class="button button-primary" type="submit">Sign in <span aria-hidden="true">→</span></button></form>
    </section><p class="auth-foot"><a class="text-link" href="../index.php">← Back to the community site</a></p></main>
    <script src="../assets/js/index.js" defer></script>
</body></html>
<?php
session_start();
require_once "config/db_connect.php";

if (empty($_SESSION["account_csrf"])) {
	$_SESSION["account_csrf"] = bin2hex(random_bytes(32));
}

$mode = $_GET["mode"] ?? (empty($_SESSION["user_id"]) ? "login" : "profile");
if (!in_array($mode, ["login", "register", "profile"], true)) {
	$mode = "login";
}
$next = ($_POST["next"] ?? $_GET["next"] ?? "") === "report.php" ? "report.php" : "account.php";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	$action = $_POST["action"] ?? "";
	$token = $_POST["csrf_token"] ?? "";
	if (!hash_equals($_SESSION["account_csrf"], $token)) {
		$error = "Your session expired. Please try again.";
	} elseif ($action === "register") {
		$name = trim($_POST["display_name"] ?? "");
		$email = strtolower(trim($_POST["email"] ?? ""));
		$phone = trim($_POST["phone"] ?? "");
		$password = $_POST["password"] ?? "";
		$confirmation = $_POST["password_confirm"] ?? "";
		if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
			$error = "Enter a name between 2 and 100 characters.";
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
			$error = "Enter a valid email address.";
		} elseif (mb_strlen($phone) > 30) {
			$error = "Your phone number must be 30 characters or fewer.";
		} elseif (strlen($password) < 12) {
			$error = "Use a password with at least 12 characters.";
		} elseif ($password !== $confirmation) {
			$error = "The passwords do not match.";
		} else {
			$hash = password_hash($password, PASSWORD_DEFAULT);
			$statement = $conn->prepare("INSERT INTO users (display_name, email, phone, password) VALUES (?, ?, ?, ?)");
			$statement->bind_param("ssss", $name, $email, $phone, $hash);
			try {
				$statement->execute();
				$userId = $conn->insert_id;
				$statement->close();
				session_regenerate_id(true);
				$_SESSION["user_id"] = (int) $userId;
				$_SESSION["user_name"] = $name;
				$_SESSION["user_email"] = $email;
				$_SESSION["account_csrf"] = bin2hex(random_bytes(32));
				header("Location: " . ($next === "report.php" ? "report.php" : "account.php?created=1"));
				exit;
			} catch (mysqli_sql_exception $exception) {
				$statement->close();
				$error = $exception->getCode() === 1062 ? "An account already exists for that email address." : "We couldn't create your account. Please try again.";
			}
		}
		$mode = "register";
	} elseif ($action === "login") {
		$email = strtolower(trim($_POST["email"] ?? ""));
		$password = $_POST["password"] ?? "";
		$statement = $conn->prepare("SELECT id, display_name, email, password FROM users WHERE email = ? LIMIT 1");
		$statement->bind_param("s", $email);
		$statement->execute();
		$user = $statement->get_result()->fetch_assoc();
		$statement->close();
		if ($user && password_verify($password, $user["password"])) {
			session_regenerate_id(true);
			$_SESSION["user_id"] = (int) $user["id"];
			$_SESSION["user_name"] = $user["display_name"];
			$_SESSION["user_email"] = $user["email"];
			$_SESSION["account_csrf"] = bin2hex(random_bytes(32));
			header("Location: " . ($next === "report.php" ? "report.php" : "account.php"));
			exit;
		}
		$error = "The email or password is incorrect.";
		$mode = "login";
	}
}

if (!empty($_SESSION["user_id"]) && $next === "report.php") {
	header("Location: report.php");
	exit;
}

function e($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$myReports = [];
if (!empty($_SESSION["user_id"]) && $mode === "profile") {
	$statement = $conn->prepare("SELECT report_code, title, category, status, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
	$statement->bind_param("i", $_SESSION["user_id"]);
	$statement->execute();
	$result = $statement->get_result();
	while ($row = $result->fetch_assoc()) {
		$myReports[] = $row;
	}
	$statement->close();
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= $mode === "profile" ? "My account" : ($mode === "register" ? "Create an account" : "Sign in") ?> | Community Voice</title>
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
	<header class="site-header">
		<a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a>
		<nav class="main-nav" aria-label="Main navigation"><a class="nav-link" href="index.php">Home</a><a class="nav-link" href="reports.php">Community reports</a><a class="nav-link" href="about.php">About</a><a class="nav-link" href="faq.php">Help</a></nav>
		<div class="header-actions"><button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode"><span class="theme-icon" aria-hidden="true">◐</span></button><?php if (!empty($_SESSION["user_id"])): ?><a class="profile-chip" href="account.php"><span class="profile-avatar"><?= e(mb_strtoupper(mb_substr($_SESSION["user_name"], 0, 1))) ?></span><span>My account</span></a><?php else: ?><a class="button button-small button-primary" href="account.php?mode=register">Create account</a><?php endif; ?></div>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-label="Open navigation"><span></span><span></span></button>
	</header>
	<main class="page-width page-main">
		<?php if ($mode === "profile" && !empty($_SESSION["user_id"])): ?>
			<div class="account-heading"><div class="profile-avatar profile-avatar-large"><?= e(mb_strtoupper(mb_substr($_SESSION["user_name"], 0, 1))) ?></div><div><p class="eyebrow">RESIDENT ACCOUNT</p><h1><?= e($_SESSION["user_name"]) ?></h1><p><?= e($_SESSION["user_email"] ?? "") ?></p></div><form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION["account_csrf"]) ?>"><button class="button button-outline" type="submit">Sign out</button></form></div>
			<?php if (isset($_GET["created"])): ?><div class="notice notice-success" role="status">Your account is ready. You can now submit and follow your reports.</div><?php endif; ?>
			<div class="account-section-heading"><div><p class="eyebrow">YOUR ACTIVITY</p><h2>My reports</h2></div><a class="button button-primary" href="report.php">Make a report <span aria-hidden="true">→</span></a></div>
			<?php if ($myReports): ?><div class="report-list"><?php foreach ($myReports as $report): ?><a class="report-row" href="reports.php?code=<?= urlencode($report["report_code"]) ?>"><span class="report-category"><?= e($report["category"]) ?> · <?= e($report["report_code"]) ?></span><span class="report-title"><?= e($report["title"]) ?></span><span class="status-pill status-<?= e(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $report["status"]), '-'))) ?>"><?= e($report["status"]) ?></span><span class="report-arrow" aria-hidden="true">↗</span></a><?php endforeach; ?></div><?php else: ?><div class="empty-state"><span aria-hidden="true">◎</span><p>You haven’t submitted a report yet.</p><a class="text-link" href="report.php">Make your first report <span aria-hidden="true">→</span></a></div><?php endif; ?>
		<?php else: ?>
			<div class="auth-wrap account-auth"><div class="page-intro"><p class="eyebrow">YOUR COMMUNITY ACCOUNT</p><h1><?= $mode === "register" ? "Join your community" : "Welcome back" ?></h1><p><?= $mode === "register" ? "Create an account to raise concerns and keep track of your reports." : "Sign in to submit reports and see their progress in one place." ?></p></div>
				<section class="form-panel">
					<?php if ($error !== ""): ?><div class="notice notice-error" role="alert"><?= e($error) ?></div><?php endif; ?>
					<form method="post" action="account.php<?= $next === "report.php" ? "?next=report.php" : "" ?>"><input type="hidden" name="csrf_token" value="<?= e($_SESSION["account_csrf"]) ?>"><input type="hidden" name="next" value="<?= e($next) ?>"><input type="hidden" name="action" value="<?= e($mode === "register" ? "register" : "login") ?>">
						<?php if ($mode === "register"): ?><div class="field"><label for="display_name">Your name</label><input id="display_name" name="display_name" maxlength="100" autocomplete="name" required></div><?php endif; ?>
						<div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="150" autocomplete="email" required></div>
						<?php if ($mode === "register"): ?><div class="field"><label for="phone">Phone <span class="muted-label">(optional)</span></label><input id="phone" name="phone" maxlength="30" autocomplete="tel"></div><?php endif; ?>
						<div class="field"><label for="password">Password</label><input id="password" name="password" type="password" minlength="<?= $mode === "register" ? "12" : "1" ?>" autocomplete="<?= $mode === "register" ? "new-password" : "current-password" ?>" required><?php if ($mode === "register"): ?><small>Use at least 12 characters.</small><?php endif; ?></div>
						<?php if ($mode === "register"): ?><div class="field"><label for="password_confirm">Confirm password</label><input id="password_confirm" name="password_confirm" type="password" minlength="12" autocomplete="new-password" required></div><?php endif; ?>
						<button class="button button-primary" type="submit"><?= $mode === "register" ? "Create account" : "Sign in" ?> <span aria-hidden="true">→</span></button>
					</form>
				</section>
				<p class="auth-foot"><?php if ($mode === "register"): ?>Already have an account? <a class="text-link" href="account.php?mode=login<?= $next === "report.php" ? "&next=report.php" : "" ?>">Sign in <span aria-hidden="true">→</span></a><?php else: ?>New to Community Voice? <a class="text-link" href="account.php?mode=register<?= $next === "report.php" ? "&next=report.php" : "" ?>">Create an account <span aria-hidden="true">→</span></a><?php endif; ?></p>
			</div>
		<?php endif; ?>
	</main>
	<footer class="site-footer page-width"><a class="brand footer-brand" href="index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a><p>Better neighborhoods, built together.</p><div class="footer-links"><a href="reports.php">Community reports</a><a href="faq.php">Help & FAQ</a><a href="admin/login.php">Leader sign in</a></div></footer>
	<script src="assets/js/index.js" defer></script>
</body>
</html>
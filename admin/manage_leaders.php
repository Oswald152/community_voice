<?php
require_once __DIR__ . "/auth.php";

$error = "";
$success = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if (!hash_equals($_SESSION["admin_csrf"], $_POST["csrf_token"] ?? "")) {
		$error = "Your session expired. Please try again.";
	} elseif (($_POST["action"] ?? "") === "update_profile") {
		$displayName = trim($_POST["display_name"] ?? "");
		if (mb_strlen($displayName) < 2 || mb_strlen($displayName) > 100) {
			$error = "Enter a name between 2 and 100 characters.";
		} else {
			$statement = $conn->prepare("UPDATE admins SET display_name = ? WHERE id = ?");
			$statement->bind_param("si", $displayName, $_SESSION["admin_id"]);
			$statement->execute();
			$statement->close();
			$_SESSION["admin_display_name"] = $displayName;
			$leaderProfile["display_name"] = $displayName;
			$leaderInitial = mb_strtoupper(mb_substr($displayName, 0, 1));
			$success = "Your profile name has been updated.";
		}
	} else {
		$displayName = trim($_POST["display_name"] ?? "");
		$username = trim($_POST["username"] ?? "");
		$password = $_POST["password"] ?? "";
		if (mb_strlen($displayName) < 2 || mb_strlen($displayName) > 100) {
			$error = "Enter a name between 2 and 100 characters.";
		} elseif (strlen($username) < 4 || strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
			$error = "Choose a username between 4 and 50 characters using letters, numbers, dots, dashes, or underscores.";
		} elseif (strlen($password) < 12) {
			$error = "Use a password with at least 12 characters.";
		} else {
			$passwordHash = password_hash($password, PASSWORD_DEFAULT);
			$statement = $conn->prepare("INSERT INTO admins (username, display_name, password) VALUES (?, ?, ?)");
			$statement->bind_param("sss", $username, $displayName, $passwordHash);
			try {
				$statement->execute();
				$statement->close();
				$success = "Leader account created. Share the sign-in details with that leader privately.";
			} catch (mysqli_sql_exception $exception) {
				$statement->close();
				$error = $exception->getCode() === 1062 ? "That username is already in use." : "We couldn't create that account. Please try again.";
			}
		}
	}
}

$leaders = [];
$result = $conn->query("SELECT id, username, display_name, created_at FROM admins ORDER BY display_name");
if ($result) {
	while ($row = $result->fetch_assoc()) {
		$leaders[] = $row;
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Leader access | Community Voice</title>
	<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
	<header class="site-header">
		<a class="brand" href="../index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a>
		<nav class="main-nav" aria-label="Leader navigation"><a class="nav-link" href="dashboard.php">Report queue</a><a class="nav-link is-current" href="manage_leaders.php">Leader access</a><a class="nav-link" href="../index.php">Community site</a></nav>
		<div class="header-actions"><button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode"><span class="theme-icon" aria-hidden="true">◐</span></button><a class="profile-chip" href="manage_leaders.php"><span class="profile-avatar"><?= e($leaderInitial) ?></span><span><?= e($_SESSION["admin_display_name"]) ?></span></a><form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION["admin_csrf"]) ?>"><button class="button button-small button-outline" type="submit">Sign out</button></form></div>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-label="Open navigation"><span></span><span></span></button>
	</header>
	<main class="page-width page-main">
		<div class="page-intro"><p class="eyebrow">PRIVATE LEADER WORKSPACE</p><h1>Leader access</h1><p>Create accounts for trusted community leaders. This page is only available after signing in.</p></div>
		<?php if ($error !== ""): ?><div class="notice notice-error" role="alert"><?= e($error) ?></div><?php elseif ($success !== ""): ?><div class="notice notice-success" role="status"><?= e($success) ?></div><?php endif; ?>
		<div class="content-grid"><section class="form-panel"><div class="profile-edit"><h2>Your profile</h2><form action="manage_leaders.php" method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION["admin_csrf"]) ?>"><input type="hidden" name="action" value="update_profile"><div class="field"><label for="profile_name">Display name</label><input id="profile_name" name="display_name" value="<?= e($_SESSION["admin_display_name"]) ?>" maxlength="100" autocomplete="name" required></div><button class="button button-outline" type="submit">Update profile</button></form></div><h2>Add a community leader</h2><form action="manage_leaders.php" method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION["admin_csrf"]) ?>"><input type="hidden" name="action" value="create_leader"><div class="field"><label for="display_name">Leader's name</label><input id="display_name" name="display_name" maxlength="100" autocomplete="name" required></div><div class="field"><label for="username">Sign-in username</label><input id="username" name="username" minlength="4" maxlength="50" autocomplete="off" required></div><div class="field"><label for="password">Temporary password</label><input id="password" name="password" type="password" minlength="12" autocomplete="new-password" required><small>At least 12 characters. Share it with the leader privately.</small></div><button class="button button-primary" type="submit">Create leader account <span aria-hidden="true">→</span></button></form></section>
			<aside class="info-panel"><h2>Current leaders</h2><?php if ($leaders): ?><div class="leader-list"><?php foreach ($leaders as $leader): ?><div class="leader-list-item"><span class="profile-avatar"><?= e(mb_strtoupper(mb_substr($leader["display_name"], 0, 1))) ?></span><span><strong><?= e($leader["display_name"]) ?></strong><small>@<?= e($leader["username"]) ?></small></span></div><?php endforeach; ?></div><?php else: ?><p>No leader accounts have been added.</p><?php endif; ?></aside>
		</div>
		<p class="auth-foot"><a class="text-link" href="dashboard.php">← Back to the report queue</a></p>
	</main>
	<script src="../assets/js/index.js" defer></script>
</body>
</html>
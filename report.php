<?php
session_start();
require_once "config/db_connect.php";

if (empty($_SESSION["user_id"])) {
	header("Location: account.php?mode=login&next=report.php");
	exit;
}

if (empty($_SESSION["report_csrf"])) {
	$_SESSION["report_csrf"] = bin2hex(random_bytes(32));
}

function e($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$submittedCode = isset($_GET["submitted"]) ? trim($_GET["submitted"]) : "";
$categories = ["Roads & walkways", "Waste & cleanliness", "Street lighting", "Parks & public spaces", "Water & drainage", "Safety", "Other"];
$accountStatement = $conn->prepare("SELECT display_name, email, phone FROM users WHERE id = ? LIMIT 1");
$accountStatement->bind_param("i", $_SESSION["user_id"]);
$accountStatement->execute();
$accountProfile = $accountStatement->get_result()->fetch_assoc();
$accountStatement->close();
if (!$accountProfile) {
	unset($_SESSION["user_id"], $_SESSION["user_name"], $_SESSION["user_email"]);
	header("Location: account.php?mode=login&next=report.php");
	exit;
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Make a report | Community Voice</title>
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
	<header class="site-header">
		<a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a>
		<nav class="main-nav" aria-label="Main navigation"><a class="nav-link" href="index.php">Home</a><a class="nav-link" href="reports.php">Community reports</a><a class="nav-link" href="about.php">About</a><a class="nav-link" href="faq.php">Help</a></nav>
		<div class="header-actions"><button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode"><span class="theme-icon" aria-hidden="true">◐</span></button><a class="profile-chip" href="account.php"><span class="profile-avatar"><?= e(mb_strtoupper(mb_substr($accountProfile["display_name"], 0, 1))) ?></span><span>My account</span></a></div>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-label="Open navigation"><span></span><span></span></button>
	</header>
	<main class="page-width page-main">
		<div class="page-intro"><p class="eyebrow">YOUR VOICE MATTERS</p><h1>Make a community report</h1><p>Tell local leaders what needs attention. Add a clear location and enough detail to help them understand the issue.</p></div>
		<?php if ($submittedCode !== ""): ?>
			<div class="notice notice-success" role="status">Your report has been received. Save this reference code to track updates: <strong><?= e($submittedCode) ?></strong>. <a class="text-link" href="reports.php?code=<?= urlencode($submittedCode) ?>">Track this report →</a></div>
		<?php elseif (isset($_GET["error"])): ?>
			<div class="notice notice-error" role="alert">We couldn't save that report. Please check the details and try again.</div>
		<?php endif; ?>
		<div class="content-grid">
			<section class="form-panel">
				<h2>What should we know?</h2>
				<form action="save/save_report.php" method="post">
					<input type="hidden" name="csrf_token" value="<?= e($_SESSION["report_csrf"]) ?>">
					<div class="field-grid">
						<div class="field"><label for="category">Category <span aria-hidden="true">*</span></label><select id="category" name="category" required><option value="">Choose a category</option><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"><?= e($category) ?></option><?php endforeach; ?></select></div>
						<div class="field"><label for="title">Short title <span aria-hidden="true">*</span></label><input id="title" name="title" maxlength="150" placeholder="e.g. Streetlight out on Cedar Lane" required></div>
					</div>
					<div class="field"><label for="description">Describe the issue <span aria-hidden="true">*</span></label><textarea id="description" name="description" maxlength="5000" placeholder="What happened? When did you notice it? Who or what is affected?" required></textarea><small>Don't include sensitive personal information.</small></div>
					<div class="field"><label for="location">Where is it? <span aria-hidden="true">*</span></label><input id="location" name="location" maxlength="255" placeholder="Street, landmark, or nearby intersection" required></div>
						<div class="reporting-account"><span class="profile-avatar"><?= e(mb_strtoupper(mb_substr($accountProfile["display_name"], 0, 1))) ?></span><span>Submitting as <strong><?= e($accountProfile["display_name"]) ?></strong><small><?= e($accountProfile["email"]) ?> · Private to community leaders</small></span><a class="text-link" href="account.php">Account</a></div>
						<div class="form-foot"><small>Your name and contact details are private and are not shown in the public report list.</small><button class="button button-primary" type="submit">Send report <span aria-hidden="true">→</span></button></div>
				</form>
			</section>
			<aside class="info-panel"><h2>What happens next?</h2><ol><li>Your report appears in the community report list.</li><li>Leaders review the details and post progress updates.</li><li>Use your reference code to check its status at any time.</li></ol><p>Reports are public so neighbors can see local concerns and progress. Personal contact details remain private.</p></aside>
		</div>
	</main>
	<footer class="site-footer page-width"><a class="brand footer-brand" href="index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a><p>Better neighborhoods, built together.</p><div class="footer-links"><a href="contact.php">Contact</a><a href="faq.php">Help & FAQ</a><a href="admin/login.php">For community leaders</a></div></footer>
	<script src="assets/js/index.js" defer></script>
</body>
</html>

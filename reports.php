<?php
session_start();
require_once "config/db_connect.php";

function e($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$code = isset($_GET["code"]) ? strtoupper(trim($_GET["code"])) : "";
$trackedReport = null;
$lookupAttempted = $code !== "";
if ($lookupAttempted) {
	$statement = $conn->prepare("SELECT report_code, category, title, description, location, status, admin_note, created_at, updated_at FROM reports WHERE report_code = ? LIMIT 1");
	$statement->bind_param("s", $code);
	$statement->execute();
	$trackedReport = $statement->get_result()->fetch_assoc();
	$statement->close();
}

$reports = [];
$result = $conn->query("SELECT report_code, category, title, description, location, status, admin_note, created_at, updated_at FROM reports ORDER BY created_at DESC LIMIT 50");
if ($result) {
	while ($row = $result->fetch_assoc()) {
		$reports[] = $row;
	}
}

function status_class($status)
{
	return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $status), '-'));
}

function report_card($report)
{
	?>
	<article class="report-card">
		<div><span class="report-category"><?= e($report["category"]) ?> · <?= e($report["report_code"]) ?></span><h2><?= e($report["title"]) ?></h2><p><?= nl2br(e($report["description"])) ?></p></div>
		<span class="status-pill status-<?= e(status_class($report["status"])) ?>"><?= e($report["status"]) ?></span>
		<div class="report-meta"><span>⌖ <?= e($report["location"]) ?></span><span>Reported <?= e(date("M j, Y", strtotime($report["created_at"]))) ?></span></div>
		<?php if (!empty($report["admin_note"])): ?><div class="report-note"><strong>Leader update:</strong> <?= nl2br(e($report["admin_note"])) ?> <span>· Updated <?= e(date("M j, Y", strtotime($report["updated_at"]))) ?></span></div><?php endif; ?>
	</article>
	<?php
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Community reports | Community Voice</title>
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
	<header class="site-header">
		<a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a>
		<nav class="main-nav" aria-label="Main navigation"><a class="nav-link" href="index.php">Home</a><a class="nav-link is-current" href="reports.php">Community reports</a><a class="nav-link" href="about.php">About</a><a class="nav-link" href="faq.php">Help</a></nav>
		<div class="header-actions"><button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode"><span class="theme-icon" aria-hidden="true">◐</span></button><?php if (!empty($_SESSION["user_id"])): ?><a class="profile-chip" href="account.php"><span class="profile-avatar"><?= e(mb_strtoupper(mb_substr($_SESSION["user_name"], 0, 1))) ?></span><span>My account</span></a><?php else: ?><a class="button button-small button-outline" href="account.php?mode=register">Create account</a><?php endif; ?><a class="button button-small button-primary" href="report.php">Make a report <span aria-hidden="true">↗</span></a></div>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-label="Open navigation"><span></span><span></span></button>
	</header>
	<main class="page-width page-main">
		<div class="page-intro"><p class="eyebrow">VISIBLE PROGRESS</p><h1>Community reports</h1><p>See what neighbors have raised, follow leader updates, or look up a report with its reference code.</p></div>
		<form class="report-search" action="reports.php" method="get"><div class="field"><label for="code">Track a report</label><input class="search-input" id="code" name="code" value="<?= e($code) ?>" placeholder="Enter reference code, e.g. CV-20260925-A1B2C3"></div><button class="button button-primary" type="submit">Find report <span aria-hidden="true">→</span></button></form>
		<?php if ($lookupAttempted): ?>
			<section class="track-panel" aria-live="polite"><h2>Tracking <?= e($code) ?></h2><?php if ($trackedReport): report_card($trackedReport); else: ?><p class="muted-copy">We couldn't find a report with that code. Check it and try again.</p><?php endif; ?></section>
		<?php endif; ?>
		<div class="filter-bar"><h2>Recent reports</h2><p>Showing <?= count($reports) ?><?= count($reports) === 50 ? " most recent" : "" ?></p></div>
		<?php if ($reports): ?><div class="report-results"><?php foreach ($reports as $report): report_card($report); endforeach; ?></div><?php else: ?><div class="empty-state"><span aria-hidden="true">◎</span><p>There are no community reports yet.</p><a class="text-link" href="report.php">Share the first one <span aria-hidden="true">→</span></a></div><?php endif; ?>
	</main>
	<footer class="site-footer page-width"><a class="brand footer-brand" href="index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a><p>Better neighborhoods, built together.</p><div class="footer-links"><a href="contact.php">Contact</a><a href="faq.php">Help & FAQ</a><a href="admin/login.php">For community leaders</a></div></footer>
	<script src="assets/js/index.js" defer></script>
</body>
</html>


<?php
session_start();
require_once "config/db_connect.php";

$reportCount = 0;
$resolvedCount = 0;
$activeCount = 0;
$recentReports = [];

$counts = $conn->query("SELECT COUNT(*) AS total, SUM(status = 'Resolved') AS resolved FROM reports");
if ($counts && ($row = $counts->fetch_assoc())) {
	$reportCount = (int) $row["total"];
	$resolvedCount = (int) $row["resolved"];
	$activeCount = max(0, $reportCount - $resolvedCount);
}

$recent = $conn->query("SELECT report_code, category, title, status, created_at FROM reports ORDER BY created_at DESC LIMIT 3");
if ($recent) {
	while ($row = $recent->fetch_assoc()) {
		$recentReports[] = $row;
	}
}

function e($value)
{
	return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f4f8fc">
	<title>Community Voice | Make your neighborhood heard</title>
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
	<a class="skip-link" href="#main">Skip to content</a>
	<header class="site-header">
		<a class="brand" href="index.php" aria-label="Community Voice home">
			<span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span>
			<span>Community <span class="brand-light">Voice</span></span>
		</a>
		<nav class="main-nav" aria-label="Main navigation">
			<a class="nav-link is-current" href="index.php">Home</a>
			<a class="nav-link" href="reports.php">Community reports</a>
			<a class="nav-link" href="about.php">About</a>
			<a class="nav-link" href="faq.php">Help</a>
		</nav>
		<div class="header-actions">
			<button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode">
				<span class="theme-icon" aria-hidden="true">◐</span>
			</button>
			<?php if (!empty($_SESSION["user_id"])): ?><a class="profile-chip" href="account.php"><span class="profile-avatar"><?= e(mb_strtoupper(mb_substr($_SESSION["user_name"], 0, 1))) ?></span><span>My account</span></a><?php else: ?><a class="button button-small button-outline" href="account.php?mode=register">Create account</a><?php endif; ?>
			<a class="button button-small button-primary" href="report.php">Make a report <span aria-hidden="true">↗</span></a>
		</div>
		<button class="menu-toggle" type="button" aria-expanded="false" aria-label="Open navigation">
			<span></span><span></span>
		</button>
	</header>

	<main id="main">
		<section class="hero page-width">
			<div class="hero-copy">
				<p class="eyebrow"><span class="eyebrow-dot"></span> A clearer way to care for where we live</p>
				<h1>Good communities<br>start with <span>a voice.</span></h1>
				<p class="hero-intro">See something that needs attention? Share it with local leaders, follow its progress, and help make our neighborhood better for everyone.</p>
				<div class="hero-actions">
					<a class="button button-primary button-large" href="report.php">Report a community issue <span aria-hidden="true">→</span></a>
					<a class="text-link" href="reports.php">Explore community reports <span aria-hidden="true">↗</span></a>
				</div>
				<div class="hero-note"><span class="note-icon" aria-hidden="true">✓</span> Every report gets a reference code you can use to track it.</div>
			</div>
			<div class="hero-visual" aria-label="Community report activity">
				<div class="visual-topline"><span class="live-indicator"></span> YOUR COMMUNITY, IN VIEW <span class="visual-period">LIVE OVERVIEW</span></div>
				<div class="visual-heading"><div><span class="visual-kicker">A little progress, together</span><h2>Local voices.<br>Visible change.</h2></div><span class="visual-spark" aria-hidden="true">↗</span></div>
				<div class="visual-stats">
					<div class="visual-stat"><strong><?= number_format($reportCount) ?></strong><span>reports shared</span></div>
					<div class="visual-stat"><strong><?= number_format($activeCount) ?></strong><span>being worked on</span></div>
					<div class="visual-stat"><strong><?= number_format($resolvedCount) ?></strong><span>resolved</span></div>
				</div>
				<div class="visual-rule"></div>
				<div class="visual-bottom"><span class="avatar-stack" aria-hidden="true"><i>J</i><i>M</i><i>A</i><b>+</b></span><span>Neighbors and leaders, working as one</span><a href="reports.php" aria-label="View community reports">→</a></div>
				<div class="visual-orbit orbit-one"></div><div class="visual-orbit orbit-two"></div>
			</div>
		</section>

		<section class="purpose-section">
			<div class="page-width purpose-grid">
				<div class="section-heading">
					<p class="eyebrow">WHY COMMUNITY VOICE</p>
					<h2>Small concerns deserve<br>a clear path forward.</h2>
				</div>
				<div class="purpose-copy">
					<p>Broken streetlights, overflowing bins, unsafe walkways: local issues are easier to solve when the right people can see them. Community Voice brings residents and community leaders together around the things that matter on our streets.</p>
					<a class="text-link" href="about.php">Learn about our objectives <span aria-hidden="true">→</span></a>
				</div>
			</div>
		</section>

		<section class="how-section page-width">
			<div class="section-heading how-heading">
				<p class="eyebrow">A SIMPLE, SHARED PROCESS</p>
				<h2>From “I noticed” to “it’s sorted.”</h2>
			</div>
			<div class="steps-grid">
				<article class="step-item"><span class="step-number">01</span><span class="step-icon" aria-hidden="true">↗</span><h3>Tell us what’s happening</h3><p>Share the issue, where it is, and what would help. Clear details help leaders act sooner.</p></article>
				<article class="step-item"><span class="step-number">02</span><span class="step-icon" aria-hidden="true">◎</span><h3>Keep your reference</h3><p>Your report gets a unique code. Use it any time to check the latest status and leader updates.</p></article>
				<article class="step-item"><span class="step-number">03</span><span class="step-icon" aria-hidden="true">✓</span><h3>Follow the progress</h3><p>Leaders review reports, share what’s being done, and mark issues resolved when the work is complete.</p></article>
			</div>
		</section>

		<section class="recent-section">
			<div class="page-width recent-inner">
				<div class="recent-heading"><div><p class="eyebrow">OPENNESS BUILDS TRUST</p><h2>What neighbors are raising</h2></div><a class="text-link" href="reports.php">View all reports <span aria-hidden="true">→</span></a></div>
				<?php if ($recentReports): ?>
					<div class="report-list">
						<?php foreach ($recentReports as $report): ?>
							<a class="report-row" href="reports.php?code=<?= urlencode($report["report_code"]) ?>">
								<span class="report-category"><?= e($report["category"]) ?></span>
								<span class="report-title"><?= e($report["title"]) ?></span>
								<span class="status-pill status-<?= strtolower(preg_replace('/[^a-z0-9]+/i', '-', $report["status"])) ?>"><?= e($report["status"]) ?></span>
								<span class="report-arrow" aria-hidden="true">↗</span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<div class="empty-state"><span aria-hidden="true">◎</span><p>No reports yet. Be the first to bring a community concern forward.</p><a class="text-link" href="report.php">Make the first report <span aria-hidden="true">→</span></a></div>
				<?php endif; ?>
			</div>
		</section>

		<section class="closing-cta page-width">
			<div><p class="eyebrow">YOUR NEIGHBORHOOD IS WORTH IT</p><h2>Make the next good thing happen.</h2></div>
			<a class="button button-light button-large" href="report.php">Share a concern <span aria-hidden="true">→</span></a>
			<span class="cta-ring" aria-hidden="true"></span>
		</section>
	</main>

	<footer class="site-footer page-width">
		<a class="brand footer-brand" href="index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a>
		<p>Better neighborhoods, built together.</p>
		<div class="footer-links"><a href="contact.php">Contact</a><a href="faq.php">Help & FAQ</a><a href="admin/login.php">For community leaders</a></div>
		<span class="copyright">© <?= date("Y") ?> Community Voice</span>
	</footer>
	<script src="assets/js/index.js" defer></script>
</body>
</html>

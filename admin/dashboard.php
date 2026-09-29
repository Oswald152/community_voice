<?php
require_once __DIR__ . "/auth.php";

$statuses = ["Reported", "In review", "In progress", "Resolved"];
$filter = $_GET["status"] ?? "All";
if ($filter !== "All" && !in_array($filter, $statuses, true)) {
    $filter = "All";
}
$reports = [];
$sql = "SELECT id, report_code, category, title, description, location, reporter_name, reporter_phone, status, admin_note, created_at FROM reports";
if ($filter !== "All") {
    $sql .= " WHERE status = '" . $conn->real_escape_string($filter) . "'";
}
$sql .= " ORDER BY FIELD(status, 'Reported', 'In review', 'In progress', 'Resolved'), created_at ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
$summaryResult = $conn->query("SELECT COUNT(*) AS total, SUM(status = 'Reported') AS reported, SUM(status IN ('In review', 'In progress')) AS active, SUM(status = 'Resolved') AS resolved FROM reports");
$summary = $summaryResult ? $summaryResult->fetch_assoc() : ["total" => 0, "reported" => 0, "active" => 0, "resolved" => 0];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Leader dashboard | Community Voice</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
    <header class="site-header"><a class="brand" href="../index.php"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span><span>Community <span class="brand-light">Voice</span></span></a><nav class="main-nav" aria-label="Main navigation"><a class="nav-link" href="../index.php">Community site</a><a class="nav-link is-current" href="dashboard.php">Report queue</a><a class="nav-link" href="manage_leaders.php">Leader access</a></nav><div class="header-actions"><button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Switch to dark mode"><span class="theme-icon" aria-hidden="true">◐</span></button><a class="profile-chip" href="manage_leaders.php"><span class="profile-avatar"><?= e($leaderInitial) ?></span><span><?= e($_SESSION["admin_display_name"]) ?></span></a><form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION["admin_csrf"]) ?>"><button class="button button-small button-outline" type="submit">Sign out</button></form></div><button class="menu-toggle" type="button" aria-expanded="false" aria-label="Open navigation"><span></span><span></span></button></header>
    <main class="page-width page-main"><div class="page-intro"><p class="eyebrow">COMMUNITY LEADER WORKSPACE</p><h1>Report queue</h1><p>Review concerns and keep residents informed as work progresses.</p></div>
        <?php if (isset($_GET["updated"])): ?><div class="notice notice-success" role="status">Report update saved.</div><?php elseif (isset($_GET["error"])): ?><div class="notice notice-error" role="alert">The report update could not be saved. Please try again.</div><?php endif; ?>
        <div class="visual-stats leader-summary" style="max-width:650px;margin-bottom:26px"><div class="visual-stat"><strong><?= (int) $summary["total"] ?></strong><span>total reports</span></div><div class="visual-stat"><strong><?= (int) $summary["active"] ?></strong><span>in progress</span></div><div class="visual-stat"><strong><?= (int) $summary["resolved"] ?></strong><span>resolved</span></div></div>
        <div class="leader-layout"><section><form class="filter-bar" action="dashboard.php" method="get"><div class="field"><label for="status-filter">Filter by status</label><select id="status-filter" name="status" onchange="this.form.submit()"><?php foreach (["All", ...$statuses] as $status): ?><option value="<?= e($status) ?>" <?= $filter === $status ? "selected" : "" ?>><?= e($status) ?></option><?php endforeach; ?></select></div><p><?= count($reports) ?> report<?= count($reports) === 1 ? "" : "s" ?></p></form>
            <?php if ($reports): ?><?php foreach ($reports as $report): ?><article class="leader-card"><div class="leader-card-top"><div><span class="report-category"><?= e($report["category"]) ?> · <?= e($report["report_code"]) ?></span><h2><?= e($report["title"]) ?></h2></div><span class="status-pill status-<?= e(status_class($report["status"])) ?>"><?= e($report["status"]) ?></span></div><p><?= nl2br(e($report["description"])) ?></p><div class="report-meta"><span>⌖ <?= e($report["location"]) ?></span><span>Reported <?= e(date("M j, Y", strtotime($report["created_at"]))) ?></span><?php if ($report["reporter_name"]): ?><span>By <?= e($report["reporter_name"]) ?></span><?php endif; ?><?php if ($report["reporter_phone"]): ?><span><?= e($report["reporter_phone"]) ?></span><?php endif; ?></div><form action="update_report.php" method="post"><input type="hidden" name="csrf_token" value="<?= e($_SESSION["admin_csrf"]) ?>"><input type="hidden" name="report_id" value="<?= (int) $report["id"] ?>"><div class="field"><label for="status-<?= (int) $report["id"] ?>">Status</label><select id="status-<?= (int) $report["id"] ?>" name="status" required><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $report["status"] === $status ? "selected" : "" ?>><?= e($status) ?></option><?php endforeach; ?></select></div><div class="field"><label for="note-<?= (int) $report["id"] ?>">Public progress update</label><textarea id="note-<?= (int) $report["id"] ?>" name="admin_note" maxlength="5000" placeholder="Share an update residents can see"><?= e($report["admin_note"] ?? "") ?></textarea></div><button class="button button-primary" type="submit">Save update <span aria-hidden="true">→</span></button></form></article><?php endforeach; ?><?php else: ?><div class="empty-state"><span aria-hidden="true">✓</span><p>No reports in this view.</p></div><?php endif; ?></section>
            <aside class="leader-side"><div class="leader-profile"><span class="profile-avatar profile-avatar-large"><?= e($leaderInitial) ?></span><div><strong><?= e($_SESSION["admin_display_name"]) ?></strong><span>Community leader</span></div></div><h3>Keep the community in the loop</h3><p>Set a status that reflects the latest work and add a short public update. Residents can see it in the report list and tracker.</p><a class="text-link" href="manage_leaders.php">Manage leader access <span aria-hidden="true">→</span></a><a class="text-link" href="../reports.php">View public reports <span aria-hidden="true">↗</span></a></aside>
        </div>
    </main><script src="../assets/js/index.js" defer></script>
</body></html>
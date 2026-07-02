<?php
/**
 * TDA Currents — app entry.
 * Routes: ?issue=YYYY-MM renders that issue; no param renders the latest.
 * With no issues in data/, shows the placeholder card.
 */
require __DIR__ . '/templates/helpers.php';

// data/ and uploads/ are runtime folders excluded from deploy (section 7):
// create them on first run so a fresh server works without manual setup.
foreach ([TDA_DATA, TDA_ROOT . '/uploads'] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

$settings = load_settings();
$issues   = list_issues();
$issueId  = $_GET['issue'] ?? ($issues[0] ?? null);
$issue    = $issueId ? load_issue($issueId) : null;

if ($issue) {
    include __DIR__ . '/templates/newsletter.php';
    exit;
}

// No issue to show: placeholder.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>TDA Currents</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800&family=Archivo:wght@400;700&display=swap" rel="stylesheet">
<style>
  :root { --ink:#1E5373; --blue:#3199D8; --muted:#5d6c78; --tint:#eaf1f8; }
  * { box-sizing: border-box; }
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
         background:#fff; color:#23303a; font-family:'Archivo',sans-serif; -webkit-font-smoothing:antialiased; }
  .card { text-align:center; padding:48px 24px; }
  .anchor { width:64px; height:64px; margin-bottom:18px; }
  h1 { font-family:'Playfair Display',serif; font-size:42px; margin:0; color:var(--ink); font-weight:800; }
  h1 em { color:var(--blue); font-weight:700; }
  .tagline { font-size:11px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:var(--muted); margin-top:10px; }
  .soon { display:inline-block; margin-top:28px; padding:12px 26px; background:var(--tint);
          border-top:3px solid var(--ink); font-family:'Playfair Display',serif; font-size:21px; font-weight:800; color:var(--ink); }
</style>
</head>
<body>
  <div class="card">
    <img class="anchor" src="assets/anchor-logo.svg" alt="">
    <h1>TDA <em>Currents</em></h1>
    <div class="tagline"><?= e($settings['tagline'] ?? 'Keeping our community connected on the water') ?></div>
    <div class="soon">Coming Soon</div>
  </div>
</body>
</html>

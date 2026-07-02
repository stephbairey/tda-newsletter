<?php
/**
 * TDA Currents — app entry.
 *
 * Views (GET):            Actions (POST):
 *   /                       ?action=save&issue=YYYY-MM
 *   ?issue=YYYY-MM          ?action=duplicate&from=YYYY-MM
 *   ?page=dashboard         ?action=upload&issue=YYYY-MM   (JSON response)
 *   ?page=edit&issue=…      ?action=save_settings
 *   ?page=settings
 *
 * The whole subdomain sits behind cPanel Directory Privacy (section 8), so
 * every route above is already password-protected.
 */
require __DIR__ . '/templates/helpers.php';
require __DIR__ . '/templates/store.php';

// data/ and uploads/ are runtime folders excluded from deploy (section 7):
// create them on first run so a fresh server works without manual setup.
foreach ([TDA_DATA, TDA_ROOT . '/uploads'] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

$settings = load_settings();
$issues   = list_issues();

/* ---------- Actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';

    if ($action === 'upload') {
        header('Content-Type: application/json');
        echo json_encode(handle_upload($_GET['issue'] ?? '', $_FILES['photo'] ?? []));
        exit;
    }

    if ($action === 'save') {
        $id = $_GET['issue'] ?? '';
        if (preg_match('/^\d{4}-\d{2}$/', $id) && save_issue($id, sanitize_issue($_POST))) {
            header("Location: ?page=edit&issue=$id&saved=1");
        } else {
            header("Location: ?page=dashboard&error=save");
        }
        exit;
    }

    if ($action === 'duplicate') {
        $newId = duplicate_issue($_GET['from'] ?? '');
        header('Location: ' . ($newId ? "?page=edit&issue=$newId" : '?page=dashboard&error=duplicate'));
        exit;
    }

    if ($action === 'delete') {
        $id = $_GET['issue'] ?? '';
        $ok = preg_match('/^\d{4}-\d{2}$/', $id) && delete_issue($id);
        header('Location: ?page=dashboard' . ($ok ? '&deleted=' . urlencode($id) : '&error=delete'));
        exit;
    }

    if ($action === 'save_settings') {
        save_settings(sanitize_settings($_POST));
        $heroError = handle_hero_upload($_FILES['hero'] ?? []);
        header('Location: ?page=settings&saved=1'
            . ($heroError !== null ? '&hero_error=' . urlencode($heroError) : ''));
        exit;
    }

    http_response_code(400);
    exit('Unknown action');
}

/* ---------- Views ---------- */
$page = $_GET['page'] ?? '';

if ($page === 'dashboard') {
    include __DIR__ . '/templates/dashboard.php';
    exit;
}

if ($page === 'settings') {
    include __DIR__ . '/templates/settings.php';
    exit;
}

if ($page === 'edit') {
    $issueId = $_GET['issue'] ?? '';
    $issue   = load_issue($issueId);
    if ($issue) {
        include __DIR__ . '/templates/editor.php';
        exit;
    }
    header('Location: ?page=dashboard');
    exit;
}

// Newsletter view (default): a specific issue, or the latest.
$issueId = $_GET['issue'] ?? ($issues[0] ?? null);
$issue   = $issueId ? load_issue($issueId) : null;

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
  .go { margin-top:22px; font-size:13px; }
  .go a { color:var(--blue); font-weight:600; }
</style>
</head>
<body>
  <div class="card">
    <img class="anchor" src="assets/anchor-logo.svg" alt="">
    <h1>TDA <em>Currents</em></h1>
    <div class="tagline"><?= e($settings['tagline'] ?? 'Keeping our community connected on the water') ?></div>
    <div class="soon">Coming Soon</div>
    <div class="go"><a href="?page=dashboard">Open the editor</a></div>
  </div>
</body>
</html>

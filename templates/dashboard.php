<?php
/** Issue list + duplicate-last-month. Expects $issues, $settings. */
$adminTitle = 'Issues';
include __DIR__ . '/_admin_head.php';
$latest = $issues[0] ?? null;
?>
<main class="admin-main">
  <h1 class="admin-h1">Issues</h1>

  <?php if (($_GET['error'] ?? '') === 'duplicate'): ?>
  <div class="notice notice-error">Could not duplicate — the next month's issue may already exist.</div>
  <?php elseif (($_GET['error'] ?? '') === 'save'): ?>
  <div class="notice notice-error">Could not save the issue. Check that data/ is writable.</div>
  <?php endif; ?>

  <?php if ($latest): ?>
  <form method="post" action="?action=duplicate&amp;from=<?= e($latest) ?>" class="dup-form">
    <button type="submit" class="btn btn-primary">Duplicate <?= e(date_label_for($latest)) ?> → <?= e(date_label_for(next_issue_id($latest))) ?></button>
    <span class="dup-hint">Copies everything — text, icons, and images — into a new issue you then edit.</span>
  </form>
  <?php endif; ?>

  <?php if (!$issues): ?>
  <p class="admin-empty">No issues yet. Seed one JSON file into <code>data/</code> to get started.</p>
  <?php else: ?>
  <table class="issue-table">
    <thead><tr><th>Issue</th><th>Month</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($issues as $id): $iss = load_issue($id); ?>
      <tr>
        <td class="issue-no-cell">No. <?= e((string)($iss['issue']['number'] ?? '?')) ?></td>
        <td class="issue-month-cell"><?= e($iss['issue']['date_label'] ?? $id) ?></td>
        <td class="issue-actions">
          <a class="btn" href="?page=edit&amp;issue=<?= e($id) ?>">Edit</a>
          <a class="btn" href="?issue=<?= e($id) ?>" target="_blank">View / print</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>
</body>
</html>

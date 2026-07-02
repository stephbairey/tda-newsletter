<?php
/** Constants editor (section 9): rarely touched. Expects $settings. */
$adminTitle = 'Settings';
include __DIR__ . '/_admin_head.php';
$s = $settings;
?>
<main class="admin-main">
  <h1 class="admin-h1">Settings</h1>
  <p class="admin-note">These change about once a year. They appear on every issue, so edit with care.</p>

  <?php if (!empty($_GET['saved'])): ?>
  <div class="notice notice-ok">Settings saved.</div>
  <?php endif; ?>

  <form method="post" action="?action=save_settings" class="settings-form">
    <div class="field">
      <label>Tagline (masthead)</label>
      <input type="text" name="tagline" value="<?= e($s['tagline'] ?? '') ?>">
    </div>
    <div class="field">
      <label>Site URL (footer)</label>
      <input type="text" name="site_url" value="<?= e($s['site_url'] ?? '') ?>">
    </div>

    <fieldset>
      <legend>Committee emails (footer, top to bottom)</legend>
      <?php for ($i = 0; $i < 5; $i++): ?>
      <div class="field">
        <input type="text" name="committee_emails[]" value="<?= e($s['committee_emails'][$i] ?? '') ?>">
      </div>
      <?php endfor; ?>
    </fieldset>

    <fieldset>
      <legend>Footer text</legend>
      <div class="field">
        <label>Title</label>
        <input type="text" name="stay_informed_title" value="<?= e($s['stay_informed_title'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Line below the title</label>
        <input type="text" name="stay_informed_text" value="<?= e($s['stay_informed_text'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Colophon (bottom line: name • address • phone)</label>
        <input type="text" name="colophon" value="<?= e($s['colophon'] ?? '') ?>">
      </div>
    </fieldset>

    <fieldset>
      <legend>Submit band (page 2)</legend>
      <div class="field">
        <label>Text</label>
        <input type="text" name="submit_text" value="<?= e($s['submit_text'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Email (shown in blue)</label>
        <input type="text" name="submit_email" value="<?= e($s['submit_email'] ?? '') ?>">
      </div>
    </fieldset>

    <fieldset>
      <legend>House ad preset ("Your ad here" months)</legend>
      <div class="field">
        <label>Headline</label>
        <input type="text" name="house_ad[name]" value="<?= e($s['house_ad']['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Line below</label>
        <input type="text" name="house_ad[tagline]" value="<?= e($s['house_ad']['tagline'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Contact line</label>
        <input type="text" name="house_ad[url]" value="<?= e($s['house_ad']['url'] ?? '') ?>">
      </div>
    </fieldset>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save settings</button>
    </div>
  </form>
</main>
</body>
</html>

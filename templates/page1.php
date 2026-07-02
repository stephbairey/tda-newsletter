<?php
/** Page 1: masthead, hero, Spotlight | Committee Highlights + Calendar,
 *  Friendly Reminder, footer. Expects $issue, $settings. */
$sp  = $issue['spotlight'] ?? [];
$ch  = $issue['committee_highlights'] ?? [];
$cal = $issue['calendar'] ?? [];
$fr  = $issue['friendly_reminder'] ?? [];
$img = $sp['image'] ?? [];
?>
<div class="sheet page-1">

  <div class="masthead">
    <div class="masthead-brand">
      <img class="masthead-anchor" src="assets/anchor-logo.svg" alt="TDA anchor logo">
      <div>
        <div class="wordmark"><span class="wordmark-tda">TDA</span><span class="wordmark-currents"> Currents</span></div>
        <div class="tagline"><?= e($settings['tagline'] ?? '') ?></div>
      </div>
    </div>
    <div class="masthead-issue">
      <div class="issue-date"><?= e($issue['issue']['date_label'] ?? '') ?></div>
      <div class="issue-no">Issue No. <?= e((string)($issue['issue']['number'] ?? '')) ?></div>
    </div>
  </div>
  <?= wave_rule('p1') ?>

  <div class="hero">
    <img class="hero-img" src="<?= e(hero_src()) ?>" alt="Tomahawk Island moorage with Mt. Hood">
  </div>

  <div class="p1-body">

    <div class="p1-left">
      <?= section_header('Spotlight', $sp['icon'] ?? null) ?>
      <h2 class="spotlight-headline"><?= e($sp['headline'] ?? '') ?></h2>
      <div class="spotlight-body">
        <?php if (!empty($img['enabled']) && !empty($img['src'])): ?>
        <div class="spotlight-photo treatment-<?= e($img['treatment'] ?? 'portrait-float') ?>">
          <img src="uploads/<?= e($img['src']) ?>" alt="<?= e($img['caption'] ?? '') ?>"
               style="object-position: <?= e($img['object_position'] ?? 'center center') ?>;">
          <?php if (!empty($img['caption'])): ?>
          <div class="spotlight-caption"><?= e($img['caption']) ?></div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <?= rich_paras($sp['body'] ?? '') ?>
      </div>
    </div>

    <div class="p1-right">
      <?= section_header('Committee Highlights', $ch['icon'] ?? null) ?>
      <div class="highlights">
        <?php foreach (array_slice($ch['items'] ?? [], 0, 2) as $i => $item): ?>
        <?php if ($i > 0): ?><div class="item-divider"></div><?php endif; ?>
        <div class="icon-item">
          <?= tda_icon($item['icon'] ?? null, 16, 'item-icon') ?>
          <?php // Strip the joint here too, so issues saved before the rule render clean.
            $chLead = preg_replace('/[\s\x{00B7}]+$/u', '', trim($item['lead'] ?? ''));
            $chText = preg_replace('/^[\s\x{00B7}]+/u', '', trim(rich($item['text'] ?? ''))); ?>
          <p><strong><?= e($chLead) ?></strong><?= $chLead !== '' && $chText !== '' ? ' · ' : ' ' ?><?= $chText ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <?= section_header('On the Calendar', $cal['icon'] ?? null, 'sec-head-calendar') ?>
      <?php foreach (array_slice($cal['events'] ?? [], 0, 2) as $i => $ev): ?>
      <?php if ($i > 0): ?><div class="item-divider"></div><?php endif; ?>
      <div class="cal-event">
        <div class="cal-chip">
          <div class="cal-month"><?= e($ev['month'] ?? '') ?></div>
          <div class="cal-day"><?= e($ev['day'] ?? '') ?></div>
        </div>
        <p class="cal-details"><strong><?= e($ev['title'] ?? '') ?></strong><br>
          <?= e($ev['when_where'] ?? '') ?><br>
          <?= e($ev['note'] ?? '') ?><br>
          <span class="muted"><?= e($ev['muted_note'] ?? '') ?></span></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="reminder">
    <?= section_header('Friendly Reminder', $fr['icon'] ?? null, 'sec-head-reminder') ?>
    <p class="reminder-text"><strong class="reminder-lead"><?= e($fr['lead'] ?? '') ?></strong> <?= rich($fr['text'] ?? '') ?></p>
  </div>

  <?= page_footer($settings) ?>
</div>

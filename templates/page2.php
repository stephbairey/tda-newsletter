<?php
/** Page 2: running head, flex slot (Q&A | editorial) + All Hands, Shout-Outs,
 *  Dock Talk + sponsor slot (ad | trivia), submit band, footer.
 *  Expects $issue, $settings. */
$flex    = $issue['flex'] ?? [];
$ah      = $issue['all_hands'] ?? [];
$so      = $issue['shout_outs'] ?? [];
$dt      = $issue['dock_talk'] ?? [];
$sponsor = $issue['sponsor'] ?? [];

$flexMode    = ($flex['mode'] ?? 'qa') === 'editorial' ? 'editorial' : 'qa';
$sponsorMode = ($sponsor['mode'] ?? 'ad') === 'trivia' ? 'trivia' : 'ad';
$ad = $sponsor['ad'] ?? [];
if ($sponsorMode === 'ad' && ($ad['sub_mode'] ?? 'sponsor') === 'house') {
    $ad = array_merge($ad, $settings['house_ad'] ?? []);
}
$dateLine = ucwords(strtolower($issue['issue']['date_label'] ?? ''));
?>
<div class="sheet page-2">

  <div class="running-head">
    <div class="running-brand">
      <img class="running-anchor" src="assets/anchor-logo.svg" alt="TDA anchor logo">
      <div class="wordmark-small"><span class="wordmark-tda">TDA</span><span class="wordmark-currents"> Currents</span></div>
    </div>
    <div class="running-issue"><?= e($dateLine) ?> &nbsp;&middot;&nbsp; Issue No. <?= e((string)($issue['issue']['number'] ?? '')) ?></div>
  </div>
  <?= wave_rule('p2') ?>

  <div class="p2-grid">

    <div class="p2-row">
      <div class="p2-col">
        <?php if ($flexMode === 'qa'): $qa = $flex['qa'] ?? []; ?>
        <?= section_header('Current Questions', $qa['icon'] ?? null, 'sec-head-p2') ?>
        <div class="qa-block qa-question">
          <span class="qa-initial qa-q">Q</span>
          <div>
            <p class="qa-text"><?= e($qa['question'] ?? '') ?></p>
            <?php if (attrib($qa['question_by'] ?? '') !== ''): ?>
            <p class="qa-attrib">&mdash; <?= e(attrib($qa['question_by'])) ?></p>
            <?php endif; ?>
          </div>
        </div>
        <div class="qa-block">
          <span class="qa-initial qa-a">A</span>
          <div>
            <p class="qa-text"><?= e($qa['answer'] ?? '') ?></p>
            <?php if (attrib($qa['answer_by'] ?? '') !== ''): ?>
            <p class="qa-attrib">&mdash; <?= e(attrib($qa['answer_by'])) ?></p>
            <?php endif; ?>
          </div>
        </div>
        <?php else: $ed = $flex['editorial'] ?? []; $edImg = $ed['image'] ?? []; ?>
        <?= section_header('Why It Matters', $ed['icon'] ?? null, 'sec-head-p2') ?>
        <?php if (!empty($ed['headline'])): ?>
        <h3 class="editorial-headline"><?= e($ed['headline']) ?></h3>
        <?php endif; ?>
        <div class="editorial-body">
          <?php // Photo lives inside the body (like the Spotlight) so the
                // portrait float actually wraps the text around it. ?>
          <?php if (!empty($edImg['enabled']) && !empty($edImg['src'])): ?>
          <div class="editorial-photo treatment-<?= e($edImg['treatment'] ?? 'landscape-banner') ?>">
            <img src="uploads/<?= e($edImg['src']) ?>" alt="<?= e($edImg['caption'] ?? '') ?>"
                 style="object-position: <?= e($edImg['object_position'] ?? 'center center') ?>;">
            <?php if (!empty($edImg['caption'])): ?>
            <div class="spotlight-caption"><?= e($edImg['caption']) ?></div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          <?= rich_paras($ed['body'] ?? '') ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="col-divider"></div>

      <div class="p2-col">
        <?= section_header('All Hands on Deck', $ah['icon'] ?? null, 'sec-head-p2') ?>
        <p class="ah-intro"><?= rich($ah['intro'] ?? '') ?></p>
        <?php foreach (array_slice($ah['items'] ?? [], 0, 3) as $i => $item): ?>
        <?php if ($i > 0): ?><div class="item-divider item-divider-ah"></div><?php endif; ?>
        <div class="icon-item">
          <?= tda_icon($item['icon'] ?? null, 18, 'item-icon') ?>
          <p class="ah-item-text"><strong><?= e($item['lead'] ?? '') ?></strong> <?= rich($item['text'] ?? '') ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="shoutouts">
      <?= section_header('Shout‑Outs', $so['icon'] ?? null, 'sec-head-p2') ?>
      <div class="icon-item">
        <?= tda_icon($so['item_icon'] ?? null, 18, 'item-icon') ?>
        <p class="shoutouts-text"><?= rich($so['text'] ?? '') ?></p>
      </div>
    </div>

    <div class="p2-row">
      <div class="p2-col">
        <?= section_header('Dock Talk', $dt['icon'] ?? null, 'sec-head-p2') ?>
        <div class="dock-eyebrow"><?= e($dt['eyebrow'] ?? '') ?></div>
        <div class="icon-item">
          <?= tda_icon($dt['item_icon'] ?? null, 18, 'item-icon') ?>
          <p class="dock-text"><?= rich($dt['text'] ?? '') ?></p>
        </div>
      </div>

      <div class="col-divider"></div>

      <div class="p2-col">
        <?php if ($sponsorMode === 'ad'): ?>
        <?= section_header('Community Sponsor', $sponsor['icon'] ?? null, 'sec-head-p2') ?>
        <div class="sponsor-card">
          <div class="sponsor-name"><?= e($ad['name'] ?? '') ?></div>
          <div class="sponsor-tagline"><?= e($ad['tagline'] ?? '') ?></div>
          <div class="sponsor-url"><?= e($ad['url'] ?? '') ?></div>
        </div>
        <?php else: $tr = $sponsor['trivia'] ?? []; ?>
        <?= section_header('Dock Trivia', $sponsor['icon'] ?? null, 'sec-head-p2') ?>
        <div class="trivia">
          <div class="trivia-headline"><?= e($tr['headline'] ?? '') ?></div>
          <p class="trivia-text"><?= e($tr['text'] ?? '') ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <div class="submit-band">
    <p><?= e($settings['submit_text'] ?? '') ?> <span class="submit-email"><?= e($settings['submit_email'] ?? '') ?></span></p>
  </div>

  <?= page_footer($settings) ?>
</div>

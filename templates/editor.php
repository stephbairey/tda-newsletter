<?php
/**
 * The issue editor. Expects $issue, $issueId, $settings.
 *
 * Rich fields are contenteditable divs with a three-button toolbar (bold,
 * italic, clear — section 12); js/editor.js serializes them into the hidden
 * textarea on submit, and the server re-sanitizes on save regardless.
 */
$adminTitle = 'Edit ' . ($issue['issue']['date_label'] ?? $issueId);

/**
 * Rich text widget. $multi allows paragraphs (Enter); single-line fields don't.
 * $opts feeds the character-limit engine in editor.js:
 *   'max'    => int     soft cap on visible characters (counter warns, never blocks)
 *   'group'  => string  shares a combined cap with other fields (see data-group-counter)
 *   'limits' => array   photo-dependent caps, keyed 'none' / treatment name
 */
function rich_field(string $name, string $value, bool $multi = false, array $opts = []): void {
    $display = $multi
        ? rich_paras($value)
        : rich($value);
    $attrs = '';
    if (isset($opts['max']))    $attrs .= ' data-max="' . (int)$opts['max'] . '"';
    if (isset($opts['group']))  $attrs .= ' data-group="' . e($opts['group']) . '"';
    if (isset($opts['limits'])) $attrs .= " data-limits='" . json_encode($opts['limits']) . "'";
    ?>
    <div class="rich <?= $multi ? 'rich-multi' : 'rich-single' ?>"<?= $attrs ?>>
      <div class="rich-toolbar">
        <button type="button" class="rich-btn" data-cmd="bold" title="Bold"><strong>B</strong></button>
        <button type="button" class="rich-btn" data-cmd="italic" title="Italic"><em>I</em></button>
        <button type="button" class="rich-btn" data-cmd="clear" title="Remove formatting">Tx</button>
      </div>
      <div class="rich-area" contenteditable="true" data-multi="<?= $multi ? '1' : '0' ?>"><?= $display ?></div>
      <textarea class="rich-store" name="<?= e($name) ?>" hidden><?= e($value) ?></textarea>
    </div>
    <?php
}

/** Icon picker widget: a button showing the current icon + a hidden input. */
function icon_field(string $name, string $value, string $label = 'Icon'): void {
    ?>
    <div class="icon-field">
      <span class="icon-field-label"><?= e($label) ?></span>
      <button type="button" class="icon-btn" data-icon="<?= e($value) ?>" title="Change icon">
        <?= tda_icon($value, 20) ?>
      </button>
      <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
    </div>
    <?php
}

/** Photo widget: upload, treatment, drag-to-reposition, caption, on/off. */
function image_field(string $prefix, array $img, string $defaultTreatment): void {
    $treatment = $img['treatment'] ?? $defaultTreatment;
    $src = $img['src'] ?? '';
    $pos = $img['object_position'] ?? '50% 50%';
    ?>
    <fieldset class="photo-widget">
      <legend>Photo</legend>
      <label class="photo-toggle">
        <input type="checkbox" name="<?= e($prefix) ?>[enabled]" value="1" <?= !empty($img['enabled']) ? 'checked' : '' ?>>
        Show a photo in this section
      </label>
      <div class="photo-controls">
        <div class="photo-frame-wrap">
          <div class="photo-frame treatment-<?= e($treatment) ?>" data-empty="<?= $src ? '0' : '1' ?>">
            <img src="<?= $src ? 'uploads/' . e($src) : '' ?>" alt="" style="object-position: <?= e($pos) ?>;" draggable="false">
            <div class="photo-frame-hint">Drag the photo to frame it</div>
          </div>
        </div>
        <div class="photo-fields">
          <div class="field">
            <label>Shape</label>
            <select name="<?= e($prefix) ?>[treatment]" class="photo-treatment">
              <option value="portrait-float" <?= $treatment === 'portrait-float' ? 'selected' : '' ?>>Portrait (floats left of the text)</option>
              <option value="landscape-banner" <?= $treatment === 'landscape-banner' ? 'selected' : '' ?>>Banner (full width, above the text)</option>
            </select>
          </div>
          <div class="field">
            <label>Caption (optional)</label>
            <input type="text" name="<?= e($prefix) ?>[caption]" value="<?= e($img['caption'] ?? '') ?>" data-count="1" data-max="50">
          </div>
          <div class="field">
            <label>Upload a new photo (JPG, PNG, or WebP). Best size: 150x220 px, 300 dpi.</label>
            <input type="file" class="photo-upload" accept="image/jpeg,image/png,image/webp">
            <span class="photo-upload-status"></span>
          </div>
          <input type="hidden" class="photo-src" name="<?= e($prefix) ?>[src]" value="<?= e($src) ?>">
          <input type="hidden" class="photo-pos" name="<?= e($prefix) ?>[object_position]" value="<?= e($pos) ?>">
        </div>
      </div>
    </fieldset>
    <?php
}

/** One lead-in + rich text item row (Committee Highlights, All Hands).
 *  $opts: 'text_max' caps the text field; 'group' pools lead + text into a
 *  combined cap (used by All Hands, whose item count varies). */
function lead_item(string $prefix, array $item, string $leadLabel, array $opts = []): void {
    $group = isset($opts['group']) ? ' data-group="' . e($opts['group']) . '"' : '';
    ?>
    <div class="lead-item">
      <?php icon_field("{$prefix}[icon]", $item['icon'] ?? 'anchor'); ?>
      <div class="lead-item-fields">
        <div class="field">
          <label><?= e($leadLabel) ?> <span class="hint">(bolded automatically)</span></label>
          <input type="text" name="<?= e($prefix) ?>[lead]" value="<?= e($item['lead'] ?? '') ?>"<?= $group ?>>
        </div>
        <div class="field">
          <label>Text</label>
          <?php rich_field("{$prefix}[text]", $item['text'] ?? '', false,
              array_filter(['max' => $opts['text_max'] ?? null, 'group' => $opts['group'] ?? null])); ?>
        </div>
      </div>
    </div>
    <?php
}

include __DIR__ . '/_admin_head.php';

$sp = $issue['spotlight'];
$flex = $issue['flex'];
$sponsor = $issue['sponsor'];
$houseAd = $settings['house_ad'] ?? [];
?>
<main class="admin-main admin-main-editor">

  <div class="editor-head">
    <h1 class="admin-h1">Edit <?= e($issue['issue']['date_label'] ?? $issueId) ?></h1>
    <div class="editor-head-actions">
      <a class="btn" href="?issue=<?= e($issueId) ?>" target="_blank">Preview / print</a>
      <button type="submit" form="issue-form" class="btn btn-primary">Save issue</button>
    </div>
  </div>

  <?php if (!empty($_GET['saved'])): ?>
  <div class="notice notice-ok">Saved.</div>
  <?php endif; ?>

  <div id="fit-report" class="fit-report" hidden></div>

  <form id="issue-form" method="post" action="?action=save&amp;issue=<?= e($issueId) ?>"
        data-issue="<?= e($issueId) ?>">

    <section class="ed-section">
      <h2 class="ed-title">Masthead</h2>
      <div class="field-row">
        <div class="field">
          <label>Issue date (e.g. JULY 2026) <span class="hint">(shown in caps either way)</span></label>
          <input type="text" name="issue[date_label]" id="date-label" value="<?= e($issue['issue']['date_label'] ?? '') ?>">
          <span class="field-warn" id="month-warn" hidden></span>
        </div>
        <div class="field field-narrow">
          <label>Issue number</label>
          <input type="number" name="issue[number]" min="1" value="<?= e((string)($issue['issue']['number'] ?? 1)) ?>">
        </div>
      </div>
    </section>

    <h2 class="ed-page-break">Page 1</h2>

    <section class="ed-section">
      <div class="ed-title-row">
        <h2 class="ed-title">Spotlight</h2>
        <?php icon_field('spotlight[icon]', $sp['icon'] ?? 'anchor', 'Section icon'); ?>
      </div>
      <div class="field">
        <label>Headline <span class="hint">(one printed line: 10–30 characters)</span></label>
        <input type="text" name="spotlight[headline]" value="<?= e($sp['headline'] ?? '') ?>" data-count="1" data-max="30" data-min="10">
      </div>
      <?php image_field('spotlight[image]', $sp['image'] ?? [], 'portrait-float'); ?>
      <div class="field">
        <label>Story <span class="hint">(Enter starts a new paragraph)</span></label>
        <?php rich_field('spotlight[body]', $sp['body'] ?? '', true,
            ['limits' => ['none' => 1200, 'portrait-float' => 850, 'landscape-banner' => 650]]); ?>
      </div>
    </section>

    <section class="ed-section">
      <div class="ed-title-row">
        <h2 class="ed-title">Committee Highlights</h2>
        <?php icon_field('committee_highlights[icon]', $issue['committee_highlights']['icon'] ?? 'anchor', 'Section icon'); ?>
      </div>
      <p class="ed-note">Four slots. Leave one blank and it simply won&rsquo;t print.</p>
      <?php foreach (array_pad(array_slice($issue['committee_highlights']['items'], 0, 4), 4, []) as $i => $item):
          lead_item("committee_highlights[items][$i]", $item, 'Committee name', ['text_max' => 150]);
      endforeach; ?>
    </section>

    <section class="ed-section">
      <div class="ed-title-row">
        <h2 class="ed-title">On the Calendar</h2>
        <?php icon_field('calendar[icon]', $issue['calendar']['icon'] ?? 'calendar', 'Section icon'); ?>
      </div>
      <p class="ed-note">Four slots; leave one blank and it simply won&rsquo;t print. Use a 3-letter abbreviation for the month (&lsquo;AUG&rsquo;) and a numerical date (&lsquo;4&rsquo;).</p>
      <?php foreach (array_pad(array_slice($issue['calendar']['events'], 0, 4), 4, []) as $i => $ev):
          // Stored as one "time · place" line; split for editing, rejoined on save.
          $ww = explode(' · ', $ev['when_where'] ?? '', 2);
      ?>
      <div class="cal-item">
        <div class="field field-narrow">
          <label>Month</label>
          <input type="text" name="calendar[events][<?= $i ?>][month]" maxlength="4" value="<?= e($ev['month'] ?? '') ?>">
        </div>
        <div class="field field-narrow">
          <label>Day</label>
          <input type="text" name="calendar[events][<?= $i ?>][day]" maxlength="2" value="<?= e($ev['day'] ?? '') ?>">
        </div>
        <div class="cal-item-fields">
          <div class="field">
            <label>Event title</label>
            <input type="text" name="calendar[events][<?= $i ?>][title]" value="<?= e($ev['title'] ?? '') ?>" data-count="1" data-max="30">
          </div>
          <div class="field-row">
            <div class="field">
              <label>Time</label>
              <input type="text" name="calendar[events][<?= $i ?>][when]" value="<?= e($ww[0] ?? '') ?>">
            </div>
            <div class="field">
              <label>Place</label>
              <input type="text" name="calendar[events][<?= $i ?>][where]" value="<?= e($ww[1] ?? '') ?>" data-count="1" data-max="25">
            </div>
          </div>
          <div class="field-row">
            <div class="field">
              <label>Note</label>
              <input type="text" name="calendar[events][<?= $i ?>][note]" value="<?= e($ev['note'] ?? '') ?>" data-count="1" data-max="30">
            </div>
            <div class="field">
              <label>Call to action</label>
              <input type="text" name="calendar[events][<?= $i ?>][muted_note]" value="<?= e($ev['muted_note'] ?? '') ?>" data-count="1" data-max="30">
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </section>

    <section class="ed-section">
      <div class="ed-title-row">
        <h2 class="ed-title">Friendly Reminder</h2>
        <?php icon_field('friendly_reminder[icon]', $issue['friendly_reminder']['icon'] ?? 'anchor', 'Section icon'); ?>
      </div>
      <div class="field">
        <label>Lead-in <span class="hint">(bolded automatically)</span></label>
        <input type="text" name="friendly_reminder[lead]" value="<?= e($issue['friendly_reminder']['lead'] ?? '') ?>" data-group="fr">
      </div>
      <div class="field">
        <label>Text</label>
        <?php rich_field('friendly_reminder[text]', $issue['friendly_reminder']['text'] ?? '', false, ['group' => 'fr']); ?>
        <div class="char-count" data-group-counter="fr" data-group-max="125" data-group-label="lead-in + text"></div>
      </div>
    </section>

    <h2 class="ed-page-break">Page 2</h2>

    <section class="ed-section" id="flex-section">
      <div class="ed-title-row">
        <h2 class="ed-title">Flex slot</h2>
        <div class="mode-toggle">
          <label><input type="radio" name="flex[mode]" value="qa" <?= ($flex['mode'] ?? 'qa') !== 'editorial' ? 'checked' : '' ?>> Current Questions (Q&amp;A)</label>
          <label><input type="radio" name="flex[mode]" value="editorial" <?= ($flex['mode'] ?? '') === 'editorial' ? 'checked' : '' ?>> Why It Matters (editorial)</label>
        </div>
      </div>

      <div class="mode-panel" data-mode="qa">
        <?php icon_field('flex[qa][icon]', $flex['qa']['icon'] ?? 'envelope', 'Section icon'); ?>
        <div class="field">
          <label>Question</label>
          <textarea name="flex[qa][question]" rows="3" data-count="1" data-max="300"><?= e($flex['qa']['question'] ?? '') ?></textarea>
        </div>
        <div class="field">
          <label>Asked by <span class="hint">(e.g. Tom Gentry, Slip 570 — the dash is added for you)</span></label>
          <input type="text" name="flex[qa][question_by]" value="<?= e($flex['qa']['question_by'] ?? '') ?>" data-count="1" data-max="30">
        </div>
        <div class="field">
          <label>Answer</label>
          <textarea name="flex[qa][answer]" rows="6" data-count="1" data-max="490"><?= e($flex['qa']['answer'] ?? '') ?></textarea>
        </div>
        <div class="field">
          <label>Answered by</label>
          <input type="text" name="flex[qa][answer_by]" value="<?= e($flex['qa']['answer_by'] ?? '') ?>" data-count="1" data-max="30">
        </div>
      </div>

      <div class="mode-panel" data-mode="editorial">
        <?php icon_field('flex[editorial][icon]', $flex['editorial']['icon'] ?? 'house-on-water', 'Section icon'); ?>
        <div class="field">
          <label>Headline <span class="hint">(one printed line: 10–30 characters)</span></label>
          <input type="text" name="flex[editorial][headline]" value="<?= e($flex['editorial']['headline'] ?? '') ?>" data-count="1" data-max="30" data-min="10">
        </div>
        <?php image_field('flex[editorial][image]', $flex['editorial']['image'] ?? [], 'landscape-banner'); ?>
        <div class="field">
          <label>Body <span class="hint">(Enter starts a new paragraph)</span></label>
          <?php rich_field('flex[editorial][body]', $flex['editorial']['body'] ?? '', true,
              ['limits' => ['none' => 885, 'portrait-float' => 850, 'landscape-banner' => 500]]); ?>
        </div>
      </div>
    </section>

    <section class="ed-section" id="all-hands-section">
      <div class="ed-title-row">
        <h2 class="ed-title">All Hands on Deck</h2>
        <?php icon_field('all_hands[icon]', $issue['all_hands']['icon'] ?? 'waving-hand', 'Section icon'); ?>
      </div>
      <div class="field">
        <label>Intro line</label>
        <?php rich_field('all_hands[intro]', $issue['all_hands']['intro'] ?? '', false, ['group' => 'ah']); ?>
      </div>
      <p class="ed-note">Between 1 and 3 items. The count below combines the intro and every item.</p>
      <div id="all-hands-items">
        <?php foreach (array_slice($issue['all_hands']['items'], 0, 3) as $i => $item): ?>
        <div class="ah-item">
          <?php lead_item("all_hands[items][$i]", $item, 'Lead-in', ['group' => 'ah']); ?>
          <button type="button" class="btn btn-small ah-remove">Remove</button>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-small" id="ah-add">Add an item</button>
      <div class="char-count" data-group-counter="ah" data-group-max="650" data-group-label="intro + all items"></div>
    </section>

    <section class="ed-section">
      <div class="ed-title-row">
        <h2 class="ed-title">Shout-Outs</h2>
        <?php icon_field('shout_outs[icon]', $issue['shout_outs']['icon'] ?? 'flower', 'Section icon'); ?>
      </div>
      <?php icon_field('shout_outs[item_icon]', $issue['shout_outs']['item_icon'] ?? 'anchor', 'Item icon'); ?>
      <div class="field">
        <label>Text <span class="hint">(bold the names)</span></label>
        <?php rich_field('shout_outs[text]', $issue['shout_outs']['text'] ?? '', false, ['max' => 230]); ?>
      </div>
    </section>

    <section class="ed-section">
      <div class="ed-title-row">
        <h2 class="ed-title">Dock Talk</h2>
        <?php icon_field('dock_talk[icon]', $issue['dock_talk']['icon'] ?? 'lightning', 'Section icon'); ?>
      </div>
      <div class="field-row">
        <div class="field">
          <label>Eyebrow <span class="hint">(e.g. DID YOU KNOW?)</span></label>
          <input type="text" name="dock_talk[eyebrow]" value="<?= e($issue['dock_talk']['eyebrow'] ?? '') ?>">
        </div>
        <?php icon_field('dock_talk[item_icon]', $issue['dock_talk']['item_icon'] ?? 'anchor', 'Item icon'); ?>
      </div>
      <div class="field">
        <label>Tip</label>
        <?php rich_field('dock_talk[text]', $issue['dock_talk']['text'] ?? '', false, ['max' => 200]); ?>
      </div>
    </section>

    <section class="ed-section" id="sponsor-section">
      <div class="ed-title-row">
        <h2 class="ed-title">Sponsor slot</h2>
        <div class="mode-toggle">
          <label><input type="radio" name="sponsor[mode]" value="ad" <?= ($sponsor['mode'] ?? 'ad') !== 'trivia' ? 'checked' : '' ?>> Ad card</label>
          <label><input type="radio" name="sponsor[mode]" value="trivia" <?= ($sponsor['mode'] ?? '') === 'trivia' ? 'checked' : '' ?>> Trivia (no card)</label>
        </div>
      </div>
      <?php icon_field('sponsor[icon]', $sponsor['icon'] ?? 'megaphone', 'Section icon'); ?>

      <div class="mode-panel" data-mode="ad">
        <div class="mode-toggle mode-toggle-sub">
          <label><input type="radio" name="sponsor[ad][sub_mode]" value="sponsor" <?= ($sponsor['ad']['sub_mode'] ?? 'sponsor') !== 'house' ? 'checked' : '' ?>> Real sponsor</label>
          <label><input type="radio" name="sponsor[ad][sub_mode]" value="house" <?= ($sponsor['ad']['sub_mode'] ?? '') === 'house' ? 'checked' : '' ?>> House ad ("Your ad here")</label>
        </div>
        <div class="sponsor-fields" data-sub="sponsor">
          <div class="field">
            <label>Sponsor name</label>
            <input type="text" name="sponsor[ad][name]" value="<?= e($sponsor['ad']['name'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Tagline</label>
            <input type="text" name="sponsor[ad][tagline]" value="<?= e($sponsor['ad']['tagline'] ?? '') ?>">
          </div>
          <div class="field">
            <label>URL or Phone number</label>
            <input type="text" name="sponsor[ad][url]" value="<?= e($sponsor['ad']['url'] ?? '') ?>">
          </div>
        </div>
        <div class="sponsor-fields sponsor-house-preview" data-sub="house">
          <p class="ed-note">Uses the house preset from <a href="?page=settings">Settings</a>:</p>
          <div class="house-preview">
            <strong><?= e($houseAd['name'] ?? 'Your ad here') ?></strong><br>
            <?= e($houseAd['tagline'] ?? '') ?><br>
            <em><?= e($houseAd['url'] ?? '') ?></em>
          </div>
        </div>
      </div>

      <div class="mode-panel" data-mode="trivia">
        <div class="field">
          <label>Headline</label>
          <input type="text" name="sponsor[trivia][headline]" value="<?= e($sponsor['trivia']['headline'] ?? '') ?>">
        </div>
        <div class="field">
          <label>One short sentence</label>
          <input type="text" name="sponsor[trivia][text]" value="<?= e($sponsor['trivia']['text'] ?? '') ?>">
        </div>
      </div>
    </section>

    <div class="form-actions form-actions-sticky">
      <button type="submit" class="btn btn-primary">Save issue</button>
      <a class="btn" href="?issue=<?= e($issueId) ?>" target="_blank">Preview / print</a>
    </div>
  </form>

  <!-- Icon palette (one instance, moved next to whichever picker is open) -->
  <div id="icon-palette" hidden>
    <?php foreach (list_icons() as $iconId): ?>
    <button type="button" class="icon-choice" data-icon="<?= e($iconId) ?>" title="<?= e($iconId) ?>">
      <?= tda_icon($iconId, 22) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Offscreen print view used for the overflow check. Positioned offscreen
       rather than hidden: display:none would skip layout entirely and every
       measurement would come back 0 ("always fits"). -->
  <iframe id="fit-frame" src="?issue=<?= e($issueId) ?>&amp;measure=1" tabindex="-1" aria-hidden="true"></iframe>
</main>
<script src="<?= asset('js/editor.js') ?>"></script>
</body>
</html>

<?php
/**
 * Write-side of the flat-file store: sanitize + save issues, duplicate an
 * issue (real copies, section 13), settings save, image uploads.
 *
 * Everything posted by the browser is rebuilt field-by-field through this
 * whitelist. Rich fields go through rich() (inline strong/em/br only, section
 * 12); everything else is stored as plain text. Fixed counts are enforced
 * here, not trusted from the client.
 */

require_once __DIR__ . '/helpers.php';

/** Plain-text field: no tags at all, trimmed, normalized whitespace. */
function plain(?string $s): string {
    $s = strip_tags($s ?? '');
    $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
    return trim(preg_replace('/[ \t]+/', ' ', $s));
}

/** Rich single-paragraph field: inline marks only, no newlines. */
function rich_line(?string $s): string {
    return trim(rich(str_replace(["\r", "\n"], ' ', $s ?? '')));
}

/** Rich multi-paragraph field: paragraphs separated by blank lines. */
function rich_block(?string $s): string {
    $paras = preg_split('/\n\s*\n/', trim(str_replace("\r", '', $s ?? '')));
    $paras = array_filter(array_map(fn($p) => trim(rich($p)), $paras), fn($p) => $p !== '');
    return implode("\n\n", $paras);
}

/** Icon id: must exist in the set, else the anchor fallback. */
function icon_id(?string $s): string {
    $id = preg_replace('/[^a-z0-9-]/', '', strtolower($s ?? ''));
    return ($id !== '' && is_file(TDA_ICONS . "/$id.svg")) ? $id : 'anchor';
}

/** Uploaded-image filename: only files that actually exist in uploads/. */
function upload_ref(?string $s): string {
    $f = basename(trim($s ?? ''));
    return ($f !== '' && is_file(TDA_ROOT . "/uploads/$f")) ? $f : '';
}

function image_meta(array $in, string $defaultTreatment): array {
    $treatments = ['portrait-float', 'landscape-banner'];
    $pos = trim($in['object_position'] ?? '');
    if (!preg_match('/^\d{1,3}% \d{1,3}%$/', $pos)) $pos = '50% 50%';
    $src = upload_ref($in['src'] ?? '');
    return [
        'enabled'         => !empty($in['enabled']) && $src !== '',
        'src'             => $src,
        'caption'         => plain($in['caption'] ?? ''),
        'treatment'       => in_array($in['treatment'] ?? '', $treatments, true)
                             ? $in['treatment'] : $defaultTreatment,
        'object_position' => $pos,
    ];
}

/**
 * Rebuild a full issue from a POST payload. Unknown keys are dropped; counts
 * are clamped to the block taxonomy (section 10).
 */
function sanitize_issue(array $in): array {
    $items = function (array $list, int $min, int $max, callable $fn): array {
        $out = [];
        foreach (array_slice(array_values($list), 0, $max) as $item) {
            if (is_array($item)) $out[] = $fn($item);
        }
        while (count($out) < $min) $out[] = $fn([]);
        return $out;
    };
    $leadText = fn(array $i) => [
        'icon' => icon_id($i['icon'] ?? ''),
        'lead' => plain($i['lead'] ?? ''),
        'text' => rich_line($i['text'] ?? ''),
    ];

    return [
        'issue' => [
            'date_label' => plain($in['issue']['date_label'] ?? ''),
            'number'     => max(1, (int)($in['issue']['number'] ?? 1)),
        ],
        'spotlight' => [
            'icon'     => icon_id($in['spotlight']['icon'] ?? ''),
            'headline' => plain($in['spotlight']['headline'] ?? ''),
            'image'    => image_meta($in['spotlight']['image'] ?? [], 'portrait-float'),
            'body'     => rich_block($in['spotlight']['body'] ?? ''),
        ],
        'committee_highlights' => [
            'icon'  => icon_id($in['committee_highlights']['icon'] ?? ''),
            'items' => $items($in['committee_highlights']['items'] ?? [], 2, 2, $leadText),
        ],
        'calendar' => [
            'icon'   => icon_id($in['calendar']['icon'] ?? ''),
            'events' => $items($in['calendar']['events'] ?? [], 2, 2, fn(array $e) => [
                'month'      => strtoupper(substr(plain($e['month'] ?? ''), 0, 4)),
                'day'        => substr(plain($e['day'] ?? ''), 0, 2),
                'title'      => plain($e['title'] ?? ''),
                'when_where' => plain($e['when_where'] ?? ''),
                'note'       => plain($e['note'] ?? ''),
                'muted_note' => plain($e['muted_note'] ?? ''),
            ]),
        ],
        'friendly_reminder' => [
            'icon' => icon_id($in['friendly_reminder']['icon'] ?? ''),
            'lead' => plain($in['friendly_reminder']['lead'] ?? ''),
            'text' => rich_line($in['friendly_reminder']['text'] ?? ''),
        ],
        'flex' => [
            'mode' => ($in['flex']['mode'] ?? 'qa') === 'editorial' ? 'editorial' : 'qa',
            'qa' => [
                'icon'        => icon_id($in['flex']['qa']['icon'] ?? ''),
                'question'    => plain($in['flex']['qa']['question'] ?? ''),
                'question_by' => plain($in['flex']['qa']['question_by'] ?? ''),
                'answer'      => plain($in['flex']['qa']['answer'] ?? ''),
                'answer_by'   => plain($in['flex']['qa']['answer_by'] ?? ''),
            ],
            'editorial' => [
                'icon'     => icon_id($in['flex']['editorial']['icon'] ?? ''),
                'headline' => plain($in['flex']['editorial']['headline'] ?? ''),
                'image'    => image_meta($in['flex']['editorial']['image'] ?? [], 'landscape-banner'),
                'body'     => rich_block($in['flex']['editorial']['body'] ?? ''),
            ],
        ],
        'all_hands' => [
            'icon'  => icon_id($in['all_hands']['icon'] ?? ''),
            'intro' => rich_line($in['all_hands']['intro'] ?? ''),
            'items' => $items($in['all_hands']['items'] ?? [], 1, 3, $leadText),
        ],
        'shout_outs' => [
            'icon'      => icon_id($in['shout_outs']['icon'] ?? ''),
            'item_icon' => icon_id($in['shout_outs']['item_icon'] ?? ''),
            'text'      => rich_line($in['shout_outs']['text'] ?? ''),
        ],
        'dock_talk' => [
            'icon'      => icon_id($in['dock_talk']['icon'] ?? ''),
            'eyebrow'   => plain($in['dock_talk']['eyebrow'] ?? ''),
            'item_icon' => icon_id($in['dock_talk']['item_icon'] ?? ''),
            'text'      => rich_line($in['dock_talk']['text'] ?? ''),
        ],
        'sponsor' => [
            'mode' => ($in['sponsor']['mode'] ?? 'ad') === 'trivia' ? 'trivia' : 'ad',
            'icon' => icon_id($in['sponsor']['icon'] ?? ''),
            'ad' => [
                'sub_mode' => ($in['sponsor']['ad']['sub_mode'] ?? 'sponsor') === 'house' ? 'house' : 'sponsor',
                'name'     => plain($in['sponsor']['ad']['name'] ?? ''),
                'tagline'  => plain($in['sponsor']['ad']['tagline'] ?? ''),
                'url'      => plain($in['sponsor']['ad']['url'] ?? ''),
            ],
            'trivia' => [
                'headline' => plain($in['sponsor']['trivia']['headline'] ?? ''),
                'text'     => plain($in['sponsor']['trivia']['text'] ?? ''),
            ],
        ],
    ];
}

function save_issue(string $id, array $issue): bool {
    if (!preg_match('/^\d{4}-\d{2}$/', $id)) return false;
    $json = json_encode($issue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return file_put_contents(TDA_DATA . "/$id.json", $json . "\n", LOCK_EX) !== false;
}

function sanitize_settings(array $in): array {
    $emails = [];
    foreach (array_slice((array)($in['committee_emails'] ?? []), 0, 5) as $em) {
        $em = plain($em);
        if ($em !== '') $emails[] = $em;
    }
    return [
        'tagline'             => plain($in['tagline'] ?? ''),
        'site_url'            => plain($in['site_url'] ?? ''),
        'committee_emails'    => $emails,
        'stay_informed_title' => plain($in['stay_informed_title'] ?? ''),
        'stay_informed_text'  => plain($in['stay_informed_text'] ?? ''),
        'submit_text'         => plain($in['submit_text'] ?? ''),
        'submit_email'        => plain($in['submit_email'] ?? ''),
        'colophon'            => plain($in['colophon'] ?? ''),
        'house_ad' => [
            'name'    => plain($in['house_ad']['name'] ?? ''),
            'tagline' => plain($in['house_ad']['tagline'] ?? ''),
            'url'     => plain($in['house_ad']['url'] ?? ''),
        ],
    ];
}

function save_settings(array $settings): bool {
    $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return file_put_contents(TDA_DATA . '/settings.json', $json . "\n", LOCK_EX) !== false;
}

/** "AUGUST 2026" from "2026-08". */
function date_label_for(string $id): string {
    $d = DateTime::createFromFormat('Y-m-d', $id . '-01');
    return $d ? strtoupper($d->format('F Y')) : $id;
}

/** The month after an issue id: "2026-07" -> "2026-08". */
function next_issue_id(string $id): string {
    $d = DateTime::createFromFormat('Y-m-d', $id . '-01');
    return $d ? $d->modify('+1 month')->format('Y-m') : $id;
}

/**
 * Duplicate an issue (section 13): clone the JSON, bump date and number, and
 * copy every referenced image to a new file so the issues share no state.
 * Returns the new id, or null on failure.
 */
function duplicate_issue(string $fromId): ?string {
    $issue = load_issue($fromId);
    if (!$issue) return null;
    $newId = next_issue_id($fromId);
    if (load_issue($newId)) return null; // never overwrite an existing issue

    $copyImage = function (array $img) use ($newId): array {
        if (!empty($img['src']) && is_file(TDA_ROOT . '/uploads/' . $img['src'])) {
            // Strip any previous issue prefix so names don't accrete.
            $base = preg_replace('/^\d{4}-\d{2}-/', '', $img['src']);
            $new = $newId . '-' . $base;
            if (copy(TDA_ROOT . '/uploads/' . $img['src'], TDA_ROOT . '/uploads/' . $new)) {
                $img['src'] = $new;
            }
        }
        return $img;
    };

    $issue['issue']['date_label'] = date_label_for($newId);
    $issue['issue']['number'] = (int)($issue['issue']['number'] ?? 0) + 1;
    $issue['spotlight']['image'] = $copyImage($issue['spotlight']['image'] ?? []);
    if (!empty($issue['flex']['editorial']['image'])) {
        $issue['flex']['editorial']['image'] = $copyImage($issue['flex']['editorial']['image']);
    }
    return save_issue($newId, $issue) ? $newId : null;
}

/**
 * Store an uploaded photo in uploads/, named for the issue. Returns
 * ['ok' => true, 'src' => filename] or ['ok' => false, 'error' => message].
 */
function handle_upload(string $issueId, array $file): array {
    if (!preg_match('/^\d{4}-\d{2}$/', $issueId)) return ['ok' => false, 'error' => 'Bad issue id.'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed — try a smaller file (the server limit may be 2 MB).'];
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        return ['ok' => false, 'error' => 'Please upload a JPG, PNG, or WebP image.'];
    }
    $base = pathinfo($file['name'], PATHINFO_FILENAME);
    $base = preg_replace('/[^a-z0-9-]+/', '-', strtolower($base));
    $base = trim(substr($base, 0, 40), '-') ?: 'photo';
    $name = "$issueId-$base." . $types[$info[2]];
    $n = 1;
    while (is_file(TDA_ROOT . "/uploads/$name")) {
        $name = "$issueId-$base-" . (++$n) . '.' . $types[$info[2]];
    }
    if (!move_uploaded_file($file['tmp_name'], TDA_ROOT . "/uploads/$name")) {
        return ['ok' => false, 'error' => 'Could not write the file to uploads/.'];
    }
    return ['ok' => true, 'src' => $name];
}

<?php
/**
 * Shared helpers for TDA Currents templates.
 * Flat-file storage: data/settings.json (constants) + data/YYYY-MM.json (per issue).
 */

define('TDA_ROOT', dirname(__DIR__));
define('TDA_DATA', TDA_ROOT . '/data');
define('TDA_ICONS', TDA_ROOT . '/icons/svg');

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Asset URL with a cache-busting version (file mtime). Browsers cache CSS/JS
 * aggressively; without this, editors keep running last week's stylesheet
 * after a deploy and "fixed" bugs stay visibly broken for them.
 */
function asset(string $path): string {
    $f = TDA_ROOT . '/' . $path;
    return e($path . (is_file($f) ? '?v=' . filemtime($f) : ''));
}

function load_settings(): array {
    $file = TDA_DATA . '/settings.json';
    if (!is_file($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

/** Issue ids are YYYY-MM, newest first. */
function list_issues(): array {
    $ids = [];
    foreach (glob(TDA_DATA . '/*.json') as $f) {
        $id = basename($f, '.json');
        if (preg_match('/^\d{4}-\d{2}$/', $id)) $ids[] = $id;
    }
    rsort($ids);
    return $ids;
}

function load_issue(string $id): ?array {
    if (!preg_match('/^\d{4}-\d{2}$/', $id)) return null;
    $file = TDA_DATA . '/' . $id . '.json';
    if (!is_file($file)) return null;
    return json_decode(file_get_contents($file), true) ?: null;
}

/**
 * Whitelist inline marks only: <strong>, <em>, <br>. Everything else is
 * stripped. Applied on render (and on save, once the editor exists) so the
 * fixed print boxes can never be broken by block-level markup.
 */
function rich(?string $html): string {
    $html = strip_tags($html ?? '', '<strong><em><br>');
    // Normalize <br/> variants and drop any attributes smuggled onto allowed tags.
    $html = preg_replace('/<(strong|em)\b[^>]*>/i', '<$1>', $html);
    $html = preg_replace('/<br\b[^>]*>/i', '<br>', $html);
    return $html;
}

/**
 * Render-only autolink: wrap email addresses, http(s) URLs, and bare domains
 * in <a> tags so the electronically-distributed PDF is clickable. Styled
 * invisible by `.sheet a` (inherit color, no underline), so print output is
 * unchanged. Runs on the ALREADY-sanitized output of e()/rich() — rich()
 * strips <a>, and stored JSON stays markup-minimal, so this must never run
 * on the save path. Only text between tags is transformed; the tags
 * themselves pass through untouched. Bare domains use a conservative TLD
 * list so ordinary prose can't false-positive into a link.
 */
function linkify(string $html): string {
    $rx = '~
        [A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}   # email
      | https?://[^\s<>"\']+                                               # URL with scheme
      | (?<![\w.@-])(?:[A-Za-z0-9-]+\.)+(?:com|org|net|edu|gov|us|info|io)\b(?:/[^\s<>"\']*)?  # bare domain
    ~xi';
    $parts = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $i => $part) {
        if ($part === '' || $part[0] === '<') continue;
        $parts[$i] = preg_replace_callback($rx, function ($m) {
            $text = $m[0];
            // Sentence punctuation right after a link stays plain text.
            $trail = '';
            if (preg_match('/[.,;:!?)]+$/', $text, $t)) {
                $trail = $t[0];
                $text  = substr($text, 0, -strlen($trail));
            }
            if ($text === '') return $m[0];
            if (strpos($text, '@') !== false) {
                $href = 'mailto:' . $text;
            } elseif (stripos($text, 'http') === 0) {
                $href = $text;
            } else {
                $href = 'https://' . $text;
            }
            return '<a class="auto-link" href="' . $href . '">' . $text . '</a>' . $trail;
        }, $part);
    }
    return implode('', $parts);
}

/** Render a rich body whose paragraphs are separated by blank lines. */
function rich_paras(?string $text, string $class = ''): string {
    $out = '';
    $paras = preg_split('/\n\s*\n/', trim($text ?? ''));
    $attr = $class !== '' ? ' class="' . e($class) . '"' : '';
    foreach ($paras as $p) {
        if (trim($p) === '') continue;
        $out .= "<p$attr>" . linkify( rich($p) ) . "</p>\n";
    }
    return $out;
}

/** All icon ids in the set, sorted. */
function list_icons(): array {
    $ids = array_map(fn($f) => basename($f, '.svg'), glob(TDA_ICONS . '/*.svg'));
    sort($ids);
    return $ids;
}

/**
 * Inline an icon from /icons/svg by id, sized via width/height attributes and
 * colored by CSS currentColor. Unknown or missing ids fall back to the anchor
 * (section 16: a slot never renders empty).
 */
function tda_icon(?string $id, int $size = 17, string $class = ''): string {
    static $cache = [];
    $id = preg_replace('/[^a-z0-9-]/', '', strtolower($id ?? ''));
    if ($id === '' || !is_file(TDA_ICONS . "/$id.svg")) $id = 'anchor';
    if (!isset($cache[$id])) {
        $svg = file_get_contents(TDA_ICONS . "/$id.svg");
        $svg = preg_replace('/<\?xml[^>]*\?>|<!--.*?-->/s', '', $svg);
        // Strip fixed dimensions from the root tag; sizing comes from us.
        $svg = preg_replace_callback('/<svg\b[^>]*>/', function ($m) {
            return preg_replace('/\s(width|height)="[^"]*"/', '', $m[0]);
        }, $svg, 1);
        $cache[$id] = trim($svg);
    }
    $attrs = sprintf('class="icon %s" width="%d" height="%d" aria-hidden="true"',
        e($class), $size, $size);
    return preg_replace('/<svg\b/', "<svg $attrs", $cache[$id], 1);
}

/**
 * Masthead hero: an editor-uploaded replacement in uploads/ wins over the
 * repo asset. Lives in uploads/ so a deploy can never clobber it (section 7).
 * Cache-busted by mtime since the filename never changes.
 */
function hero_src(): string {
    foreach (['png', 'jpg', 'webp'] as $ext) {
        $f = TDA_ROOT . "/uploads/masthead-hero.$ext";
        if (is_file($f)) return "uploads/masthead-hero.$ext?v=" . filemtime($f);
    }
    return 'assets/currents-hero.png';
}

/** Section header: uppercase label + rule + icon. Used by every section. */
function section_header(string $label, ?string $iconId, string $marginClass = ''): string {
    return '<div class="sec-head ' . e($marginClass) . '">'
        . '<span class="sec-label">' . e($label) . '</span>'
        . '<span class="sec-rule"></span>'
        . tda_icon($iconId, 17, 'sec-icon')
        . '</div>';
}

/** Footer + colophon, identical on both pages. */
function page_footer(array $settings): string {
    $emails = '';
    foreach ($settings['committee_emails'] ?? [] as $em) {
        $emails .= '<div class="footer-email">' . linkify( e($em) ) . '</div>';
    }
    return '<div class="footer">'
        . '<div class="footer-left">'
        .   '<img class="footer-anchor" src="assets/anchor-logo.svg" alt="">'
        .   '<div class="footer-informed">'
        .     '<div class="footer-title">' . e($settings['stay_informed_title'] ?? 'Stay Informed') . '</div>'
        .     '<div class="footer-text">' . linkify( e($settings['stay_informed_text'] ?? '') ) . '</div>'
        .     '<div class="footer-url">' . linkify( e($settings['site_url'] ?? '') ) . '</div>'
        .   '</div>'
        . '</div>'
        . '<div class="footer-right">'
        .   '<div class="footer-reach">Reach the Committees</div>'
        .   $emails
        . '</div>'
        . '</div>'
        . '<div class="colophon">' . linkify( e($settings['colophon'] ?? '') ) . '</div>';
}

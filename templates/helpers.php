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

/** Render a rich body whose paragraphs are separated by blank lines. */
function rich_paras(?string $text, string $class = ''): string {
    $out = '';
    $paras = preg_split('/\n\s*\n/', trim($text ?? ''));
    $attr = $class !== '' ? ' class="' . e($class) . '"' : '';
    foreach ($paras as $p) {
        if (trim($p) === '') continue;
        $out .= "<p$attr>" . rich($p) . "</p>\n";
    }
    return $out;
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

/** Section header: uppercase label + rule + icon. Used by every section. */
function section_header(string $label, ?string $iconId, string $marginClass = ''): string {
    return '<div class="sec-head ' . e($marginClass) . '">'
        . '<span class="sec-label">' . e($label) . '</span>'
        . '<span class="sec-rule"></span>'
        . tda_icon($iconId, 17, 'sec-icon')
        . '</div>';
}

/** Masthead wave rule. Each instance needs a unique pattern id. */
function wave_rule(string $uid): string {
    return <<<SVG
<svg class="wave-rule" width="100%" height="12" aria-hidden="true">
  <defs>
    <pattern id="mw-$uid" width="26" height="12" patternUnits="userSpaceOnUse">
      <path d="M0 6 q6.5 -5 13 0 t13 0" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"></path>
    </pattern>
  </defs>
  <rect width="100%" height="12" fill="url(#mw-$uid)"></rect>
</svg>
SVG;
}

/** Footer + colophon, identical on both pages. */
function page_footer(array $settings): string {
    $emails = '';
    foreach ($settings['committee_emails'] ?? [] as $em) {
        $emails .= '<div class="footer-email">' . e($em) . '</div>';
    }
    return '<div class="footer">'
        . '<div class="footer-left">'
        .   '<img class="footer-anchor" src="assets/anchor-logo.svg" alt="">'
        .   '<div class="footer-informed">'
        .     '<div class="footer-title">' . e($settings['stay_informed_title'] ?? 'Stay Informed') . '</div>'
        .     '<div class="footer-text">' . e($settings['stay_informed_text'] ?? '') . '</div>'
        .     '<div class="footer-url">' . e($settings['site_url'] ?? '') . '</div>'
        .   '</div>'
        . '</div>'
        . '<div class="footer-right">'
        .   '<div class="footer-reach">Reach the Committees</div>'
        .   $emails
        . '</div>'
        . '</div>'
        . '<div class="colophon">' . e($settings['colophon'] ?? '') . '</div>';
}

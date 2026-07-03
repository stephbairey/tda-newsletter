# TDA Currents CMS

Operating manual for this project. This file is the single source of truth for how the
project works and every decision behind it. For exact colors, type scale, and per-section
layout measurements, see the design reference (`README.md` from the Claude Design
handoff). If this file and the design reference ever disagree, **this file wins**, but they
should not: the reference covers pixels, this file covers behavior.

---

## 1. What this is

TDA Currents is a monthly two-page print newsletter for the Tomahawk Island Floating
Home Community (Tomahawk Destiny Association), a floating-home moorage on the Columbia
River in Portland, Oregon.

This repo is a small PHP CMS that produces the newsletter. It lets a non-technical editor
change copy, swap icons, add and remove images and items, toggle a couple of blocks, and
export a print-ready PDF, all without touching code. It runs at
`newsletter.tomahawkdestiny.com`, password-protected, walled off from the main
WordPress site on `tomahawkdestiny.com`.

## 2. Prime directive

Produce a **print-ready two-page PDF**: US Letter, 8.5 x 11 portrait, color, front and back
of a single sheet, that goes to 9cent Color Copies in Vancouver WA for printing. Every
design and code decision serves that output. If a choice makes the layout prettier on
screen but less reliable in print, print wins.

## 3. Who runs it, and why simplicity is the point

One editor today (Maya, who goes by Steph in the moorage context). A backup editor later
(assume Gigi). The explicit goal is **no single point of failure**: the tool must be simple
enough that a non-technical person can run it, and simple enough to hand off. That goal
outranks cleverness everywhere in this project. When two approaches both work, pick the
one with fewer moving parts and less that a backup person could break.

## 4. Stack and hard constraints

- **PHP**, plain. No framework. Confirmed present on the host (`php.ini` in the doc root).
- **Flat-file storage.** One JSON file per issue in `data/`. **No database, no MySQL.**
  Rationale: portable, human-readable, backed up by copying a folder, easy for a backup
  person to understand.
- **All JSON saves are atomic: write-temp-then-rename.** Every persist goes through
  `atomic_write_json()` in `templates/store.php`: write to a dotted temp file in the same
  directory (same filesystem, so the rename is atomic), fflush + fsync, then `rename()` over
  the target; on any failure, remove the temp and leave the original untouched. A crash or
  host stall mid-save can leave only the complete old file or the complete new file, never a
  torn one. Never write onto a live JSON file directly (`file_put_contents` on the target is
  the bug, not a shortcut).
- **No build step, no bundler, no npm.** Editors and a backup person cannot run a build.
  Plain PHP, CSS, and vanilla JS served directly.
- **Icons are Maya's own SVG set**, committed in the repo at `/icons/svg`. **Not Lucide.**
  Every icon in the newsletter is editor-selectable from this set. See section 15.
- **Fonts:** Playfair Display (display/serif) and Archivo (body/sans), from Google Fonts.
  Exact weights and the import string are in the design reference.
- Brand colors (load-bearing, full token set in the design reference): `--ink` `#1E5373`,
  `--blue` `#3199D8`, section-label blue `#2E8FCD`.

## 5. Page geometry and resolution

The printed page is the **full white 8.5 x 11 sheet**. There is no desk color, background
fill, or drop shadow; those appear in the Claude Design screenshots only as screen chrome
(the mockup floating on a desk). The only fill on the page is the footer tint block, which sits
inside the margins. Nothing colored reaches the paper edge, so **no bleed and no crop
marks** are needed. This is the simplest print case.

**Author the page box in inches, not pixels**, so the geometry is self-documenting and dpi
stays out of the CSS:

```css
.page  { width: 8.5in; height: 11in; padding: 0.5in; }
@page  { size: letter; margin: 0; }
```

On print, inches map to real inches. On screen, the browser renders 1in as 96px, giving an
816 x 1056 display for free. The content area falls out of the padding at 7.5 x 10in, no
separate declaration needed. (The design reference authored these in px, 816 x 1056 with
48px padding, which is numerically identical. If recreating from the `.dc.html`, convert to
inches for print clarity.)

Each page is its own sheet: `break-after: page` between them. Keep the mockup's grayscale
preview toggle (`filter: grayscale(1)` on the page root) as a nice-to-have for previewing a
black-and-white run.

The dpi reference, for whoever preps photos (see section 10). The HTML/PDF itself is vector
and has no dpi; these numbers only govern the resolution of raster images placed into the
page:

|              | Physical    | 96dpi (CSS px) | 300dpi (raster) |
|--------------|-------------|----------------|-----------------|
| Full sheet   | 8.5 x 11 in | 816 x 1056     | 2550 x 3300     |
| Content area | 7.5 x 10 in | 720 x 960      | 2250 x 3000     |
| Margin       | 0.5 in      | 48             | 150             |

## 6. Server and repo layout

The subdomain's document root **is** `/home/tomahawk/newsletter.tomahawkdestiny.com/`. It
is a sibling of the main site's `public_html`, not inside it. App files live directly in the doc
root. Do not create a nested `public_html` inside it.

**Local repo** (what CC edits; the `[deploy]` / `[local]` tags show what section 7 pushes):

```
tda-newsletter/
├── CLAUDE.md          # [local]  this file
├── deploy.sh          # [local]  the deploy script
├── index.php          # [deploy] app entry
├── templates/         # [deploy] page + section templates
├── css/               # [deploy]
├── js/                # [deploy]
├── icons/svg/         # [deploy] the editor-selectable SVG set
├── assets/            # [deploy] anchor-logo.svg, currents-hero.png
├── uploads/           # [local]  CMS writes this on the server; seed only (josh-talia.jpg)
├── data/              # [local]  CMS writes this on the server; per-issue JSON
└── reference/         # [local]  .dc.html, README.md, page screenshots. Never deploys.
```

**Server doc root** (the deploy target):

```
/home/tomahawk/
├── public_html/                       # main site + WordPress. DO NOT TOUCH.
├── newsletter.tomahawkdestiny.com/    # doc root = deploy target
│   ├── .well-known/  .htaccess  php.ini  .user.ini  cgi-bin/   # cPanel's. Leave alone.
│   ├── index.php  templates/  css/  js/                        # app code, deployed by CC
│   ├── icons/  assets/                                         # static art, deployed by CC
│   ├── data/        # per-issue JSON, written by the CMS at runtime. NOT deployed.
│   └── uploads/     # editor-uploaded images, written by the CMS. NOT deployed.
└── newsletter_archive/                # outside every web root: private, deploy-proof
```

Note that `reference/`, `CLAUDE.md`, and `deploy.sh` exist only in the repo; they are held
back by the section 7 exclude list and never appear on the server.

Key facts that constrain everything below:

- `.well-known/` in the doc root is used for SSL renewal. Deleting it breaks HTTPS.
- `data/` and `uploads/` are written by the running app on the server. They do not come
  from the repo and must never be overwritten or deleted by a deploy.
- `newsletter_archive/` sits at home-directory level, outside all web roots. Nothing serves
  it by URL, no deploy can reach it, and PHP is not expected to write to it (it may be
  blocked by `open_basedir`). See section 14.

- `.well-known/` in the doc root is used for SSL renewal. Deleting it breaks HTTPS.
- `data/` and `uploads/` are written by the running app on the server. They do not come
  from the repo and must never be overwritten or deleted by a deploy.
- `newsletter_archive/` sits at home-directory level, outside all web roots. Nothing serves
  it by URL, no deploy can reach it, and PHP is not expected to write to it (it may be
  blocked by `open_basedir`). See section 14.

## 7. Deploy

SSH is enabled and live on the TDA account. Deploy is a **deliberate command**, not
sync-on-save. A half-edited file should never reach a live site.

**Live connection values:**

- Host: `tomahawkdestiny.com`
- Port: **1157** (not 22; must be stated explicitly on every ssh/rsync call)
- User: `tomahawk`
- Key: `~/.ssh/tda_deploy` (no passphrase, so deploys run unattended)
- Deploy target (doc root): `/home/tomahawk/newsletter.tomahawkdestiny.com/`

**Additive push only.** Never run a destructive full mirror of the doc root. A `--delete`
would wipe `.well-known/`, `php.ini`, and the runtime content folders. The deploy pushes
only the app's own files and folders and leaves everything else in the doc root alone.

**Never deploy over, or delete, `data/` or `uploads/`.** Those hold every issue's content
and every uploaded image.

**Deploy source: repo root with an exclude list.** The app lives at the repo root
(`index.php`, `templates/`, `css/`, `js/`, `assets/`, `icons/`), so the deploy pushes the root
and holds back everything that must never go live: `.git/`, `CLAUDE.md`, `deploy.sh`,
`reference/` (which holds the `.dc.html` source, `README.md`, and the page screenshots), and
the runtime content dirs `data/` and `uploads/`.

```bash
rsync -avz -e "ssh -p 1157 -i ~/.ssh/tda_deploy" \
  --exclude '.git/' --exclude 'CLAUDE.md' --exclude 'deploy.sh' \
  --exclude 'reference/' --exclude 'data/' --exclude 'uploads/' \
  ./ tomahawk@tomahawkdestiny.com:/home/tomahawk/newsletter.tomahawkdestiny.com/
```

No `--delete` (additive only), so this never removes `.well-known/`, `php.ini`, or
server-written content. Because `data/` and `uploads/` are excluded, deploy does not create
them: they must exist and be writable on the server. Have the app create them on first run if
missing, or make them once by hand. Isolate all of this in a single `deploy.sh`.

## 8. Editor authentication (v1: cPanel Directory Privacy)

Lock the editor with **cPanel Directory Privacy** (htpasswd basic auth), configured entirely
in cPanel, no code to maintain. Adding the backup editor is one entry. The only downside is
the plain browser login popup, which is fine for an internal tool and safe now that the
subdomain has HTTPS. A branded PHP session login is a possible later upgrade, not v1.

Protect the editor and admin routes. The public-facing print/read view does not need to be
locked unless Maya wants the whole subdomain private.

## 9. Content model

Two buckets. **Constants** rarely change (roughly yearly). **Per-issue content** changes
every month.

**Constants** (a single settings file): tagline, both logos, the five committee emails, the
colophon address and phone, the "send us photos" submit line, the site URL. Editors touch
these almost never. (The submit CTA — title, text, email — prints as a tinted Shout-Outs-style
box pinned to the bottom of the Spotlight column on page 1, so the ask lands after the
content; its old home, the page-2 submit band, was removed in July 2026 to make room for
the wavy rule under the running head.)

**Per-issue content** (one JSON file per issue, e.g. `data/2026-07.json`):

- **Masthead:** issue date, issue number. (The hero banner is a fixed asset in `/assets`, not
  a per-issue upload; see Assets below.)
- **Spotlight:** icon, headline, body, optional photo + caption, image treatment/crop
- **Committee Highlights:** 4 item slots { icon, bold lead-in, text }; blank slots don't print
- **Calendar:** 4 event slots { month, day, title, time and place, note }; blank slots don't print
- **Friendly Reminder:** { icon, lead-in, text }
- **Flex slot:** mode (`qa` or `editorial`) plus the matching fields (see section 10)
- **All Hands on Deck:** intro line + 1 to 3 items { icon, lead-in, text }
- **Shout-Outs:** { icon, text }
- **Dock Talk:** eyebrow + { icon, text }
- **Sponsor slot:** mode (`ad` or `trivia`) plus fields; ad has sub-mode (`sponsor` or
  `house`) (see section 10)

Every icon-bearing field stores a chosen icon id from the SVG set. For which fields accept
inline formatting versus plain text, and how lead-ins work, see section 12.

## 10. Block taxonomy

Three kinds of block. This distinction is the heart of the layout and keeps a fixed print
page from getting away from us.

**Fixed-count blocks.** A fixed number of slots, no add/remove UI; a slot left entirely
blank is skipped at render (raised from 2 to 4 in July 2026).
- Committee Highlights: 4 slots.
- Calendar: 4 slots.
These feed the tight upper-right quadrant of page one; capping their count is what keeps that
region predictable. Filling every slot plus a long Spotlight will overflow — the fit
warning is the backstop, and short months just leave slots blank.

**Toggle blocks.** Editor picks a mode per issue.
- **Flex slot** (page 2): `qa` or `editorial`.
  - `qa` ("Current Questions"): big Playfair Q and A initials, a question and answer, italic
    attributions.
  - `editorial` ("Why It Matters"): a mini-Spotlight. Section header, optional image (variable
    aspect, section 11), flowing body text, no Q/A initials or indents.
  - The section label and icon swap with the mode.
- **Sponsor slot** (page 2): `ad` or `trivia`.
  - `ad`: the dashed card. Either a real sponsor (name, tagline, URL) or the house default
    (`house` sub-mode: "Your ad here", the why, and who to contact). Provide the house copy
    as a one-click preset so a quiet month is not a retype.
  - `trivia`: a headline plus one short sentence. No card, no background. A small plain block
    for months with no ad.
- **Spotlight image:** on or off. Off means text fills the box.

**Repeatable-within-a-range blocks.** A list the editor adds to or removes from.
- All Hands on Deck: 1 to 3 items. The block fills its vertical space at any count. Horizontal
  rules render **between** items only, never above the first or below the last, generated by
  count. Enforce the min and max; refuse input beyond the range with a clear message. The
  same principle applies to any future list block: every repeatable block needs a min and max
  the fixed page can hold.

## 11. Images and variable aspect ratios

Editors will upload portrait and landscape photos and will not preprocess them. A fixed box
still has to look right. Handle it this way, in the Spotlight and the Editorial flex slot:

- Offer a small set of **fixed image treatments** the editor chooses from, each with locked
  dimensions: portrait float (as Josh/Talia uses now), landscape banner (full column width,
  fixed height, above the text), and possibly a contain-on-tint full frame.
- Pair the treatment with an **in-browser crop and reposition** step: any orientation uploads,
  the editor drags to frame it inside the chosen treatment, saves.

Result: any aspect ratio goes in, a correct fixed frame comes out, the text area stays
bounded, and copyfitting still works. Uploaded originals land in `uploads/`.

**Source resolution (target ~300dpi at printed size):**
- **Hero:** spans the full ~7.5in content width, so ~2250px wide minimum. Check CD's export;
  if it came out small it will print soft. It is line-art, so re-exporting large is easy.
- **Spotlight / editorial photos:** print small (roughly 1.6in float up to ~4in banner). A long
  edge of 1300 to 1500px covers every treatment.
- **Logos (anchor, vector mark):** keep as SVG if the vector source exists (resolution-proof,
  like the icons); otherwise ~900px raster.

**Assets and folders in the repo (these supersede the design-reference manifest).**

- `/assets` (static, deploys with the app):
  - `anchor-logo.svg` — the anchor mark for the masthead and both footers. **Replaces the
    handoff's `tda-anchor-cut.png`.** The design reference and the `.dc.html` both point at the
    old PNG; use this SVG instead and repoint. Vector, so it stays crisp at any print size.
  - `currents-hero.png` — the Mt. Hood masthead banner, already preprocessed and sized for
    print, with the oval Tomahawk Island logo and the wavy top rule baked into the image
    (no separate overlay; the handoff's `tda-vector-logo.png` was removed). Authored at
    **2250x450 (5:1)**; the CSS renders the hero at the full content width x 1.5in with
    `object-fit: cover`, so other shapes crop rather than reflow page 1. Treated as a
    fixed banner, not per-issue content. Settings can upload a replacement, stored as
    `uploads/masthead-hero.*` (deploy-proof; only the latest upload is kept), which wins
    over this asset on every issue.
  - `wavy-line.png` — the wavy rule as a raster (2250px wide, ~300dpi). The original SVG
    pattern printed poorly, so page 1's wave is now baked into the hero image and page 2
    renders this PNG under the running head. The `wave_rule()` SVG helper was removed.
- `/icons/svg` (static, deploys): the editor-selectable SVG icon set (section 16).
- `/uploads` (CMS content, excluded from deploy):
  - `josh-talia.jpg` — the issue-one Spotlight photo. Spotlight photos are per-issue, so they
    live here rather than in `/assets`.
- `/reference` (reference only, never deployed): the `.dc.html` source, `README.md`, and the
  two page screenshots. CC reads these to recreate and visually check the layout; the screenshots
  are the target the rebuild is compared against, since the `.dc.html` needs its proprietary
  runtime to render.

## 12. Text formatting, overflow, and limits

**Rich text, deliberately crippled.** The design needs inline emphasis (bold lead-ins, bold
names in Shout-Outs, emphasis inside prose), so the prose fields use a WYSIWYG editor whose
toolbar is exactly three controls: **bold, italic, clear-formatting.** Nothing else. No
headings, font sizes, colors, lists, links, or block elements. Anything block-level fights the
fixed boxes and breaks the locked design, so the editor allows inline marks only.

- **Allowed HTML:** `<strong>`, `<em>`, `<br>`. Everything else is stripped.
- **Strip on paste.** Flatten pasted content (especially from Word or Google Docs) down to
  the allowed marks. This is the single most important safeguard for the backup editor.
- **Sanitize server-side on save.** Stored content is HTML now, so PHP whitelists it on the
  way in (allow the three tags above, strip the rest). Never trust what the browser posts.
  This protects both the design and the public read view.

**Which fields are rich versus plain:**
- **Rich (bold/italic):** Spotlight body, Editorial body, Committee Highlights item body,
  All Hands item body, Dock Talk body, Shout-Outs body, Friendly Reminder body.
- **Plain text** (the template styles them; the editor applies no formatting): all headlines
  and section labels, calendar fields, Q/A and other attributions, the Dock Talk eyebrow,
  issue date and number, sponsor and trivia fields, URLs. The **Q&A answer** is the one
  plain field that keeps the editor's line breaks (Enter in the textarea prints; stored via
  `plain_lines()`, rendered with `nl2br`).
- **Structured lead-ins** (a separate plain field, auto-bolded by the template): the bold
  opener on Committee Highlights, All Hands, and Friendly Reminder items. The editor types the
  phrase in its own box and the template bolds it, so the signature look is always right
  without anyone having to remember to bold it.

**Overflow: soft warning, not a hard block.** When text overflows its box, warn on screen but
still allow save. Maya is the managing editor and copyfits deliberately; a hard block is
infuriating mid-edit. Make the warning loud and obvious for the backup editor's sake.

**Count rendered text, not markup.** The overflow measure and any character limit count the
visible text length, not the HTML string, or the tags inflate the count into nonsense.

**Character limits are discovered, not guessed.** They were discovered in the July 2026
stress test. They are **soft caps**: nothing blocks typing or pasting past a limit (hard
enforcement misbehaved on paste and was removed in July 2026). The live counter under every
limited field is the warning — over the cap it turns red on a pale red band. Counts are of
rendered text, per above. The caps (defined in `templates/editor.php`):

- Headlines (Spotlight, Editorial): 10–30 chars; the printed headline is **one line, no
  wrap** (`white-space: nowrap`), which is what forces brevity.
- Spotlight body: 1200 no photo / 850 portrait / 650 landscape (the cap follows the photo
  toggle and treatment). Editorial body: 885 / 850 / 500. Captions: 50.
- Committee Highlights item text: 150 each. Calendar: title/note/call-to-action 30, place 25.
- Friendly Reminder: lead-in + text **combined** 125.
- Q&A: question 300, answer 490, attributions 30 (the em-dash is added by the template).
- All Hands: intro + all items **combined** 650 (combined because the item count varies).
- Shout-Outs: 230. Dock Talk tip: 200.

The caps were each calibrated against otherwise-normal issue content; maxing every page-2
field at once still overflows, and the overflow warning is the backstop for that case.

## 13. Duplicate an issue

"Duplicate last month" clones the previous issue's JSON into a new file, bumps the date and
issue number, and opens the editor pre-filled. Most months change only a few fields.

**Carry everything forward as real copies**: copy, icons, and images. Images are copied as
real files (not references), so the new issue is fully standalone and the editor deletes what
they do not want. A duplicated issue never shares state with its parent.

## 14. Print and PDF export

- A **dedicated print view** renders the two pages at true 8.5 x 11 with the print stylesheet
  active (`@page { size: letter; margin: 0; }`, page box in inches per section 5).
- **Export is browser Save-as-PDF** from that view. Put an instruction on or beside the
  button: print at 100% scale, margins none, no headers or footers. The output is vector, so
  text and rules stay sharp at the printer's real resolution regardless of the raster image dpi.
- **No server-side PDF.** The host bans background processes, so headless Chrome as a
  service is not available, and PHP PDF libraries choke on this layout's flexbox, inline SVG,
  and CDN webfonts. The browser is the same engine that drew the page, so its PDF is
  pixel-faithful. Revisit only as a future upgrade if the manual step proves flaky.

## 15. Archive

`newsletter_archive/` lives at home-directory level, off the web and deploy-proof.

For v1 the archive is **manual**: after Save-as-PDF for the printer, drop that PDF into
`newsletter_archive/` via cPanel File Manager. One drag, no code. Automating it later would
mean testing whether PHP can write to a path outside the subdomain sandbox
(`open_basedir` may block it), so it is deliberately deferred.

**Important:** the archived PDF is a flattened final record. It is **not** what lets you reprint
or duplicate an issue. That capability lives in the JSON plus images in `data/` and
`uploads/`. The no-single-point-of-failure goal therefore rides on backing up the JSON (pull
`data/` and `uploads/` down periodically), not on the PDF archive.

## 16. Icons

All icons come from Maya's SVG set at `/icons/svg`. Every icon in the newsletter, both
section-header icons and inline item icons on both pages, is editor-selectable via a picker
that renders the set; the chosen icon's id is stored in the JSON alongside its field.

- Keep the SVGs **single-color using `currentColor`** so one file works in either context.
  Headers render section-blue, inline list icons render ink; CSS sets the color by context.
- Designate the **anchor** as the default and fallback icon, so a slot never renders empty if
  an id is missing or an editor skips the pick.

## 17. Voice and brand

Warm, playful, community-first. The opposite of dry board minutes, and distinct from both
board communications and Maya's Lingua Ink business voice. Readers should feel what is
happening at the moorage and how to be part of it.

Masthead essentials (exact type values in the design reference): the "TDA Currents"
wordmark (TDA in ink, "Currents" in blue italic, Playfair), the anchor mark, the tagline
"Keeping our community connected on the water," and the date plus "Issue No. X". Use a
**single rule** under the masthead, not a double rule.

## 18. Design reference and precedence

The Claude Design handoff `README.md` is the visual reference: design tokens, type scale,
per-section measurements, reusable patterns (section header, wave rule), and the asset
manifest. Treat it as authoritative for pixels only.

The original source file is a Design Component (`.dc.html`) that depends on proprietary
runtime scaffolding (`<x-dc>`, `support.js`, `data-props`, `<script data-dc-script>`).
**Read the markup and styles inside it; do not reproduce that scaffolding.** Recreate the
layout in this project's plain PHP/CSS.

If this file and the reference ever conflict, this file governs. The reference has been edited to
remove its one real conflict (it previously specified Lucide icons; icons are now the SVG set).

## 19. Guardrails (do not)

- Do not put app files in `public_html`; that is the main WordPress site.
- Do not add a database, a build step, or a bundler.
- Do not use Lucide; use the SVG set at `/icons/svg`.
- Do not reproduce the `.dc.html` runtime scaffolding.
- Do not author the page canvas in 300dpi pixels; author in inches (section 5).
- Do not create variable-height sections outside the defined ranges.
- Do not allow block-level HTML in editor content; inline `<strong>`/`<em>`/`<br>` only,
  sanitized server-side (section 12).
- Do not deploy with `--delete`, or without the section 7 exclude list.
- Do not overwrite or delete `.well-known/`, `php.ini`, `.user.ini`, `.htaccess`, `data/`,
  or `uploads/`.
- Do not make PHP write to `newsletter_archive/` in v1.

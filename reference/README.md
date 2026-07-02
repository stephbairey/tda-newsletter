# Handoff: TDA Currents Newsletter

## Overview
"TDA Currents" is a printable two-page community newsletter for the **Tomahawk Island Floating Home Community** (Tomahawk Destiny Association). It is a monthly print-first layout (US Letter, portrait) built around a masthead, hero image, and a set of magazine-style content sections: neighbor spotlight, committee highlights, calendar, Q&A, volunteer callouts, shout-outs, tips, a sponsor slot, and a repeating footer.

The goal of this handoff is to recreate this design in a real, editable codebase so the newsletter can be produced and updated for future issues.

> **This file describes issue one's static layout only.** All dynamic and CMS behavior, plus the project's current tooling and asset decisions, live in `CLAUDE.md`, which is authoritative and supersedes this file on any conflict. Two items here are already superseded: icons are the project's own SVG set at `/icons/svg` (editor-selectable), not Lucide; and the anchor mark is `anchor-logo.svg`, not `tda-anchor-cut.png`.

## About the Design Files
The files in this bundle are **design references created in HTML** — a prototype showing the intended look and print layout, **not** production code to copy directly. The original file is a "Design Component" (`.dc.html`) that depends on a proprietary runtime (`support.js`, `<x-dc>`, `data-props`) which is **not** included and should **not** be reproduced.

Your task is to **recreate this design in the target codebase's environment** using its established patterns (React, Vue, plain HTML/CSS, a static-site generator, etc.). If no codebase exists yet, pick the most appropriate approach — for a print-oriented newsletter that non-technical editors will update monthly, a simple templated HTML/CSS page (or a lightweight React/Astro component with content pulled from a data file) is a good fit.

**Ignore the DC-specific scaffolding.** The parts that matter are: the markup structure, the inline styles, the CSS custom properties, the fonts, and the two page layouts. The `<script data-dc-script>` block at the bottom only did three things you can reimplement trivially if wanted: set `--ink`/`--blue` from props, toggle a grayscale filter, and render the icons.

## Fidelity
**High-fidelity (hifi).** This is a pixel-considered, print-ready mockup with final colors, typography, spacing, and copy. Recreate it faithfully. Exact measurements, hex values, and font settings are documented below.

## Page Setup
- **Page size:** US Letter portrait — `816px × 1056px` per page at 96dpi (8.5in × 11in). Two pages.
- **Page padding:** `48px` on all sides.
- **Page background:** `#fff`; desk/screen background behind pages: `#cdd4da`.
- Each page is a vertical flex column (`display:flex; flex-direction:column`).
- Base body text: `13.5px`, `line-height:1.5`, color `#23303a`.
- For real printing, set `@page { size: letter; margin: 0; }` and give each page its own physical sheet (e.g. `break-after: page`). On screen the mock stacks the two pages with `26px` vertical margins and a drop shadow (`0 12px 40px rgba(30,55,85,.22)`) — drop that shadow/margin for print.

## Design Tokens
Defined as CSS custom properties on each page root:

- `--ink` (primary dark blue): `#1E5373`
- `--blue` (accent bright blue): `#3199D8`
- Section-label blue (used in headers/rules): `#2E8FCD`
- `--text` (body): `#23303a`
- `--muted` (secondary/captions): `#5d6c78`
- `--rule` (hairline dividers): `#c7d2db`
- `--tint` (section fill / footer bg): `#eaf1f8`
- `--tintln` (light border): `#cfe0f0`
- Desk background: `#cdd4da`
- White: `#fff`

**Typography**
- Display/serif: **Playfair Display** (Google Fonts) — weights 500–900, plus italics. Used for masthead wordmark, headlines, big date/calendar numerals, Q/A initials, sponsor name.
- Body/sans: **Archivo** (Google Fonts) — weights 400–800, plus italics. Everything else.
- Import: `https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;0,900;1,500;1,600;1,700&family=Archivo:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap`
- `-webkit-font-smoothing: antialiased`, `box-sizing: border-box` globally, `p { margin:0 }`.

**Type scale (as used)**
- Masthead "TDA Currents": Playfair, 37px (800 / italic 700).
- Masthead tagline: Archivo 10px, 700, `letter-spacing:2px`, uppercase, muted.
- Masthead date "JULY 2026": Playfair 21px 800; "Issue No. 1": 10px 700 uppercase muted.
- Section labels (e.g. "Spotlight"): Archivo 11px, 700, `letter-spacing:2px`, uppercase, color `#2E8FCD`.
- Section headline ("New Faces at Slip 510"): Playfair 25px 800, ink.
- Body copy: 13–13.5px, line-height 1.5–1.58.
- Captions / attributions: 9.5–11px, muted, sometimes italic.
- Calendar day numeral: Playfair 23px 800; month tab: 9px 800 uppercase, `letter-spacing:1.5px`.

**Icons:** the project's custom SVG set at `/icons/svg`, editor-selectable per slot (not Lucide, not a CDN). The names below are the intended default for each slot: `users`, `clipboard-list`, `wrench`, `party-popper`, `calendar-days`, `paw-print`, `messages-square`, `hand-helping`, `printer`, `footprints`, `camera`, `heart`, `anchor`, `lightbulb`, `waves`, `megaphone`. Render at 16–18px. Section-header icons are `#2E8FCD`; inline list icons are `--ink`. Keep the SVGs single-color using `currentColor` so one file serves both.

**Reusable pattern — section header:** a flex row (`gap:10px`, `align-items:center`): uppercase label span (no-wrap) + a flex-`1` `1.5px` rule (`#2E8FCD`, `opacity:.85`) + a section icon from the SVG set. Repeated for every section on both pages.

**Reusable pattern — masthead wave rule:** an SVG `<rect>` filled with a repeating wavelet `<pattern>` (12px tall), `color:var(--blue)`. Path `M0 6 q6.5 -5 13 0 t13 0` at `stroke-width:2.5`, `stroke-linecap:round`, tile width 26px. Give each instance a unique pattern `id`.

## Page 1 — Layout & Content

**Masthead** (flex row, space-between, bottom-aligned):
- Left: 46px anchor logo (`assets/anchor-logo.svg`) + wordmark ("TDA" ink 800 / " Currents" blue italic 700, Playfair 37px) with tagline below: "Keeping our community connected on the water".
- Right: "JULY 2026" (Playfair 21px ink) + "Issue No. 1".
- Wave rule below, `margin-top:6px`.

**Hero** (`margin-top:16px`): full-width photo `assets/currents-hero.png` (Tomahawk Island moorage with Mt. Hood). Vector logo `assets/tda-vector-logo.png` overlaid absolute at `left:2.2%; top:7%; width:20.5%`, with `drop-shadow(0 3px 9px rgba(20,55,85,.3))`. Carries `data-comment-anchor` on hero img — not needed in production.

**Body** (`margin-top:20px`, flex row, `gap:26px`, `align-items:stretch`):
- **Left column (flex 1.48) — Spotlight:** header ("Spotlight" + `users` icon), headline "New Faces at Slip 510", then body text with a **floated photo** (`assets/josh-talia.jpg`, `width:153px`, `float:left`, `margin:3px 15px 9px 0`, `1px` rule border, `height:204px` `object-fit:cover` `object-position:center 28%`) and an ink caption bar overlaid at its bottom ("Josh, Talia, and Zeke", 9.5px 600 white on ink). Three paragraphs of copy follow (see source for exact text; uses `&#8209;` non-breaking hyphens and typographic quotes/dashes).
- **Right column (flex 1):**
  - *Committee Highlights* (header + `clipboard-list`): two items, each an icon (`wrench`, `party-popper`) + paragraph with bold ink lead-in, separated by a `1px --rule` divider (`margin:11px 0`).
  - *On the Calendar* (header + `calendar-days`, `margin:20px 0 11px`): two event rows. Each = a 50px-wide bordered date chip (`1.5px solid --ink`; ink month tab in white, Playfair day numeral) + event details paragraph (bold title, time · place, note, muted "All members welcome."). Divider between. Events: **Board Meeting** AUG 12, 6:30 PM · Clubhouse; **Annual Progressive Dinner** SEP 27, 5:00 PM · Start at A-Dock.

**Friendly Reminder** (full width, `margin-top:16px`): header + `paw-print`; single paragraph, bold "Leashes, please." lead-in.

**Footer** (`margin-top:18px`, `border-top:3px solid --ink`, `background:--tint`, `padding:15px 18px`, flex row, `gap:26px`):
- Left: 40px anchor logo + "Stay Informed" (14px 800 ink) + tagline + site URL `https://tomahawkdestiny.com` (blue 600).
- Right: "REACH THE COMMITTEES" label + five committee emails (community/maintenance/epic/finance/arc @tomahawkdestiny.com), blue 600, 11.5px, right-aligned.
- Colophon bar below (`border-top:1px solid --tintln`, centered, 10px muted): "Tomahawk Island Floating Home Community • 288 N Tomahawk Island Drive, Portland, OR 97217 • (503) 735-3057".

## Page 2 — Layout & Content

**Running head** (smaller masthead): 34px anchor + "TDA Currents" (Playfair 22px) on left; "July 2026 · Issue No. 1" (10px uppercase muted) on right. Wave rule below.

**Section grid** (`margin-top:22px`, flex column, `gap:24px`):
- **Row 1** (flex row, `gap:26px`, with a `1px --rule` vertical divider between the two columns):
  - *Current Questions* (`messages-square`): a Q/A block. Big Playfair "Q" (blue 800) + question + italic muted attribution "— Marian Holt, Finger D"; big Playfair "A" (ink 800) + answer paragraph + "— Dale Whitcomb, Dock Committee".
  - *All Hands on Deck* (`hand-helping`): intro line, then three icon+paragraph items (`printer` monthly print run, `footprints` door-to-door delivery, `camera` dock photographer), divided by `1px --rule` lines.
- **Row 2 — Shout-Outs** (full width, `background:--tint`, `padding:17px 18px 18px`): header + `heart`; `anchor` icon + paragraph thanking Greg Korn and Russell Menenberg.
- **Row 3** (flex row, `gap:26px`, vertical `--rule` divider):
  - *Dock Talk* (`lightbulb`): "DID YOU KNOW?" eyebrow (11px 700 uppercase muted) + `waves` icon + tip paragraph (Pine-Sol / river otters).
  - *Community Sponsor* (`megaphone`): a centered card, `1.5px dashed --tintln` border, `padding:16px`, vertically centered: "Lingua Ink Media" (Playfair 19px 800 ink) + tagline + URL `https://linguainkmedia.com`.

**Submit band** (`border-top:2.5px solid --blue`, `margin-top:22px`, `padding-top:14px`, centered): "Have photos, tips, or questions? Send them to community@tomahawkdestiny.com" (email in blue 700).

**Footer + colophon:** identical to Page 1.

## Interactions & Behavior
This is a **static print/read layout** — no click handlers, navigation, animations, or form logic. The only runtime behavior in the prototype was:
1. **Theme override:** set `--ink` and `--blue` CSS variables from two color props (`ink` default `#1E5373`, `accent` default `#3199D8`).
2. **Grayscale toggle:** a `grayscale` boolean prop applied as `filter: grayscale(1)` on each page root (useful for previewing a B&W print run).
3. **Icon rendering:** inline the SVGs from `/icons/svg`. The prototype used Lucide's runtime; the project does not.

Reimplement these however suits the codebase — CSS variables + a theme object, or just hardcode the two brand colors. None are required for a faithful static reproduction.

## State Management
None required. If editors need to update content monthly, the natural improvement is to lift all copy (issue date, spotlight text, calendar events, Q&A, sponsor, emails) into a **data file / CMS entry** and template the two pages over it — but the prototype itself holds no state.

## Assets
Included in `assets/` (referenced from the HTML as `uploads/…` in the original — repoint paths as needed):
- `currents-hero.png` — page-1 hero photo (moorage with Mt. Hood).
- `josh-talia.jpg` — spotlight photo (Josh, Talia, and Zeke).
- `anchor-logo.svg` — anchor logo mark (masthead + footers). Replaces the prototype's `tda-anchor-cut.png`.
- `tda-vector-logo.png` — full vector logo overlaid on the hero ("Tomahawk Island Floating Home Community — Established 1999").

Not embedded assets (loaded from CDNs): Google Fonts (Playfair Display, Archivo). Icons are local SVGs in `/icons/svg`, not a CDN.

## Screenshots
Reference renders of the final design are in `screenshots/`:
- `01-full.png` — Page 1
- `02-full.png` — Page 2

## Files
- `TDA Currents Newsletter.dc.html` — the source design reference (both pages). Note the `<x-dc>`, `support.js`, and `<script data-dc-script>` wrapper is proprietary runtime scaffolding — read the markup/styles inside it, but do not reproduce that scaffolding.
- `assets/` — the four bundled images.

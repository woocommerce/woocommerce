# Save & Publish Header — Classic Product Editor Prototype

**Date:** 2026-06-01
**Status:** Design approved, pending implementation
**Prototype flag:** `save_publish_clarity`

## Problem

The classic product editor's publish metabox tangles three unrelated concerns together: save actions (Save Draft, Update), status metadata (Draft/Published/Visibility), and secondary actions (Move to Trash, Copy to draft). This causes merchants to misread what "Update" does, accidentally change product status when they mean to save, and lose confidence that their edits persisted. Enterpret shows three separate complaint clusters around edits reverting, fields not updating, and progress lost after errors — all pointing to the same surface.

## Context

- **Editor target:** Classic product editor (PHP metaboxes, `product` post type)
- **Scope:** Prototype only — toggled via the existing DevPanel ("Dev" button, bottom-right)
- **Reference design:** Jana's order edit sandbox PR #64626 (page header with back link, primary button, kebab menu)
- **Scope excludes:** The functional bug investigation (edits reverting after save) — that is a separate track

## Solution

Add a fixed page-level action bar that owns all save/publish actions. Reduce the existing Publish metabox to status and visibility fields only, and rename it "Visibility".

## Sticky Action Bar

**Positioning:**
- `position: fixed`
- `top: 65px` (32px WP admin bar + 33px WooCommerce sub-header `.woocommerce-layout__header`)
- `left: 160px` (clears the WordPress admin sidebar; collapses to `36px` when sidebar is folded via `.folded` on `body`)
- `right: 0`
- `z-index: 9998`
- Background: `#1d2327` (matches WP admin bar)
- A spacer `div` of equal height is injected below it so no page content is hidden

**Left side:**
- `← Back to products` text link → `edit.php?post_type=product`

**Right side (left to right):**
- `Preview` — ghost button (links to `?preview=true` or equivalent)
- `Save draft` — ghost button (submits form with `save` action; always visible in the prototype regardless of current status)
- Context-aware primary button:
  - Product status is `draft` or `auto-draft`: label is `Publish`
  - Product status is `publish` or `future`: label is `Update`
- `⋯` kebab button — opens a dropdown with:
  - `Copy to a new draft` (existing link from publish metabox)
  - `Move to Trash` (destructive, red text)

**Kebab dropdown:**
- Appears on button click, dismissed on click outside
- Positioned below and right-aligned to the kebab button
- Background: white, border: 1px solid `#c3c4c7`, border-radius: 4px, box-shadow

## Visibility Metabox (renamed from Publish)

**Title:** Changed from "Publish" to "Visibility"

**Keeps:**
- Status field (Draft / Published / Pending Review)
- Visibility field (Public / Password protected / Private)
- Catalog visibility (Shop and search results / etc.)
- Published on / Scheduled date field

**Removes (hidden via CSS):**
- Preview Changes button
- Save Draft button
- Update / Publish button
- Move to Trash link
- Copy to a new draft link
- The `#minor-publishing-actions` and `#major-publishing-actions` divs

## Implementation

### New file: `Improvements/SavePublishClarity.php`

- Namespace: `Automattic\WooCommerce\Internal\Prototype\Improvements`
- `init()` — no-ops if `DevPanel::is_flag_enabled('save_publish_clarity')` is false
- Hooks:
  - `admin_head` → `output_styles()` — outputs inline CSS for the action bar and metabox cleanup
  - `admin_footer` → `output_header_html()` — outputs the action bar HTML and inline JS (back link, buttons, kebab)
  - `gettext` filter with `'woocommerce'` domain → rename "Publish" metabox title to "Visibility"

### DevPanel.php

Add to `FLAGS` array:
```php
'save_publish_clarity' => 'Save & publish header',
```

### bootstrap.php

Add:
```php
use Automattic\WooCommerce\Internal\Prototype\Improvements\SavePublishClarity;
// ...
SavePublishClarity::init();
```

## CSS approach

The publish metabox action buttons are hidden (not removed) so the form still works if the user bypasses the prototype. The action bar buttons submit the same form fields.

Key selectors to hide in the metabox:
- `#minor-publishing-actions` (Save Draft + Preview)
- `#major-publishing-actions` (Move to Trash + Publish/Update button)
- `#misc-publishing-actions .misc-pub-section:last-child` (Copy to a new draft)

Metabox title rename is done via JS (`document.querySelector('#submitdiv .hndle span')`) rather than a PHP `gettext` filter to avoid side-effects on other screens.

## Sidebar width handling

WordPress applies `.folded` to `body` when the admin sidebar is collapsed (36px). The action bar left offset uses a CSS custom property or body class:

```css
body:not(.folded) #wc-proto-save-header { left: 160px; }
body.folded #wc-proto-save-header { left: 36px; }
```

## Out of scope

- Unsaved changes indicator (separate improvement)
- Sticky save bar on scroll (this bar is fixed, so it covers this)
- The functional bug where edits revert after save (separate engineering investigation)
- Mobile/responsive layout

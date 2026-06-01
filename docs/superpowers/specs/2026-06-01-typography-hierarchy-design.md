# Typography Hierarchy Pass — Classic Product Editor

**Date:** 2026-06-01
**Branch:** poligilad/product-editor-improvements
**Status:** Approved

## Overview

A typography hierarchy pass for the classic wp-admin product edit screen, enabled by a new dev panel checkbox. Uses WPDS tokens with hardcoded fallbacks throughout, following the same pattern as PR #64717 (Items metabox typography polish).

## Approach

PHP `admin_head` inline CSS injection, consistent with the existing `SavePublishClarity` prototype improvement on this branch. No build step required.

## Flag

- **Key:** `typography_hierarchy`
- **Label:** `Typography hierarchy`
- Added to `DevPanel::FLAGS` alongside the existing prototype flags.

## File Changes

| File | Change |
|---|---|
| `src/Internal/Prototype/DevPanel.php` | Add flag to `FLAGS` array |
| `src/Internal/Prototype/Improvements/TypographyHierarchy.php` | New class (see structure below) |
| `src/Internal/Prototype/bootstrap.php` | Register `TypographyHierarchy::init()` |

## Class Structure

`TypographyHierarchy.php` mirrors `SavePublishClarity.php`:

- `const FLAG_KEY = 'typography_hierarchy'`
- `static init()` — checks flag, hooks `admin_head` → `output_styles()`
- `static output_styles()` — guards with `DevPanel::is_supported_screen()`, outputs `<style id="wc-proto-typo-styles">`

## Typography Tiers

All rules scoped to `body.post-type-product`. Three tiers:

### 1. Metabox titles
Selectors: `.postbox .hndle h2`

```css
font-size: var(--wpds-typography-font-size-md, 13px);
font-weight: var(--wpds-typography-font-weight-medium, 499);
color: var(--wpds-color-fg-content-neutral, #1e1e1e);
```

### 2. Field labels
Selectors: `.woocommerce_options_panel .form-field label`, `.woocommerce_options_panel fieldset.form-field legend`

```css
font-size: var(--wpds-typography-font-size-sm, 12px);
font-weight: var(--wpds-typography-font-weight-regular, 400);
color: var(--wpds-color-fg-content-neutral-weak, #707070);
```

Labels are de-emphasized (regular weight, muted color) so content reads first — matching Jana's approach in PR #64717.

### 3. Helper/description text
Selectors: `.woocommerce_options_panel .form-field .description`

```css
font-size: var(--wpds-typography-font-size-xs, 11px);
font-weight: var(--wpds-typography-font-weight-regular, 400);
color: var(--wpds-color-fg-content-neutral-weak, #707070);
```

Labels and description text share neutral-weak color; size difference (12px vs 11px) is the only distinction between them.

## Token Reference

| Token | Resolved value | Usage |
|---|---|---|
| `--wpds-typography-font-size-md` | 13px | Metabox titles |
| `--wpds-typography-font-size-sm` | 12px | Field labels |
| `--wpds-typography-font-size-xs` | 11px | Helper text |
| `--wpds-typography-font-weight-medium` | 499 | Metabox titles |
| `--wpds-typography-font-weight-regular` | 400 | Labels + helper text |
| `--wpds-color-fg-content-neutral` | #1e1e1e | Metabox title color |
| `--wpds-color-fg-content-neutral-weak` | #707070 | Labels + helper text color |

## Out of Scope

- Block editor (React) product screen
- Order edit screens
- Line-height adjustments (not included — no evidence they need changing)
- WooCommerce settings pages (outside the product edit screen)

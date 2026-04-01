# WooCommerce Block Patterns Smoke Test

Bulk-insert all WooCommerce block patterns into a test page for visual review.

## Option 1: Browser Console Snippet (Recommended)

1. Open wp-admin and create a new **Page** (or edit an existing one) in the block editor.
2. Open browser DevTools (`F12` or `Cmd+Shift+J`).
3. Paste the contents of `console-snippet.js` into the console and press Enter.
4. All WooCommerce block patterns will be inserted into the page with heading separators.
5. Preview the page to visually review all patterns at once.

## Option 2: WP-CLI Script

From the WordPress root (or via `wp-env`):

```bash
# If using wp-env from plugins/woocommerce/:
pnpm wp-env run cli wp eval-file wp-content/plugins/woocommerce/tests/smoke-test-patterns/wp-cli-insert-patterns.php

# Or with standalone WP-CLI:
wp eval-file plugins/woocommerce/tests/smoke-test-patterns/wp-cli-insert-patterns.php
```

This creates a draft page titled "WooCommerce Patterns Smoke Test" with all patterns inserted.

## Notes

- These are **dev-only utilities**, not production code.
- The browser snippet works with any WooCommerce version that registers block patterns.
- The WP-CLI script creates a draft page (not published).

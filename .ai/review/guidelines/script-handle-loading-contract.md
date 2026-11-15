# Script handles and loading strategy

**Source:**

- #67365: deprecated `wc-blocks` handles made admin-only, breaking payment extensions that depended on them on the frontend.
- #64431 (legacy shim loading strategy) reverted in #67309.
- #68379: reverted moving the tokenization script to the footer with defer.
- #61599 / #61589 (10.3.3): deprecated handles added back for extensions; #61693 (10.3.4) removed the notices.
- #58993 / #58971 (9.9.5): always load block styles for classic themes.
- #61837 (10.3.5), #60758, #61205: respect theme support; block-theme-only dequeue.
- Earlier review findings: a handle hoisted to head ran `$(document.body).on` before the body existed.

**What broke:** Extensions and gateways list WC script handles as dependencies. Renaming, gating, deferring, or moving those handles broke checkout on sites core never tests.

**Pattern to catch:** Renaming or removing a handle, changing its deps, registering it only in some contexts, changing `strategy` / `in_footer`, or gating by `wp_is_block_theme` / `current_theme_supports`.

**How to confirm:** Grep for the handle name as a dependency in-tree; extensions are out of tree, so no match proves nothing. Check inline scripts and localized data still attach. Check both classic and block themes.

**Don't flag when:** New handles. Handles that were never public (undocumented, unreleased).

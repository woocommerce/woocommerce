# Stylesheet layout regressions

**Source:**

- #58842: Product Price block class names.
- #64890: focus indicator.
- #55366 (9.6.2): secondary color variable.
- #57275 (9.8.2): Product Collection nav margin.
- #67814: alignment change reverted.
- #68790 (11.1.1): Mini Cart drawer under the footer.
- #63825 (10.6.2): WP 7.0.
- Earlier review findings: Cart/Checkout editor lost its gutter: `column-gap` on an element the editor forces to `display: block`; Mini-Cart title spacing removed.

**What broke:** Style edits removed spacing, changed display, or renamed classes themes target. Editor and frontend layouts drifted apart.

**Pattern to catch:** Removing spacing or gap rules, changing `display`, renaming or removing class names themes target, or selectors that apply only in the editor or only on the frontend.

**How to confirm:** Where else does the removed rule's spacing come from? Does the editor wrapper change `display`? Grep the class name in PHP render callbacks and templates.

**Don't flag when:** New selectors scoped to new blocks.

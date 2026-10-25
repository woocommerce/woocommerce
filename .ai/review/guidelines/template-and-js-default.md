# Template markup and no-JS defaults

**Source:**

- #63171 / #63194 (10.5.1), #63262 (10.5.2): the variable add-to-cart button shipped `disabled` until `wc-add-to-cart-variation` enabled it; page builders without that script were stuck.
- #68020: fixed template markup contracts.
- #68213: extension markup broke variable product forms.
- #68766: reverted a new template filter.

**What broke:** A template change made the default state broken until a script ran, and sites that did not load that script lost the button. Themes keep old template overrides, so markup changes also split behavior across sites.

**Pattern to catch:** In `templates/`, changes to markup, ids, classes, or hooks, or new required JS. Any default flipped from working to "broken until JS runs" (disabled, hidden). Theme overrides keep the old markup until updated.

**How to confirm:** Does the change need JS to work? Is it guarded with `wp_script_is( ..., 'enqueued' )`? Was `@version` bumped? Do hooks still fire in the same order?

**Don't flag when:** Copy-only template changes with a version bump. Progressive enhancement that keeps the no-JS state working.

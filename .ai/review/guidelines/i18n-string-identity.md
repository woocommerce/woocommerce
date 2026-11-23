# Translation string identity

**Source:**

- #52448: JS translations broke when the plugin folder was renamed.
- #61918 (10.3.5): concatenated translation scripts in Mini-Cart.
- #64881: early textdomain load.
- Earlier review findings: changing the cross-sells heading msgid showed English in some locales; removing a leading space ran sentences together.

**What broke:** Editing a released string drops its translations until retranslated, so stores saw English. Concatenated fragments and broken script translation loading did the same.

**Pattern to catch:** Editing released msgids, concatenating translated fragments, or changing how script translations load (`wp_set_script_translations` handles and paths, textdomain timing).

**How to confirm:** Is the change copy-only for a released string? Suggest `_x` context or accept it with a note. Check `wp_set_script_translations` handles and paths still match the built script.

**Don't flag when:** New strings. Typo fixes the PR calls out.

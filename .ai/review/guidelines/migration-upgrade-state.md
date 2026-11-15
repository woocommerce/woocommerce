# DB updates and migrations on real data

**Source:**

- #57257 (9.9): update routine fatal.
- #61022 (10.2.1): a refunded order may not exist.
- #68772 (HPOS migration date) reverted in #69025.
- #65146: index column restored.
- #58498: uninstall fatals.
- Earlier review findings: renaming migration key `11.2.0` to `11.2.0-1` re-ran 8 sibling migrations; one transient DB error abandoned the reset; PRIMARY KEY change.

**What broke:** Update routines assumed every row existed and had the new shape, re-ran because a version key was renamed, or gave up on one error. Index changes on large tables had to be undone.

**Pattern to catch:** `wc_update_*` callbacks, `db_updates` keys, `dbDelta` / schema, and uninstall. Watch for:

- assuming every row or entity exists and has the new shape;
- renaming or reordering version keys;
- all-or-nothing loops;
- index or key changes on large tables.

**How to confirm:** Read `$db_updates` in `class-wc-install.php` for key changes. Check per-row error handling and batching. Run the routine in your head on a store with orphans and old values.

**Don't flag when:** New tables or columns created with defaults. New idempotent update callbacks guarded by existence checks.

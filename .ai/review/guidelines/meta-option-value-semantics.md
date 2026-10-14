# Meta and option value semantics

**Source:**

- #65766 deleted stale variation attribute meta; reverted in #66975.
- #58804 (9.9.4): fix storing an empty shipping rate cost as `'0'` instead of `''`; readers treated the two differently.
- #66848: malformed gateway settings fatalled.
- #62095 (10.3.6): malformed transient on PHP 8.4.
- #67164, #65703.
- Earlier review findings: clearing an image with int 0 deleted `_thumbnail_id` instead of storing `"0"`; avatar default `'blank'` became `''`, losing the fallback; a `url` key dropped from a released serialized option.

**What broke:** Writes changed what a falsy value means, keys vanished from released stored arrays, or reads trusted a stored shape that real sites did not have.

**Pattern to catch:** - Changing what a falsy value means on write: `0`, `''`, `null`, delete vs store.

- Removing keys from a stored array.
- Deleting "stale" meta that extensions may own.
- Reading options, transients, or meta and assuming a type. `get_post_meta( ..., true )` returns `''` when missing, not `null`.

**How to confirm:** Grep the key string to list its readers. Check how each treats `''` vs missing vs `'0'`. For deletes, ask who else writes that key.

**Don't flag when:** New keys added to a stored array. Values written only by new, unreleased code.

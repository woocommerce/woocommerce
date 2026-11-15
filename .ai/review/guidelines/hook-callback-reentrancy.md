# Hook callbacks that recurse through other plugins

**Source:**

- #70198 (11.3.0 RC-1, rolled back on Atomic): a `get_the_guid` callback read `_wp_attached_file` meta. Real Media Library's `get_post_metadata` callback for that key calls `get_the_guid()`, so stores running both ran out of memory on most pages with media. Fixed in #70782.

**Pattern to catch:** A hunk adds or changes a callback that, inside the callback, reads filtered data or calls something that fires the same hook. The risk is higher when:

- the hook runs many times per request. The following examples are not an exclusive list, but are examples of types of hooks that might be affected. `get_the_guid`, `attachment_link`, `post_link`, `the_permalink`, `the_content`, `get_post_metadata`, `option_*`, `user_has_cap`, `map_meta_cap`, `home_url`;
- the read is post, user, or term meta, an option, a permalink or GUID, `current_user_can()`, or `home_url()`;
- the read happens before cheap checks such as the post type or values the hook already passed in.

**How to confirm:** For each read inside the callback, name the filters it fires (`get_{$meta_type}_metadata`, `option_{$name}`, `user_has_cap`, and so on). Ask whether a plausible callback on those filters would fire the hook under review, for example a media plugin reading a GUID or permalink while filtering attachment meta. Then check for a re-entrancy guard around the read that resets in `finally`, and that the nested call returns a value that keeps the outer result correct. An empty in-tree grep for such callbacks proves nothing; they live in other plugins.

**Don't flag when:** The callback only uses its arguments and functions that fire no hooks, the read cannot fire the hook again (for example, a direct `$wpdb` query), or a guard already covers the read.

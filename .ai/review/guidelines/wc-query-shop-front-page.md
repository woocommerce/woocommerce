# Shop page and front-page main query

**Source:**

- #61788: a slug fallback entered the front-page shop branch but left `pagename` set, so the query returned zero products.
- #63202 / #63205 (10.5.1): the Divi shop page vanished; fixed by removing the fallbacks.
- #46429 (8.8.1): wrong Shop title.
- #64117: shop rewrite corruption from WP-CLI and cron.

**What broke:** New ways into the shop/front-page main-query branch skipped that branch's cleanup, so the shop showed no products or the wrong page on some sites.

**Pattern to catch:** A new fallback, a widened guard, or a new condition that enters an existing main-query branch. For each cleanup line in the branch (`page_id`, `pagename`, `post_type`), ask what state the new entry path carries in.

**How to confirm:** Trace `GET /` with `page_on_front` set to the shop, resolved by ID, by slug, and by a page builder. Check `$q->get( 'pagename' )` after the branch.

**Don't flag when:** Changes outside the main query (`is_main_query()` false).

# Blocking remote calls on page renders

**Source:**

- #69160: the Helper updates cache moved to a one-week lifetime but counts as fresh for 12 hours, so
  `get_updates_count()` made a blocking WooCommerce.com request while an admin page rendered. Fixed in
  #69454.
- Earlier review finding: a tracker POST made blocking now ran inside the WooCommerce.com connect
  request.

**What broke:** A remote request that used to be cached, deferred, or non-blocking started running
synchronously while a page rendered. A slow or unreachable remote host then delayed that page.

**Pattern to catch:**

- A cache lifetime and its freshness check drifting apart (`set_transient( ..., WEEK_IN_SECONDS )`
  next to an "older than 12 hours" check), so a stale-but-present cache falls through to a live
  request.
- `'blocking' => false` removed, or a remote call moved from cron or AJAX into a render hook,
  `admin_menu`, `admin_notices`, or a menu badge count.
- A new `wp_remote_*` or `WC_Helper_API` call on a path that renders a page.

**How to confirm:** Follow the call from the render hook to the request. Read the cache read and its
guards on the way: what makes it return early, and when can that be skipped? Is anything bounding how
often it runs (a separate count cache, a backoff)?

**Don't flag when:** The request already ran on that path before the change, it runs only in cron,
Action Scheduler, AJAX or REST handlers, or the cache in front of it is unchanged.

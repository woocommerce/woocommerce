# Client request failure handling

**Source:**

- #69101: autofill not restored when address suggestions failed.
- #62416 (10.4.1): cached API request in the iAPI cart store.
- #68372: corrupt localStorage cart cache.
- #60394 (10.1.1).
- Earlier review findings: dismiss POST with no rejection handler; post-import cleanup moved to client AJAX with no retry; tracker POST made blocking.

**What broke:** Client requests failed without a fallback, blocked the UI on non-critical calls, or served stale or corrupt cached data. Work moved from server to client lost its delivery guarantee.

**Pattern to catch:** Moving work from server to client. Fetches without `.catch` or a fallback. Blocking the UI on non-critical requests. Cached or `localStorage` / `sessionStorage` data read without validation.

**How to confirm:** What happens on a network error, a 4xx, or malformed JSON? Is there still a server-side guarantee for the work?

**Don't flag when:** Fire-and-forget telemetry that is explicitly best-effort.

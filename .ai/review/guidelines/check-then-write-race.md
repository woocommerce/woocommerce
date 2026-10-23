# Check-then-write races

**Source:**

- #67931: reverted a stale-reservation change.
- #54269 (9.5.2): limited-usage coupons applied concurrently.
- #61582 (10.3.2): a race deleted orders.
- #66237: duplicate download permissions.
- Earlier review findings: excluding a shopper's own stale holds let them double-reserve or oversell; two concurrent first sends both INSERT.

**What broke:** Code read a count or row and then wrote based on it, with no lock or atomic update. Concurrent checkouts oversold stock, overused coupons, duplicated rows, or deleted live orders.

**Pattern to catch:** SELECT then INSERT/UPDATE without a unique key, a lock, or atomic SQL. Covers stock reservation, coupon usage counts, idempotency meta, and order cleanup. Also exclusions that widen what one customer can hold.

**How to confirm:** Look for `INSERT ... ON DUPLICATE KEY`, `GET_LOCK`, a unique index, or a conditional `UPDATE ... WHERE`. Picture two requests interleaving between the read and the write.

**Don't flag when:** Single-writer cron or admin-only paths that hold a lock.

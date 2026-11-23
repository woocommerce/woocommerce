# HPOS sync and compatibility mode

**Source:**

- #61293 (full CPT read for sync-on-read) reverted by #62402; #62408 (10.4.1) prevented unnecessary writes.
- #62514 / #62532 (10.4.3): the modified date caused sync loops.
- #61381: placeholder post synced into the order.
- #66510: legacy meta cache.
- #68206: notes lost on untrash.
- #63175: sync-on-read turned off by default.

**What broke:** Sync between the HPOS tables and posts wrote on every read, looped on modified dates, or copied placeholder data into real orders. Fixes were reverted and the feature was turned off by default.

**Pattern to catch:** Any change to HPOS / CPT sync, compatibility mode, placeholder posts, or the compare-then-write step. Sync must compare and write only on a real difference. Array or object equality and dates must not look "changed" on every read.

**How to confirm:** Trace a read with compatibility mode on and HPOS authoritative, then CPT authoritative. Does a read cause a write? Does that write bump the modified date so the next read writes again?

**Don't flag when:** Changes behind the off-by-default sync-on-read flag, if the gate covers every changed path. If behavior still differs, report at Medium.

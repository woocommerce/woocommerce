# Hook timing and temporary global state

**Source:**

- #69538: building the mailer on `woocommerce_init` froze `woocommerce_email_classes` before features added their emails.
- #64881: early textdomain load.
- #63066: REST controllers built before WC loaded.
- #57255 (9.8.2): cart loaded too early.
- #69148: email render state not restored when another plugin threw.
- #67312: cart context not restored after hydrating a Store API route.
- Earlier review findings: `render_block` filter left removed on throw; taxonomy unregistered for the rest of the request.

**What broke:** Work moved to an earlier hook ran before what it depended on was registered. Temporary changes to filters, globals, or cart context were not undone when code threw or returned early.

**Pattern to catch:** - Moving work to an earlier or later hook, or building a singleton early.

- Changing global state temporarily without restoring it on every exit: `remove_filter`, `$GLOBALS`, `switch_to_*`, registered taxonomies, cart or customer context. Look for a missing `finally`.

**How to confirm:** Which hook and priority runs the code, and what registers later on `init` (features hook in at priority 1+)? For temporary state, find every return and throw between the change and the restore.

**Don't flag when:** Restore in `finally`, or state that is request-local and never read after.

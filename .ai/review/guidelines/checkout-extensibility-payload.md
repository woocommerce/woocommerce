# Checkout extension payloads

**Source:**

- #69521: reverted conditional checkout field rules.
- #69129: crash when an address field filter recalculated totals.
- #67100: fields outside the billing/shipping fieldsets.
- #51630 (9.3.3): Additional Fields API on virtual products.
- #59693.
- Earlier review findings: name equality badged compatible gateways; strict `typeof number` on an extension payload; non-boolean `isVisible` deleted; server `empty( hidden )` vs client `true === hidden`.

**What broke:** Block checkout code read data that extensions write and tightened the type checks or dropped values it did not expect. Server and client also disagreed on the same flag.

**Pattern to catch:** Client code reading extension-registered data (checkout filters, payment method registrations, additional fields, `extensions` data) with tighter checks, or dropping unknown values. Server and client predicates that differ for the same flag.

**How to confirm:** List what PHP and extensions send (`1`, `'1'`, `true`, `'yes'`). Compare the server and client predicates side by side.

**Don't flag when:** Validation on values only core writes.

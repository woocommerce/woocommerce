# Payment callbacks and capture requests

**Source:**

- #63834: a PayPal webhook payload whose `links` was not an array fatalled the handler.
- #68307: an array order key on the PayPal return path reached `hash_equals()` and fatalled on PHP 8.
- #62304, #63464: capture on order status change asked PayPal again for an authorization or order that had returned 404, on every later status change, until a meta flag recorded the check.
- #62582: capturing an authorization PayPal had already captured returned `AUTHORIZATION_ALREADY_CAPTURED`, and the gateway treated it as a failure.
- #63203: a capture failed on PayPal's duplicate invoice ID check; the fix patches a new invoice ID and retries once.

**What broke:** Gateway code assumed one clean, well-formed exchange. Real callbacks arrive malformed and repeated, and capture runs again on every order status change, so a permanent error or an already-done result kept coming back.

**Pattern to catch:**

- A handler that changes order state (`payment_complete()`, `update_status()`, refunds, stock) without first proving the sender: a PayPal post-back that returns `VERIFIED`, a signed-request check in the REST `permission_callback`, or a server-side lookup of the payment.
- Amount or currency taken from the payload without comparing it with the order (`validate_amount()`, `validate_currency()`).
- A state change or gateway call that a repeated event or a later status change can trigger again, with no record that it already ran or already failed for good.
- A gateway error handled as one generic failure: a permanent error (404) retried on every trigger, an "already done" answer reported as failed, or a retry with no bound.
- Payload or request fields read without a type or shape check.

**How to confirm:** Send the handler the same event twice, then an older event after a newer one. Does the order change state again? Move the order through processing and completed twice: how many gateway requests go out? Remove the sender proof: can an anonymous request reach the state change? Send a field with the wrong type: does the handler log and return, or fatal?

**Don't flag when:** A request that no callback or status change can repeat (building the checkout redirect). A duplicate check already present in a shared helper the handler calls. A missing nonce on an IPN, webhook, or gateway return: sender proof is the guard there.

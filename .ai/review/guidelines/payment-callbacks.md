# Payment callbacks: IPN, webhooks and return URLs

**Source:**

- #63834: a PayPal webhook payload whose `links` was not an array fatalled the handler.
- #63464: a 404 from PayPal's order details led to repeated capture requests for the same order.
- #63203: a retried capture failed on PayPal's duplicate invoice ID check.
- #68307: an array order key on the PayPal return path reached `hash_equals()` and fatalled on PHP 8.

**What broke:** Gateway callbacks assumed one clean, well-formed delivery. Real callbacks arrive malformed, repeated, retried, and out of order.

**Pattern to catch:**

- A new or changed IPN, webhook, or return handler (`WC_Gateway_Paypal_IPN_Handler`, `WebhookHandler`, `class-wc-rest-paypal-webhooks-controller.php`, `woocommerce_api_*`).
- A handler that changes order state (`payment_complete()`, `update_status()`, refunds, stock) without first proving the sender: a PayPal post-back that returns `VERIFIED`, a signed-request check in the REST `permission_callback`, or a server-side lookup of the payment.
- Amount or currency taken from the payload without comparing it with the order (`validate_amount()`, `validate_currency()`).
- A state change with no "already processed" check, so a repeated or late event completes, captures, refunds, or restocks twice.
- Payload fields read without a type or shape check.

**How to confirm:** Send the handler the same event twice, then an older event after a newer one. Does the order change state again? Remove the sender proof: can an anonymous request reach the state change? Send a field with the wrong type: does the handler log and return, or fatal?

**Don't flag when:** Outbound requests to the gateway (building the order, capture calls) that change no order state from a callback. A duplicate check already present in a shared helper the handler calls.

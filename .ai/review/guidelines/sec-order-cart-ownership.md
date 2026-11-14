# Order and cart ownership

**Source:**

- #62557 (10.4.3): guest orders readable without key plus billing address.
- #64568: non-`WC_Order` objects in Store API order auth.
- #67549 (11.0.1): coupons on order payments.
- #67550: cart token source.
- #68397: extension data leaking between namespaces.
- #67557 (11.0.1): password-protected short descriptions.
- #67308: excluded IDs leaking into search.
- #65368: exception info leak.
- #68426: protected reviews cached for other users.

**What broke:** Order, cart, and customer data was reachable without proving ownership, or leaked through errors, caches, and search. Several shipped as security point releases.

**Pattern to catch:** New reads of order, cart, or customer data, or changes to how ownership is proved (order key, `hash_equals`, cart token, customer ID). Errors or caches that expose other users' data.

**How to confirm:** Can a guest or another customer reach the path? Is the key compared with `hash_equals` on strings? Does the cache key include the user?

**Don't flag when:** Admin-only paths with capability checks.

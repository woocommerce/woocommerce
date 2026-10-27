# Behavior gated on an inferred proxy

**Source:**

- #66488 (issue #66613): "no shipping lines" and "billing == shipping" proxies were always true on shipping-disabled stores, because the Store API copies billing into shipping when `needs_shipping()` is false.
- Earlier review findings: NULL customer_id read as guest; name equality flagged gateways; template inferred from the request-time global product; subtotal != total read as a manual edit.

**What broke:** Logic inferred intent from the shape of stored data rather than an explicit flag. Some store setups always satisfy the proxy, so the branch fired for everyone.

**Pattern to catch:** A new or changed conditional that infers intent from stored state or field equality: no shipping lines means virtual, billing equals shipping means copy, NULL customer means guest, same name means same gateway, a request global means the template.

**How to confirm:** List every writer of the compared state. Then list setups where the proxy is always true: shipping off, taxes off, guest checkout, HPOS on or off, admin or API edits, multicurrency. Cite the producer.

**Don't flag when:** Predicates on explicit flags or meta written for this purpose.

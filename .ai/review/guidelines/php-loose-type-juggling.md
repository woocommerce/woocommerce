# Loose PHP values reaching strict code

**Source:**

- #65957: numeric `post_status` keys from `get_post_stati()` became ints, a strict_types TypeError; the bad value was already cached (counters disabled in #67058).
- #57596: `price_decimals` became `false` before `woocommerce_shipping_method_add_rate_args`.
- #59479 (10.0.1): strict-types fatal hotfix.
- #66219 (10.9.3): a mail callback returned a non-bool.
- #67763: a non-string payment method came back from a filter.
- #64012: `""` offset added to an int.
- #65507: string minus string on refunds.
- #65496: a cached parent ID read back as a string.
- #51751: `template_include` returned null.
- Earlier review findings: divide by `per_page` = 0.

**What broke:** Values from loose sources reached `declare(strict_types=1)` code, typed params, or arithmetic and fatalled. In #65957 the bad value was cached too, so the fatal outlived the request that made it.

**Pattern to catch:** A value from a loose source reaches strict_types code, a typed param or return, arithmetic, or a divisor without being normalized.

- Loose sources: array keys (PHP turns `'123'` into `123`), `wp_cache_get`, filter returns, options, meta, request input, and `''` / `null` / `false` stand-ins for numbers.
- The reverse also breaks: a new `is_string()` / `is_array()` guard that drops values extensions used to return.

**How to confirm:** Check the file for `strict_types`. Grep the value's producers: `apply_filters` sites, `get_option` defaults (often `''`), and `wp_cache_get` writers. For array keys, ask whether a key can be a numeric string. Check divisors for a zero guard.

**Don't flag when:** The value is cast or validated on the same path (`(string)`, `absint`, `wc_format_decimal`) and that also covers the cached readback. A typed param on a private method whose callers all pass literals.

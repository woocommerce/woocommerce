# Request input shape and over-strict validators

**Source:**

- #69015 (`rest_route[]`), #68584 (order-received `key[]`), #63723 (`urldecode()` given an array), #56768 (filter attributes), #55586 (`orderby[]`), #68270 (wc-auth): fatals when a param was an array.
- #56049: malformed pagination input.
- #69662 (11.2.1): validator rejected 4-digit AR postcodes.
- #60348 (10.1.1): flat-rate formula rejected.
- #57594 (9.8.3): hidden postcode field validated.
- #52361 (9.4.1): int param rejected; #58693: float quantities rejected.
- Earlier review findings: 500 on ordinary bad input; `glob()` and stream wrappers.

**What broke:** Two shapes. Request params assumed to be strings fatalled when sent as `x[]=`. New or tighter validators rejected input that real stores, locales, or extensions had always sent.

**Pattern to catch:** - Reading `$_GET` / `$_POST` / `get_param()` / `get_query_var()` and passing it straight into a string function.

- Adding or tightening a validator (regex, `true ===`, enum, required) on a field that used to accept more.

**How to confirm:** Can the param be sent as `x[]=`? Is there an `is_string` or `wc_clean` before the string op? For validators, list what producers send today: extensions, old data, locales (`1` / `'1'` / `'yes'`, older postcode formats).

**Don't flag when:** The REST schema already declares the type, so WP rejects arrays before the callback. A validator that only runs on new input with no existing writers.

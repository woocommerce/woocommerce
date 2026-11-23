# Auth tokens, nonces and cookies

**Source:**

- #68827 (11.1.1): stricter dispatch check in REST API key auth.
- #69523: API key nonce handling.
- #67408 (11.0.1): session cookie hashing.
- #68862 (11.1.1): session cookie validation, mobile app login, legacy options API.
- #68270: array wc-auth params.
- #67577: credential source when hosts strip `Authorization`.
- Earlier review findings: `hash_equals` TypeError kept.

**What broke:** Changes to how API keys, nonces, session cookies, and app logins are parsed and compared needed security point releases, and some locked out real clients.

**Pattern to catch:** Changes to credential parsing or comparison (`hash_equals` type safety), cookie hash format, nonce verification, or accepted credential sources (header vs URL).

**How to confirm:** Non-string inputs, timing-safe comparison, and whether old cookies or keys still validate after an upgrade (or are meant not to).

**Don't flag when:** Refactors with identical comparison and source logic.

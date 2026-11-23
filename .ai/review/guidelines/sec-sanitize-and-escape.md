# Sanitizing and escaping

**Source:**

- #66820 / #67476 (11.0.1): store notice entity-encoded HTML executed.
- #68209: settings attributes and currency symbols unescaped.
- #50010 / #50015 (9.1.4): block attributes unescaped.
- #48348 (8.9.3): order attribution.
- #68475: endpoint slug not sanitized.
- #68802: Reply-To names; #67893, #67925, #67079.
- #68961 (11.1.2): reviews through the comment pipeline.
- #68186, #66060: double escaping.
- Earlier review findings: endpoint slug; `wp_kses_post` / `is_string` applied to filter output.

**What broke:** Output lost late escaping, new settings skipped the sanitizing their siblings had, and entity decoding re-enabled HTML. The reverse also shipped: escaping applied to data bound for JSON or another filter corrupted it.

**Pattern to catch:** Output without late escaping. Saved settings without sanitizing like sibling fields. Decoding entities before render. Escaping data that goes to JSON or another filter.

**How to confirm:** Follow the value to its sink (HTML, attribute, URL, JSON). Compare with sibling fields' sanitize callbacks.

**Don't flag when:** Escaping already present at the sink. Data rendered as React text nodes (auto-escaped) unless through `dangerouslySetInnerHTML` or `RawHTML`.

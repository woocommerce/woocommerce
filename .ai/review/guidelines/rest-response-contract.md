# REST, Store API and export response shape

**Source:**

- #68186: restored plain-text product search responses.
- #50881 (9.2.2): partly reverted a system_status change.
- #52174: restored `total_formatted` in exports.
- #69161: Store API address encoding.
- #56048 (9.7.1): `decodeEntities` handling.
- Earlier review findings: order `item_data` changed from an ID-keyed object to a list; `static::IDENTIFIER` rebound the order item `extensions` schema; new setting type unmapped in the Settings UI schema; CSV column inserted mid-file.

**What broke:** Response shapes changed under clients: keys, list vs map, entity encoding, schema namespace, and CSV column order. Headless, mobile, and import tools broke and fields had to be restored.

**Pattern to catch:** Changing keys, nesting, list vs map, types, entity encoding, schema namespace or `IDENTIFIER`, column order, or status code of a REST, Store API, or CSV/export response.

**How to confirm:** Compare `get_item_schema()` and `prepare_item_for_response()` output for a sample entity. Grep JS consumers in `client/blocks` and `packages/js`. Headless and mobile clients are out of tree, so no in-tree consumer proves nothing.

**Don't flag when:** Additive fields. Changes to an endpoint marked experimental or unreleased, with the gate verified.

# Email Editor Parity tool

Visual parity checker for the WooCommerce email editor. It compares the
Gutenberg editor canvas with the rendered email HTML for the same content,
and reports layout differences: widths, paddings, borders, offsets, and
horizontal overflow.

## How it works

For each fixture (a file with block markup) the tool:

1. Seeds a `woo_email` post via the e2e test-helper plugin REST API.
2. Opens the post in the email editor and screenshots the canvas
   (`.is-root-container` inside `iframe[name="editor-canvas"]`).
3. Opens the rendered preview (`?preview=true`, the same HTML used for real
   emails) and screenshots `.email_content_wrapper`.
4. Extracts per-block box geometry from both surfaces and compares them
   with tolerances (strict horizontally, loose vertically).
5. Runs a pixel diff and checks for horizontal overflow.
6. Writes an HTML report with side-by-side, overlay, and diff views.

Dynamic content is not compared 1:1 — only boxes and pixels. Vertical
tolerances absorb small text-rendering differences.

## Requirements

-   The WooCommerce e2e wp-env running on port 8086:
    `pnpm --filter=@woocommerce/plugin-woocommerce env:e2e:start`
    (needs Docker running).
-   Monorepo dependencies installed (`pnpm install` at the repo root).

## Usage

From `tools/email-editor-parity`:

```bash
pnpm install:browser # once: download chromium for playwright
pnpm compare         # run all fixtures
pnpm compare nested  # only fixtures whose filename contains "nested"
pnpm clean           # delete old runs from out/, keep the latest
```

`pnpm clean --keep=3` keeps the three most recent runs;
`pnpm clean --all` deletes everything.

Flags and env vars:

-   `--cleanup` — delete the seeded posts after the run (default: keep them,
    so you can open the editor/preview links from the report).
-   `EP_BASE_URL`, `EP_USERNAME`, `EP_PASSWORD` — target another site
    (default `http://localhost:8086`, `admin`/`password`).
-   `EP_HEADED=1` — run with a visible browser.

Results land in `out/<timestamp>/report.html`. Exit code is 1 when any
fixture has geometry failures.

## Fixtures

Files in `fixtures/*.html` contain raw block markup (what `post_content`
stores). Each known parity bug should become a fixture. To create one,
build the content in the email editor, then copy the markup from the code
editor view (Options → Code editor).

Note: hand-written markup must match the block's expected serialization,
or the editor shows "block recovery" warnings. The tool detects these and
adds a warning to the report.

## Known systematic divergences

Some differences are by design and the tool is tuned around them:

-   The email renders blocks as nested tables; the editor uses divs and flex.
-   Root horizontal padding is one rule in the editor, but distributed
    per-block in the email (`Spacing_Preprocessor`).
-   `clamp()` font sizes are flattened to the max value only in the email.
-   Computed padding/border can sit on a different wrapper element in the
    email markup — the report shows these as informational rows; the box
    positions are the real signal.

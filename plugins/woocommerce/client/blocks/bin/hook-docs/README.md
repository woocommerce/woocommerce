# Hook docs generator

`pnpm --filter=@woocommerce/block-library build:docs` regenerates the hook documentation:

1. `composer install` (in `client/blocks`) installs [`wp-hooks/generator`](https://github.com/wp-hooks/generator). A small patch (`wp-hooks-generator.patch`, applied via `cweagans/composer-patches`) keeps the `@return`/`@throws` tag types the docs render, downgrades unknown-docblock-tag fatals to warnings, and makes the generator emit the docblock text as raw Markdown instead of Parsedown-rendered HTML. The generator declares `php >= 8.3`, but its code runs fine on the repo's PHP 8.1 toolchain (verified: byte-identical output), so `composer.json` pins `config.platform.php` to `8.3.0` and disables the runtime platform check — revalidate this if `wp-hooks/generator` is ever bumped past 1.0.2.
2. `wp-hooks-generator` scans the PHP sources in `plugins/woocommerce/src` and writes `data/actions.json` and `data/filters.json` — a gitignored intermediate that the next step consumes in the same run. The scan is limited to the WooCommerce Blocks PHP (`src/Blocks` and `src/StoreApi`) by the `extra.wp-hooks.ignore-files` list in `client/blocks/composer.json` — if a new top-level directory is added to `plugins/woocommerce/src` that should not appear in these docs, add it to that list.
3. `node ./bin/hook-docs` renders the JSON into `docs/third-party-developers/extensibility/hooks/actions.md` and `filters.md`, and `bin/add-doc-footer.sh` re-appends the feedback footer to the regenerated files. Docblock text passes through as Markdown; `utilities/docblock-to-markdown.js` only normalizes it for the generated files (soft-wrapped prose is joined into single-line paragraphs, list items keep their own lines with a blank line before the list, fenced code blocks pass through untouched — nested list indentation is not preserved).

Do not edit `actions.md` or `filters.md` by hand — fix the source docblocks instead and regenerate.

Notes:

- Hooks without a docblock at the call site are skipped by the generator (it warns during the build), and `internal_`-prefixed hooks are filtered out of the generated reference on purpose.
- `composer-patches` v1 does not track patch content in `composer.lock`, so after editing `wp-hooks-generator.patch` run `rm -rf vendor && composer install` to re-apply it — a plain `composer install` with an existing `vendor/` is a no-op.
- Docblock descriptions and tag text should be valid Markdown — it lands in the generated docs as-is. Use backticks around literal angle-bracket placeholders (`` `<hook-name>` ``), otherwise markdownlint flags them as inline HTML.
- `composer.lock` pins `erusev/parsedown 1.8.0-beta-7` because `wp-hooks/generator` requires that exact beta; with the patch it is only used internally for hook-alias detection, never for the doc output, and it is dev tooling only and never ships.

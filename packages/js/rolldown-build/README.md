# @woocommerce/rolldown-build

Builds the WooCommerce blocks and admin scripts with [Rolldown](https://rolldown.rs). It writes the files PHP enqueues: classic scripts that set `wc.*` globals, `.asset.php` dependency lists, lazy chunks, stylesheets with RTL versions, and translator comments that `wp i18n make-pot` reads.

This package holds the shared parts. Each plugin package keeps its own entries and options:

- Blocks: `plugins/woocommerce/client/blocks/bin/rolldown/`
- Admin: `plugins/woocommerce/client/admin/bin/rolldown/`

## How it works

Rolldown cannot write classic scripts that share code: IIFE output cannot be split, and ES modules load deferred, which breaks extensions that read `wc.*` globals when their own script loads. So Rolldown writes CommonJS chunks, and `lib/classic.mjs` links them:

- Each entry becomes one classic script. Shared chunks named in the config (such as `wc-blocks-vendors`) become their own classic scripts and are listed as dependencies in `.asset.php`, like WordPress packages.
- A small runtime registry, one per build, resolves `require()` calls between chunks and reads externals such as `wp.data` from `window`.
- Lazy imports load files from `chunks/` through the registry.
- Styles go through `lib/css.mjs`: Sass, PostCSS, cssnano and rtlcss, joined per output file in import order.

Other files:

- `lib/shared.mjs`: externals rules, the same as the dependency extraction webpack plugin.
- `lib/prop-types.mjs`: removes propTypes from production builds.
- `lib/analyze.mjs`: the bundle report.

## Commands

From the repository root:

```sh
pnpm --filter=@woocommerce/plugin-woocommerce build      # everything
pnpm --filter=@woocommerce/block-library build           # blocks
pnpm --filter=@woocommerce/admin-library build           # admin
pnpm --filter=@woocommerce/block-library start           # blocks, watch mode
pnpm --filter=@woocommerce/admin-library watch:build     # admin, watch mode
```

The build scripts also run directly, for example `node bin/rolldown/build.mjs --out=/tmp/blocks` from `client/blocks`.

Options:

- `--dev` or `NODE_ENV=development`: a development build with source maps.
- `--out=<dir>`: write somewhere other than `plugins/woocommerce/assets/client/`.
- `ASSET_CHECK=true`: one `assets.json` file instead of `.asset.php` files (`build:check-assets`).
- `WP_BUNDLE_ANALYZER=1` or `ANALYZE=1`: writes `bundle-report.html` and `bundle-report.json` in the output folder, listing each script and the modules inside it.

Watch mode rebuilds on changes to sources, Sass partials, Interactivity API script modules, the dependency detection script and `block.json` files.

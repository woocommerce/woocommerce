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

## Hot reload for wc-admin

`client/admin` has a Vite dev server for the `app`, `embed` and `wp-admin-scripts/*` entries. Package entries such as `components` cannot use it: other scripts read their `wc.*` globals when they load, and Vite serves ES modules, which run later.

1. Build admin once: `pnpm --filter=@woocommerce/admin-library build`.
2. From `client/admin`, point the entries you work on at Vite: `node bin/rolldown/dev-stub.mjs app product-tour`.
3. In `wp-config.php`, set `define( 'SCRIPT_DEBUG', true );`, and copy `bin/rolldown/mu-plugin-example.php` to `wp-content/mu-plugins/`. It installs the React DevTools hook before React loads, which React Fast Refresh needs.
4. Start the server: `pnpm --filter=@woocommerce/admin-library dev:hot`. The browser must reach `http://localhost:5173`.
5. Open the page that loads the entry. Edits to its source files show up without a page reload.

To undo, build admin again and remove the mu-plugin and `SCRIPT_DEBUG`.

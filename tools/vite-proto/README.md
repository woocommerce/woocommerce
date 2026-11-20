# Vite 8 / Rolldown build prototype

A prototype that builds the WooCommerce frontend (`client/blocks` and `client/admin`) with Rolldown instead of webpack. It writes the same files PHP enqueues, so the plugin runs on its output without PHP changes. Not for production. See `NOTES.md` for results and known gaps.

## Setup

1. Install the monorepo once, from the repository root: `pnpm install`. The prototype uses the repo's Sass, PostCSS, rtlcss and WordPress packages from there.
2. Install the prototype's own tools (Vite, Rolldown, sass-embedded), from this folder:

```sh
cd tools/vite-proto
npm install
```

## Build

From `tools/vite-proto`:

```sh
npm run build          # blocks, then admin
npm run build:blocks   # about 9 s
npm run build:admin    # about 8 s
```

The output goes where webpack writes it: `plugins/woocommerce/assets/client/blocks` and `plugins/woocommerce/assets/client/admin`. It replaces the webpack output there. To get webpack's files back, run `pnpm --filter=@woocommerce/plugin-woocommerce build` (or the package's `build:project:bundle`).

To build somewhere else, for example to compare with webpack:

```sh
npm run build:blocks -- --out=/tmp/rolldown-blocks
npm run build:admin -- --out=/tmp/rolldown-admin
node compare.mjs /path/to/webpack/admin /tmp/rolldown-admin   # file set, .asset.php, sizes
node compare-css.mjs /path/to/webpack/admin /tmp/rolldown-admin
```

Other `build:blocks` options: `--only=frontend,styles` (names of single configs), `--sass=sass-embedded` (faster, but CSS is not byte-identical to webpack).

### From the normal build

Set `WC_BUNDLER=rolldown` to make the `build:project:bundle` scripts of `client/blocks` and `client/admin` run this prototype instead of webpack. Run `npm install` here first. For example, from the repository root:

```sh
WC_BUNDLER=rolldown pnpm --filter=@woocommerce/plugin-woocommerce build
```

Without the variable, nothing changes. The prototype is not a pnpm workspace package: adding it rewrote about 3,200 lines of `pnpm-lock.yaml`, mostly peer changes from `sass-embedded`.

### Other build options

- `--dev`: a development build with source maps, like `NODE_ENV=development`. `npm run build:dev` builds both.
- `ASSET_CHECK=true`: one `assets.json` file instead of `.asset.php` files, like the webpack `build:check-assets` script.
- `WP_BUNDLE_ANALYZER=1` (or `ANALYZE=1`): writes `bundle-report.html` and `bundle-report.json` in the output folder, listing each script and the modules inside it, biggest first. It is a static list, not webpack-bundle-analyzer's treemap.
- Admin scripts get `.LICENSE.txt` files like webpack's. The text matches, but Rolldown removes the leading space on each comment line.

## Watch

```sh
npm run watch:blocks
npm run watch:admin
```

These write development builds, with source maps, into the same asset folders. A change to a Sass partial (`_*.scss`) rebuilds the stylesheets that use it. `watch:blocks` also rebuilds the Interactivity API script modules, the dependency detection script and `block.json` files.

## Hot reload for wc-admin (Vite dev server)

This covers the wc-admin `app` entry only. The rest of the page keeps using built files, from webpack or from this prototype.

1. Build admin once, with webpack or `npm run build:admin`.
2. Replace the built entry with the stub that loads the app from Vite:
   `cp dev/stub.js ../../plugins/woocommerce/assets/client/admin/app/index.js`
3. In your WordPress site, set `define( 'SCRIPT_DEBUG', true );` in `wp-config.php` and copy `dev/mu-plugin-example.php` to `wp-content/mu-plugins/`. It installs the React DevTools hook before React loads, which React Fast Refresh needs.
4. Start the server: `npm run dev:admin`. It listens on `http://localhost:5173`; the browser must reach that address.
5. Open WooCommerce > Home. Edits to files under `client/admin/client` show up without a page reload.

Undo: rebuild admin, remove the mu-plugin and `SCRIPT_DEBUG`.

## Files

- `lib/classic.mjs`: Rolldown options and the linker that turns Rolldown's CommonJS chunks into classic scripts, `.asset.php` files and lazy chunks.
- `lib/css.mjs`: Sass, PostCSS, cssnano and RTL, joined per chunk.
- `lib/shared.mjs`: externals rules (same as the dependency extraction plugin).
- `configs/*.mjs`: one file per webpack config group.
- `dev/`: Vite dev server setup.
- `bench.sh`, `run.py`, `watchbench.py`, `pot.sh`, `pot-diff.py`, `live/`: the measurement scripts used for `NOTES.md`. Some have paths for the orb where they were written.

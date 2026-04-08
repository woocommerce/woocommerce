# Autoresearch: WooCommerce Build Speed

## Objective
Optimize the `pnpm run build` command in the WooCommerce monorepo. The build uses wireit for orchestration/caching and runs across ~47 workspace packages. The heaviest tasks are:

1. **Admin client webpack bundle** (`plugins/woocommerce/client/admin/`) — single webpack config, babel-loader + ForkTsCheckerWebpackPlugin, many entry points
2. **Blocks client webpack bundle** (`plugins/woocommerce/client/blocks/`) — 11 parallel webpack configs (Core, Main, Frontend, Payments, Extensions, SiteEditor, Styling, CartCheckout, InteractivityBlocks, InteractivityAPI, DependencyDetection)
3. **~25 JS packages** (`packages/js/*`) — each builds CJS + ESM via tsc, some also have webpack bundle + style builds
4. **Legacy assets** (`plugins/woocommerce/client/legacy/`) — grunt task
5. **Copy/rsync steps** at the plugin level

Wireit caches outputs and skips unchanged tasks, so **second builds are fast**. We're optimizing the **full clean build**.

## Metrics
- **Primary**: `build_seconds` (s, lower is better) — total wall-clock time for `pnpm run build`
- **Secondary**: none initially; may add phase timings later

## How to Run
`./autoresearch.sh` — cleans build artifacts, runs `pnpm run build`, outputs `METRIC build_seconds=<value>`.

## Files in Scope
- `plugins/woocommerce/client/admin/webpack.config.js` — admin webpack config
- `plugins/woocommerce/client/blocks/webpack.config.js` — blocks webpack entry
- `plugins/woocommerce/client/blocks/bin/webpack-configs.js` — blocks webpack config factory
- `plugins/woocommerce/client/blocks/bin/webpack-helpers.js` — blocks webpack helpers
- `plugins/woocommerce/client/blocks/bin/webpack-shared-config.js` — blocks shared optimization config
- `plugins/woocommerce/client/blocks/bin/webpack-entries.js` — blocks entry points
- `plugins/woocommerce/client/blocks/bin/webpack-config-interactive-blocks.js` — interactivity blocks config
- `plugins/woocommerce/client/blocks/bin/webpack-config-interactivity.js` — interactivity API config
- `plugins/woocommerce/client/blocks/bin/webpack-config-dependency-detection.js` — dependency detection config
- `plugins/woocommerce/client/blocks/tsconfig.json` — blocks TypeScript config
- `plugins/woocommerce/client/admin/tsconfig.json` — admin TypeScript config
- `packages/js/*/package.json` — wireit configs for package builds
- `packages/js/*/tsconfig*.json` — TypeScript configs for packages
- `package.json` — root build script
- `pnpm-workspace.yaml` — workspace config

## Off Limits
- Source code (`.ts`, `.tsx`, `.js`, `.jsx`, `.scss` files) — functionality must not change
- Test files
- Build output structure/filenames — must remain compatible
- No new dependencies

## Constraints
- No breaking changes to build output
- No new dependencies
- Build must complete successfully
- All existing entry points and outputs must be preserved

## What's Been Tried
(Nothing yet — establishing baseline)

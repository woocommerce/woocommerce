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

### Wins (kept)
1. **Remove ForkTsCheckerWebpackPlugin + Terser 2→1 passes** (95→87s): Type checker during build is redundant, 2 Terser passes wasteful
2. **Webpack 5 filesystem cache for admin + blocks** (87→56s warm): Persists module graph across builds. Cold builds add ~14s, warm saves ~31s
3. **Add --noCheck to ESM builds** (56→35s): ESM was doing full type checking while CJS had --noCheck. 24 packages updated
4. **Fix package webpack cache logic** (35→34s): Cache type was inverted (memory in prod, filesystem in dev). Fixed to always filesystem

### Dead Ends
- tsc --incremental: Incompatible with clean:build (stale tsbuildinfo)
- Unique webpack cache names: No benefit, webpack isolates by hash
- Explicit TerserPlugin for admin: Worse with warm cache
- Remove lodash from admin webpack: No measurable impact
- NODE_OPTIONS V8 tuning: Too much memory overhead for 50+ processes
- Remove CJS from wireit dependencies: CJS builds don't block critical path
- Skip CJS builds entirely: Admin code imports from CJS build/ paths
- Increase webpack parallelism: Already sufficient
- Disable concatenateModules: Cached, no warm benefit, larger bundles

### Additional Dead Ends (post-34s plateau)
- Remove wireit dependencies from tsc builds: No improvement, tsc isn't the bottleneck with warm cache
- Filter build to plugin deps only: Worse cold, same warm
- V8 semi-space tuning: Too much memory for 50+ processes
- Reduce webpack parallelism/concatenateModules: All cached

### Key Insights
- With warm webpack cache, all webpack config optimizations are irrelevant (cached)
- Remaining 34s is dominated by: ~48 tsc invocations (2-3s each, chained), wireit orchestration, grunt legacy
- Critical path: tsc dependency chain (5-6 levels deep) → webpack → copy steps
- The deepest chain is: internal-ts-config → leaf packages → data/components → experimental → product-editor → settings-editor → admin-library
- The remaining ~34s overhead is structural: pnpm/wireit process orchestration, file fingerprinting (~5800 source files), Node.js process startup for ~50 tasks
- Further improvements would require replacing the build tooling (e.g., swc instead of tsc, turbopack instead of webpack, or a unified build script)

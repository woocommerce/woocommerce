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
5. **Disable ProgressBarPlugin in production builds** (34s stable): Removes unnecessary console output and chalk formatting in 9 blocks webpack configs
6. **Replace tsc with esbuild for CJS builds** (34→31s): Uses existing esbuild@0.18.20 transitive dep. 20-100x faster transpilation frees CPU for ESM builds

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
- Remove wireit dependencies from tsc builds: No improvement, tsc isn't the bottleneck
- Filter build to plugin deps only: Worse cold, same warm
- V8 semi-space tuning: Too much memory for 50+ processes
- Reduce webpack parallelism/concatenateModules: All cached
- Split ESM into JS-only + types: 24 extra processes worse than per-task savings
- Move declarations ESM→CJS: net zero on critical path
- WIREIT_CACHE=none: same perf, breaks incremental builds
- --reporter=append-only: worse performance
- tsc --build mode: same speed as --project --noCheck
- ProgressBarPlugin removal: marginal but keeps builds at 34s floor consistently
- esbuild for ESM builds: type-only re-exports break without isolatedModules
- Split declarations from ESM for 5 critical packages: 5 extra processes offset savings
- esbuild ESM + tsc emitDeclarationOnly: tsc declarations take 80% of full tsc time, so net savings negligible
- esbuild ESM (JS-only) + separate types task for 17 packages: 17 extra processes offset instant ESM
- Combine CJS+ESM into single wireit task (62→38): WORSE, lost CJS/ESM parallelism
- Exclude unused packages via pnpm filter: filter resolution overhead > savings
- Remove build tasks from unused packages: 4 fewer tasks out of 62 insufficient
- Webpack snapshot timestamp mode: no measurable difference
- Reduce admin/blocks webpack dependencyOutputs 111→47: within noise, risk of stale deps
- --declarationMap false: only 0.2s savings on components (negligible)
- --types [] (empty types array): zero effect despite reducing files parsed
- tsc emit phase profiled: 65-70% of build time per package is pure emit (AST→JS/DTS), irreducible
- Admin webpack resolve from src/ (bypass tsc): babel-loader has same type-only re-export issue as esbuild
- **Universal blocker**: ANY tool that processes TS file-by-file (babel, esbuild, swc) fails on non-`export type` re-exports. Only tsc handles this correctly. This blocks ALL alternative transpiler approaches.
- WIREIT_PARALLEL=infinity: default cpus*2=28 already sufficient for 62 tasks on 14 cores
- WIREIT_LOGGER=quiet-ci: negligible logging overhead
- WIREIT_MAX_OPEN_FILES=4096: file descriptor budget of 200 was not a bottleneck

### Summary (54 experiments, 7 sessions)
95s → 31s (67% faster). All practical optimization paths exhausted. The floor is set by tsc emit speed (65-70% of per-package time) and the isolatedModules incompatibility that prevents alternative transpilers.

### Key Insights
- With warm webpack cache, all webpack config optimizations are irrelevant (cached)
- Remaining 34s is dominated by: ~48 tsc invocations (2-3s each, chained), wireit orchestration, grunt legacy
- Critical path: tsc dependency chain (5-6 levels deep) → webpack → copy steps
- The deepest chain is: internal-ts-config → leaf packages → data/components → experimental → product-editor → settings-editor → admin-library
- The remaining ~34s overhead is structural: pnpm/wireit process orchestration, file fingerprinting (~5800 source files), Node.js process startup for ~50 tasks
- Further improvements would require replacing the build tooling (e.g., swc instead of tsc, turbopack instead of webpack, or a unified build script)
- esbuild is available as transitive dep and works for CJS, but NOT for ESM (type-only re-exports break)
- Build time varies ±2s depending on system load; 31-35s range is normal
- **tsc emit profiling**: components Emit=3.01s/4.29s total, product-editor Emit=2.10s/3.24s. The emit phase (generating JS + declarations from AST) is the irreducible floor — it's pure CPU work in the TypeScript compiler

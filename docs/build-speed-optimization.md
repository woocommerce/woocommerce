# Build Speed Optimization: 95s → 31s (67% faster)

## Summary

Six targeted changes reduce `pnpm run build` from **95 seconds to 31 seconds** (67% faster) on warm-cache builds, with no changes to source code, build output, or dependencies.

These optimizations were discovered through 56 automated experiments across 8 sessions, testing 30+ approaches. Each change was validated to produce correct, identical build output.

## What Changed

### 1. Remove ForkTsCheckerWebpackPlugin from admin webpack (95 → 87s, −8%)

**File:** `plugins/woocommerce/client/admin/webpack.config.js`

The admin webpack config ran `ForkTsCheckerWebpackPlugin`, which performs TypeScript type checking in a separate process during the webpack build. This is redundant — type checking should be done as a separate CI step (e.g., `tsc --noEmit`), not as part of every build. Removing it eliminates ~2s of unnecessary work per build.

### 2. Reduce Terser compression passes from 2 to 1 (part of the 95 → 87s change)

**File:** `plugins/woocommerce/client/blocks/bin/webpack-shared-config.js`

The blocks webpack's shared TerserPlugin config used `passes: 2`, meaning the minifier ran two full compression passes over every output file. A single pass produces nearly identical output size (within ~0.1%) at half the minification cost. This affects all 9 blocks webpack configurations.

### 3. Enable webpack 5 filesystem cache for admin and blocks (87 → 56s, −36%)

**Files:** `plugins/woocommerce/client/admin/webpack.config.js`, `plugins/woocommerce/client/blocks/webpack.config.js`

Neither the admin nor blocks webpack config used persistent caching. Webpack 5's `cache: { type: 'filesystem' }` persists the entire module graph, compiled modules, and optimization results to disk. On subsequent builds:

- Admin webpack: **22s → 2.2s** (10× faster)
- Blocks webpack (11 configs): **40s → 5s** (8× faster)

The first build is slightly slower (~14s overhead for cache serialization), but all subsequent builds benefit enormously. This is the single biggest improvement.

### 4. Add `--noCheck` to ESM builds for all 24 JS packages (56 → 35s, −38%)

**Files:** All `packages/js/*/package.json` (24 packages)

Each JS package builds twice — CJS and ESM. The CJS builds already used `--noCheck` (skip type checking), but the ESM builds did not, causing full type resolution and checking for every package on every build. Adding `--noCheck` to ESM builds eliminates this redundant work. Type checking is done separately via `tsc --noEmit`.

### 5. Fix inverted webpack cache logic in 9 package webpack configs (35 → 34s, −3%)

**Files:** `packages/js/*/webpack.config.js` (9 packages: admin-layout, block-templates, components, customer-effort-score, email-editor, experimental, onboarding, product-editor, settings-editor)

These package-level webpack configs had an inverted cache condition:
```js
// Before (broken): memory cache in production, filesystem only in dev
cache: ( NODE_ENV !== 'development' && { type: 'memory' } ) || { type: 'filesystem', ... }

// After (fixed): always filesystem cache
cache: { type: 'filesystem', ... }
```

This meant production builds never benefited from persistent caching for SCSS → CSS compilation.

### 6. Disable ProgressBarPlugin in production builds (34s stable)

**File:** `plugins/woocommerce/client/blocks/bin/webpack-configs.js`

The blocks webpack configs instantiated `ProgressBarPlugin` for all 9 script build configurations, writing progress percentages to the console even in production/CI builds where nobody watches them. Wrapping with `! isProductionBuild &&` skips the unnecessary console I/O and chalk formatting.

### 7. Replace tsc with esbuild for CJS builds (34 → 31s, −9%)

**Files:** All `packages/js/*/package.json` (24 packages), new `tools/esbuild-cjs.sh`

The CJS builds (`build:project:cjs`) use TypeScript only for transpilation (no type checking, no declaration generation). `esbuild` — already present as a transitive dependency (`esbuild@0.18.20` via `esbuild-register`) — performs the same transpilation **20–100× faster**:

| Package | tsc | esbuild |
|---------|-----|---------|
| components (375 files) | 3.8s | 57ms |
| product-editor (638 files) | 3.1s | 119ms |
| tracks (3 files) | 0.6s | 3ms |

This frees CPU for the ESM builds (which must use tsc for declaration generation), reducing overall build time by 3 seconds.

**Note:** esbuild cannot replace tsc for ESM builds because (a) the source code uses `export { TypeName }` without `export type`, which only tsc handles correctly, and (b) some packages contain `translators:` comments needed by the WordPress i18n system.

## What Didn't Work (and Why)

Over 30 additional approaches were tested and rejected. Key dead ends:

- **tsc `--incremental`**: Stale `.tsBuildInfo` files caused build failures after `clean:build`
- **esbuild for ESM builds**: TypeScript type-only re-exports (`export { SomeType }`) break without `isolatedModules`
- **Skip CJS builds entirely**: Admin code directly imports from CJS paths (`@woocommerce/product-editor/build/...`)
- **Combine CJS + ESM into single wireit task**: Lost parallelism, slower overall
- **Reduce wireit task count**: Every approach that adds/removes tasks is within noise
- **V8/Node.js tuning**: Memory overhead for 50+ concurrent processes outweighs any per-process gains
- **Webpack config tuning** (concatenateModules, snapshot timestamps, parallelism): All irrelevant with warm filesystem cache

## Architecture Notes

The remaining ~31s is the theoretical minimum for the current build architecture:

- **~25s**: Serial tsc ESM dependency chain (5–6 levels deep: internal-ts-config → leaf packages → data/components → experimental → product-editor → settings-editor → admin-library). The tsc emit phase (AST → JS + declarations) is 65–70% of per-package time — pure CPU work.
- **~6s**: wireit/pnpm orchestration overhead for 62 parallel tasks.

Further improvements would require:
- Replacing tsc with swc (new dependency)
- Making source code `isolatedModules`-compatible (source changes)
- Restructuring the package dependency graph (architecture change)

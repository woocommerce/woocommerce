# Build Speed Optimization Ideas

## Tried and Failed (do not retry)
- tsc --incremental with external tsBuildInfoFile: incompatible with clean:build
- Unique webpack cache names per blocks config: no benefit
- Explicit TerserPlugin for admin: worse with warm cache
- Remove lodash from admin webpack config: no measurable impact
- NODE_OPTIONS --max-semi-space-size=128: too much memory per process
- Remove CJS from wireit dependency tracking: no critical path impact
- Skip CJS builds entirely: admin code imports from CJS build/ paths
- Increase webpack parallelism: already sufficient
- Disable concatenateModules: cached, no benefit
- Remove wireit dependencies from tsc: tsc not the bottleneck
- Filter build to plugin deps only: worse cold, same warm
- WIREIT_CACHE=none: same performance, breaks incremental builds
- --reporter=append-only instead of --stream: worse performance
- --parallel instead of --stream: crashes (ignores dependency order)
- Split ESM into JS-only + types: 24 extra processes add more overhead than saved
- Move declarations from ESM to CJS: net zero on critical path
- tsc --build mode: same speed as --project --noCheck

## Promising but Requires New Dependencies (blocked)
- **Replace babel-loader with swc-loader** — 10-20x faster transpilation
- **Replace tsc with swc for package transpilation** — eliminates tsc startup overhead
- **Use turbopack/rspack** — much faster than webpack

## Architectural Changes (high effort, uncertain payoff)
- **TypeScript composite project references** — batch all packages into single tsc -b
- **Replace grunt legacy build with modern Node.js script** — not on critical path (3.6s, parallel)
- **Single-process tsc for all packages** — custom script using TS compiler API
- **Reduce critical path depth** — restructure package dependencies (very invasive)

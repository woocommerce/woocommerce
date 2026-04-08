# Build Speed Optimization Ideas

## Tried and Failed
- tsc --incremental with external tsBuildInfoFile: incompatible with clean:build (stale info)
- Unique webpack cache names per blocks config: no benefit, webpack isolates by hash
- Explicit TerserPlugin for admin: worse with warm cache
- Remove lodash from admin webpack config: no measurable impact
- NODE_OPTIONS --max-semi-space-size=128: no benefit, too much memory per process
- Remove CJS from wireit dependency tracking: no impact on critical path

## Promising but Complex
- **Replace babel-loader with swc-loader in blocks webpack** — would need to add swc-loader as dep (currently blocked by no-new-deps constraint)
- **Use TypeScript project references** — batch all ~24 packages into single tsc -b invocation, reducing process startup overhead (~0.5s × 48 = 24s total)
- **Skip CJS builds entirely** — webpack only uses ESM. CJS outputs may only be needed for tests. Could conditionally skip with env var
- **Replace grunt legacy build with modern tooling** — grunt has high overhead. Could use sass + postcss + terser directly
- **Merge package ESM builds** — instead of 24 separate tsc invocations, use composite projects
- **Parallelize blocks webpack as separate processes** — instead of 11 configs in one multi-compiler, run each as separate webpack process for true parallelism

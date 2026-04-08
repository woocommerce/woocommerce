# Build Speed Optimization — COMPLETE

**95s → 31s (67% faster)** — 54 experiments across 7 sessions, 6 kept optimizations.

## Why 31s is the floor

1. **tsc emit phase**: 65-70% of per-package build time is AST→JS/DTS generation (pure CPU)
2. **isolatedModules blocker**: source code uses `export { TypeName }` (not `export type`), so no file-by-file transpiler (esbuild, babel, swc) can replace tsc for ESM
3. **Process overhead**: 62 wireit tasks × ~0.5s overhead = structural minimum
4. **Critical path depth**: 5-6 levels of tsc ESM dependency chain is irreducible without restructuring packages

## Everything that was tried and failed (30+ approaches)
See autoresearch.md "Dead Ends" — comprehensively documented.

## The only paths forward (all blocked by constraints)
- New deps: swc-loader, esbuild-loader, turbopack/rspack
- Source changes: `isolatedModules: true`, consistent `export type {}`, restructure deps
- Architecture: replace wireit with custom parallel build orchestrator

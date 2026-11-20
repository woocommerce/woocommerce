# Vite 8 / Rolldown prototype notes (thread T-01a1e83a)

- Trunk commit: 3bce0a8cefa4486bdbe95f47fb7f03a390ceb06f
- Coordinator thread: T-8280f4cc-b0df-49ea-8192-f7fe4bbf5007 (report there with send_thread_message)
- Machine: 4 cores, 8 GB (7 usable), Node 26.13.1, pnpm 10.33.0
- Prototype dir: .amp/in/orb-backup/vite-proto (own node_modules, reinstall with npm i)
- Control (trunk webpack) output: /home/user/wc-bench/control/{blocks,admin}

## Log
- Installed vite 8.3.8 (bundles rolldown 1.2.19; standalone rolldown latest is 1.3.2).
- Control build, single cold run each (caches deleted): blocks 119.3 s / 3150 MB peak, admin 58.07 s / 3109 MB peak. Output copied to /home/user/wc-bench/control/{blocks,admin}.
  Blocks control: 853 files, 211 js, 226 css, 234 asset.php. Admin control: 357 files, 121 js, 116 css, 154 files in chunks/.
- Browser targets from @wordpress/browserslist-config: chrome151 edge152 firefox158 safari26.6 ios18.5 opera134 samsung30.
- Rolldown experiment (/tmp/rd-test): format cjs + codeSplitting.groups + strictExecutionOrder gives named shared chunks that use require("./x.js"). Idea: wrap each chunk in a tiny classic-script module registry so shared chunks stay classic scripts.
- Repo has sass-embedded 1.100.0, rtlcss 4.3.0, cssnano 6.1.2, autoprefixer 10.5.0, postcss 8.5.26 in node_modules/.pnpm.
- DESIGN (works): one Rolldown CJS build per webpack config; every chunk wrapped as a factory in a per-config registry (lib/classic.mjs). Entry/lazy files inline the unnamed shared chunks they need; named groups (entriesAware) become container files with the names PHP registers; externals stay Rolldown externals (wc-ext:) and are swapped for window globals at link time; asset.php deps = externals of every chunk an entry reaches.
- Blocks build (build-blocks.mjs) = 8.8 s single run, all 12 configs + block.json + manifest. File set equals control except 88 unused *-style.asset.php, and 122.js/219.js -> payments-<hash>.js. asset.php: 141/146 identical, 5 have extra wc-types (Rolldown tree-shakes globally, not per entry). Module asset file: 81/81 records same.
- CSS (lib/css.mjs): sass 1.69.5 (same as sass-loader) + postcss + cssnano + rtlcss; 194/224 byte-identical, 22 same modules other order, 8 differ by URL escaping only. sass-embedded 1.100 is faster (4.4 s vs 6.4 s styles) but orders mixed declarations differently.
- JS size blocks: 5301 KB raw / 1573 KB gzip vs control 4626 / 1430 (+15% / +10%).
- TODO: admin config, i18n check, benchmarks, watch, Vite dev server, browser checks, report.

## Log, part 2 (after the summary hand-off)
- CORRECTION: the prototype runs rolldown 1.2.19 (the one vite 8.3.8 depends on), not 1.3.2.
- Admin: configs/admin.mjs + build-admin.mjs. 55 Rolldown builds in one process: one for app + embed + settings-embed (the entries with lazy chunks), one per other entry (so each entry tree-shakes alone, like webpack). About 8 s.
- Admin fixes needed: Oxc parse error in packages/js/components/src/experimental-select-control/selected-items.tsx (`( { selectedItem: any, index: any } ) =>` binds `any` twice; patched in a plugin); plain .css also goes through Sass with the prelude; require() inside bundled CJS (react/jsx-runtime -> react) must count as a dependency; do NOT put 'import' in resolve.conditionNames (require() then gets ESM files: `n is not a function` in react-dates, wc.components never set).
- Lazy chunks: only entries hold the lazy file map (lazy files listing their own lazy files made a hash loop). Lazy files leave out chunks every parent entry already carries. Shared lazy code over 20 KB gets its own chunks/<hash8>.js.
- Admin vs control: asset.php 56/57 same (wcsettings-deprecation loses wp-element: unused import). CSS 96/112 identical; 16 differ: 10 by which chunk file holds a module, 6 by one module (asset names). JS 9943 KB raw / 2589 KB gzip vs 8756 / 2231 (+13.6% / +16%). strictExecutionOrder off would give 9410 / 2411 (not safe to ship without checking order).
- i18n (wp i18n make-pot --debug --skip-php, on built files): blocks 1681 entries both sides, 0 "Could not parse", comments 117 control vs 118 Rolldown (2 lost, 3 gained; one lost was `// Translators:` capital T, regex filter fixed after). Admin 2334 entries both sides, 0 "Could not parse", translator comments 0 in control (trunk strips them) vs 290 in Rolldown.
- Live: /home/user/wc-bench/live/full-control and full-rolldown, 14 pages each, 0 page errors both. Same probes except script tag counts (split lazy files) and cart state. Block validation console error for all-reviews is in control too.
- bench.sh (5 alternating rounds) -> /home/user/wc-bench/bench.jsonl; watchbench.py + watch.mjs for watch timing; dev/vite.config.mjs + dev/stub.js for the Vite dev server try.

## Final results (all measured on this orb, 4 cores, 8 GB)
- Build benchmark, 5 alternating rounds (bench.sh, /home/user/wc-bench/bench.jsonl), median (range), peak RSS median:
  - blocks: webpack cold 118.29 s (113.24-121.76) 3218 MB; webpack warm 14.85 s (14.73-15.43) 1913 MB; Rolldown 9.23 s (8.90-9.67) 2329 MB.
  - admin: webpack cold 56.89 s (53.78-57.14) 3120 MB; webpack warm 5.82 s (5.71-5.83) 1253 MB; Rolldown 8.10 s (7.89-8.19) 2093 MB.
  - After the last fixes, 3 more Rolldown runs: blocks 9.46-9.76 s, admin 8.08-8.32 s (bench-final.jsonl).
  - All exits 0, same file counts every run. Limiter on the Rolldown side: Sass (JS), PostCSS/cssnano and rtlcss on the Node main thread (blocks JS-only build 2.7 s, styles + modules 7.7 s). Webpack side not profiled.
- Determinism: CSS marks were load-order numbers, so 57 admin JS files differed between runs. Marks are now hashes of the path; two runs are byte-identical.
- Watch (watchbench.py, 1 start and 3 edits per file per side, /home/user/wc-bench/watch.jsonl):
  - admin: webpack start 32.1 s, JS edit 1.43 s median (1.39-1.85), SCSS 1.44; Rolldown start 8.35 s, JS 1.93 (1.91-2.02), SCSS 1.91 (1.86-2.06).
  - blocks: webpack start 63.0 s (12 configs), JS 3.38 (3.05-3.42), SCSS 3.79 (3.71-3.86); Rolldown start 7.32 s (9 configs: no script modules, no dependency-detection), JS 2.13 (2.00-2.14), SCSS 3.07 (3.04-3.09).
  - Needed: recompile a Sass file only when its mtime changed (Rolldown reloads every module on rebuild). Sass partials are not watched.
- Vite 8.3.8 dev server for the admin `app` entry (dev/vite.config.mjs, dev/stub.js, dev/mu-plugin-example.php): works. Ready in 251 ms, first page load 3.6 s cold, 1.5 s warm, JS edit to DOM 35-51 ms (3 edits) with state kept, SCSS edit applied without reload, 0 page errors on Home, Analytics, Revenue, Marketing. Also works on top of trunk's webpack assets (46 ms). Needs SCRIPT_DEBUG and a DevTools hook before React loads; needs sass-embedded.
- i18n final: blocks 1681 entries both, 0 "Could not parse", comments 117 control vs 119 Rolldown (1 "lost" is a wrong match in control: 'Loading…'). Admin 2334 both, 0 "Could not parse", comments 0 control vs 290 Rolldown.
- Live final: /home/user/wc-bench/live/full-control vs final-rolldown, 14 pages, 0 page errors both sides; checkout rechecked 4 times alternating, same.
- Cleanup done: control assets restored (diff -rq = 0), test page 14 deleted, caches flushed, Vite service stopped, mu-plugin and SCRIPT_DEBUG removed, git status clean.

## Production pieces added later

All local commits on `try/vite-rolldown-proto`. Production output is unchanged by each one (same file set, `.asset.php` lists and sizes, checked with `compare.mjs`).

- Requires between chunks resolve at run time from Rolldown's chunk metadata, not by regex. Translator comments are edited on the parsed code.
- Source maps in development builds (`--dev`, watch).
- propTypes dropped in production for code outside node_modules. Lazy files load each other instead of copying shared code.
- Watch: Sass partials, Interactivity API script modules, dependency detection and `block.json`.
- `.LICENSE.txt` files for admin, like webpack's Terser plugin.
- `ASSET_CHECK=true` writes `assets.json`. `WP_BUNDLE_ANALYZER=1` writes a static bundle report.
- `WC_BUNDLER=rolldown` switch in the `build:project:bundle` scripts.
- Hot reload for `app`, `embed` and every `wp-admin-scripts` entry (`dev/stub.mjs`).
- Smoke check after all of it: shop, cart, checkout, wc-admin Home and Analytics, 0 page errors and 0 console errors.

Still open:

- Size: JS is about 13% bigger than webpack (blocks 5312 vs 4626 KB raw, admin 9523 vs 8756 KB). Most of it is Rolldown's lazy-init wrappers from `strictExecutionOrder`. Turning it off saves about 7% on blocks but needs browser proof that execution order still holds.
- CSS order: 22 blocks stylesheets have the same rules in a different order. Needs a visual check.
- Extension compatibility: not tested with real extensions.
- pnpm workspace: making this a workspace package rewrote about 3,200 lockfile lines, so it keeps its own `npm install`. A real migration would add Rolldown and sass-embedded to the blocks and admin packages and trim the lockfile.
- CI, Storybook (uses webpack through Storybook's builder), and replacing the webpack configs are not started. Jest does not use webpack and needs no change.

# Native TypeScript compilation experiment results

Native compilation has substantial potential, but a complete native-emission migration is not ready to adopt. The strongest candidate with unchanged public declarations is the email-editor split pipeline: retain TS5 emission and perform checking with the native nightly. It passes and preserves every emitted declaration/map hash while reducing compilation time by 57% in three local pairs.

## Repeated clean compilation measurements

Three repetitions per compiler/workload, rotating execution order. These measure type checking and declaration emission, not webpack, Babel or esbuild asset builds. Compilers: TS 5.7.3 on Node 24.15.0, native TS 7.0.2, and pinned nightly 7.1.0-dev.20261101.1.

| Workload | TS5 median | TS7 stable median | TS7 nightly median | Correctness status |
| --- | --- | --- | --- | --- |
| Data (4 projects) | 1.36s | 0.19s (7.2×) | 0.19s (7.2×) | All pass |
| Email editor | 9.80s | 4.41s (2.2×) | 2.37s (4.1×) | All pass |
| Admin (18 projects) | 39.62s | 12.74s (3.1×) | 5.60s (7.1×) | TS5 passes; native fails with 3 / 5 diagnostics |

The admin timings show potential on equivalent configured source roots, not a successful replacement build. Stable reports two DOMPurify type conflicts and one unused React ts-expect-error; nightly adds two implicit-any diagnostics in homescreen. No source fixes or error suppression were applied. Both native emitters also change public declarations in ways confirmed by consumer tests below.

## Candidate with unchanged email declarations

The sequential pipeline is:

1. TS 5.7.3: `tsc --build packages/js/email-editor/tsconfig.json --emitDeclarationOnly --noCheck --force`.
2. Native nightly, with the experimental config adaptations: `tsc --build packages/js/email-editor/tsconfig.json --noEmit --force`.

Type checking remains required in the second phase. The first phase only emits declarations; this is not accepting unchecked code. Every phase exited 0, and declarations/maps were byte-identical to the original full TS5 build in all three repetitions. Native checking did not modify them.

| Email-editor comparison | Compiler time, median (range) | Median peak RSS |
| --- | --- | --- |
| Original TS5 full build | 9.75s (9.58–10.26) | 1.63 GiB |
| TS5 emit + native check | 4.22s (4.17–4.31) | 1.42 GiB |

That is 2.31× faster, saving 5.53 seconds. Pipeline time is the sum of the two timed compiler processes; it excludes harness configuration switching and artifact comparison. The compiler phases are sequential, so pipeline peak RSS is the larger phase peak. This is a local candidate using a prerelease compiler, not an approved CI rollout. A permanent implementation should use separate compiler-specific config/buildinfo files rather than rewriting a shared config, and must validate on Linux CI before claiming PR savings.

The same shortcut is not established for the entire admin graph: TS5 --noCheck changed 26 declarations compared with full TS5 emission. The attempted full-graph split screen stopped at that comparison; it is not included as a successful pipeline measurement. Observed differences include union ordering, but complete compatibility was not established.

## Migration cost and compatibility boundary

The reference [configuration patch](native-config.patch) touches only 3 files: 17 inserted lines and 7 removed lines. Email editor needs the shared-config and email-config portions; the admin-only alias change is irrelevant to its isolated build. The native compiler is provisioned independently, leaving pnpm resolution, TS5 API consumers, Jest and ESLint intact. No production source, current tsconfig, package manifest or CI workflow is changed by this branch; the patch is unapplied, and the harness applies it only inside a temporary snapshot.

The patch replaces removed node10 resolution with bundler resolution, disables package export/import-map resolution to retain prior lookup behavior, preserves automatic ambient types and the old side-effect-import checking default, rewrites admin's explicit ~ alias without baseUrl, and removes email's obsolete downlevelIteration setting. A TS5 control using the adapted settings (with its existing automatic-type default) passes and emits all 735 declarations byte-identically to the original TS5 baseline.

The native emitters change 177/180 of 735 full-graph declarations. Some differences are cosmetic. Two actual consumer regressions were demonstrated using the same TS5 consumer compiler against each output set:

- An explicit `undefined` assignment to an optional property in data's `cleanQuery` return type passes with TS5 declarations but fails with both native outputs under `exactOptionalPropertyTypes` (TS2375).
- Constructing the exported `ImageUpload` with a props argument passes with TS5 declarations but fails with both native outputs (TS2554); the emitted constructor becomes zero-argument.

A suspected nightly ambient-destructuring problem was tested and disproven: that declaration passes the TS5 consumer check. These three checks are targeted compatibility probes, not a comprehensive API audit. The native producer passing is insufficient evidence that all declaration consumers remain compatible.

## Memory and measurement limits

Stable native TS7 is not uniformly leaner: full-admin median peak RSS is 4.65 GiB versus 2.80 GiB for TS5; nightly uses 2.58 GiB. For email it is 2.95 GiB versus 1.63 GiB, with nightly 1.50 GiB. These are process RSS measurements, not live-heap attribution.

The host was an Apple M4 Pro with 14 logical CPUs and 48 GiB RAM. Native parallelism used its defaults. Each trial removed graph outputs/buildinfo and used a fresh Node runtime-cache directory; installed dependencies and OS page caches remained warm. Timings use macOS /usr/bin/time at 0.01-second resolution. The data workload is small, so its 7× ratio saves only about 1.17 seconds. No hosted CI or PR-critical-path speedup has been measured for TS7.

Source revision: b9cfe5fb160c4c261e442e0bc118dac70293d657. The snapshot includes every tracked file, copied installed dependencies with no external symlinks, and required generated ambient declarations: 451 files plus five existing patches. Omitting that generation caused the earlier Bun benchmark's failed baseline; the prepared unchanged TS5 baseline passes. Root parity establishes matching configured roots, not identical resolution of every imported declaration across compiler versions. Repeated outputs and diagnostics were identical within each measured compiler/workload.

## Reproduce and next decision

Use [the runner](README.md) with `--split-email` to reproduce the 27 compiler trials, adapted TS5 control, consumer probes and three split-pipeline pairs. Raw local evidence includes resource logs, all diagnostics, emitted declarations, source/output hashes, npm integrity records and measurement JSON; it is intentionally excluded from the branch.

The smallest promising follow-up is a PR-confined Linux trial of the email split pipeline with its own config/buildinfo files. Broader native emission needs source-type migration and declaration compatibility work first. The experiment establishes the opportunity and its boundaries without weakening existing checks or changing production code.

# PHPUnit startup benchmark

This experiment measures the complete cost of preparing one PHP 8.5 PHPUnit environment on fresh `ubuntu-24.04` runners. It does not change the normal CI workflow. Results and a rollout recommendation will accompany the benchmark PR.

## Comparisons

| Variant | Change |
| --- | --- |
| `baseline` | Normal wp-env setup with the experiment's pinned WordPress, theme versions, and image digests. |
| `cleanup` | Excludes disabled phpMyAdmin from the image pull and omits the global PHPUnit installations. WooCommerce still invokes its own vendor PHPUnit. |
| `cache-cold` | Builds normal images with a dedicated Buildx builder, exports their layers, and saves them to the Actions cache. |
| `cache-warm` | Restores the matching layers on a fresh runner and imports them into the Compose builds. |
| `cache-miss` | Requests a nonexistent Actions cache and builds a fresh environment. |
| `cache-incompatible` | Restores a real cache but changes its compatibility identity, requiring a fresh build. |

The first wave collects three baseline, cleanup, and cold-cache samples. The second collects three paired baseline/warm samples and two fallback checks. Its dependency on the first wave is experimental ordering, not a proposed production job dependency. Cold-cache jobs also run the tests; there is no separate image producer whose cost can disappear from the comparison.

The native-cache follow-up uses the same Docker daemon and its existing containerd image store, avoiding a separate builder and image tarball transfer. It collects one cold producer, three fresh-runner baseline/warm pairs, and both fallback checks. Its initial cold cost is one observation, not a median. Trigger it with `[native-benchmark]` in the branch commit message, or dispatch `PHPUnit startup native-cache benchmark` when available on the default branch.

Baseline and cleanup retain normal CI's Bake build path. Cache variants use Compose's direct BuildKit integration (`COMPOSE_BAKE=false`), which honors `BUILDX_BUILDER` and Compose's cache import/export settings. This avoids Bake's additional filesystem entitlement for exporting outside the build context. The comparison includes that integration change and its costs; it does not isolate cache lookup alone. The cold wave cancels its remaining samples if a job fails, so a broken harness cannot repeat across the whole matrix.

`start.mjs` wraps wp-env's Docker-file writer for this process only. Every variant receives the same input pins; only the selected transformation differs. Image caches include generated Dockerfile content, build arguments, platform, and lockfile in their identity. Databases, volumes, WordPress installations, and the checked-out plugin are never cached.

## Run and inspect

1. Push this experiment to `ci/benchmark-phpunit-startup` with `[benchmark]` in the commit message. That explicit marker starts the benchmark before the workflow exists on the default branch. Ordinary pushes do not start it.
2. Once the workflow exists on the default branch, manual dispatch is also available. Every sample checks out the triggering revision.
3. Download the `benchmark-inputs` artifact and the `benchmark-<wave>-<sample>-<variant>-attempt-<attempt>` artifacts from the run.
4. Compare matching samples within a wave. Preserve failures and network anomalies in the report. Inspect `startup.log` for actual cached build steps; finding an Actions cache alone is not proof that the build used it.
5. Compare PHP versions, extensions and configuration, database and WordPress versions, themes, checkout and fixture hashes, and the test/assertion totals. The cleanup variant intentionally omits global PHPUnit; all variants use the same vendor executable.
6. Rerun a warm sample on a fresh runner and confirm it still accepts the cache and passes. Review the two fallback samples to confirm they accept no image cache and still pass.

The representative existing tests cover product import and its filesystem fixture, product REST operations and database access, and template behavior. This is a startup experiment, not replacement coverage for the full PHPUnit matrix. Run the relevant full suites before proposing a production rollout.

## Acceptance criteria

- Median warm setup must improve by at least 20 seconds and all three warm samples must beat their paired baselines.
- Setup includes Docker and builder initialization, cache restore, wp-env startup, cache export, and explicit cache save. Also compare complete job durations from the Actions API, including post steps; a faster startup with a slower complete job is not sufficient.
- Runtime fingerprints and representative test results must agree. Missing and incompatible caches must recover through a fresh build.
- Report cold overhead, transferred cache size, total runner time including the input resolver and all experimental jobs, and the number of consumers needed to repay cold-cache overhead. Prefer repayment within four warm consumers.
- Do not introduce a serial image-builder dependency into default PR CI. Extend sampling only if variance prevents a decision. Recommending no rollout is a valid outcome.

Run local harness checks from the repository root after installing the plugin's dependencies:

```sh
node --test .github/workflows/scripts/phpunit-startup-benchmark/*.test.mjs
plugins/woocommerce/node_modules/.bin/eslint .github/workflows/scripts/phpunit-startup-benchmark/*.mjs
actionlint .github/workflows/phpunit-startup-benchmark.yml .github/workflows/phpunit-startup-native-benchmark.yml .github/workflows/phpunit-startup-sample.yml
```

The launcher refuses to run outside CI to avoid changing a developer's wp-env override or runtime. The unit tests exercise transformations using temporary files and do not start Docker.

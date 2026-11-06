# PHPUnit startup benchmark results

**Recommendation: ship no production caching change from this benchmark.** A separate Buildx builder made warm setup 14.3 seconds slower. Docker's native builder saved 10.3 seconds, below the predefined 20-second threshold, while restoring roughly 495 MiB per consumer. Keep the opt-in benchmark and evidence for review; do not add its image cache or producer dependency to normal CI.

The smaller unused-tooling cleanup remains a candidate when the deferred wp-env work resumes. Its measured gain is modest and inconsistent, so this PR does not propose shipping the benchmark's generated-file transformation as a workaround. Published prebuilt images were not tested; these layer-cache results do not establish their performance or justify a new image-publishing pipeline.

## Scope and method

This experiment uses fresh GitHub-hosted `ubuntu-24.04` runners, PHP 8.5.12, WordPress 7.1.4, wp-env 12.0.0, MariaDB 12.3.3, and PHPUnit 9.6.35. It starts from trunk commit `2da4be9d5c72b6366e8e2bc4052fc5aa1b980c63`. It is a new comparison, not a claimed reduction from the earlier 92-second observation on PR #68877.

Every sample within a run uses the same checkout, resolved WordPress/theme versions, image digests, dependencies, fixtures, and focused test selection. The two experiments resolved identical external inputs; [measured-inputs.json](measured-inputs.json) preserves them. WordPress/theme archives and package repositories are version-addressed, not fully content-addressed; image digests alone do not make future builds bit-for-bit reproducible.

The normal baseline and cleanup retain Compose's Bake path. Cache variants use direct Compose builds so the generated cache import/export settings take effect. The measurements cover the complete integration change. Host dependency/build caches remain enabled in every variant, as in normal CI.

- **All-in setup:** wall time from before Docker setup until after explicit cache save. Includes builder initialization, cache restore, wp-env downloads/builds/configuration, and cache export/save.
- **Startup:** the wp-env command alone; a subset of setup.
- **Complete job:** GitHub's job start-to-completion timestamps, including dependencies, tests, evidence upload, and post-job cleanup. Queue time is excluded.
- **Runner cost:** sum of job durations, including the input resolver and cold producers. This is elapsed runner time, not a billing statement.

The predefined acceptance criteria require at least 20 seconds of median warm setup savings, all three paired warm samples faster, an improved complete-job median, correct fallback/rerun behavior, and equivalent runtimes/tests. Prefer repayment of cold overhead within four warm consumers. The experimental cold-to-warm dependency is not eligible for default PR CI.

## Separate-builder results

[Run 42301707199](https://github.com/woocommerce/woocommerce/actions/runs/42301707199), revision `e7273f90cfcc51f9855d1124493ba6364cef7d23`. Values are seconds; three samples per median unless marked otherwise.

| Comparison | Variant | All-in setup | Startup | Complete job |
| --- | --- | ---: | ---: | ---: |
| Cold wave | Normal baseline | 86.302 | 73.104 | 148 |
| Cold wave | Remove unused tooling | 73.913 | 62.533 | 133 |
| Cold wave | Build and save image caches | 114.811 | 94.875 | 188 |
| Warm wave | Normal baseline | 78.628 | 65.241 | 133 |
| Warm wave | Restore image caches | 92.962 | 70.977 | 151 |
| Recovery, one sample | Missing cache | 96.742 | 82.735 | 163 |
| Recovery, one sample | Incompatible cache | 102.523 | 82.651 | 171 |

Warm paired setup savings were **-14.334, +4.971, and -25.203 seconds**. Only one pair improved, and the median complete job became slower. Cold production added 28.509 seconds to median setup and 40 seconds to median complete-job duration; there is no measured warm benefit to repay that overhead.

The caches really were used: all warm jobs imported both `wordpress` and `cli` caches, with 55 cached build steps versus 14 within-job cached steps in the baselines. However, the first warm sample spent 17.5 seconds loading image tarballs from the separate builder into Docker, plus seven seconds starting that builder. This prompted the native-builder follow-up rather than a conclusion about every possible image cache implementation.

The cleanup comparison removes the disabled phpMyAdmin service from the pull and skips both global PHPUnit installations. It saved 12.389 seconds in median setup and 15 seconds in median complete-job time. Paired setup savings were **+12.389, -4.230, and +24.282 seconds**: two of three improved. This is a smaller, noisy candidate for later work, not evidence of a consistent 20-second saving. The upstream wp-env work remains deferred.

## Native-builder follow-up

[Run 42304030592](https://github.com/woocommerce/woocommerce/actions/runs/42304030592), revision `1a55f05a659bdf8eee4a0a2a6fe19664b4ab8935`. The existing Docker daemon has the containerd image store enabled and can export/import local build caches. This avoids a separate builder and its image transfer. The workflow checks both the storage configuration and actual builder driver.

| Variant | Samples | All-in setup | Startup | Complete job |
| --- | ---: | ---: | ---: | ---: |
| Cold cache producer | 1 | 111.341 | 97.164 | 172 |
| Normal baseline | 3 | 86.524 | 75.365 | 148 |
| Warm cache | 3 | 76.240 | 56.557 | 133 |
| Missing cache | 1 | 114.277 | 101.996 | 182 |
| Incompatible cache | 1 | 131.480 | 111.632 | 203 |

Warm paired setup savings were **+10.146, +25.244, and +10.284 seconds**. All three improved, but the median saving was only **10.284 seconds**, below the 20-second criterion. The 18.808-second startup-only improvement shrank once Docker setup and cache restore were included. Complete-job medians improved by 15 seconds.

The single cold producer took 24.817 seconds more setup than the warm-wave baseline median. At the measured median saving, that suggests three warm consumers to repay the setup overhead; complete-job overhead suggests two. This is only a rough estimate: the producer has one observation and its baseline is from the later wave. It does not overcome the failed minimum-benefit criterion.

Both recovery checks passed with zero imported image caches. A [fresh-runner rerun of warm sample 1](https://github.com/woocommerce/woocommerce/actions/runs/42304030592/job/128195136863) also passed: 86.393 seconds setup, 68.772 seconds startup, and 155 seconds for the complete job. It imported both caches, reused 55 build steps, and retained the original runtime fingerprints and test totals. This is an unpaired repeatability check, kept outside the three-pair medians; its slower timing also illustrates runner/network variability.

## Correctness and limits

All 26 primary samples passed the same 89 existing tests and 307 assertions. Within each experiment, their PHP versions, loaded extensions and effective configuration, WordPress/database versions, theme inventories, checkout, fixture hashes, and input manifests match. The tests cover CSV fixture import, product REST/database operations, and template behavior. Every sample checks the vendor PHPUnit executable and fixture presence. No database volume, WordPress installation, or checkout is restored from the image cache.

Both recovery cases in each experiment passed with zero image-cache imports. The incompatible case restores an archive but changes the image compatibility identity, proving a rejected cache falls back to a fresh build. This does not test archive corruption or an Actions service outage.

Full PHPUnit HPOS-on/off suites and other PHP/E2E configurations have not been run for this experiment because no variant clears the rollout threshold. They are required before a candidate advances to production; the focused tests alone do not establish full-suite equivalence. Three pairs provide a bounded engineering comparison, not a population estimate or a confidence interval.

## Cost and evidence

The cache contains about 521.4 MB uncompressed; one observed Actions transfer was 518,789,133 bytes (about 495 MiB). Each warm consumer restores that archive on its own runner. The main experiment consumed **2,634 runner seconds (43 minutes 54 seconds)**, including 555 seconds across the three cold producers, which also ran tests.

The native-builder experiment consumed **1,474 runner seconds (24 minutes 34 seconds)**, including its 172-second cold producer. Its observed cache transfer was 518,801,564 bytes. The separate rerun consumed another **155 runner seconds (2 minutes 35 seconds)**. GitHub carries previously completed jobs into the rerun's metadata; their original execution time is not counted twice.

Earlier diagnostic runs consumed another **1,628 runner seconds (27 minutes 8 seconds)**. They are excluded from the performance medians, but retained in investigation cost:

| Run | Runner seconds | Reason it is diagnostic |
| --- | ---: | --- |
| [42300041265](https://github.com/woocommerce/woocommerce/actions/runs/42300041265) | 304 | Harness attempted to pin unused Dockerfiles for the disabled test stack; fixed by selecting active services. |
| [42300526007](https://github.com/woocommerce/woocommerce/actions/runs/42300526007) | 503 | Bake rejected an external cache-export path; direct Compose integration resolved it. |
| [42301016754](https://github.com/woocommerce/woocommerce/actions/runs/42301016754) | 821 | Successful initial samples used direct Compose for the baseline; stopped to restore the normal Bake control. |

Total investigation cost: **5,891 runner seconds (98 minutes 11 seconds)** across primary comparisons, diagnostics, and the rerun. No additional full-suite or E2E matrix was launched after the candidates failed the shipping threshold.

[measurements.json](measurements.json) preserves individual sample timings, job IDs, cache imports, test totals, and medians after Actions artifacts expire. Raw artifacts are retained for seven days on each linked run. Read the [benchmark README](README.md) for reproduction and artifact interpretation.

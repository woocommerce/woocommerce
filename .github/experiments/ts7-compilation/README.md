# TypeScript native compiler experiment

This local experiment compares TypeScript 5.7.3 running on Node with native TypeScript 7.0.2 and 7.1.0-dev.20261101.1 for type checking and declaration emission. It does not measure webpack or esbuild asset builds. The native admin workload reports errors; those results are retained rather than treated as successful builds. The experiment does not establish that emitted declarations are identical.

Use the checkout and installed dependencies from the revision under investigation. The scripts snapshot tracked working-tree files and package-owned dependency directories, verify the installed lockfile, and reject external symlinks. They do not install workspace dependencies or change workspace package manifests. The native compiler packages are downloaded separately from the npm registry and verified against their SHA-512 integrity metadata. `native-config.patch` records the three configuration changes used in the experiment; it is an unapplied reference artifact. The runner applies equivalent changes only inside its snapshot.

## Run

Requires macOS arm64 on APFS, Python 3.12 or newer, Node 24.15.0, a populated `node_modules` installation matching `pnpm-lock.yaml`, network access to npm, and enough free space for the snapshot and results. Run on an otherwise idle machine for useful timings.

```bash
python3 .github/experiments/ts7-compilation/run.py \
    --node /path/to/node-24.15.0/bin/node \
    --data-dir /path/outside/checkout/ts7-results
```

Add `--split-email` to repeat the lower-risk email experiment: TS5 emits declarations with `--noCheck`, then the nightly performs the required full check with `--noEmit`. Three alternating pairs compare that sequence against the ordinary TS5 build. Every split run must pass both phases and preserve all baseline declaration/map hashes. This mode still performs type checking; `--noCheck` applies only to the separate emission phase.

The optional `--source` selects another checkout; otherwise the current repository is used. `TMPDIR` must be set to a directory on APFS. The runner creates its own snapshot there with `cp -cR`, canonicalizes paths, retargets copied absolute links into the snapshot, and refuses to proceed if any link resolves outside it. The data directory must be empty. Generated internal-build declarations and all five patches are prepared at their normal location in the snapshot before compilation.

For data, email, and admin, three trials rotate the compiler order: TS5/TS7/next, next/TS5/TS7, then TS7/next/TS5. Each of the 27 runs resets output and build-info files, checks source coverage and input hashes, and records wall time, peak RSS, diagnostics, exit status, compiler output, project graph, and emitted files. Data and email must pass; TS5 admin must pass and native admin errors are recorded. The runner then verifies repeated output/diagnostic identity, runs the adapted-configuration TS5 control, and checks three consumers against each emitted declaration set. The two demonstrated native consumer failures are expected evidence, not suppressed build errors. Results depend on the exact checkout, dependencies, machine, and environment.

The data directory and snapshot remain after the run, including on failure. To clean up, inspect `location.json` in the data directory, remove the recorded `root` snapshot directory, then remove the data directory. Never remove a directory without checking its path first.

## Results

[The recorded findings](results.md) distinguish passing workloads, failing full-admin measurements, memory costs, and confirmed declaration compatibility changes. The configuration patch is exploratory and must not be applied as an approved migration.

# CI Job Command

A CLI command for generating the jobs needed by the `ci.yml` file.

A CLI command for parsing CI workflow configuration from `package.json` files.

Usage: `pnpm utils ci-jobs` (Considers all projects changed and returns all jobs)
Usage: `pnpm utils ci-jobs --base-ref <base-ref>` (Checks for changes between HEAD and `base-ref`)

## Ignoring files

`--ignore <glob>` drops matching files before any project claims ownership of them, so they never
mark a project — or, through the dependency graph, any of its dependents — as changed. It is
repeatable, and the globs use the same syntax as a job's `changes` config:

```sh
pnpm utils ci-jobs --base-ref trunk --ignore '{,**/}*.md' --ignore '{,**/}readme.txt'
```

The command has no opinion about which files matter. `.github/workflows/ci.yml` owns that list in
its `NON_CODE_PATHS` env, which also builds the `needs-code-validation` gate, so the two stay in
agreement by construction.


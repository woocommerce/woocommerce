# CI Job Command

A CLI command for generating the jobs needed by the `ci.yml` file.

A CLI command for parsing CI workflow configuration from `package.json` files.

Usage: `pnpm utils ci-jobs` (Considers all projects changed and returns all jobs)
Usage: `pnpm utils ci-jobs --base-ref <base-ref>` (Checks for changes between HEAD and `base-ref`)


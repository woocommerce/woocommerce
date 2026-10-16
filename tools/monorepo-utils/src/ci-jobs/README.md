# CI Job Command

A CLI command for generating the jobs needed by the `ci.yml` file.

A CLI command for parsing CI workflow configuration from `package.json` files.

Usage: `pnpm utils ci-jobs` (Considers all projects changed and returns all jobs)
Usage: `pnpm utils ci-jobs --base-ref <base-ref>` (Checks for changes between HEAD and `base-ref`)

## Ignoring files

A project can declare an `ignore` list in its `config.ci`, and any job can declare its
own next to its `changes`. Both take a glob or an array of globs, in the same syntax as
`changes`. Jobs inherit the project-level list unless they declare their own, which
**replaces** the default — so `"ignore": []` opts a job out of the project policy
entirely:

```json
{
    "config": {
        "ci": {
            "ignore": [ "{,**/}*.md", "{,**/}readme.txt" ],
            "lint": {
                "command": "lint",
                "changes": [ "**/*.php" ]
            },
            "tests": [
                {
                    "name": "docs tests",
                    "command": "test:docs",
                    "changes": [ "**/*.md" ],
                    "ignore": []
                }
            ]
        }
    }
}
```

Changed files matching the list are invisible to that job's trigger decision:

-   They do not trigger the job through its own `changes` globs.
-   They do not mark a dependency as changed for the job, so a dependency whose only
    changes are ignored files no longer forces the job to run. A dependency whose own
    jobs triggered still forces dependents to run, regardless of what they ignore.

The list is scoped to the job that declares it. A sibling job in the same project that
needs to process the same files simply omits `ignore` or declares a narrower one.

Details worth knowing:

-   Paths are project-relative, so use `{,**/}` prefixes to also reach files at the
    project root — a bare `**/*.md` misses `README.md`.
-   Ignore globs compile with minimatch's `dot` option (unlike `changes`), so the policy
    also reaches files inside dot directories such as `.ai/`.
-   Leading negation (`!glob`) is rejected at parse time; extglob patterns like
    `changelog/!(*.php)` are supported.
-   When every project is forced to be changed — no base ref, or a lockfile change —
    `ignore` is not consulted and all jobs run.

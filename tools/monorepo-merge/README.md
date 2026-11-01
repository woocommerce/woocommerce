# monorepo-merge

Brings another GitHub repository into the WooCommerce monorepo: `merge` imports its code with full history, and `transfer-issues` moves its issues across. It is rarely needed, so read this before running it.

## Requirements

- Node 24 and the monorepo's dependencies (`pnpm install`).
- For `merge`: `git` and [`git-filter-repo`](https://github.com/newren/git-filter-repo) (for example, `brew install git-filter-repo`).
- For `transfer-issues`: a GitHub token with the `repo` and `project` scopes, read from `GH_TOKEN`, `GITHUB_TOKEN`, or `gh auth token`. Add the `project` scope to a `gh` login with `gh auth refresh -s project`.

Run `tools/monorepo-merge/bin/monorepo-merge.mjs --help` for the full usage.

## Merging a repository

Start from an up-to-date branch off `trunk`, then run:

```bash
tools/monorepo-merge/bin/monorepo-merge.mjs merge woocommerce/example-repo packages/js/example --branch trunk
```

The command clones the repository into a temporary directory, rewrites its history so every file lives under the destination path, and merges it with `git merge --allow-unrelated-histories` into the current branch of the checkout that contains this tool, wherever you run it from. It stops unless that checkout's root is two directories above `tools/monorepo-merge`. To try it without touching your monorepo checkout, copy the tool to `tools/monorepo-merge` inside a scratch git repository, so the scratch repository becomes the target, and run it from there. In commit messages, `(#123)` becomes a link to the source repository's pull request and a bare `#123` becomes `woocommerce/example-repo#123`, so old references keep pointing at the right place. `--branch` is the source branch to import and defaults to `main`.

Merge the resulting pull request with a merge commit. Squashing it discards the imported history.

## Transferring issues

```bash
tools/monorepo-merge/bin/monorepo-merge.mjs transfer-issues woocommerce/example-repo --labels "focus: example"
```

Transfers the issues matching `--searchFilter` (default `is:open`) into `--destination` (default `woocommerce/woocommerce`). Both repositories must have the same owner. After the transfer it restores any GitHub Projects field values that did not carry over and adds the `--labels` you list, which must already exist in the destination.

Transfers cannot be undone, and the command asks for confirmation twice. Add `--dry-run` first to run every lookup and list the issues it would transfer, without prompting or changing anything. GitHub search returns at most 1,000 issues per query, so run the command again to move the rest. Transferred issues no longer match the search. A `--searchFilter` value that starts with `-` must use the `--searchFilter=-label:example` form.

## Development

```bash
pnpm --filter=monorepo-merge test
```

Runs the unit tests, then ESLint. The commit message tests need `python3`.

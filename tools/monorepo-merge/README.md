# monorepo-merge

Brings another GitHub repository into the WooCommerce monorepo: `merge` imports its code with full history, and `transfer-issues` moves its issues across. It is rarely needed, so read this before running it.

Because it can go years between runs, have an LLM review `bin/` and `src/` before you use it. Ask it to check for changes to the GitHub GraphQL API, `git`, or `git-filter-repo` that affect the import or the issue transfer, and for improvements worth making first. Then run `transfer-issues` with `--dry-run` before the real transfer.

## Requirements

- macOS or Linux with Node 24. The tool has no runtime dependencies; `pnpm install` is only needed to run its tests.
- For `merge`: `git` and [`git-filter-repo`](https://github.com/newren/git-filter-repo) (for example, `brew install git-filter-repo`).
- For `transfer-issues`: a GitHub token with the `repo` and `project` scopes, read from `GH_TOKEN`, `GITHUB_TOKEN`, or `gh auth token`. Add the `project` scope to a `gh` login with `gh auth refresh -s project`.

Run `tools/monorepo-merge/bin/monorepo-merge.mjs --help` for the full usage.

## Merging a repository

Start from an up-to-date branch off `trunk`, then run:

```bash
tools/monorepo-merge/bin/monorepo-merge.mjs merge woocommerce/example-repo packages/js/example --branch trunk
```

The command clones the repository into a temporary directory, rewrites its history so every file lives under the destination path, and merges it with `git merge --allow-unrelated-histories` into the current branch of the checkout that contains this tool, wherever you run it from. It stops unless that checkout's root is two directories above `tools/monorepo-merge`. To try it without touching your monorepo checkout, copy the tool to `tools/monorepo-merge` inside a scratch git repository, so the scratch repository becomes the target, and run it from there. In commit messages, `(#123)` becomes a link to the source repository's pull request and a standalone `#123` becomes `woocommerce/example-repo#123`, so old references keep pointing at the right place. References that are already qualified, such as `other/repo#12`, are left alone. The source repository's tags are not imported. `--branch` is the source branch to import and defaults to `main`.

Merge the resulting pull request with a merge commit. Squashing it discards the imported history.

### After the merge

The imported code still uses its own text domain for translations. Change it to `woocommerce` in a follow-up commit on the same branch:

- JavaScript: once the imported package uses the monorepo's ESLint config, run ESLint with `--fix`. The `@wordpress/i18n-text-domain` rule in `@woocommerce/eslint-plugin`'s recommended config replaces other text domains with `woocommerce` and adds missing ones.
- PHP: add the WPCS `WordPress.Utils.I18nTextDomainFixer` sniff to the package's `phpcs.xml`, with `old_text_domain` set to the imported text domain and `new_text_domain` set to `woocommerce`, then run `phpcbf`. `packages/php/woocommerce-analytics/phpcs.xml` shows the syntax. The root `phpcs.xml` reports other text domains but does not fix them.

## Transferring issues

```bash
tools/monorepo-merge/bin/monorepo-merge.mjs transfer-issues woocommerce/example-repo --labels "focus: example"
```

Transfers the issues matching `--searchFilter` (default `is:open`) into `--destination` (default `woocommerce/woocommerce`). Both repositories must have the same owner. After the transfer it restores any GitHub Projects field values that did not carry over and adds the `--labels` you list, which must already exist in the destination. If any transfer or post-transfer step fails, the command lists the failures and exits with a non-zero status.

Transfers cannot be undone, and the command asks for confirmation twice. Add `--dry-run` first to run every lookup and list the issues it would transfer, without prompting or changing anything. GitHub search returns at most 1,000 issues per query, so run the command again to move the rest. Transferred issues no longer match the search. A `--searchFilter` value that starts with `-` must use the `--searchFilter=-label:example` form.

## Development

```bash
pnpm --filter=monorepo-merge test
```

Runs the unit tests, then ESLint. The commit message tests need `python3`.

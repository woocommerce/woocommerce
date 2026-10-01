# Release contributors list

This tool generates an HTML list of contributors between two WooCommerce release refs. The [release commits and contributors workflow](../../.github/workflows/release-commits-and-contributors.yml) uploads the list as an artifact for the release team.

## Setup

1. Run `pnpm install` from the monorepo root.
2. Set `GITHUB_ACCESS_TOKEN` to a GitHub token that can read the compared repositories. For local use, copy `.env.sample` to `.env` in this directory and set the token, or export it in the shell.

## Generate a list

Run this from `tools/release-contributors`:

```bash
pnpm release-contributors release/11.0 release/10.9
```

The command prints the path to the generated HTML file in the system temporary directory. It reads the WooCommerce and Action Scheduler release changes from GitHub; it does not publish a post.

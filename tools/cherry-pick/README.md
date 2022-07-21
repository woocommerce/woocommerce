# Cherry Pick

A tool to automate cherry picking fixes into a release.

### Prerequisite

You will need to set two environment variable:
* **GITHUB_CHERRY_PICK_TOKEN** - Generate a personal access token from GitHub and make sure it has the `Repo` scope. Assign this token to the environment variable.
* **GITHUB_REMOTE_URL** - Depending on if you use `https` or `ssh` when you use `git clone`. Set the value `https` or `ssh` to that environment variable.

### Usage

Usage: `pnpm cherry-pick <release_branch> <pull_request_number>`. Separate pull request numbers with a comma (no space) if more than one.

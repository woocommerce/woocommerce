# Dependabot

This repository uses [`Dependabot`](https://docs.github.com/en/code-security/dependabot/dependabot-security-updates/configuring-dependabot-security-updates) to help with keeping all the package dependencies (NPM, Composer, GitHub Actions) up to date. Without this in place, it's very easy to let the package versions we're using go stale and end up with a backlog of chores for updating those in the future. It is essential to keep dependencies updated to avoid security problems and lower overall upgrade costs.
The process is automated: Dependabot creates a branch and a PR with a package bump in package.json. A new package-lock.json is created. Automated tests are executed. Also, Dependabot will create a maximum of 10 PRs for each ecosystem (NPM, Composer, GitHub Actions).

It is the responsibility of the porter to review these PRs weekly and merge/reject them.

Dependabot's configuration is located at [`.github/dependabot.yml` path](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/.github/dependabot.yml).

## Auto-merge

This repository also has auto-merge enabled for selected dev dependencies. Debendabot will automatically merge pull requests that pass all the required checks and meet the defined merge criteria, helping us to streamline the process of keeping dependencies up to date even further.

The list of dependencies marked for auto-merge can be found at [`.github/workflows/auto-merge-dependabot.yml`](https://github.com/woocommerce/woocommerce-blocks/blob/trunk/.github/workflows/auto-merge-dependabot.yml).

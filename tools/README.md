# Monorepo Infrastructure & Tools

This document outlines the monorepo's infrastructure and the rationale behind the decisions we've made.

## Task Orchestration

Each project within the monorepo follows a small, consistent script naming scheme so we can run related tasks together with [pnpm's script-pattern execution](https://pnpm.io/cli/run#running-multiple-scripts):

```json
{
	"scripts": {
		"build": "pnpm build:project",
		"build:project": "pnpm --if-present '/^build:project:.*$/'",
		"lint": "pnpm --if-present '/^lint:lang:.*$/'",
		"lint:fix": "pnpm --if-present '/^lint:fix:lang:.*$/'",
		"watch:build": "pnpm --if-present --parallel '/^watch:build:project:.*$/'",
		"watch:build:project": "pnpm --if-present run '/^watch:build:project:.*$/'"
	}
}
```

**To ensure consistency across the monorepo, these scripts should not be edited.** New scripts should be added using the regex naming scheme — for example, `build:project:bundle` might be a script that runs `webpack`. Useful pnpm options:

- `--if-present`: pnpm will not error if a script isn't found.
- `--parallel`: runs matching scripts in parallel (used for watch tasks that don't terminate).

### Cross-project orchestration

For projects that need to coordinate work across the workspace, prefer explicit `--filter` lists over the older `--filter="$npm_package_name..."` topological cascade. The cascade builds every transitive dependency in order; explicit lists are clearer about what runs and avoid pulling in unrelated workspace builds.

For example, the `plugin-woocommerce` build invokes its three asset producers in parallel and then runs its own post-bundle work:

```json
{
	"scripts": {
		"build": "pnpm --parallel --filter='@woocommerce/admin-library' --filter='@woocommerce/block-library' --filter='@woocommerce/classic-assets' build:project && pnpm build:project"
	}
}
```

Each producer writes its bundles directly into the plugin's final asset locations (`plugins/woocommerce/assets/{client/admin,client/blocks,css,js}`), which removes the intermediate copy step the old cascade used.

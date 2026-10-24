# Monorepo Infrastructure & Tools

This document outlines the monorepo's infrastructure and the rationale behind the decisions we've made.

## Task Orchestration

Each project within the monorepo follows a small, consistent script naming scheme so we can run related tasks together with [pnpm's script-pattern execution](https://pnpm.io/cli/run#running-multiple-scripts):

```json
{
	"scripts": {
		"build": "pnpm build:project",
		"build:project": "pnpm --stream '/^build:project:.*$/'",
		"lint": "pnpm --if-present '/^lint:lang:.*$/'",
		"lint:fix": "pnpm --if-present '/^lint:fix:lang:.*$/'",
		"watch:build": "pnpm watch:build:project",
		"watch:build:project": "pnpm --stream '/^watch:build:project:.*$/'"
	}
}
```

`build` aliases `build:project`; `watch:build` aliases `watch:build:project`. New work is added under the regex naming scheme — for example, `build:project:bundle` might be a script that runs `webpack`. pnpm runs every regex match concurrently, so no extra orchestrator (`concurrently`, `--parallel`) is needed.

`--if-present` is only used when the regex may match nothing for a given project (e.g. linting groups where some packages have no rules). If a project has the scripts, drop `--if-present`; if it doesn't, drop the wrapper script entirely.

`--parallel` is never used here — it tells pnpm to run the script across the entire workspace rather than the current project, which is not what these wrappers want.

### Cross-project orchestration

When one project needs to build another, prefer an explicit `--filter` to the dependency over `--filter="$npm_package_name..."` topological cascade. The cascade implicitly rebuilds every transitive dependency; explicit filters keep the call site honest about what runs.

For example, `@woocommerce/plugin-woocommerce` fans out to its three asset producers:

```json
{
	"scripts": {
		"build:project:admin": "pnpm --filter='@woocommerce/admin-library' build",
		"build:project:blocks": "pnpm --filter='@woocommerce/block-library' build",
		"build:project:classic-assets": "pnpm --filter='@woocommerce/classic-assets' build"
	}
}
```

Each producer writes its bundles directly into the plugin's final asset locations (`plugins/woocommerce/assets/{client/admin,client/blocks,css,js}`), so there is no intermediate copy step.

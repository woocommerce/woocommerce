# Monorepo Infrastructure & Tools

This document aims to provide an outline for the monorepo's infrastructure as well as the rationale behind the decisions we've made.

## Task Orchestration

Our repository makes aggressive use of [parallelization using PNPM's `--filter` syntax](https://pnpm.io/filtering). This allows us to minimize the amount of time developers spend waiting for tasks like builds to complete. Each project within the monorepo will contain the following scripts if applicable:

```json
{
	"scripts": {
		"build": "pnpm --if-present --workspace-concurrency=Infinity --stream --filter=\"$npm_package_name...\" '/^build:project:.*$/'",
		"lint": "pnpm --if-present '/^lint:lang:.*$/'",
		"lint:fix": "pnpm --if-present '/^lint:fix:lang:.*$/'",
		"watch:build": "pnpm --if-present --workspace-concurrency=Infinity --filter=\"$npm_package_name...\" --parallel '/^watch:build:project:.*$/'",
	}
}
```

These scripts outline the naming scheme used in order to facilitate [task parallelization using regular expressions](https://pnpm.io/cli/run#running-multiple-scripts). **To ensure consistency across the monorepo, these scripts should not be edited.** New scripts should be added using the naming scheme outlined by the above regular expressions, for example, `build:project:bundle` might be a script to run a tool like `webpack`. We also utilize a number of PNPM options to ensure a positive developer experience:

- `--if-present`: Ensures that PNPM will not error if a script is not found.
- `--workspace-concurrency=Infinity`: Runs as many of the tasks in parallel as possible.
- `--stream`: Makes the script output legible by putting all of their output into a single stream.
- `--filter="$npm_package_name..."`: This filter tells PNPM that we want to run the script against the current project _and_ all of its dependencies down the graph.

We also provide `build:project` and `watch:build:project` scripts which only run the associated scripts on the current package without any of its dependencies.

## Task Output Caching

Our repository uses [`wireit`](https://github.com/google/wireit) to provide task output caching. In particular, this allows us to cache the output of `build` scripts so that they don't run unnecessarily. The goal is to minimize the amount of time that developers spend waiting for projects to build.

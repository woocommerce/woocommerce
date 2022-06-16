# Development

This document aims to provide as much context as possible to aid in the development of plugins, packages, and tools in the monorepo.

## Getting Started

Please refer to [the Getting Started section of the `README.md`](README.md#getting-started) for a general-purpose guide on getting started. The rest of this document will assume that you've installed all of the prequisites and setup described there.

## Turborepo Commands

Our repository uses [Turborepo](https://turborepo.org) for `build`, `lint`, and `test` commands. This tool ensures that all dependencies of a plugin, package, or tool are prepared before running a command. When using `pnpm -- turbo run {command}` without any options, it will execute that command against every project in the repository. You can view a list of the commands Turborepo supports in [our turbo.json file](turbo.json).

### Plugin, Package, and Tool Filtering

If you are interested in running a `turbo` command against a single plugin, package, or tool, you can do so with the `--filter` flag. This flag supports the `"name"` option in `package.json` files, paths, and globs.

If you would like to read more about the syntax, please check out [the Turborepo filtering documentation](https://turborepo.org/docs/core-concepts/filtering).

### Examples

Here are some examples of the ways you can use Turborepo commands:

```bash
# Lint and build all plugins, packages, and tools
pnpm -- turbo run lint build

# Build WooCommerce Core and all of its dependencies
pnpm -- turbo run build --filter='woocommerce'

# Lint the @woocommerce/components package
pnpm -- turbo run lint --filter='@woocommerce/components'

# Test all of the @woocommerce scoped packages
pnpm -- turbo run test --filter='@woocommerce/*'

# Build all of the JavaScript packages
pnpm -- turbo run build --filter='./packages/js/*'

# Build everything except WooCommerce Core
pnpm -- turbo run build --filter='!woocommerce' 

# Lint everything that has changed since the last commit
pnpm -- turbo run build --filter='[HEAD^1]'
```

## Other Commands

Outside of the commands in [our turbo.json file](turbo.json), each plugin, package, and tool may have unique scripts in their `package.json` files. In these cases, you can execute those commands using `pnpm {script}` and the same `--filter` syntax as Turborepo.

### Examples

Here are some examples of the commands you will make use of.

```bash
# Add a changelog entry for WooCommerce Core
pnpm changelog add --filter=woocommerce

# Create the woocommerce.zip file
pnpm build:zip --filter=woocommerce
```

## Plugin Development Environments

The plugins in our repository make use of [the `@wordpress/env` package](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/). This supplies convenient commands for creating, destroying, cleaning, and testing WordPress environments.

```bash
# Make sure you are in the working directory of the plugin you are interested in setting up the environment for
cd plugins/woocommerce
# Start will create the environment if necessary or start an existing one
pnpm -- wp-env start
# Stop will, well, stop the environment
pnpm -- wp-env stop
# Destroy will remove all of the environment's files.
pnpm -- wp-env destroy
```

Each of the [plugins in our repository](plugins) support using this tool to spin up a development environment. Note that rather than having a single top-level environment, each plugin has its own. This is done in order to prevent conflicts between them.

Please check out [the official documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) if you would like to learn more about this tool.

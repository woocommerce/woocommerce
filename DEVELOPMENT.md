# Development

This document aims to provide as much context as possible to aid in the development of plugins, packages, and tools in the monorepo.

## Getting Started

Please refer to [the Getting Started section of the `README.md`](README.md#getting-started) for a general-purpose guide on getting started. The rest of this document will assume that you've installed all of the prerequisites and setup described there.

### Plugin, Package, and Tool Filtering

In order to run commands on individual projects you will need to utilize [PNPM's --filter flag](https://pnpm.io/filtering). This flag supports `"name"` option in `package.json`, paths, and globs.

### Examples

Here are some examples of the ways you can use `pnpm` commands:

```bash
# Lint and build all plugins, packages, and tools.
pnpm lint && pnpm build

# Build WooCommerce Core and all of its dependencies
pnpm --filter='@woocommerce/plugin-woocommerce' build

# Lint the @woocommerce/components package
pnpm --filter='@woocommerce/components' lint

# Test all of the @woocommerce scoped packages
pnpm --filter='@woocommerce/*' test

# Build all of the JavaScript packages
pnpm --filter='./packages/js/*' build

# Build everything except WooCommerce Core
pnpm --filter='!@woocommerce/plugin-woocommerce' build

# Build everything that has changed since the last commit
pnpm --filter='[HEAD^1]' build
```

### Examples

Here are some examples of the commands you will make use of.

```bash
# Add a changelog entry for WooCommerce Core
pnpm --filter='@woocommerce/plugin-woocommerce' changelog add

# Create the woocommerce.zip file
pnpm --filter='@woocommerce/plugin-woocommerce' build:zip
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

## Troubleshooting

### Installing PHP in Unix (e.g. Ubuntu)

Many unix systems such as Ubuntu will have PHP already installed. Sometimes without the extra packages you need to run WordPress and this will cause you to run into troubles.

Use your package manager to add the extra PHP packages you'll need.
e.g. in Ubuntu you can run:

```
sudo apt update
sudo apt install php-bcmath \
                 php-curl \
                 php-imagick \
                 php-intl \
                 php-json \
                 php-mbstring \
                 php-mysql \
                 php-xml \
                 php-zip
```

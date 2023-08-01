# Development

This document aims to provide as much context as possible to aid in the development of plugins, packages, and tools in the monorepo.

## Getting Started

Please refer to [the Getting Started section of the `README.md`](README.md#getting-started) for a general-purpose guide on getting started. The rest of this document will assume that you've installed all of the prequisites and setup described there.

## Wireit

TBD

### Plugin, Package, and Tool Filtering

TBD - this will refer to pnpm filter

### Examples

Here are some examples of the ways you can use pnpm commands:

TBD - new examples

### Cache busting Wireit

TBD

## Other Commands

Each plugin, package, and tool may have unique scripts in their `package.json` files. In these cases, you can execute those commands using `pnpm {script}`.

### Examples

Here are some examples of the commands you will make use of.

```bash
# Add a changelog entry for WooCommerce Core
pnpm --filter=woocommerce run changelog add

# Create the woocommerce.zip file
pnpm --filter=woocommerce run build:zip
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

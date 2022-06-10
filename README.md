<p align="center"><a href="https://woocommerce.com/"><img src="https://woocommerce.com/wp-content/themes/woo/images/logo-woocommerce@2x.png" alt="WooCommerce"></a></p>

Welcome to the WooCommerce Monorepo on GitHub. Here you can find all of the packages, plugins, and tools used in the development of the core WooCommerce plugin as well as WooCommerce extensions. You can browse the source, look at open issues, contribute code, and keep tracking of ongoing development.

We recommend all developers to follow the [WooCommerce development blog](https://woocommerce.wordpress.com/) to stay up to date about everything happening in the project. You can also [follow @DevelopWC](https://twitter.com/DevelopWC) on Twitter for the latest development updates.

## Repository Structure

* [**Plugins**](plugins): Our repository contains plugins that relate to or otherwise aid in the development of WooCommerce.
  * [**WooCommerce Core**](plugins/woocommerce): The core WooCommerce plugin is available in the plugins directory.
* [**Packages**](packages): Contained within the packages directory are all of the [PHP](packages/php) and [JavaScript](packages/js) provided for the community. Some of these are internal dependencies and are marked with an `internal-` prefix.
* [**Tools**](tools): We also have a growing number of tools within our repository. Many of these are intended to be utilities and scripts for use in the monorepo, but, this directory may also contain external tools.

## Getting Started

To get up and running within the WooCommerce Monorepo, you will need to make sure that you have installed all of the prerequisites.

### Prerequisites

* [NVM](https://github.com/nvm-sh/nvm#installing-and-updating): While you can always install Node through other means, we recommend using NVM to ensure you're aligned with the version used by our development teams. Our repository contains [an `.nvmrc` file](.nvmrc) which helps ensure you are using the correct version of Node.
* [PNPM](https://pnpm.io/installation): Our repository utilizes PNPM to manage project dependencies and run various scripts involved in building and testing projects.
* [PHP 7.2+](https://www.php.net/manual/en/install.php): WooCommerce Core currently features a minimum PHP version of 7.2. While you don't need to use it to run a local development environment, you will need it to utilize Composer.
* [Composer](https://getcomposer.org/doc/00-intro.md): We use Composer to manage all of the dependencies for PHP packages and plugins.

Once you've installed all of the prerequisites, you can run the following commands.

```bash
# Ensure that you're using the correct version of Node
nvm use
# Install all of the NPM and Composer dependencies within the Monorepo
pnpm install
```

### Building, Linting, and Testing

Our repository uses [Turborepo](https://turborepo.org) for running `build`, `lint`, `test`, and `e2e` commands. This tool ensures that all dependencies of a package, plugin, or tool are prepared before running a command. It also provides caching for command outputs in order to ensure that work is not performed unnecessarily.

Without any additional flags, running a command will execute it against every project in the monorepo. For example, `pnpm exec turbo run build` will build all of the projects within the monorepo. `pnpm exec turbo run test` will run unit tests for all of the projects within the monorepo.

This behavior can be desireable, as the cache should ensure anything that has not changed is not rebuilt. There are times, however, that you may want to explicitly run a command against a specific project.

This can be done using the `--filter` flag. For example, running `pnpm exec turbo run build --filter=woocommerce` will build the WooCommerce plugin, as well as all of the dependencies required for the plugin to function.

The `--filter` syntax also supports paths, such as `--filter='./plugins/**'` to build all of the plugins in the monorepo. [You can read more about the filtering syntax in Turborepo's documentation](https://turborepo.org/docs/core-concepts/filtering).

### Project-Specific Commands

Outside of the above `turbo` commands, there may be times where you want to run a command on a specific project. This can _also_ be done using the `--filter` syntax, however, you will run these commands using `pnpm`. For example, `pnpm postinstall --filter=woocommerce` will run the `"postinstall"` script from `plugins/woocommerce/package.json`.

## Development Environments

Our repository makes use of [the `@wordpress/env` package](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) for providing development environments out-of-the-box. Once you have installed the package and its related dependencies, you should be able to run `wp-env start` in any of the `plugins/` folders. This will start a development environment and provide you with a URL to begin testing code using.

## Reporting Security Issues
To disclose a security issue to our team, [please submit a report via HackerOne here](https://hackerone.com/automattic/).

## Support
This repository is not suitable for support. Please don't use our issue tracker for support requests, but for core WooCommerce issues only. Support can take place through the appropriate channels:

* If you have a problem, you may want to start with the [self help guide](https://docs.woocommerce.com/document/woocommerce-self-service-guide/).
* The [WooCommerce.com premium support portal](https://woocommerce.com/contact-us/ ) for customers who have purchased themes or extensions.
* [Our community forum on wp.org](https://wordpress.org/support/plugin/woocommerce) which is available for all WooCommerce users.
* The WooCommerce Help and Share Facebook group.
* For customizations, you may want to check our list of [WooExperts](https://woocommerce.com/experts/) or [Codeable](https://codeable.io/).

Support requests in issues on this repository will be closed on sight.

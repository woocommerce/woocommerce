# WooCommerce Monorepo

![WooCommerce](https://woocommerce.com/wp-content/themes/woo/images/logo-woo@2x.png)

Welcome to the WooCommerce Monorepo on GitHub. Here you can find all of the plugins, packages, and tools used in the development of the core WooCommerce plugin as well as WooCommerce extensions. You can browse the source, look at open issues, contribute code, and keep tracking of ongoing development.

We recommend all developers to follow the [WooCommerce development blog](https://developer.woocommerce.com/blog/) to stay up to date about everything happening in the project. You can also [follow @DevelopWoo](https://x.com/DevelopWoo) on X (formerly Twitter) for the latest development updates.

## Getting Started

To get up and running within the WooCommerce Monorepo, you will need to make sure that you have installed all of the prerequisites.

### Prerequisites

-   [NVM](https://github.com/nvm-sh/nvm#installing-and-updating): While you can always install Node through other means, we recommend using NVM to ensure you're aligned with the version used by our development teams. Our repository contains [an `.nvmrc` file](.nvmrc) which helps ensure you are using the correct version of Node.
-   [PNPM](https://pnpm.io/installation): Our repository utilizes PNPM to manage project dependencies and run various scripts involved in building and testing projects.
-   [PHP 7.4+](https://www.php.net/manual/en/install.php): WooCommerce Core currently features a minimum PHP version of 7.4. It is also needed to run Composer and various project build scripts. See [troubleshooting](DEVELOPMENT.md#troubleshooting) for troubleshooting problems installing PHP.
-   [Composer](https://getcomposer.org/doc/00-intro.md): We use Composer to manage all of the dependencies for PHP packages and plugins.

Note: A POSIX compliant operating system (e.g., Linux, macOS) is assumed. If you're working on a Windows machine, the recommended approach is to use [WSL](https://learn.microsoft.com/en-us/windows/wsl/install) (available since Windows 10).

Once you've installed all of the prerequisites, the following will prepare all of the build outputs necessary for development:

```bash
# Ensure that correct version of Node is installed and being used
nvm install
# Install the PHP and Composer dependencies for all of the plugins, packages, and tools
pnpm install -frozen-lockfile
# Build all of the plugins, packages, and tools in the monorepo
pnpm build
```

## Repository Structure

Each plugin, package, and tool has its own `package.json` file containing project-specific dependencies and scripts. Most projects also contain a `README.md` file with any project-specific setup instructions and documentation.

-   [**Plugins**](plugins): Our repository contains plugins that relate to or otherwise aid in the development of WooCommerce.
    -   [**WooCommerce Core**](plugins/woocommerce): The core WooCommerce plugin is available in the plugins directory.
-   [**Packages**](packages): Contained within the packages directory are all of the [PHP](packages/php) and [JavaScript](packages/js) provided for the community. Some of these are internal dependencies and are marked with an `internal-` prefix.
-   [**Tools**](tools): We also have a growing number of tools within our repository. Many of these are intended to be utilities and scripts for use in the monorepo, but, this directory may also contain external tools.

If you'd like to learn more about how our monorepo works, [please check out this guide here](tools/README.md).

## Reporting Security Issues

To disclose a security issue to our team, [please submit a report via HackerOne here](https://hackerone.com/automattic/).

## Support

This repository is not suitable for support. Please don't use our issue tracker for support requests, but for core WooCommerce issues only. Support can take place through the appropriate channels:

-   If you have a problem, you may want to start with the [self help guide](https://woocommerce.com/document/woocommerce-self-service-guide/).
-   The [WooCommerce.com premium support portal](https://woocommerce.com/contact-us/) for customers who have purchased themes or extensions.
-   [Our community forum on wp.org](https://wordpress.org/support/plugin/woocommerce) which is available for all WooCommerce users.
-   [The Official WooCommerce Facebook Group](https://www.facebook.com/groups/advanced.woocommerce).
-   For customizations, you may want to check our list of [WooExperts](https://woocommerce.com/experts/) or [Codeable](https://codeable.io/).

NOTE: Unfortunately, we are unable to honor support requests in issues on this repository; as a result, any requests submitted in this manner will be closed.

## Community

For peer to peer support, real-time announcements, and office hours, please [join our slack community](https://woocommerce.com/community-slack/)!

## Contributing to WooCommerce

As an open source project, we rely on community contributions to continue to improve WooCommerce. To contribute, please follow the pre-requisites above and visit our [Contributing to Woo](https://developer.woocommerce.com/docs/contribution/contributing/) doc for more links and contribution guidelines.

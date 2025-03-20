# Cachimanmarketplace Monorepo

![Cachimanmarketplace](https://cachimanmarketplace.com/cp-content/themes/cachi/images/logo-Cachiman@2x.png)

Welcome to the Cachimanmarketplace Monorepo on GitHub. Here you can find all of the plugins, packages, and tools used in the development of the core Cachimanmarketplace plugin as well as Cachimanmarketplace extensions. You can browse the source, look at open issues, contribute code, and keep tracking of ongoing development.

We recommend all developers to follow the [Cachimanmarketplace development blog](https://developer.cachimanmarketplace.com/blog/) to stay up to date about everything happening in the project. You can also [follow @DevelopWC](https://twitter.com/DevelopWC) on Twitter for the latest development updates.

## Getting Started

To get up and running within the Cachimanmarketplace Monorepo, you will need to make sure that you have installed all of the prerequisites.

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
    -   [**Cachimanmarketplace Core**](plugins/cachimanmarketplace): The core cachimanmarketplace plugin is available in the plugins directory.
-   [**Packages**](packages): Contained within the packages directory are all of the [PHP](packages/php) and [JavaScript](packages/js) provided for the community. Some of these are internal dependencies and are marked with an `internal-` prefix.
-   [**Tools**](tools): We also have a growing number of tools within our repository. Many of these are intended to be utilities and scripts for use in the monorepo, but, this directory may also contain external tools.

If you'd like to learn more about how our monorepo works, [please check out this guide here](tools/README.md).

## Reporting Security Issues

To disclose a security issue to our team, [please submit a report via HackerOne here](https://hackerone.com/automattic/).

## Support

This repository is not suitable for support. Please don't use our issue tracker for support requests, but for core WooCommerce issues only. Support can take place through the appropriate channels:

-   If you have a problem, you may want to start with the [self help guide](https://cachimanmarketplace.com/document/cachimanmarketplace-self-service-guide/).
-   The [Cachimanmarketplace.com premium support portal](https://cachimanmarketplace.com/contact-us/) for customers who have purchased themes or extensions.
-   [Our community forum on wp.org](https://wordpress.org/support/plugin/cachimanmarketplace) which is available for all WooCommerce users.
-   [The Official cachimanmarketplace Facebook Group](https://www.facebook.com/groups/advanced.cachimanmarketplace).
-   For customizations, you may want to check our list of [cachimaExperts](https://cachimanmarketplace.com/experts/) or [Codeable](https://codeable.io/).

NOTE: Unfortunately, we are unable to honor support requests in issues on this repository; as a result, any requests submitted in this manner will be closed.

## Community

For peer to peer support, real-time announcements, and office hours, please [join our slack community](https://cachimanmarketplace.com/community-slack/)!

## Contributing to Cachimanmarketplace 

As an open source project, we rely on community contributions to continue to improve cachimanmarketplace. To contribute, please follow the pre-requisites above and visit our [Contributing to Cachiman](https://developer.cachiman.com/docs/category/contributing/) doc for more links and contribution guidelines.

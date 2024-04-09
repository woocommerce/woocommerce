---
post_title: Setting up your development environment
menu_title: Development environment setup
tags: tutorial, setup
---

## Introduction

Building an extension for WooCommerce is a straightforward process, but there are a several moving parts and a few supporting software tools you'll want to familiarize yourself with. This guide will walk you through the steps of getting a basic development environment set up for building WooCommerce extensions.

If you would like to contribute to the WooCommerce core platform; please read our [contributor documentation and guidelines](https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment).

## Prerequisites

### Recommended reading

WooCommerce extensions are a specialized type of WordPress plugin. If you are new to WordPress plugin development, take a look at a few of these articles in the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/).

### Required software

[Git](https://git-scm.com/)
[nvm](https://github.com/nvm-sh/nvm/blob/master/README.md)
[NodeJS](https://nodejs.org/en)
[PNpm](https://pnpm.io/)
[Composer](https://getcomposer.org/download/)

Note: If you're working on a Windows machine, you may want to take a look at the Building Extensions in Windows Environments section of this guide before proceeding.

### Setting up your reusable WordPress development environment

In addition to the software listed above, you'll also want to have some way of setting up a local development server stack. There are a number of different tools available for this, each with a certain set of functionality and limitations. We recommend choosing an option below that fits your preferred workflow best.

### WordPress-specific tools

[vvv](https://varyingvagrantvagrants.org/) - A highly configurable, cross-platform, and robust environment management tool powered by VirtualBox and Vagrant. This is one the tool that the WooCommerce Core team recommends to contributors.

[wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) - A command-line utility maintained by the WordPress community that allows you to set up and run custom WordPress environments with Docker and JSON manifests.

[LocalWP](https://localwp.com/) - A cross-platform app that bills itself as a one-click WordPress installation.

### General PHP-based web stack tools

[MAMP](https://www.mamp.info/en/mac/) - A local server environment that can be installed on Mac or Windows.

[WAMP](https://www.wampserver.com/en/) - A Windows web development environment that lets you create applications with Apache2, PHP, and MySQL.

[XAMPP](https://www.apachefriends.org/index.html) - An easy-to-install Apache distribution containing MariaDB, PHP, and Perl. It's available for Windows, Linux, and OS X.

### Minimum server requirements

Regardless of the tool you choose for managing your development environment, you should make sure it [meets the server recommendations](https://woocommerce.com/document/server-requirements/?utm_source=wooextdevguide) for WooCommerce as well as the [requirements for running WordPress](https://wordpress.org/about/requirements/).

## Anatomy of a WordPress development environment (public_html/)

While development environments can vary, the basic file structure for a WordPress environment should be consistent.

When developing a WooCommerce extension, you'll usually be doing most of your work within the public_html directory of your local server. For now, take some time to familiarize yourself with a few key paths:

`wp-content/debug.log` - This is the file where WordPress writes the important output such as errors and other messages useful for debugging.

`wp-content/plugins/` - This is the directory on the server where WordPress plugin folders live.

`wp-content/themes/` - This is the directory on the server where WordPress theme folders live.

## Adding WooCommerce Core to your environment

When developing an extension for WooCommerce, it's helpful to install a development version of WooCommerce core.

### Clone the WC Core repo into `wp-content/plugins/`

```sh
cd /your/server/wp-content/plugins
git clone https://github.com/woocommerce/woocommerce.git
cd woocommerce
```

### Activate the required Node version

```sh
nvm use

Found '/path/to/woocommerce/.nvmrc' with version <v12>
Now using node v12.21.0 (npm v6.14.11)
```

Note: if you don't have the required version of Node installed, NVM will alert you so you can install it:

```sh
Found '/path/to/woocommerce/.nvmrc' with version <v12>
N/A: version "v12 -> N/A" is not yet installed.

You need to run "nvm install v12" to install it before using it.
```

### Install dependencies

```sh
pnpm install && composer install
```

### Build WooCommerce

```sh
pnpm build
```

Running this script will compile the JavaScript and CSS that WooCommerce needs to operate. If you try to run WooCommerce on your server without generating the compiled assets, you may experience errors and other unwanted side-effects.

Note: In some environments, you may see an out-of-memory error when you try to build WooCommerce. If this happens, you simply need to adjust the memory_limit setting in your environment's php.ini configuration to a higher value. The process for changing this value varies depending on the environment management tooling you use, so it's best to consult your tool's documentation before making any changes.

## Adding WooCommerce Admin to your environment

Installing a development version of WooCommerce Admin will give you access to some helpful utilities such as a built-in script for generating React-powered WooCommerce extensions.

### Clone the WC Admin repo into `wp-content/plugins/`

```sh
cd /your/server/wp-content/plugins
git clone https://github.com/woocommerce/woocommerce-admin.git
cd woocommerce-admin
```

### Activate the required Node version

```sh
nvm use
Found '/path/to/woocommerce-admin/.nvmrc' with version <lts/*>
Now using node v14.16.0 (npm v6.14.11)
```

Note: if you don't have the required version of Node installed, NVM will alert you so you can install it.

```sh
Found '/path/to/woocommerce-admin/.nvmrc' with version <v12>
N/A: version "lts/* -> N/A" is not yet installed.

You need to run "nvm install lts/*" to install it before using it.
```

Pro-tip: WooCommerce Admin may require a different version of Node than WooCommerce Core requires. Keep this in mind when navigating between directories using the same shell session. As a best practice, always make sure to activate the correct version of Node using nvm use before running any commands inside a cloned repository.

### Install dependencies

```sh
npm install && composer install
```

## Build a development version of WooCommerce Admin

Building a development version will compile unminified versions of asset files, which is useful when debugging extensions that interact with WooCommerce Admin features.

```sh
npm run dev
```

If you run into trouble when building WooCommerce Admin, take a look at this wiki article for troubleshooting help.

## Adding WooCommerce Blocks to your environment

Installing a development version of WooCommerce Blocks is not required in every case, but having a standalone installation of the feature-plugin version of this extension allows you to work with the latest features, which can be helpful for compatibility testing and future-proofing your extension.

### Clone the WC Blocks repo into `wp-content/plugins/`

```sh
cd /your/server/wp-content/plugins
git clone https://github.com/woocommerce/woocommerce-gutenberg-products-block.git
cd woocommerce-gutenberg-products-block
```

### Activate the required Node version

```sh
nvm use

Found '/path/to/woocommerce-gutenberg-products-block/.nvmrc' with version <lts/*>
Now using node v14.16.0 (npm v6.14.11)
```

Note: if you don't have the required version of Node installed, NVM will alert you so you can install it.

```sh
Found '/path/to/woocommerce-gutenberg-products-block/.nvmrc' with version <v12>
N/A: version "lts/* -> N/A" is not yet installed.

You need to run "nvm install lts/*" to install it before using it.
```

Pro-tip: WooCommerce Blocks may require a different version of Node than WooCommerce Core requires. Keep this in mind when navigating between directories using the same shell session. As a best practice, always make sure to activate the correct version of Node using nvm use before running any commands inside a cloned repository.

### Install dependencies

```sh
npm install && composer install
Build the assets
npm run build
```

This will compile and minify the JavaScript and CSS from the /assets directory to be served.

## Finishing up

Once you have WooCommerce and its sibling extensions installed in your WordPress environment, start up your server, browse to your site and handle any initial setup steps or importing you'd like to do. This is a good time to load sample data and activate themes and plugins.

Depending on which extensions you installed in your environment you should have one or more of the following directories in your `public_html` directory:

- `wp-content/plugins/woocommerce`
- `wp-content/plugins/woocommerce-admin`
- `wp-content/plugins/woocommerce-gutenberg-products-block`
- `wp-content/themes/storefront`

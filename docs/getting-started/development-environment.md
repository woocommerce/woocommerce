---
post_title: Setting up your development environment
menu_title: Development environment setup
tags: tutorial, setup
---

## Introduction

Creating a WooCommerce extension - which extends the WooCommerce plugin, or developing a theme for the WooCommerce plugin can be excellent ways to build custom functionality into your store and even monetize your development through the [WooCommerce Marketplace](https://woocommerce.com/products/). 

This guide will walk you through the steps of getting a basic development environment set up for building WooCommerce extensions.

If you would like to contribute to the WooCommerce core platform; please read our [contributor documentation and guidelines](https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment).

## Prerequisites

WooCommerce does adhere to WordPress code standards and guidelines, so it's best to familiarize yourself with [WordPress Development](https://learn.wordpress.org/tutorial/introduction-to-wordpress/) as well as [PHP](https://www.php.net/). 

Knowledge and understanding of [WooCommerce Hooks and Filters](https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/) will allow you to add and change code without editing core files. You can learn more about WordPress hooks and filters in the [WordPress Plugin Development Handbook](https://developer.wordpress.org/plugins/hooks/).

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

[vvv](https://varyingvagrantvagrants.org/) - A highly configurable, cross-platform, and robust environment management tool powered by VirtualBox and Vagrant. This is one tool that the WooCommerce Core team recommends to contributors.

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

### Install and Activate the required Node version

```sh
nvm install
Found '/path/to/woocommerce/.nvmrc' with version <v20>
v20.17.0 is already installed.
Now using node v20.17.0 (npm v10.8.2)
```

Note: if you don't have the required version of Node installed, NVM will install it.

### Install dependencies

```sh
pnpm install && composer install
```


### Build WooCommerce

```sh
pnpm build
```

Running this script will compile the JavaScript and CSS that WooCommerce needs to operate. If you try to run WooCommerce on your server without generating the compiled assets, you may experience errors and other unwanted side-effects.

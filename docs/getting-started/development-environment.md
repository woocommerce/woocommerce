---
post_title: Setting up your development environment
menu_title: Development environment setup
tags: tutorial, setup
---

## Introduction

This guide will walk you through the steps of setting up a basic development environment, which can be useful for store builders, contributors, and extending WooCommerce.

Creating a WooCommerce extension — a plugin which extends the functionality of WooCommerce — or developing a WooCommerce-compatible theme can be an excellent way to build custom functionality into your store and potentially monetize your development through the [WooCommerce Marketplace](https://woocommerce.com/products/?utm_source=wooextdevguide). 

If you would like to contribute to the WooCommerce core platform, please read our [contributor documentation and guidelines](https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment).

If you want to sell your extensions or themes on the WooCommerce marketplace, please [read more about becoming a Woo partner](https://woocommerce.com/partners/?utm_source=wooextdevguide).

## Prerequisites

WooCommerce adheres to WordPress code standards and guidelines, so it's best to familiarize yourself with [WordPress Development](https://learn.wordpress.org/tutorial/introduction-to-wordpress/) as well as [PHP](https://www.php.net/). Currently WooCommerce requires PHP 7.4 or newer.

Knowledge and understanding of [WooCommerce hooks and filters](https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/?utm_source=wooextdevguide) will allow you to add and change code without editing core files. You can learn more about WordPress hooks and filters in the [WordPress Plugin Development Handbook](https://developer.wordpress.org/plugins/hooks/).

### Recommended reading

WooCommerce extensions are a specialized type of WordPress plugin. If you are new to WordPress plugin development, take a look at some of the articles in the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/).

### Required software

There are some specific software requirements you will need to consider when developing WooCommerce extensions. The necessary software includes:

- [Git](https://git-scm.com/)
- [nvm](https://github.com/nvm-sh/nvm/blob/master/README.md)
- [Node.js](https://nodejs.org/)
- [pnpm](https://pnpm.io/)
- [Composer](https://getcomposer.org/)

Note: A POSIX compliant operating system (e.g., Linux, macOS) is assumed. If you're working on a Windows machine, the recommended approach is to use [WSL](https://learn.microsoft.com/en-us/windows/wsl/install) (available since Windows 10).

### Setting up a reusable WordPress development environment

In addition to the software listed above, you'll also want to have some way of setting up a local development server stack. There are a number of different tools available for this, each with a certain set of functionality and limitations. We recommend choosing from the options below that fit your preferred workflow best.

#### WordPress-specific tools

Below are a couple of tools designed specifically for a WordPress environment:

- [vvv](https://varyingvagrantvagrants.org/) is a highly configurable, cross-platform, and robust environment management tool powered by VirtualBox and Vagrant. This is one tool that the WooCommerce Core team recommends to contributors.
- [wp-env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) is a command-line utility maintained by the WordPress community that allows you to set up and run custom WordPress environments with [Docker](https://www.docker.com/) and JSON manifests.

#### General PHP-based web stack tools

Below is a collection of tools to help you manage your environment that are not WordPress-specific.

- [MAMP](https://www.mamp.info/en/mac/) - A local server environment that can be installed on Mac or Windows.
- [WAMP](https://www.wampserver.com/en/) - A Windows web development environment that lets you create applications with Apache2, PHP, and MySQL.
- [XAMPP](https://www.apachefriends.org/index.html) - An easy-to-install Apache distribution containing MariaDB, PHP, and Perl. It's available for Windows, Linux, and OS X.

#### Minimum server requirements

Regardless of the tool you choose for managing your development environment, you should make sure it [meets the server recommendations](https://woocommerce.com/document/server-requirements/?utm_source=wooextdevguide) for WooCommerce as well as the [requirements for running WordPress](https://wordpress.org/about/requirements/).

## Anatomy of a WordPress development environment

While development environments can vary, the basic file structure for a WordPress environment should be consistent.

When developing a WooCommerce extension, you'll usually be doing most of your work within the `public_html/` directory of your local server. Take some time to familiarize yourself with a few key paths:

- `wp-content/debug.log` is the file where WordPress writes the important output such as errors and other messages that can be useful for debugging.
- `wp-content/plugins/` is the directory on the server where WordPress plugin folders live.
- `wp-content/themes/` is the directory on the server where WordPress theme folders live.

## Add WooCommerce Core to your environment

When developing an extension for WooCommerce, it's helpful to install a development version of WooCommerce Core. 

You can install WooCommerce Core on your development environment by:

1. Cloning the WooCommerce Core repository.
2. Installing and activating the required Node version.
3. Installing WooCommerce’s dependencies.
4. Building WooCommerce.

### Clone the WooCommerce Core repository

You can clone the WooCommerce Core repository into `wp-content/plugins/` using the following CLI command:

```sh
cd /your/server/wp-content/plugins
git clone https://github.com/woocommerce/woocommerce.git
cd woocommerce
```

### Install and activate Node

It is recommended to install and activate Node using [Node Version Manager](https://github.com/nvm-sh/nvm) (or **nvm**). You can install nvm using the following CLI command:

```sh
nvm install
```

You can learn more about how to install and utilize nvm in [the nvm GitHub repository](https://github.com/nvm-sh/nvm?tab=readme-ov-file#intro).

### Install dependencies

To install WooCommerce dependencies, use the following CLI command:

```sh
pnpm install
composer install
```

### Build WooCommerce

Use the following CLI command to compile the JavaScript and CSS that WooCommerce needs to operate:

```sh
pnpm build
```

Note: If you try to run WooCommerce on your server without generating the compiled assets, you may experience errors and other unwanted side-effects.

Alternatively, you can generate a `woocommerce.zip` file with the following command: 

```sh
pnpm build:zip
```

A `woocommerce.zip` file may be helpful if you’d like to upload a modified version of WooCommerce to a separate test environment.

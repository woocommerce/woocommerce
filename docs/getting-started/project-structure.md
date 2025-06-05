---
post_title: Project structure
sidebar_label: Project structure
sidebar_position: 1
---

# Project Structure

## Prerequisites

WooCommerce adheres to WordPress code standards and guidelines, so it's best to familiarize yourself with [WordPress Development](https://learn.wordpress.org/tutorial/introduction-to-wordpress/) as well as [PHP](https://www.php.net/). Currently WooCommerce requires PHP 7.4 or newer.

Knowledge and understanding of [WooCommerce hooks and filters](https://woocommerce.com/document/introduction-to-hooks-actions-and-filters/?utm_source=wooextdevguide) will allow you to add and change code without editing core files. You can learn more about WordPress hooks and filters in the [WordPress Plugin Development Handbook](https://developer.wordpress.org/plugins/hooks/).

## Recommended reading

WooCommerce extensions are a specialized type of WordPress plugin. If you are new to WordPress plugin development, take a look at some of the articles in the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/).

## Anatomy of a WordPress environment

While development environments can vary, the basic file structure for a WordPress environment should be consistent.

When developing a WooCommerce extension, you'll usually be doing most of your work within the `public_html/` directory of your local server.

There are three directories in a WordPress installation. The `wp-admin` and `wp-includes` directories include core functionality and should not be modified. The third directory, `wp-content` is where custom configurations and user-generated media is stored.

 Take some time to familiarize yourself with a few key paths inside `wp-content`:

* `wp-content/debug.log` is the file where WordPress writes the important output such as errors and other messages that can be useful for debugging.  
* `wp-content/plugins`/ is the directory on the server where WordPress plugin folders live.  
* `wp-content/themes/` is the directory on the server where WordPress theme folders live. A theme is a collection of templates and styles, and WordPress can have only one active theme.

Finally, at the root of your WordPress installation is one more configurable file, `wp-config.php`. This file acts similarly to a `.env` file and stores important security credentials and variables that define your environment configuration.

## WooCommerce Plugin Structure 

When adding WooCommerce to a WordPress installation, you can either install the plugin from inside the WordPress dashboard or manually upload the plugin directory to the `wp-content/plugins` directory. 

**Important:** The WooCommerce repository is a monorepo that includes multiple plugins and packages. To install WooCommerce from the repository, you cannot simply clone the entire repo into `wp-content/plugins`. The WooCommerce plugin sits in the monorepo inside the `plugins/` directory, so if you’re planning to develop from the repository, we recommend cloning it outside of your local WordPress installation and then using a symlink to “place” it in `wp-content/plugins`. 

Each plugin, package, and tool has its own `package.json` file containing project-specific dependencies and scripts. Most projects also contain a `README.md` file with any project-specific setup instructions and documentation.

- [**Plugins**](http://plugins): Our repository contains plugins that relate to or otherwise aid in the development of WooCommerce.  
  - [**WooCommerce Core**](http://plugins/woocommerce): The core WooCommerce plugin is available in the plugins directory.  
- [**Packages**](http://packages): Contained within the packages directory are all of the [PHP](http://packages/php) and [JavaScript](http://packages/js) provided for the community. Some of these are internal dependencies and are marked with an `internal-` prefix.  
- [**Tools**](http://tools): We also have a growing number of tools within our repository. Many of these are intended to be utilities and scripts for use in the monorepo, but, this directory may also contain external tools.

If you'd like to learn more about how our monorepo works, [please check out this guide here](http://tools/README.md).

## Theming and Extending Functionality

Unless you’re contributing directly to WooCommerce core, you will not edit WordPress or WooCommerce files directly. All modification of functionality is done by creating a custom extension or modifying the `functions.php` file of your active theme. 

To edit the *design* of a WooCommerce store, we recommend modifying or creating a custom theme. Learn more about theming WooCommerce in our [Theme Development Handbook](/docs/theming/theme-development/classic-theme-developer-handbook).

To edit the *functionality* of a WooCommerce store, you have multiple options. First, you can use the [Woo Marketplace](https://woocommerce.com/marketplace) to find a suitable, pre-made extension that meets your needs. For simple customizations, you can learn more about easy ways to add [code snippets](/docs/code-snippets/) to your store. For more advanced development needs, we recommend building a custom extension (i.e. a WordPress plugin). 
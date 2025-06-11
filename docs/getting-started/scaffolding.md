---
post_title: Scaffolding and sample store data
sidebar_label: Scaffolding and sample data
sidebar_position: 3
---

# Scaffolding and sample store data

WooCommerce offers a number of starter kits and scaffolds depending on what you are building.

## Starter Themes

If you’re designing a WooCommerce store, you have two options for theme development: Classic Themes and Block Themes.

- Classic themes use PHP templates to override designs to key pages on your store, including your product pages, product archives, shopping cart, and checkout page. While sites running a classic theme can use the WordPress block editor, many of the templates are not editable within the editor itself.  
- Block themes use the WordPress site editor to generate every aspect of the WordPress site, including the header and footer, product pages, archives, and the cart and checkout pages. Designs built in the site editor can be exported into flat HTML files, but the files themselves are typically edited in the WordPress editor.

### Storefront Theme (Classic)

Storefront is Woo’s flagship classic theme, available in the [WordPress Theme Directory](https://wordpress.org/themes/). You can either rename and modify the theme itself, or override specific aspects of it using a child theme. 

For more information on building a classic WooCommerce theme, read our classic theme development handbook. For a comprehensive guide on creating a child block theme and understanding the differences between a classic and block theme, please refer to [WooCommerce block theme development](http://../block-theme-development/theming-woo-blocks.md) and [WordPress block child theme development](https://learn.wordpress.org/lesson-plan/create-a-basic-child-theme-for-block-themes/).

### Block Starter Themes

If you are completely new to block theme development, please check [Develop Your First Low-Code Block Theme](https://learn.wordpress.org/course/develop-your-first-low-code-block-theme/) to learn about block theme development, and explore the [Create Block Theme plugin](https://wordpress.org/plugins/create-block-theme/) tool when you're ready to create a new theme.

For more information, check out our [Block Theme Development handbook](/docs/theming/block-theme-development/theming-woo-blocks).

## Extension Scaffolds

### woocommerce/create-woo-extension

Create Woo Extension is an NPX command that scaffolds an entire WooCommerce extension for your store. The generated extensions adds a React-based settings page integrating with WooCommerce Admin. Also included are PHP and Javascript unit testing, linting, and Prettier IDE configuration for WooCommerce and WordPress.

Read our full tutorial on using the [create-woo-extension package](/docs/extensions/getting-started-extensions/building-your-first-extension).

### WooCommerce admin extension examples

Inside of the WooCommerce plugin are a set of example extensions that showcase different use-cases for modifying WooCommerce core functionality. Some examples include adding a custom report, a custom payment gateway, and modifying the WooCommerce dashboard.

Read our full tutorial showcaseing [how to extend WooCommerce analytics reports](https://developer.woocommerce.com/docs/how-to-extend-woocommerce-analytics-reports/).

## Relevant WordPress Scaffolds

### Default WordPress Theme

The default WordPress theme (Twenty-Twenty Five as of the time of this writing) is a great place to see the best practices and standard conventions of a WordPress block theme. Using the Create Block Theme tool, you can modify the theme design from within the site edtitor and then export your new design to a custom child theme.

### wordpress/create-block

If you’re adding additional content or design elements to WordPress, it may make sense to create a custom block. The WordPress block editor package library includes a scaffolding tool called WordPress Create Block that helps you spin up custom blocks that can be inserted into any page or template.

Read more about the [`wordpress/create-block` package](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/).

## Sample Store Data

### Core Plugin Sample Data

It may be helpful to load your local store with sample. In the WooCommerce core plugin, you can find CSV and XML files that can be imported directly into WooCommerce using the WordPress admin or via WC-CLI. The sample data is located in  [`/plugins/woocommerce/sample-data/`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/sample-data).

### Smooth Generator

For more advanced testing, you may want sample customers and order data. [Smooth Generator](https://github.com/woocommerce/wc-smooth-generator) is a plugin to help you generate WooCommerce-related data for testing. Use the WP Admin interface for basic operations, or the CLI tool for more advanced features. Download and install the latest version from the [Releases page](https://github.com/woocommerce/wc-smooth-generator/releases) and browse the repository for more documentation.
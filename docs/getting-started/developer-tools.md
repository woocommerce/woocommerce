---
post_title: WooCommerce developer tools
sidebar_label: Developer tools
sidebar_position: 5
---

# Developer tools

This guide provides an overview of essential tools and libraries for WooCommerce development. It's intended for developers looking to enhance their WooCommerce projects efficiently.

## Productivity Tools

Use these resources to power up your WooCommerce development workflows.

### Development

#### [wp-cli](https://wp-cli.org/)

This is the command-line interface for [WordPress](https://wordpress.org/). You can update plugins, configure multisite installations and much more, without using a web browser.

#### [wc-cli](/docs/wc-cli/cli-overview)

When WooCommerce is running on a WordPress installation, the WP-CLI is extended with additional functionality for managing your store data.

#### [wp-env](https://www.npmjs.com/package/@wordpress/env)

This command-line tool lets you easily set up a local WordPress Docker environment for building and testing plugins and themes. It's simple to install and requires no configuration.

#### [woocommerce/eslint-plugin](https://www.npmjs.com/package/@woocommerce/eslint-plugin)

This is an [ESLint](https://eslint.org/) plugin including configurations and custom rules for WooCommerce development.

#### [WordPress Scripts](https://www.npmjs.com/package/@wordpress/scripts)

The ⁠@wordpress/scripts package is a set of tools and scripts designed to streamline the development process of WordPress projects, particularly for block development and custom Gutenberg integrations. It includes a Webpack build process along with configuration for tasks like linting, styling, and testing.

It also includes the [Dependency Extraction Webpack Plugin](https://www.npmjs.com/package/@wordpress/dependency-extraction-webpack-plugin), which allows JavaScript bundles produced by webpack to leverage WordPress style dependency sharing without an error-prone process of manually maintaining a dependency list.

### Testing

#### [Smooth Generator](https://github.com/woocommerce/wc-smooth-generator)

A plugin to help you generate WooCommerce-related data for testing. Use the WP Admin interface for basic operations, or the CLI tool for more advanced features. Download and install the latest version from the [Releases page](https://github.com/woocommerce/wc-smooth-generator/releases) and review the [documentation on GitHub](https://github.com/woocommerce/wc-smooth-generator).

#### [WooCommerce Dummy Payments Gateway](https://github.com/woocommerce/woocommerce-gateway-dummy)

A dummy payment gateway for your WooCommerce development needs, with built-in support for subscriptions and the block-based checkout.

#### [QIT](https://qit.woo.com/)

QIT is a testing platform for WooCommerce plugins and themes with managed tests, E2E tests, and disposable local testing environments.

### Static analysis

PHP stubs describe WordPress, WooCommerce, and extension APIs so tools such as PHPStan can analyze integrations without loading their source code.

#### [WordPress stubs](https://github.com/php-stubs/wordpress-stubs)

Declarations for WordPress core functions, classes, and interfaces.

#### [WooCommerce stubs](https://github.com/php-stubs/woocommerce-stubs)

Declarations for WooCommerce Core functions, classes, and interfaces.

#### [WooCommerce extension stubs](https://github.com/woocommerce/php-stubs)

Versioned declarations for Woo extensions. Use them with WooCommerce stubs to check integrations against the minimum extension versions you support.

## Libraries

Use these resources to help take some of the heavy lifting off of fetching and transforming data \-- as well as creating UI elements.

### API Clients

#### [WooCommerce REST API - JavaScript](https://www.npmjs.com/package/@woocommerce/woocommerce-rest-api)

The official JavaScript library for working with the WooCommerce REST API.

#### [WooCommerce Store API](https://developer.woocommerce.com/docs/category/store-api/)

The Store API provides public Rest API endpoints for the development of customer-facing cart, checkout, and product functionality. It follows many of the patterns used in the WordPress REST API.

In contrast to the WooCommerce REST API, the Store API is unauthenticated and does not provide access to sensitive store data or other customer information.

#### [wordpress/api-fetch](https://www.npmjs.com/package/@wordpress/api-fetch)

The `@wordpress/api-fetch` package is a utility that simplifies AJAX requests to the WordPress REST API. It's a wrapper around `window.fetch` that provides a consistent interface for handling authentication, settings, and errors, allowing developers to easily interact with WordPress backend services.

### Components

For WooCommerce admin UI, follow the [admin component guidance](../extensions/ux-guidelines-extensions/README.md#admin-components): prefer `@wordpress/ui`, and use `@wordpress/components` as a fallback for verified coverage gaps in the supported version. Document the missing capability and the reason for each fallback.

Component selection is separate from WooCommerce integration. Continue to use the supported WooCommerce APIs for page registration, settings, data, navigation and saving.

#### [WordPress UI](https://www.npmjs.com/package/@wordpress/ui)

Start here for shared WordPress Design System components in new admin interfaces and modernization work. Review the [component documentation and examples](https://wordpress.github.io/gutenberg/?path=/docs/design-system-components-introduction--docs), including the supported APIs and theme setup for your target package versions.

#### [WordPress Components](https://www.npmjs.com/package/@wordpress/components)

This package supplies components used in existing WordPress interfaces. For new WooCommerce admin work, use it when `@wordpress/ui` lacks a required component or capability in the supported version. Verify the fallback's API, accessibility, styling and compatibility using the [component reference](https://wordpress.github.io/gutenberg/?path=/docs/components-introduction--docs).

### JavaScript Utility Packages

#### [CSV Export](https://www.npmjs.com/package/@woocommerce/csv-export)

A set of functions to convert data into CSV values, and enable a browser download of the CSV data.

#### [Currency](https://www.npmjs.com/package/@woocommerce/currency)

A collection of utilities to display and work with currency values.

#### [Data](https://www.npmjs.com/package/@woocommerce/data)

Utilities for managing the WooCommerce Admin data store.

#### [Date](https://www.npmjs.com/package/@woocommerce/date)

A collection of utilities to display and work with date values.

#### [Navigation](https://www.npmjs.com/package/@woocommerce/navigation)

A collection of navigation-related functions for handling query parameter objects, serializing query parameters, updating query parameters, and triggering path changes.

#### [Number](https://www.npmjs.com/package/@woocommerce/number)

A collection of utilities to properly localize numerical values in WooCommerce.

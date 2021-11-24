# Folder Structure

The following snippet explains how the WooCommerce Blocks repository is structured omitting irrelevant or obvious items with further explanations:

    │
    ├── LICENSE
    ├── README.md
    ├── .github/CODEOWNERS
    ├── .github/CODE_OF_CONDUCT.md
    ├── .github/CONTRIBUTING.md
    ├── .github/SECURITY.md
    │
    ├── .distignore
    ├── .editorconfig
    ├── .eslintignore
    ├── .eslintrc
    ├── .gitattributes
    ├── .gitignore
    ├── .prettierrc.js
    ├── .stylelintrc.json
    ├── phpcs.xml.dist
    │   Dot files and config files used to configure the various linting tools
    │   used in the repository (PHP, JS, styles...).
    │
    ├── .nvmrc
    │   Required Node version of the project.
    │
    ├── babel.config.js
    ├── global.d.ts
    ├── tsconfig.base.json
    ├── tsconfig.json
    ├── webpack.config.js
    │   Transpilation and bundling config files.
    │
    ├── phpcs.xml
    │   Config file for the PHP Coding Standards.
    │
    ├── phpunit.xml.dist
    │   Config file for PHPUnit.
    │
    ├── postcss.config.js
    │   Config file for PostCSS.
    │
    ├── renovate.json
    │   Config file for Renovate.
    │
    ├── docker-compose.yml
    │   Docker config files for the development and testing environment.
    │
    ├── .env
    ├── .wp-env.json
    │   Config files for the development and testing environment.
    │   Includes WordPress, the WooCommerce plugin and the Storefront theme.
    │
    ├── composer.lock
    ├── composer.json
    │   Handling of PHP dependencies. Used for development tools and autoloading.
    │
    ├── package-lock.json
    ├── package.json
    │	Handling of JavaScript dependencies. Both for development tools and
    │   production dependencies. The package.json also serves to define common
    |   tasks and scripts used for day to day development.
    │
    ├── readme.txt
    │   Readme of the WooCommerce Blocks plugin hosted on the WordPress
    │   plugin repository.
    │
    ├── woocommerce-gutenberg-products-block.php
    │   Entry point of the WooCommerce Blocks plugin.
    │
    ├── .github/*
    │   Config of the different Github features (issues and PR templates, CI,
    │   owners).
    │
    ├── .sources/*
    │   Sketch files of the WooCommerce Blocks plugin.
    │
    ├── .wordpress-org/*
    │   Assets of the WooCommerce Blocks plugin hosted on the WordPress plugin
    │   repository.
    │
    ├── assets/css/*
    │   The SCSS files of the WooCommerce Blocks plugin.
    │
    ├── assets/js/*
    │   The React components of the WooCommerce Blocks plugin.
    │
    ├── assets/js/atomic
    │   The atomic components such as product title, product rating, product
    │   image, etc. These atomic components are used by the product blocks.
    │
    ├── assets/js/base
    │   Base contains components specific to the frontend of the store.
    │   Components placed here avoid loading larger Gutenberg dependencies to
    │   keep client script sizes to a minimum.
    │
    ├── assets/js/blocks-registry
    │   Files that allows developers to connect their extensions to this plugin.
    │
    ├── assets/js/blocks
    │   The main blocks of the WooCommerce Blocks plugin such as the Active
    │   Filters Block.
    │
    ├── assets/js/data
    │   Functionality to store data using Redux and wp.data.
    │
    ├── assets/js/editor-components
    │   Editor components such as the block-title component.
    │
    ├── assets/js/extensions
    │   TypeScript files to allow Google Analytics tracking of specific events
    │   such as active payment method and removing cart items.
    │
    ├── assets/js/filters
    │   Filter such as excluding the checkout draft from Google Analytics.
    │
    ├── assets/js/hocs
    │   The Higher Order Components of the WooCommerce Blocks plugin.
    │
    ├── assets/js/icons
    │   The WooCommerce Blocks Icon library.
    │
    ├── assets/js/legacy
    │   The legacy code of the WooCommerce Blocks plugin.
    │
    ├── assets/js/middleware
    │   The middleware code to handle Store API calls.
    │
    ├── assets/js/payment-method-extensions
    │   Functionality for the payment options such as PayPal and Stripe.
    │
    ├── assets/js/previews
    │   The previews of various components such the All Products Block.
    │
    ├── assets/js/settings
    │   Functionality to view the settings in the frontend.
    │
    ├── assets/js/shared
    │   Shared components of the WooCommerce Blocks plugin.
    │
    ├── assets/js/types
    │   TypeScript definitions of the WooCommerce Blocks plugin.
    │
    ├── assets/js/utils
    │   Shared utilities of the WooCommerce Blocks plugin.
    │
    ├── bin/*
    │   Set of scripts used to build the WordPress packages.
    │
    ├── docs/*
    │   Set of documentation pages of the WooCommerce Blocks plugin.
    │
    ├── images
    │   Images for the payment options and the previews.
    │
    ├── packages
    │   Things that are likely to be published as npm packages in the future. Packages can be:
    │    - Production JavaScript scripts and styles loaded on WordPress
    │      and the WooCommerce Blocks plugin or distributed as npm packages.
    │    - Development tools available on npm.
    │
    ├── patches
    │   Patches for 3rd party scripts applied when installing dependencies.
    │
    ├── src
    │   The core PHP files of the WooCommerce Blocks plugin.
    │
    ├── storybook
    │   Config of the [WooCommerce Blocks Storybook](https://woocommerce.github.io/woocommerce-gutenberg-products-block/).
    │
    ├── templates/emails
    │   Email templates of the WooCommerce Blocks plugin.
    │
    ├── tests/e2e
    │   Set of end-to-end tests.
    │
    ├── tests/js
    │   Configuration for Jest.
    │
    ├── tests/php
    │   Configuration for the PHP unit tests
    │
    ├── tests/utils
    │   Utilities for the test cases.
    │

    ## Credits

    This file is inspired by the great work of @JustinyAhin and @gziolo in https://github.com/WordPress/gutenberg/blob/trunk/docs/contributors/folder-structure.md.

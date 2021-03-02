# WooCommerce Admin

This is a feature plugin for a modern, javascript-driven WooCommerce Admin experience.

## Prerequisites

[WordPress 5.4 or greater](https://wordpress.org/download/) and [WooCommerce 4.8.0 or greater](https://wordpress.org/plugins/woocommerce/) should be installed prior to activating the WooCommerce Admin feature plugin.

For better debugging, it's also recommended you add `define( 'SCRIPT_DEBUG', true );` to your wp-config. This will load the unminified version of all libraries, and specifically the development build of React.

## Development

After cloning the repo, install dependencies:

-   `npm install` to install JavaScript dependencies.
-   `composer install` to gather PHP dependencies.

Now you can build the files using one of these commands:

-   `npm run build` : Build a production version
-   `npm run dev` : Build a development version
-   `npm start` : Build a development version, watch files for changes
-   `npm run build:release` : Build a WordPress plugin ZIP file (`woocommerce-admin.zip` will be created in the repository root)

For more helper scripts [see here](./CONTRIBUTING.md#helper-scripts)

For some debugging tools/help [see here](./CONTRIBUTING.md#debugging)

For local development setup using Docker [see here](./docker/wc-admin-wp-env/README.md)

### Testing

#### End-to-end tests

Tests live in `./tests/e2e`. An existing build is required prior running, please refer to the section above for steps. E2E tests have their own Docker container to run the WordPress server. Start
the server using:

```
npm run docker:up
```

Run tests using:

```
npm run test:e2e-dev
```

or in headless mode:

```
npm run test:e2e
```

Run a single test by adding the file name:

```
npm run test:e2e-dev complete-onboarding-wizard.test.js
```

## Common Issues

If you're encountering any issue setting things up, chances are we have been there too. Please have a look at our [wiki](https://github.com/woocommerce/woocommerce-admin/wiki/Common-Issues) for a list of common problems.

## Privacy

If you have enabled WooCommerce usage tracking ( option `woocommerce_allow_tracking` ) then, in addition to the tracking described in https://woocommerce.com/usage-tracking/, this plugin also sends information about the actions that site administrators perform to Automattic - see https://automattic.com/privacy/#information-we-collect-automatically for more information.

## Contributing

There are many ways to contribute – reporting bugs, adding translations, feature suggestions and fixing bugs. For full details, please see [CONTRIBUTING.md](./CONTRIBUTING.md)

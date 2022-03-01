# WooCommerce Admin

This is a feature plugin for a modern, javascript-driven WooCommerce Admin experience.

## Prerequisites

[WordPress 5.6 or greater](https://wordpress.org/download/) and [WooCommerce 5.7.0 or greater](https://wordpress.org/plugins/woocommerce/) should be installed prior to activating the WooCommerce Admin feature plugin.

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
-   `DRY_RUN=1 npm run build:release` : Builds a Wordpress plugin ZIP **without** pushing it to Github and creating a release.

For more helper scripts [see here](./CONTRIBUTING.md#helper-scripts)

For some debugging tools/help [see here](./CONTRIBUTING.md#debugging)

For local development setup using Docker [see here](./docker/wc-admin-wp-env/README.md)

### Typescript

The `npm run ts:check` command will check your TypeScript files for errors, and has been added to `.vscode/tasks.json`. 
Running this task in vscode will highlight the errors in your editor file navigator.

If you allow the `npm run ts:check:watch` command to run automatically as configured, it will run in the background and pick up any errors as you save the files.
Note: Even if you don't run this task, the IDE uses its language server to pick up type errors in files that are open. This is only necessary for picking up errors
across the entire repository even when they haven't been opened in the IDE.

### Testing

#### End-to-end tests

Tests live in `./tests/e2e`. An existing build is required prior running, please refer to the section above for steps. E2E tests use the `@woocommerce/e2e-environment` package which hosts a Docker container for testing, by default the container can be accessed at `http://localhost:8084`

All the commands from `@woocommerce/e2e-environment` can be run through `npx`.

```
# Set up the e2e environment
npm i
npx wc-e2e docker:up
```

Run tests using:

```
npx wc-e2e test:e2e-dev
```

or in headless mode:

```
npx wc-e2e test:e2e
```

Run a single test by adding the path to the file name:

```
npx wc-e2e test:e2e-dev tests/e2e/specs/activate-and-setup/complete-onboarding-wizard.test.ts
```

### Documentation

There is documentation in 2 forms available in the repo. A static set of documentation supported by docsify and also a Storybook containing component documentation for `@woocommerce/components`.

To view the docsify docs locally you can do:

```
npm install
cd docs
npx docsify serve
```

When deployed the docsify docs also host an embedded version of the storybook docs. To generate that and test it locally in docsify you'll need to run:

```
npm install
npm run docs
cd docs
npx docsify serve
```

Then navigate to `Components` from the left hand menu in the docs.

If you would like to view the storybook docs hosted standalone, then you can run:

```
npm install
npm run storybook
```

If you would like to view the storybook docs in right-to-left styling, you can run this instead:

```
npm install
npm run storybook-rtl
```

## Common Issues

If you're encountering any issue setting things up, chances are we have been there too. Please have a look at our [wiki](https://github.com/woocommerce/woocommerce-admin/wiki/Common-Issues) for a list of common problems.

## Privacy

If you have enabled WooCommerce usage tracking ( option `woocommerce_allow_tracking` ) then, in addition to the tracking described in https://woocommerce.com/usage-tracking/, this plugin also sends information about the actions that site administrators perform to Automattic - see https://automattic.com/privacy/#information-we-collect-automatically for more information.

## Contributing

There are many ways to contribute – reporting bugs, adding translations, feature suggestions and fixing bugs. For full details, please see [CONTRIBUTING.md](./CONTRIBUTING.md)

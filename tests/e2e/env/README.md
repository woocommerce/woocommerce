# End to End Testing Environment

A reusable and extendable E2E testing environment for WooCommerce extensions.

## Installation

```bash
npm install @woocommerce/e2e-environment --save
npm install jest --global
```

## Configuration

The `@woocommerce/e2e-environment` package exports configuration objects that can be consumed in JavaScript config files in your project. Additionally, it contains several files to serve as the base for a Docker container and Travis CI setup.

### Babel Config

Make sure you `npm install @babel/preset-env --save` if you have not already done so. Afterwards, extend your project's `babel.config.js` to contain the expected presets for E2E testing.

```js
const { babelConfig: e2eBabelConfig } = require( '@woocommerce/e2e-environment' );

module.exports = function( api ) {
	api.cache( true );

	return {
		...e2eBabelConfig,
		presets: [
			...e2eBabelConfig.presets,
			'@wordpress/babel-preset-default',
		],
		....
	};
};
```

### ES Lint Config

The E2E environment uses Puppeteer for headless browser testing, which uses certain globals variables. Avoid ES Lint errors by extending the config.

```js
const { esLintConfig: baseConfig } = require( '@woocommerce/e2e-environment' );

module.exports = {
	...baseConfig,
	root: true,
	parser: 'babel-eslint',
	extends: [
		...baseConfig.extends,
		'wpcalypso/react',
		'plugin:jsx-a11y/recommended',
	],
	plugins: [
		...baseConfig.plugins,
		'jsx-a11y',
	],
	env: {
		...baseConfig.env,
		browser: true,
		node: true,
	},
	globals: {
		...baseConfig.globals,
		wp: true,
		wpApiSettings: true,
		wcSettings: true,
	},
	....
};
```

### Jest Config

The E2E environment uses Jest as a test runner. Extending the base config is needed in order for Jest to run your project's test files.

```js
const path = require( 'path' );
const { jestConfig: baseE2Econfig } = require( '@woocommerce/e2e-environment' );

module.exports = {
	...baseE2Econfig,
	// Specify the path of your project's E2E tests here.
	roots: [ path.resolve( __dirname, '../specs' ) ],
};
```

**NOTE:** Your project's Jest config file is expected to found at: `tests/e2e/config/jest.config.js`.

### Webpack Config

The E2E environment provides a `@woocommerce/e2e-tests` alias for easy use of the WooCommerce E2E test helpers.

```js
const { webpackAlias: coreE2EAlias } = require( '@woocommerce/e2e-environment' );

module.exports = {
	....
	resolve: {
		alias: {
			...coreE2EAlias,
			....
		},
	},
};
```

### Docker Setup

The E2E environment will look for a `docker-compose.yaml` file in your project root. This will be combined with the base Docker config in the package. This is where you'll map your local project files into the Docker container(s).

```yaml
version: '3.3'

services:

  wordpress-www:
    volumes:
      # This path is relative to the first config file
      # which is in node_modules/@woocommerce/e2e/env
      - "../../../:/var/www/html/wp-content/plugins/your-project-here"

  wordpress-cli:
    volumes:
      - "../../../:/var/www/html/wp-content/plugins/your-project-here"
```

#### Docker Container Initialization Script

You can provide an initialization script that will run in the WP-CLI Docker container. Place an executable file at `tests/e2e/docker/initialize.sh` in your project and it will be copied into the container and executed. While you can run any commands you wish, the intent here is to use WP-CLI to set up your testing environment. E.g.:

```
#!/bin/bash

echo "Initializing WooCommerce E2E"

wp plugin install woocommerce --activate
wp theme install twentynineteen --activate
wp user create customer customer@woocommercecoree2etestsuite.com --user_pass=password --role=customer --path=/var/www/html
```

### Additional Packages

There are a few packages you may find useful in writing tests scripts. You can see these in use in the `tests/e2e/utils` and `tests/e2e/specs` folders.

- `@wordpress/e2e-test-utils`
- `config`

### Travis CI

Add the following to the appropriate sections of your `.travis.yml` config file.

```yaml
version: ~> 1.0

  include:
    - name: "Core E2E Tests"
    php: 7.4
    env: WP_VERSION=latest WP_MULTISITE=0 RUN_E2E=1

....

script:
  - npm install jest --global
  - npm explore @woocommerce/e2e-environment -- npm run test:e2e

....

after_script:
  - npm explore @woocommerce/e2e-environment -- npm run docker:down
```

Use `[[ ${RUN_E2E} == 1 ]]` in your bash scripts to test for the core e2e test run.

## Usage

Start Docker

```bash
npm explore @woocommerce/e2e-environment -- npm run docker:up
```

Run E2E Tests

```bash
npm explore @woocommerce/e2e-environment -- npm run test:e2e
npm explore @woocommerce/e2e-environment -- npm run test:e2e-dev
```

Stop Docker

```bash
npm explore @woocommerce/e2e-environment -- npm run docker:down
```

## Additional information

Refer to [`tests/e2e/specs`](https://github.com/woocommerce/woocommerce/tree/master/tests/e2e/specs) for some test examples, and [`tests/e2e`](https://github.com/woocommerce/woocommerce/tree/master/tests/e2e) for general information on e2e tests.

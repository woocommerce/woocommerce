# End to End Testing Environment

A reusable and extendable E2E testing environment for WooCommerce extensions.

## Installation

```bash
npm install @woocommerce/e2e-environment --save
npm install jest --global
```

## Configuration

The `@woocommerce/e2e-environment` package exports configuration objects that can be consumed in JavaScript config files in your project. Additionally, it includes a hosting container for running tests and includes instructions for creating your Travis CI setup.

### Babel Config

Make sure you `npm install @babel/preset-env --save` if you have not already done so. Afterwards, extend your project's `babel.config.js` to contain the expected presets for E2E testing.

```js
const { useE2EBabelConfig } = require( '@woocommerce/e2e-environment' );

module.exports = function( api ) {
	api.cache( true );

	return useE2EBabelConfig( {
		presets: [
			'@wordpress/babel-preset-default',
		],
	} );
};
```

### ES Lint Config

The E2E environment uses Puppeteer for headless browser testing, which uses certain globals variables. Avoid ES Lint errors by extending the config.

```js
const { useE2EEsLintConfig } = require( '@woocommerce/e2e-environment' );

module.exports = useE2EEsLintConfig( {
	root: true,
	env: {
		browser: true,
		es6: true,
		node: true
	},
	globals: {
		wp: true,
		wpApiSettings: true,
		wcSettings: true,
		es6: true
	},
} );
```

### Jest Config

The E2E environment uses Jest as a test runner. Extending the base config is needed in order for Jest to run your project's test files.

```js
const path = require( 'path' );
const { useE2EJestConfig } = require( '@woocommerce/e2e-environment' );

const jestConfig = useE2EJestConfig( {
	roots: [ path.resolve( __dirname, '../specs' ) ],
} );

module.exports = jestConfig;
```

**NOTE:** Your project's Jest config file is expected to be: `tests/e2e/config/jest.config.js`.

### Jest Puppeteer Config

The test sequencer uses the following default Puppeteer configuration:

```js
// headless
	puppeteerConfig = {
		launch: {
			// Required for the logged out and logged in tests so they don't share app state/token.
			browserContext: 'incognito',
		},
	};
// dev mode
	puppeteerConfig = {
		launch: {
			...jestPuppeteerConfig.launch, // @automattic/puppeteer-utils
			ignoreHTTPSErrors: true,
			headless: false,
			args: [ '--window-size=1920,1080', '--user-agent=chrome' ],
			devtools: true,
			defaultViewport: {
				width: 1280,
				height: 800,
			},
		},
	};
```

You can customize the configuration in `tests/e2e/config/jest-puppeteer.config.js`

```js
const { useE2EJestPuppeteerConfig } = require( '@woocommerce/e2e-environment' );

const puppeteerConfig = useE2EJestPuppeteerConfig( {
	launch: {
		headless: false,
	}
} );

module.exports = puppeteerConfig;
```

### Jest Setup

Jest provides setup and teardown functions similar to PHPUnit. The default setup and teardown is in [`tests/e2e/env/src/setup/jest.setup.js`](src/setup/jest.setup.js). Additional setup and teardown functions can be added to [`tests/e2e/config/jest.setup.js`](../config/jest.setup.js)

### Container Setup

Depending on the project and testing scenario, the built in testing environment container might not be the best solution for testing. This could be local testing where there is already a testing container, a repository that isn't a plugin or theme and there are multiple folders mapped into the container, or similar. The `e2e-environment` container supports using either the built in container or an external container. See the the appropriate readme for  details:

- [Built In Container](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e/env/builtin.md)
- [External Container](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e/env/external.md)

## Additional information

Refer to [`tests/e2e/core-tests`](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e/core-tests) for some test examples, and [`tests/e2e`](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e) for general information on e2e tests.

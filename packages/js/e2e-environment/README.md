# End to End Testing Environment

A reusable and extendable E2E testing environment for WooCommerce extensions.

## Installation

```bash
npm install @woocommerce/e2e-environment --save
npm install jest --global
```

## Configuration

The `@woocommerce/e2e-environment` package exports configuration objects that can be consumed in JavaScript config files in your project. Additionally, it includes a basic hosting container for running tests and includes instructions for creating your Travis CI setup.

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

The E2E environment uses Jest as a test runner. Extending the base config is necessary in order for Jest to run your project's test files.

```js
const path = require( 'path' );
const { useE2EJestConfig } = require( '@woocommerce/e2e-environment' );

const jestConfig = useE2EJestConfig( {
	roots: [ path.resolve( __dirname, '../specs' ) ],
} );

module.exports = jestConfig;
```

**NOTE:** Your project's Jest config file is: `tests/e2e/config/jest.config.js`.

#### Test Screenshots

The test sequencer provides a screenshot function for test failures. To enable screenshots on test failure use

```shell script
WC_E2E_SCREENSHOTS=1 npx wc-e2e test:e2e
```

To take adhoc in test screenshots use

```js
await takeScreenshotFor( 'name of current step' );
```

Screenshots will be saved to `tests/e2e/screenshots`. This folder is cleared at the beginning of each test run.

### Override default test timeout

To override the default timeout for the tests, you can use the `DEFAULT_TIMEOUT_OVERRIDE` flag and pass in a maximum timeout in milliseconds. For example, you can pass it in when running the tests from the command line:

```bash
DEFAULT_TIMEOUT_OVERRIDE=35000 npx wc-e2e test:e2e
```

This value will override the default Jest timeout as well as pass the timeout to the following Puppeteer methods:

* page.setDefaultTimeout();
* page.setDefaultNavigationTimeout();

For a list of the methods that the above timeout affects, please see the Puppeteer documentation for [`page.setDefaultTimeout()`](https://pptr.dev/#?product=Puppeteer&version=v10.2.0&show=api-pagesetdefaulttimeouttimeout) and [`page.setDefaultNavigationTimeout`](https://pptr.dev/#?product=Puppeteer&version=v10.2.0&show=api-pagesetdefaultnavigationtimeouttimeout) for more information.

### Test Against Previous WordPress Versions

You can use the `LATEST_WP_VERSION_MINUS` flag to determine how many versions back from the current WordPress version to use in the Docker environment. This is calculated from the current WordPress version minus the set value. For example, if `LATEST_WP_VERSION_MINUS` is set to 1, it will calculate the current WordPress version minus one, and use that for the WordPress Docker container. 

For example, you could run the following command:

```bash
LATEST_WP_VERSION_MINUS=2 npx wc-e2e docker:up
```

In this example, if the current WordPress version is 6.0, this will go two versions back and use the WordPress 5.8 Docker image for the tests.

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

Jest provides [setup and teardown functions](https://jestjs.io/docs/setup-teardown) similar to PHPUnit. The default setup and teardown is in [`tests/e2e/env/src/setup/jest.setup.js`](src/setup/jest.setup.js). Additional setup and teardown functions can be added to [`tests/e2e/config/jest.setup.js`](../config/jest.setup.js)

#### Console filtering

**Added version 0.2.3**
By default, messages logged to the console are included in the test results. The test runner suppresses 404 not found and proxy connection messages. 

Pages that you are testing may contain repetitive console output that you expect. Use `addConsoleSuppression` in your jest setup script to filter these repetitive messages:

```js
addConsoleSuppression( 'suppress this after the first instance' );
addConsoleSuppression( 'suppress this completely', false );
```

Console suppressions can be removed with `removeConsoleSuppression`. The `searchString` parameter needs to match the `addConsoleSuppression` parameter:

```js
removeConsoleSuppression( 'suppress this after the first instance' );
```

### Container Setup

Depending on the project and testing scenario, the built in testing environment container might not be the best solution for testing. This could be local testing where there is already a testing container, a repository that isn't a plugin or theme and there are multiple folders mapped into the container, or similar. The `e2e-environment` test runner supports using either the built in container or an external container. See the appropriate readme for  details:

- [Built In Container](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e/env/builtin.md)
- [External Container](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e/env/external.md)

### Slackbot Setup

The test runner has support for posting a message and screenshot to a Slack channel when there is an error in a test. It currently supports both Travis CI and Github actions.

To implement the Slackbot in your CI:

- Create a [Slackbot App](https://slack.com/help/articles/115005265703-Create-a-bot-for-your-workspace)
- Give the app the following permissions:
  - `channels:join`
  - `chat:write`
  - `files:write`
  - `incoming-webhook`
- Add the app to your channel
- Invite the Slack app user to your channel `/invite @your-slackbot-user`
- In your CI environment
  - Add the environment variable `WC_E2E_SCREENSHOTS=1`
  - Add your app Oauth token to a CI secret `E2E_SLACK_TOKEN`
  - Add the Slack channel name (without the #) to a CI secret `E2E_SLACK_CHANNEL`
  - Add the secrets to the test run using the same variable names

To test your setup, create a pull request that triggers an error in the E2E tests.

## Plugin functions

Depending on the testing scenario, you may wish to upload a plugin that can be used in the tests from a remote location.

To download a zip file, you can use `getRemotePluginZip( fileUrl )` to get the remote zip. This returns the filepath of the location where the zip file was downloaded to. For example, you could use this method to download the latest nightly version of WooCommerce:

```javascript
const pluginZipUrl = 'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip';
await getRemotePluginZip( pluginZipUrl );
```

The above method also makes use of the following utility methods which can also be used:

- `checkNestedZip( zipFilePath, savePath )` used to check a plugin zip file for any nested zip files. If one is found, it is extracted. Returns the path where the zip file is located.
- `downloadZip( fileUrl, downloadPath )` can be used to directly download a plugin zip file from a remote location to the provided path.

### Get the latest released zip URL

If you would like to get the latest release zip URL, which can be used in the methods mentioned above, you can use the following helper function to do so:

`getLatestReleaseZipUrl( owner, repository, getPrerelease, perPage )`

This will return a string with the latest release URL. Optionally, you can use the `getPrerelease` boolean flag, which defaults to false, on whether or not to get a prerelease instead. The `perPage` flag can be used to return more results when getting the list of releases. The default value is 3.

## Additional information

Refer to [`tests/e2e/core-tests`](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e/core-tests) for some test examples, and [`tests/e2e`](https://github.com/woocommerce/woocommerce/tree/trunk/tests/e2e) for general information on e2e tests.

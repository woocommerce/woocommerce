# WooCommerce Playwright End to End Tests

This is the documentation for the new e2e testing setup based on Playwright and wp-env. It superseedes the Puppeteer and e2e-environment [setup](../tests/e2e), which we will gradually deprecate.

## Table of contents

-   [Pre-requisites](#pre-requisites)
-   [Introduction](#introduction)
-   [About the Environment](#about-the-environment)
-   [Test Variables](#test-variables)
-   [Guide for writing e2e tests](#guide-for-writing-e2e-tests)
    -   [Tools for writing tests](#tools-for-writing-tests)
    -   [Creating test structure](#creating-test-structure)
    -   [Writing the test](#writing-the-test)
-   [Guide for using test reports](#guide-for-using-test-reports)
    -   [Viewing the Playwright HTML report](#viewing-the-playwright-html-report)
    -   [Viewing the Allure report](#viewing-the-allure-report)
-   [Debugging tests](#debugging-tests)

## Pre-requisites

-   Node.js ([Installation instructions](https://nodejs.org/en/download/))
-   NVM ([Installation instructions](https://github.com/nvm-sh/nvm))
-   PNPM ([Installation instructions](https://pnpm.io/installation))
-   Docker and Docker Compose ([Installation instructions](https://docs.docker.com/engine/install/))

Note, that if you are on Mac and you install docker through other methods such as homebrew, for example, your steps to set it up might be different. The commands listed in steps below may also vary.

If you are using Windows, we recommend using [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/) for running E2E tests. Follow the [WSL Setup Instructions](./WSL_SETUP_INSTRUCTIONS.md) first before proceeding with the steps below.

### Introduction

End-to-end tests are powered by Playwright. The test site is spinned up using `wp-env` (recommended), but we will continue to support `e2e-environment` in the meantime.

**Running tests for the first time:**

-   `nvm use` (uses the default node version you have set in NVM)
-   `pnpm install` (installs dependencies)
-   `pnpm run build --filter=woocommerce` (builds WooCommerce locally)
-   `cd plugins/woocommerce` (changes into the WooCommerce plugin folder)
-   `npx playwright install` (installs the latest Playwright version)
-   `pnpm env:start` (starts the local environment)
-   `pnpm test:e2e-pw` (runs tests in headless mode)

To run the test again, re-create the environment to start with a fresh state:

-   `pnpm env:restart` (restarts the local environment)
-   `pnpm test:e2e-pw` (runs tests in headless mode)

Other ways of running tests (make sure you are in the `plugins/woocommerce` folder):

-   `pnpm test:e2e-pw` (usual, headless run)
-   `pnpm test:e2e-pw --headed` (headed -- displaying browser window and test interactions)
-   `pnpm test:e2e-pw --debug` (runs tests in debug mode)
-   `pnpm test:e2e-pw ./tests/e2e-pw/tests/activate-and-setup/basic-setup.spec.js` (runs a single test)

To see all options, make sure you are in the `plugins/woocommerce` folder and run `pnpm playwright test --help`

### About the environment

The default values are:

-   Latest stable WordPress version 
-   PHP 7.4
-   MariaDB
-   URL: `http://localhost:8086/`
-   Admin credentials: `admin/password`

If you want to customize the port or admin credentials, check the [Test Variables](#test-variables) section.

If you would like to customize the `PHP`, `WordPress` or `WooCommerce` versions installed in the environment, you can define `UPDATE_WP_JSON_FILE=1` along with any or all of the following env vars when building the environment.
- `WP_VERSION`
  - Acceptable versions are `nightly`, `trunk`, and any version listed on [WordPress Releases] page.
- `WC_VERSION`
  - Acceptable versions can be found on the [WooCommerce Releases](https://github.com/woocommerce/woocommerce/releases) page
- `PHP`
  - Any PHP version you see it. Please note that WooCommerce requries a minimum of PHP 7.2.

**Example**

The command below will create and environment with WordPress version 6.2, WooCommerce version 7.5.1 and PHP version 8.2 installed.

`UPDATE_WP_JSON_FILE=1 WP_VERSION=6.2 WC_TEST_VERSION=7.5.1 PHP_VERSION=8.2 pnpm run env:test`

If you'd like to run with the default configuation, simply remove the `UPDATE_WP_JSON_FILE`.


For more information how to configure the test environment for `wp-env`, please checkout the [documentation](https://github.com/WordPress/gutenberg/tree/trunk/packages/env) documentation.

### Test Variables

The test environment uses the following test variables:

```json
{
	"url": "http://localhost:8086/",
	"users": {
		"admin": {
			"username": "admin",
			"password": "password"
		},
		"customer": {
			"username": "customer",
			"password": "password"
		}
	}
}
```

If you need to modify the port for your local test environment (eg. port is already in use) or use, edit [playwright.config.js](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/playwright.config.js). Depending on what environment tool you are using, you will need to also edit the respective `.json` file.

**Modiify the port wp-env**

Edit [.wp-env.json](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/.wp-env.json) and [playwright.config.js](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/playwright.config.js).

**Modify port for e2e-environment**

Edit [tests/e2e/config/default.json](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/tests/e2e/config/default.json).\*\*\*\*

### Starting/stopping the environment

After you run a test, it's best to restart the environment to start from a fresh state. We are currently working to reset the state more efficiently to avoid the restart being needed, but this is a work-in-progress.

-   `pnpm env:stop` to stop the environment
-   `pnpm env:destroy` when you make changes to `.wp-env.json`
-   `pnpm env:start` to start the environment
-   `pnpm env:restart` to stop/destroy and then start the environment (useful for re-testing)

## Guide for writing e2e tests

### Creating test structure

It is a good practice to start working on the test by identifying what needs to be tested on the higher and lower levels. For example, if you are writing a test to verify that merchant can create a virtual product, the overview of the test will be as follows:

-   Merchant can create virtual product
    -   Merchant can log in
    -   Merchant can create virtual product
    -   Merchant can verify that virtual product was created

Once you identify the structure of the test, you can move on to writing it.

### Writing the test

The structure of the test serves as a skeleton for the test itself. You can turn it into a test by using `describe()` and `it()` methods of Playwright:

-   [`test.describe()`](https://playwright.dev/docs/api/class-test#test-describe) - creates a block that groups together several related tests;
-   [`test()`](https://playwright.dev/docs/api/class-test#test-call) - actual method that runs the test.

Based on our example, the test skeleton would look as follows:

```js
test.describe( 'Merchant can create virtual product', () => {
	test( 'merchant can log in', async () => {} );

	test( 'merchant can create virtual product', async () => {} );

	test( 'merchant can verify that virtual product was created', async () => {} );
} );
```

## Guide for using test reports

The tests would generate three kinds of reports after the run:

1. A Playwright HTML report.
1. A Playwright JSON report.
1. Allure results.

By default, they are saved inside the `test-results` folder. If you want to save them in a custom location, just assign the absolute path to the environment variables mentioned in the [Playwright](https://playwright.dev/docs/test-reporters) and [Allure-Playwright](https://www.npmjs.com/package/allure-playwright) documentation.

| Report                 | Default location                 | Environment variable for custom location |
| ---------------------- | -------------------------------- | ---------------------------------------- |
| Playwright HTML report | `test-results/playwright-report` | `PLAYWRIGHT_HTML_REPORT`                 |
| Playwright JSON report | `test-results/test-results.json` | `PLAYWRIGHT_JSON_OUTPUT_NAME`            |
| Allure results         | `test-results/allure-results`    | `ALLURE_RESULTS_DIR`                     |

### Viewing the Playwright HTML report

Use the `playwright show-report $PATH_TO_PLAYWRIGHT_HTML_REPORT` command to open the report. For example, assuming that you're at the root of the WooCommerce monorepo, and that you did not specify a custom location for the report, you would use the following commands:

```bash
cd plugins/woocommerce
pnpm exec playwright show-report tests/e2e-pw/test-results/playwright-report
```

For more details about the Playwright HTML report, see their [HTML Reporter](https://playwright.dev/docs/test-reporters#html-reporter) documentation.

### Viewing the Allure report

This assumes that you're already familiar with reports generated by the [Allure Framework](https://github.com/allure-framework), particularly:

-   What the `allure-results` and `allure-report` folders are, and how they're different from each other.
-   Allure commands like `allure generate` and `allure open`.

Use the `allure generate` command to generate an HTML report from the `allure-results` directory created at the end of the test run. Then, use the `allure open` command to open it on your browser. For example, assuming that:

-   You're at the root of the WooCommerce monorepo
-   You did not specify a custom location for `allure-results` (you did not assign a value to `ALLURE_RESULTS_DIR`)
-   You want to generate the `allure-report` folder in `plugins/woocommerce/tests/e2e-pw/test-results`

Then you would need to use the following commands:

```bash
cd plugins/woocommerce
pnpm exec allure generate --clean tests/e2e-pw/test-results/allure-results --output tests/e2e-pw/test-results/allure-report
pnpm exec allure open tests/e2e-pw/test-results/allure-report
```

A browser window should open the Allure report.

If you're on [WSL](https://learn.microsoft.com/en-us/windows/wsl/about) however, you might get this message right after running the `allure open` command:

```
Starting web server...
2022-12-09 18:52:01.323:INFO::main: Logging initialized @286ms to org.eclipse.jetty.util.log.StdErrLog
Can not open browser because this capability is not supported on your platform. You can use the link below to open the report manually.
Server started at <http://127.0.1.1:38917/>. Press <Ctrl+C> to exit
```

In this case, take note of the port number (38917 in the example above) and then use it to navigate to `http://localhost`. Taking the example above, you should be able to view the Allure report on http://localhost:38917.

To know more about the allure-playwright integration, see their [GitHub documentation](https://github.com/allure-framework/allure-js/tree/master/packages/allure-playwright).

## Debugging tests

For Playwright debugging, follow [Playwright's documentation](https://playwright.dev/docs/debug).

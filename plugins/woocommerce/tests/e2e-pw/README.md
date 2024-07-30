# WooCommerce Playwright End-to-End Tests

This is the documentation for the e2e testing setup based on Playwright and `wp-env`.

## Table of contents

- [Pre-requisites](#pre-requisites)
- [Introduction](#introduction)
- [About the Environment](#test-environment)
- [Guide for writing e2e tests](#writing-e2e-tests)
- [Guide for using test reports](#test-reports)
- [Debugging tests](#debugging-tests)

## Pre-requisites

- Go through
  the [WooCommerce Monorepo prerequisites](https://github.com/woocommerce/woocommerce/blob/trunk/README.md#prerequisites)
  first, including the commands to get everything working.
- Install Docker and Docker Compose ([Installation instructions](https://docs.docker.com/engine/install/)).

Note, that if you are on Mac and you install Docker through other methods such as homebrew, for example, your steps to
set it up might be different. The commands listed in steps below may also vary.

If you are using Windows, we recommend
using [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/) for running E2E tests. Follow
the [WSL Setup Instructions](./WSL_SETUP_INSTRUCTIONS.md) first before proceeding with the steps below.

## Introduction

End-to-end tests are powered by Playwright. By default, the test site is spun up using `wp-env`.

**Running tests for the first time:**

Start in the repository root folder:

- `nvm use` (uses the default node version you have set in NVM)
- `pnpm install` (installs dependencies)
- `pnpm --filter='@woocommerce/plugin-woocommerce' build` (builds WooCommerce locally)
- `cd plugins/woocommerce` (changes into the WooCommerce plugin folder)
- `pnpm env:start` (starts the `wp-env` based local environment)
- `pnpm test:e2e` (runs all the tests in headless mode)

To re-create the environment for a fresh state:

`pnpm env:restart` (resets and restarts the local environment)

You can refer to the pnpm scripts in the `package.json` file for more commands. Check out the `env:some-command` scripts
for managing the `wp-env` environment.

Other ways of running tests (make sure you are in the `plugins/woocommerce` folder):

- `pnpm test:e2e` (usual, headless run)
- `pnpm test:e2e --headed` (headed -- displaying browser window and test interactions)
- `pnpm test:e2e --debug` (runs tests in debug mode)
- `pnpm test:e2e ./tests/e2e-pw/tests/**/basic.spec.js` (runs a single test file - `basic.spec.js` in this case)
- `pnpm test:e2e --ui` (open tests in [Playwright UI mode](https://playwright.dev/docs/test-ui-mode)). You may need
  to increase the [test timeout](https://playwright.dev/docs/api/class-testconfig#test-config-timeout) by setting
  the `DEFAULT_TIMEOUT_OVERRIDE` environment variable like so:

    ```bash
    # Increase test timeout to 3 minutes
    export DEFAULT_TIMEOUT_OVERRIDE=180000 pnpm test:e2e --ui
    ```

To see all the Playwright options, make sure you are in the `plugins/woocommerce` folder and
run `pnpm playwright test --help`

## Test environment

The find the default environment setup you check the `.wp-env.json` configuration file in the `plugins/woocommerce`
folder.

For more information on how to configure the test environment for `wp-env`, please check out
the official [documentation](https://github.com/WordPress/gutenberg/tree/trunk/packages/env).

### Alternate environments

The default URL and the credentials for the test environment can be set via environment variables.

If you'd like to overwrite the default values to run against a different environment (external host for
example), you can create a `.env` file in `tests/e2e-pw/`:

```bash
BASE_URL='https://www.example.com'
ADMIN_USER='admin.username'
ADMIN_PASSWORD='admin.password'
CUSTOMER_USER='customer.username'
CUSTOMER_PASSWORD='customer.password'
```

There are some pre-defined environments set in the `tests/e2e-pw/envs` path.
Each folder represents an environment, and contains a setup script, a `playwright.config.js` file and optionally an
encrypted `.env` file.
Running the tests with one of these environments will decrypt the `.env.enc` file if it exists, execute the setup 
script and then run the tests using the configuration in the `playwright.config.js` file.

To run the tests using one of these environment, you can use the `test:e2e:with-env` script. Some examples:

```bash
# Runs the tests using the gutenberg-stable environment, 
# which is set up to run a subset of relevant tests against a wp-env instance with the latest stable version of the Gutenberg plugin
pnpm test:e2e:with-env gutenberg-stable

# Runs the tests using the default-pressable environment, 
# which is an external site configured to run the tests against a permanent environment. 
# The envs/default-pressable/.env.enc file will be decrypted into .env and used to set the required environment variables
pnpm test:e2e:with-env default-pressable

# Runs all the tests with the default environment. `pnpm test:e2e` already does that, but only runs e2e, ignoring the API tests.
pnpm test:e2e:with-env default 
```

To decrypt the .env file, the `E2E_ENV_KEY` environment variable must be set.
If you're an a11n you can find the key in the Secret Store.
Run with the `E2E_ENV_KEY` environment variable set:

```bash
E2E_ENV_KEY='your-key' pnpm test:e2e:with-env default-pressable
```

### Creating an alternate environment

If you need to create a new pre-defined environment, you can follow these steps:

- create a new folder in the `tests/e2e-pw/envs` directory with the name of the environment.
  Example: `tests/e2e-pw/envs/my-new-env`
- create an `env-setup.sh` file in the new folder. This file should contain any setup steps for the environment. This
  will run before any test execution.
- create a `playwright.config.js` file in the new folder. This file should contain the configuration for the
  environment.
  It's recommended that the config extends the default configuration and only updates the necessary values.
- if you need to store an encrypted `.env` file, first create the `.env` file in the `tests/e2e-pw` folder, then
  run `E2E_ENV_KEY='your-key' ./tests/e2e-pw/bin/dotenv.sh -e my-new-env`. This script command will encrypt the `.env`
  file into `tests/e2e-pw/envs/my-new-env/.env.enc`.

## Writing e2e tests

There are no hard rules for writing tests, but use your common sense when it comes to code duplication and layers of
abstractions. The tests should be easy to read and maintain.
We think that Playwright offers a good balance between simplicity and power, so we recommend using it as it is.

Still, here's a few tips to get you started:

- create isolated tests
- use fixtures for common setup steps
- create utils for common actions
- use web first assertions
- when locating elements, prioritize user-facing attributes

Playwright's Best Practices guide is a good
read: [Playwright Best Practices](https://playwright.dev/docs/best-practices).

## Test reports

The tests would generate three kinds of reports after the run:

- A Playwright HTML report.
- A Playwright JSON report.
- Allure results.

By default, they are saved inside the `test-results` folder.

### Viewing the Playwright HTML report

Use the `playwright show-report $PATH_TO_PLAYWRIGHT_HTML_REPORT` command to open the report. For example, assuming that
you're at the root of the WooCommerce monorepo, and that you did not specify a custom location for the report, you would
use the following commands:

```bash
cd plugins/woocommerce
pnpm exec playwright show-report tests/e2e-pw/test-results/playwright-report
```

For more details about the Playwright HTML report, see
their [HTML Reporter](https://playwright.dev/docs/test-reporters#html-reporter) documentation.

### Viewing the Allure report

This assumes that you're already familiar with reports generated by
the [Allure Framework](https://github.com/allure-framework), particularly:

- What the `allure-results` and `allure-report` folders are, and how they're different from each other.
- Allure commands like `allure generate` and `allure open`.

Use the `allure generate` command to generate an HTML report from the `allure-results` directory created at the end of
the test run. Then, use the `allure open` command to open it on your browser. For example, assuming that:

- You're at the root of the WooCommerce monorepo
- You did not specify a custom location for `allure-results` (you did not assign a value to `ALLURE_RESULTS_DIR`)
- You want to generate the `allure-report` folder in `plugins/woocommerce/tests/e2e-pw/test-results`

Then you would need to use the following commands:

```bash
cd plugins/woocommerce
pnpm exec allure generate --clean tests/e2e-pw/test-results/allure-results --output tests/e2e-pw/test-results/allure-report
pnpm exec allure open tests/e2e-pw/test-results/allure-report
```

A browser window should open the Allure report.

If you're on [WSL](https://learn.microsoft.com/en-us/windows/wsl/about) however, you might get this message right after
running the `allure open` command:

```bash
Starting web server...
2022-12-09 18:52:01.323:INFO::main: Logging initialized @286ms to org.eclipse.jetty.util.log.StdErrLog
Can not open browser because this capability is not supported on your platform. You can use the link below to open the report manually.
Server started at <http://127.0.1.1:38917/>. Press <Ctrl+C> to exit
```

In this case, take note of the port number (38917 in the example above) and then use it to navigate to localhost. Taking
the example above, you should be able to view the Allure report on localhost:38917 in your browser.

To know more about the `allure-playwright` integration, see
their [GitHub documentation](https://github.com/allure-framework/allure-js/tree/master/packages/allure-playwright).

## Debugging tests

For Playwright debugging, follow [Playwright's documentation](https://playwright.dev/docs/debug).

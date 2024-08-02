# WooCommerce Puppeteer End to End Tests

**Please check the new E2E setup based on [Playwright + wp-env](./../e2e-pw).**

This package uses `Puppeteer` as test runner and `e2e-environment` to spin up a test site. [Please check the new setup](./../../e2e), using `Playwright` as test runner and `wp-env` to spin up a test site.

## Table of contents

- [Pre-requisites](#pre-requisites)
  - [Install Node.js](#install-node.js)
  - [Install NVM](#install-nvm)
  - [Install Docker](#install-docker)
- [Configuration](#configuration)
  - [Test Environment](#test-environment)
  - [Test Variables](#test-variables)
  - [Jest test sequencer](#jest-test-sequencer)
  - [Chromium Download](#chromium-download)
- [Running tests](#running-tests)
  - [Prep work for running tests](#prep-work-for-running-tests)
  - [How to run tests in headless mode](#how-to-run-tests-in-headless-mode) 
  - [How to run tests in non-headless mode](#how-to-run-tests-in-non-headless-mode)
  - [How to run tests in debug mode](#how-to-run-tests-in-debug-mode)
  - [How to run an individual test](#how-to-run-an-individual-test)
  - [How to skip tests](#how-to-skip-tests)
  - [How to run tests using custom WordPress, PHP and MariaDB versions](#how-to-run-tests-using-custom-wordpress,-php-and-mariadb-versions)
- [Guide for writing e2e tests](#guide-for-writing-e2e-tests) 
  - [Tools for writing tests](#tools-for-writing-tests)
  - [Creating test structure](#creating-test-structure)
  - [Writing the test](#writing-the-test)
  - [Best practices](#best-practices)
  - [Writing tests for WooCommerce extensions](#Writing-tests-for-WooCommerce-extensions)
- [Debugging tests](#debugging-tests)

## Pre-requisites

### Install pnpm

Follow [instructions on pnpm.io site](https://pnpm.io/installation) to install pnpm.

### Install Node.js

Follow [instructions on the node.js site](https://nodejs.org/en/download/) to install Node.js. 

### Install NVM

Follow instructions in the [NVM repository](https://github.com/nvm-sh/nvm) to install NVM. 

### Install Docker

Install Docker Desktop if you don't have it installed:

- [Docker Desktop for Mac](https://docs.docker.com/docker-for-mac/install/)
- [Docker Desktop for Windows](https://docs.docker.com/docker-for-windows/install/)

Once installed, you should see `Docker Desktop is running` message with the green light next to it indicating that everything is working as expected.

Note, that if you install docker through other methods such as homebrew, for example, your steps to set it up will be different. The commands listed in steps below may also vary.

## Configuration

This section explains how e2e tests are working behind the scenes. These are not instructions on how to build environment for running e2e tests and run them. If you are looking for instructions on how to run e2e tests, jump to [Running tests](#running-tests). 

### Test Environment

We recommend using Docker for running tests locally in order for the test environment to match the setup on GitHub CI (where Docker is also used for running tests). [An official WordPress Docker image](https://github.com/docker-library/docs/blob/master/wordpress/README.md) is used to build the site. Once the site using the WP Docker image is built, the current WooCommerce dev branch is mapped into the `plugins` folder of that newly built test site.

### Test Variables

The jest test sequencer uses the following test variables:

```
{
  "url": "http://localhost:8084/",
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

If you need to modify the port for your local test environment (eg. port is already in use), edit `tests/e2e/config/default.json`. Only edit this file while your test container is `down`.

This is also what you'll need to edit if you want to run tests against an external (or non-Docker) environment.  There are a few additional steps you'll have to take to ensure your environment is ready for testing. Complete [instructions are available here](https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/e2e-environment/external.md).

### Jest test sequencer

[Jest](https://jestjs.io/) is being used to run e2e tests. Jest sequencer introduces tools that can be used to specify the order in which the tests are being run. In our case, they are being run in alphabetical order of the directories where tests are located. This way, tests in the directory `activate-and-setup` will run first. By default, jest runs tests ordered by the time it takes to run the test (the test that takes longer to run will be run first, the test that takes less time to run will run last).

The Setup Wizard e2e test runs first to ensure that WooCommerce is active and that the setup wizard has been completed. This is necessary because `docker-up` creates a brand new install of WordPress and WooCommerce.

### Chromium Download

By default, `Puppeteer` downloads the `Chromium` package every time you run `pnpm install` or `pnpm update`. To disable that download add the following to your `.bash_profile` or `.zshrc` (whichever you use):

```shell script
export PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
```

Puppeteer will still automatically download Chromium when needed.

## Running tests

If you are using Windows, we recommend using [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/) for End-to-end testing. Follow the [WSL Setup Instructions](./WSL_SETUP_INSTRUCTIONS.md) first before proceeding with the steps below.

### Prep work for running tests

Run the following in a terminal/command line window

- `cd` to the WooCommerce monorepo folder

- `git checkout trunk` (or the branch where you need to run tests) 

- `nvm use`

- `pnpm install`

- `pnpm --filter='@woocommerce/plugin-woocommerce' build`

- `npm install jest --global` (this only needs to be done once)

- `pnpm --filter='@woocommerce/plugin-woocommerce' docker:up` (this will build the test site using Docker)

- Use `docker ps` to confirm that the Docker containers are running. You should see a log similar to one below indicating that everything had been built as expected:

```
CONTAINER ID        IMAGE               COMMAND                  CREATED             STATUS              PORTS                  NAMES
c380e1964506        env_wordpress-cli   "entrypoint.sh"          7 seconds ago       Up 5 seconds                               woocommerce_e2e_wordpress-cli
2ab8e8439e9f        wordpress:5.5.1     "docker-entrypoint.s…"   8 seconds ago       Up 7 seconds        0.0.0.0:8086->80/tcp   woocommerce_e2e_wordpress-www
4c1e3f2a49db        mariadb:10.5.5      "docker-entrypoint.s…"   10 seconds ago      Up 8 seconds        3306/tcp               woocommerce_e2e_db
```

Note that by default, Docker will download the latest images available for WordPress, PHP and MariaDB. In the example above, you can see that WordPress 5.5.1 and MariaDB 10.5.5 were used. 

See [How to run tests using custom WordPress, PHP and MariaDB versions](#how-to-run-tests-using-custom-wordpress,-php-and-mariadb-versions) if you'd like to use different versions.  

- Navigate to `http://localhost:8086/`

If everything went well, you should be able to access the site. If you changed the port to something other than `8086` as per [Test Variables](#test-variables) section, use the appropriate port to access your site. 

As noted in [Test Variables](#test-variables) section, use the following Admin user details to login:

```
Username: admin
PW: password
```

- Run `pnpm docker:down --filter=@woocommerce/plugin-woocommerce` when you are done with running e2e tests and before making any changes to test site configuration.

Note that running `pnpm docker:down --filter=@woocommerce/plugin-woocommerce` and then `pnpm docker:up --filter=@woocommerce/plugin-woocommerce` re-initializes the test container.

### How to run tests in headless mode

To run e2e tests in headless mode use the following command:

```bash
pnpm e2e --filter=@woocommerce/plugin-woocommerce
```

### How to run tests in non-headless mode

Tests run in headless mode by default. However, sometimes it's useful to observe the browser while running or developing tests. To do so, you can run tests in a non-headless (dev) mode:

```bash
pnpm e2e:dev --filter=@woocommerce/plugin-woocommerce
```

The dev mode also enables SlowMo mode. SlowMo slows down Puppeteer’s operations. This makes it easier to see what is happening in the browser.

By default, SlowMo mode adds a 50 millisecond delay between test steps. If you'd like to override the length of the delay and have the tests run faster or slower in the `-dev` mode, pass `PUPPETEER_SLOWMO` variable when running tests as shown below:

```
PUPPETEER_SLOWMO=10 pnpm e2e:dev --filter=@woocommerce/plugin-woocommerce
```

The faster you want the tests to run, the lower the value should be of `PUPPETEER_SLOWMO` should be. 

For example:

- `PUPPETEER_SLOWMO=10` - will run tests faster
- `PUPPETEER_SLOWMO=70` - will run tests slower

### How to retry failed tests

Sometimes tests may fail for different reasons such as network issues, or lost connection. To mitigate against test flakiess, failed tests are rerun up to 3 times before being marked as failed. The amount of retry attempts can be adjusted by passing the `E2E_RETRY_TIMES` variable when running tests. For example:

```
cd plugins/woocommerce
E2E_RETRY_TIMES=2 pnpm exec wc-e2e test:e2e
```

### How to run tests in debug mode

Tests run in headless mode by default. While writing tests it may be useful to have the debugger loaded while running a test in non-headless mode. To run tests in debug mode:
            
```bash
pnpm e2e:debug --filter=@woocommerce/plugin-woocommerce
```

When all tests have been completed the debugger remains active. Control doesn't return to the command line until the debugger is closed. Otherwise, debug mode functions the same as non-headless mode.

### How to run an individual test

To run an individual test, use the direct path to the spec. For example:

```bash
cd plugins/woocommerce
pnpm -- wc-e2e test:e2e ./tests/e2e/specs/wp-admin/create-order.test.js
``` 

### How to skip tests

To skip the tests, use `.only` in the relevant test entry to specify the tests that you do want to run. 

For example, in order to skip Setup Wizard tests, add `.only` to the login and activation tests as follows in the `setup-wizard.test.js`:

```
it.only( 'Can login', async () => {}
```

```
it.only( 'Can make sure WooCommerce is activated. If not, activate it', async () => {}
```

As a result, when you run `setup-wizard.test.js`, only the login and activate tests will run. The rest will be skipped. You should see the following in the terminal:

```
 PASS  tests/e2e/specs/activate-and-setup/setup-wizard.test.js (11.927s)
  Store owner can login and make sure WooCommerce is activated
    ✓ Can login (7189ms)
    ✓ Can make sure WooCommerce is activated. If not, activate it (1187ms)
  Store owner can go through store Setup Wizard
    ○ skipped Can start Setup Wizard
    ○ skipped Can fill out Store setup details
    ○ skipped Can fill out Payment details
    ○ skipped Can fill out Shipping details
    ○ skipped Can fill out Recommended details
    ○ skipped Can skip Activate Jetpack section
    ○ skipped Can finish Setup Wizard - Ready! section
  Store owner can finish initial store setup
    ○ skipped Can enable tax rates and calculations
    ○ skipped Can configure permalink settings
```

You can also use `.skip` in the same fashion. For example:

```
it.skip( 'Can start Setup Wizard', async () => {}
```

Finally, you can apply both `.only` and `.skip` to `describe` part of the test:

```
describe.skip( 'Store owner can go through store Setup Wizard', () => {}
```

### How to run tests using custom WordPress, PHP and MariaDB versions

The following variables can be used to specify the versions of WordPress, PHP and MariaDB that you'd like to use to build your test site with Docker:

- `WP_VERSION`
- `TRAVIS_PHP_VERSION`
- `TRAVIS_MARIADB_VERSION`  

The full command to build the site will look as follows:

```
TRAVIS_MARIADB_VERSION=10.5.3 TRAVIS_PHP_VERSION=7.4.5 WP_VERSION=5.4.1 pnpm docker:up --filter=@woocommerce/plugin-woocommerce
```

## Guide for writing e2e tests

### Tools for writing tests

We use the following tools to write e2e tests:

- [Puppeteer](https://github.com/GoogleChrome/puppeteer) – a Node library which provides a high-level API to control Chrome or Chromium over the DevTools Protocol
- [jest-puppeteer](https://github.com/smooth-code/jest-puppeteer) – provides all required configuration to run tests using Puppeteer
- [expect-puppeteer](https://github.com/smooth-code/jest-puppeteer/tree/master/packages/expect-puppeteer) – assertion library for Puppeteer

In the WooCommerce Core repository the tests are in `tests/e2e/core-tests/specs/` folder. However, if you are writing tests in your own project using WooCommerce Core e2e packages, the tests should be located in `tests/e2e/specs/` folder.

The following packages are used in write tests:

- `@automattic/puppeteer-utils` - utilities and configuration for running puppeteer against WordPress. See details in the [package's repository](https://github.com/Automattic/puppeteer-utils).
- `@woocommerce/e2e-utils` - this package contains utilities to simplify writing e2e tests specific to WooCommerce. See details in the [package's repository](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/e2e-utils).

### Creating test structure

It is a good practice to start working on the test by identifying what needs to be tested on the higher and lower levels. For example, if you are writing a test to verify that merchant can create a virtual product, the overview of the test will be as follows:

- Merchant can create virtual product
  - Merchant can log in
  - Merchant can create virtual product
  - Merchant can verify that virtual product was created   
  
Once you identify the structure of the test, you can move on to writing it. 

### Writing the test

The structure of the test serves as a skeleton for the test itself. You can turn it into a test by using `describe()` and `it()` methods of Jest:

- [`describe()`](https://jestjs.io/docs/en/api#describename-fn) - creates a block that groups together several related tests;
- [`it()`](https://jestjs.io/docs/en/api#testname-fn-timeout) - actual method that runs the test. 

Based on our example, the test skeleton would look as follows:

```
describe( 'Merchant can create virtual product', () => {
	it( 'merchant can log in', async () => {

	} );

	it( 'merchant can create virtual product', async () => {

	} );

	it( 'merchant can verify that virtual product was created', async () => {

	} );
} );
```

Next, you can start filling up each section with relevant functions (test building blocks). Note, that we have the `@woocommerce/e2e-utils` package where many reusable helper functions can be found for writing tests. For example, `merchant.js` of `@woocommerce/e2e-utils` package contains `merchant` object that has `login` method. As a result, in the test it can be used as `await merchant.login();` so the first `it()` section of the test will become:

```
it( 'merchant can log in', async () => {
      await merchant.login();
	} );
```

Moving to the next section where we need to actually create a product. You will find that we have a reusable function such as `createSimpleProduct()` in the `components.js` of `@woocommerce/e2e-utils` package. However, note that this function should not be used for testing creating a product because the simple product is created using WooCommerce REST API. This is not how the merchant would typically create a virtual product, we would need to test it by writing actual steps for creating a product in the test.

`createSimpleProduct()` should be used in tests where you need to test something else than creating a simple product. In other words, this function exists in order to quickly fill the site with test data required for running tests. For example, if you want to write a test that will verify that shopper can place a product to the cart on the site, you can use `createSimpleProduct()` to create a product to test the cart. 

Because `createSimpleProduct()` can't be used in the case of our example test, we'd need to navigate to the page where the user would usually create a product. To do that, there is `openNewProduct()` function of the `merchant` object that we already used above. As a result, that part of the test will look as follows:

```
it( 'merchant can create virtual product', async () => {
      await merchant.openNewProduct();
	} );
```

You would then continue writing the test using utilities where possible. 

Make sure to utilize the functions of the `@automattic/puppeteer-utils` package where possible. For example, if you need to wait for a certain element to be ready to be clicked on and then click on it, you can use `waitAndClick()` function:

```
await waitAndClick( page, '#selector' ); 
```

### Best practices

- It is best to keep the tests inside `describe()` block granular as it helps to debug the test if it fails. When the test has finished, you will see the result along with the breakdown of how each of the test sections performed. If one of the tests within `describe()` block fails, it will be shown as follows:

```
FAIL ../specs/front-end/front-end-my-account.test.js (9.219s)
  My account page
    ✓ allows customer to login (2924ms)
    ✓ allows customer to see orders (1083ms)
    x allows customer to see downloads (887ms)
    ✓ allows customer to see addresses (1161ms)
    ✓ allows customer to see account details (1066ms)
```

In the example above, you can see that `allows customer to see downloads` part of the test failed and can start looking at it right away. Without steps the test goes through being detailed, it is more difficult to debug it. 

### Writing tests for WooCommerce extensions

If you want to set up E2E tests for your WooCommerce extension you can make use of the default WooCommerce E2E package.

The [WooCommerce E2E Tests Boilerplate repo](https://github.com/woocommerce/woocommerce-e2e-boilerplate) aims to provide a stripped down version of the default WooCommerce E2E test suite along with basic set up instructions to get started.

## Debugging tests

The test sequencer (`pnpm e2e --filter=@woocommerce/plugin-woocommerce`) includes support for saving [screenshots on test errors](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/e2e-environment#test-screenshots) which can be sent to a Slack channel via a [Slackbot](https://github.com/woocommerce/woocommerce/tree/trunk/packages/js/e2e-environment#slackbot-setup).

For Puppeteer debugging, follow [Google's documentation](https://developers.google.com/web/tools/puppeteer/debugging).

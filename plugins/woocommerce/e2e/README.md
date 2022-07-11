# WooCommerce Playwright End to End Tests

 This is still a work in progress. Feel free to add a test here. We will gradually deprecate [Puppeteer](../tests/e2e).

## Table of contents

- [Pre-requisites](#pre-requisites)
  - [Install Node.js](#install-nodejs)
  - [Install NVM](#install-nvm)
  - [Install Docker](#install-pnpm)
  - [Install Docker](#install-docker)
- [Configuration](#configuration)
  - [Test Environment](#test-environment)
  - [Test Variables](#test-variables)
- [Running tests](#running-tests)
  - [Prep work for running tests](#prep-work-for-running-tests)
  - [How to run tests in headless mode](#how-to-run-tests-in-headless-mode) 
  - [How to run tests in non-headless mode](#how-to-run-tests-in-non-headless-mode)
  - [How to run tests in debug mode](#how-to-run-tests-in-debug-mode)
  - [How to run an individual test](#how-to-run-an-individual-test)
  - [How to skip tests](#how-to-skip-tests)
  - [How to run tests using custom WordPress, PHP and MariaDB versions](#how-to-run-tests-using-custom-wordpress-php-and-mariadb-versions)
- [Guide for writing e2e tests](#guide-for-writing-e2e-tests)
  - [Tools for writing tests](#tools-for-writing-tests)
  - [Creating test structure](#creating-test-structure)
  - [Writing the test](#writing-the-test)
- [Debugging tests](#debugging-tests)

## Pre-requisites

### Install Node.js

Follow [instructions on the node.js site](https://nodejs.org/en/download/) to install Node.js.

### Install NVM

Follow instructions in the [NVM repository](https://github.com/nvm-sh/nvm) to install NVM.

### Install pnpm

Follow [instructions on pnpm.io site](https://pnpm.io/installation) to install pnpm.

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

If you need to modify the port for your local test environment (eg. port is already in use) or use different playwright config, edit `tests/e2e/config/default.json` and `e2e/playwright.config.js`.

## Running tests

If you are using Windows, we recommend using [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/) for End-to-end testing. Follow the [WSL Setup Instructions](../tests/e2e/WSL_SETUP_INSTRUCTIONS.md) first before proceeding with the steps below.

### Prep work for running tests

Run the following in a terminal/command line window

- `cd` to the WooCommerce monorepo folder

- `git checkout trunk` (or the branch where you need to run tests) 

- `nvm use`

- `pnpm install`

- `pnpm dlx playwright install chromium` to install chromium.

- `pnpm -- turbo run build --filter=woocommerce`

- `pnpm docker:up --filter=woocommerce` (this will build the test site using Docker)

- Use `docker ps` to confirm that the Docker containers are running. You should see a log similar to one below indicating that everything had been built as expected:

```bash
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

- Run `pnpm docker:down --filter=woocommerce` when you are done with running e2e tests and before making any changes to test site configuration.

Note that running `pnpm docker:down --filter=woocommerce` and then `pnpm docker:up --filter=woocommerce` re-initializes the test container.

### How to run tests in headless mode

To run e2e tests in headless mode use the following command:

```bash
cd plugins/woocommerce
pnpm playwright test --config=e2e/playwright.config.js
```

### How to run tests in non-headless mode

Tests run in headless mode by default. However, sometimes it's useful to observe the browser while running or developing tests. To do so, you can run tests in a non-headless (dev) mode:

```bash
cd plugins/woocommerce
pnpm playwright test --config=e2e/playwright.config.js --headed
```

## How to retry failed tests

Sometimes tests may fail for different reasons such as network issues, or lost connection. To mitigate against test flakiess, failed tests are rerun up to 1 times before being marked as failed. The amount of retry attempts can be adjusted by passing the `--retries` variable when running tests. For example:

```bash
cd plugins/woocommerce
pnpm playwright test --config=e2e/playwright.config.js --retries 3
```

### How to run tests in debug mode

Tests run in headless mode by default. While writing tests it may be useful to have the debugger loaded while running a test in non-headless mode. To run tests in debug mode:

```bash
cd plugins/woocommerce
pnpm playwright test --config=e2e/playwright.config.js --debug
```

Playwright Inspector window will be opened and the script will be paused on the first Playwright statement. You can step over each action using the "Step over" action or resume script without further pauses.

## How to run an individual test

To run an individual test, use the direct path to the spec. For example:

```bash
cd plugins/woocommerce
pnpm playwright test --config=e2e/playwright.config.js ./e2e/tests/activate-and-setup/basic-setup.spec.js
```

## How to disable parallelization

To run an individual test, use the direct path to the spec. For example:

```bash
cd plugins/woocommerce
pnpm playwright test --config=e2e/playwright.config.js --workers=1
```

## How to skip tests

To skip the tests, use `.only` in the relevant test entry to specify the tests that you do want to run. For example:

```js
test.only( 'Can login', async () => {}
```

```js
test.only( 'Can make sure WooCommerce is activated. If not, activate it', async () => {}
```

You can also use `.skip` in the same fashion. For example:

```js
test.skip( 'Can start Setup Wizard', async () => {}
```

### How to run tests using custom WordPress, PHP and MariaDB versions

The following variables can be used to specify the versions of WordPress, PHP and MariaDB that you'd like to use to build your test site with Docker:

- `WP_VERSION`
- `TRAVIS_PHP_VERSION`
- `TRAVIS_MARIADB_VERSION`  

The full command to build the site will look as follows:

```bash
TRAVIS_MARIADB_VERSION=10.5.3 TRAVIS_PHP_VERSION=7.4.5 WP_VERSION=5.4.1 pnpm docker:up --filter=woocommerce
```

## Guide for writing e2e tests

### Tools for writing tests

We use the following tools to write e2e tests:

- [Playwright](https://playwright.dev/docs/intro) – a tool enables reliable end-to-end testing for modern web apps.

### Creating test structure

It is a good practice to start working on the test by identifying what needs to be tested on the higher and lower levels. For example, if you are writing a test to verify that merchant can create a virtual product, the overview of the test will be as follows:

- Merchant can create virtual product
  - Merchant can log in
  - Merchant can create virtual product
  - Merchant can verify that virtual product was created
  
Once you identify the structure of the test, you can move on to writing it.

### Writing the test

The structure of the test serves as a skeleton for the test itself. You can turn it into a test by using `describe()` and `it()` methods of Playwright:

- [`test.describe()`](https://playwright.dev/docs/api/class-test#test-describe) - creates a block that groups together several related tests;
- [`test()`](https://playwright.dev/docs/api/class-test#test-call) - actual method that runs the test. 

Based on our example, the test skeleton would look as follows:

```js
test.describe( 'Merchant can create virtual product', () => {
	test( 'merchant can log in', async () => {

	} );

	test( 'merchant can create virtual product', async () => {

	} );

	test( 'merchant can verify that virtual product was created', async () => {

	} );
} );
```

## Debugging tests

For Playwright debugging, follow [Playwright's documentation](https://playwright.dev/docs/debug).

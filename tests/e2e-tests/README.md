# WooCommerce End to End Tests

Automated end-to-end tests for WooCommerce.

## Table of contents

- [Pre-requisites](#pre-requisites)
  - [Install NodeJS](#install-nodejs)
  - [Install dependencies](#install-dependencies)
  - [Configuration](#configuration)
      - [Test Configuration](#test-configuration)
      - [Environment Variables](#environment-variables)
- [Running tests](#running-tests)
  - [How to run tests](#how-to-run-tests) 
- [Writing tests](#writing-tests) 

## Pre-requisites

### Install NodeJS

```bash
brew install node #MacOS
```

### Install dependencies

```bash
npm install
```

### Configuration

#### Test Configuration

The tests use environment variables to specify login test data needed to run tests. 

To login to the site, `loginUser()` utility function of [`e2e-test-utils`](https://github.com/WordPress/gutenberg/tree/master/packages/e2e-test-utils) package is being used. The function relies on the following [`config.js`](https://github.com/WordPress/gutenberg/blob/master/packages/e2e-test-utils/src/shared/config.js) file to specify base URL, Admin user details and Test user details (could be different from Admin. For example, customer):

```
const WP_ADMIN_USER = {
 	username: 'admin',
 	password: 'password',
 };
 
 const {
 	WP_USERNAME = WP_ADMIN_USER.username,
 	WP_PASSWORD = WP_ADMIN_USER.password,
 	WP_BASE_URL = 'http://localhost:8889',
 } = process.env;
 
 export {
 	WP_ADMIN_USER,
 	WP_USERNAME,
 	WP_PASSWORD,
 	WP_BASE_URL,
 };
```    

As per above, create an Admin user on the site and set its username and password:

- username: `admin`
- password: `password`

Specify base URL and Test user details using environment variables. 

#### Environment variables

Set environmental variables as shown below. Note that you don't need to add the trailing slash ('/') at the end of the site URL:

- `export WP_BASE_URL={your site URL}`
- `export WP_USERNAME={your Test user username}`
- `export WP_PASSWORD={your Test user password}`

You can unset the variables when you are done:

- `unset WP_BASE_URL`
- `unset WP_USERNAME`
- `unset WP_PASSWORD`

## Running tests

### How to run tests

To run e2e tests use the following command:

```bash
npm run test:e2e
```

Tests are being run headless by default. However, sometimes it's useful to observe the browser while running tests. To do so, `Development mode` can be enabled by passing `--dev` flag to the `test:e2e` script in `./package.json` file as follows:
                                    
```bash
"test:e2e": "./tests/bin/e2e-test-integration.js --dev",
```

Once done, start tests as usual by running `npm run test:e2e`. 

The `Development mode` also enables SlowMo mode. SlowMo slows down Puppeteer’s operations so we can better see what is happening in the browser. You can adjust the SlowMo value by editing `PUPPETEER_SLOWMO` variable in `./tests/e2e-tests/config/jest-puppeteer.dev.config.js` file. The default `PUPPETEER_SLOWMO=50` means test actions will be slowed down by 50 milliseconds.

To run an individual test, use the direct path to the spec. For example:

```bash
npm run test:e2e ./tests/e2e-tests/specs/activate-woocommerce.test.js
``` 

You can also provide the base URL, Test username and Test password like this:

```bash
WP_BASE_URL={your site URL} WP_USERNAME={your Test user username} WP_PASSWORD={your Test user password} npm run test:e2e
```

## Writing tests

We use the following tools to write e2e tests:

- [Puppeteer](https://github.com/GoogleChrome/puppeteer) – a Node library which provides a high-level API to control Chrome or Chromium over the DevTools Protocol
- [jest-puppeteer](https://github.com/smooth-code/jest-puppeteer) – provides all required configuration to run tests using Puppeteer
- [expect-puppeteer](https://github.com/smooth-code/jest-puppeteer/tree/master/packages/expect-puppeteer) – assertion library for Puppeteer

Tests are kept in `tests/e2e-tests/specs` folder. 

The following packages are being used to write tests:

- `e2e-test-utils` - End-To-End (E2E) test utils for WordPress. You can find the full list of utils [here](https://github.com/WordPress/gutenberg/tree/master/packages/e2e-test-utils). 

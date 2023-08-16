# WooCommerce Blocks Playwright End to End Tests

This is the documentation for the new E2E testing setup based on Playwright and wp-env. Over time, these playwright E2E tests should replace the current [Puppeteer E2E tests](../e2e-jest/).

## Table of contents <!-- omit in toc -->

-   [Pre-requisites](#pre-requisites)
-   [Introduction](#introduction)
    -   [Running tests for the first time](#running-tests-for-the-first-time)
    -   [To run the test again, re-create the environment to start with a fresh state](#to-run-the-test-again-re-create-the-environment-to-start-with-a-fresh-state)
    -   [Other ways of running tests](#other-ways-of-running-tests)

## Pre-requisites

-   Node.js ([Installation instructions](https://nodejs.org/en/download/))
-   NVM ([Installation instructions](https://github.com/nvm-sh/nvm))
-   Docker and Docker Compose ([Installation instructions](https://docs.docker.com/engine/install/))

Note, that if you are on Mac and you install docker through other methods such as homebrew, for example, your steps to set it up might be different. The commands listed in steps below may also vary.

If you are using Windows, we recommend using [Windows Subsystem for Linux (WSL)](https://docs.microsoft.com/en-us/windows/wsl/) for running E2E tests. Follow the [WSL Setup Instructions](../tests/e2e-jest/WSL_SETUP_INSTRUCTIONS.md) first before proceeding with the steps below.

## Introduction

End-to-end tests are powered by Playwright. The test site is spun up using `wp-env` (recommended), but we will continue to support `e2e-environment` in the meantime.

### Running tests for the first time

```sh
nvm use
```

```sh
npm install
```

```sh
npm run env:start
```

```sh
npm run test:e2e
```

### To run the test again, re-create the environment to start with a fresh state

```sh
npm run env:restart
```

```sh
npm run test:e2e
```

### Tests with side effects

We call tests that affect other tests (ones that modify the site settings, using
custom plugins) are tests with side effects and we
[split](https://github.com/woocommerce/woocommerce-blocks/pull/10508) those
tests to a separate test suite:

```sh
npm run test:e2e:side-effects
```

_Note: All commands parameters of `test:e2e` can be used for
`test:e2e:side-effects`._

### Other ways of running tests

Headless mode:

```sh
npm run test:e2e
```

Interactive UI mode:

```sh
npm run test:e2e -- --ui
```

Headed mode:

```sh
npm run test:e2e -- --headed
```

Debug mode:

```sh
npm run test:e2e -- --debug
```

Running a single test:

```sh
npm run test:e2e ./tests/e2e/tests/example.spec.ts
```

To see all options, run the following command:

```sh
npx playwright test --help
```

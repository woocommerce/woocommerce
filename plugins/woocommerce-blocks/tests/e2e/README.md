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

In the root directory, run:

```sh
nvm use
```

```sh
pnpm install
```

Now change directory to `plugins/woocommerce-blocks/`:

```sh
cd plugins/woocommerce-blocks/
```

```sh
pnpm run env:start
```

```sh
pnpm run test:e2e
```

### To run the test again, re-create the environment to start with a fresh state

```sh
pnpm run env:restart
```

```sh
pnpm run test:e2e
```

### Adding posts for testing block content

During test setup posts are automatically created from all the html files contained in `./bin/posts`.
All posts are given a title like `File Name Block` which generates a url like `file-name-block`.

e.g. `my-test.html` will generate a post with the title `My Test Block` and permalink `my-test-block`.
You'll be able to navigate to that page in your test like:

```ts
await page.goto( '/my-test-block/' );
```

Please also note that the posts are generated during initial environment setup, so if you
add or edit a post file you'll need to restart the environment to see the changes.

### Tests with side effects

We call tests that affect other tests (ones that modify the site settings, using
custom plugins) are tests with side effects and we
[split](https://github.com/woocommerce/woocommerce-blocks/pull/10508) those
tests to a separate test suite:

```sh
pnpm run test:e2e:side-effects
```

_Note: All command parameters of `test:e2e` can be used for
`test:e2e:side-effects`._

### Tests with a classic theme and a block theme with custom templates

By default, e2e tests run in a non-customized block theme. However, we also have
some e2e tests which run specifically in a classic theme and in a block theme
with custom templates. They can be run like this:

```sh
pnpm run test:e2e:classic-theme
```

```sh
pnpm run test:e2e:block-theme-with-templates
```

_Note: All command parameters of `test:e2e` can be used for these commands too.

### Other ways of running tests

Headless mode:

```sh
pnpm run test:e2e
```

Interactive UI mode:

```sh
pnpm run test:e2e --ui
```

Headed mode:

```sh
pnpm run test:e2e --headed
```

Debug mode:

```sh
pnpm run test:e2e --debug
```

Running a single test:

```sh
pnpm run test:e2e ./tests/e2e/tests/example.spec.ts
```

To see all options, run the following command:

```sh
npx playwright test --help
```

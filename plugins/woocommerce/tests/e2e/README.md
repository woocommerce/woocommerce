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

- `pnpm install` (installs dependencies; PNPM uses the pinned Node version automatically)
- `pnpm --filter='@woocommerce/plugin-woocommerce' build` (builds WooCommerce locally)
- `cd plugins/woocommerce` (changes into the WooCommerce plugin folder)
- `pnpm env:e2e` (starts the `wp-env` based E2E test environment)
- `pnpm test:e2e` (runs all the tests in headless mode)

To re-create the environment for a fresh state:

`pnpm env:e2e:restart` (resets and restarts the E2E test environment)

You can refer to the pnpm scripts in the `package.json` file for more commands. Check out the `env:some-command` scripts
for managing the `wp-env` environment.

Other ways of running tests (make sure you are in the `plugins/woocommerce` folder):

- `pnpm test:e2e` (usual, headless run)
- `pnpm test:e2e --headed` (headed -- displaying browser window and test interactions)
- `pnpm test:e2e --debug` (runs tests in debug mode)
- `pnpm test:e2e page-loads.spec.ts` (runs a single test file - `page-loads.spec.ts` in this case)
- `pnpm test:e2e ./tests/e2e/tests/merchant` (runs all tests that are found in the `merchant` folder)
- `pnpm test:e2e --ui` (open tests in [Playwright UI mode](https://playwright.dev/docs/test-ui-mode)).

To see all the Playwright options, make sure you are in the `plugins/woocommerce` folder and
run `pnpm playwright test --help`

> [!TIP]
> 
> If you're looking on how to run the API tests (which are part of the same suite as the classic e2e tests), 
> they can be run as you would run any other tests folder in the suite. 
> Keep in mind that from a tool point of view they are only a folder in the main e2e tests project as any other folder.
> 
> For convenience, a `test:api` command is offered that will run all the tests in the `api-tests` folder against the 
> default environment, but this may change. You can always find the setup by checking the `package.json` scripts section and the `playwright.config.js`.
>

## Test environment

The e2e test environment configuration can be found in the `.wp-env.e2e.json` file in the `plugins/woocommerce`
folder (the `.wp-env.json` file configures the separate dev environment, and `.wp-env.test.json` the lean PHP-unit environment).

For more information on how to configure the test environment for `wp-env`, please check out
the official [documentation](https://github.com/WordPress/gutenberg/tree/trunk/packages/env).

### Alternate environments

The test site URL and the credentials can be set via environment variables. If no variables are set, the defaults will be used,
as configured in the `test-data/data.js` file.

If you'd like to overwrite the default values to run against a different environment (external host for
example), you can create a `.env` file in `tests/e2e/`:

```bash
BASE_URL='https://www.example.com'
ADMIN_USER='admin.username'
ADMIN_PASSWORD='admin.password'
CUSTOMER_USER='customer.username'
CUSTOMER_PASSWORD='customer.password'
```

> [!WARNING]
> Running the tests using the `test:e2e` command will overwrite the `.env` file! If you want to use your own custom `.env` 
> file you should read further on how to create an alternative env, or you should run the tests using the raw Playwright 
> command: `pnpm playwright ...`

There are some pre-defined environments set in the `tests/e2e/envs` path.
Each folder represents an environment, and contains a setup script, a `playwright.config.js` file and optionally an
encrypted `.env` file.
Running the tests with one of these environments will first decrypt the `.env.enc` file if it exists, execute the setup 
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

- create a new folder in the `tests/e2e/envs` directory with the name of the environment.
  Example: `tests/e2e/envs/my-new-env`
- create an `env-setup.sh` file in the new folder. This file should contain any setup steps for the environment. This
  will run before any test execution.
- create a `playwright.config.ts` file in the new folder. This file should contain the configuration for the
  environment.
  It's recommended that the config extends the default configuration and only updates the necessary values.

> [!NOTE]
> If you previously created a custom environment with a `playwright.config.js` file, it will still work — the test runner falls back to `.js` when no `.ts` config is found. However, new environments should use `.ts`.

- if you need to store an encrypted `.env` file, first create the `.env` file in the `tests/e2e` folder, then
  run `E2E_ENV_KEY='your-key' ./tests/e2e/bin/dotenv.sh -e my-new-env`. This script command will encrypt the `.env`
  file into `tests/e2e/envs/my-new-env/.env.enc`.

> [!TIP]
> Creating an environment directory starting with `_local` will make it ignored by git, so you can store your local environment configurations without worrying about accidentally committing them.
> Example: _local_my-own-jn-testing-site

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

## Test helper plugins

Some E2E suites need fixture mechanisms that can't be expressed cleanly with REST or WP-CLI alone — for example, filter-driven content overrides, server-side event mirroring, or synchronous triggers for normally-scheduled jobs. These ship as small PHP plugins under `tests/e2e/test-plugins/`.

### Convention

Every always-on or externally downloaded helper is a **self-contained folder** at `tests/e2e/test-plugins/<slug>/<slug>.php` (the main file matches the folder name), with a full plugin header (`Plugin Name`, `Description`, `Version`, `Requires PHP`, `Author`). Never bind-mount an individual `.php` file — mount a folder or download a zip. The per-test block plugins under `blocks/` are single files, but their whole parent folder is mounted at once (see below).

Keep `Requires PHP` at the **lowest PHP version any E2E environment runs** (currently `7.4`, the same floor as WooCommerce itself) and keep the helper's code compatible with it. WordPress silently refuses to load a plugin whose `Requires PHP` is higher than the running version: it still reports as active, but none of its hooks run and its REST routes return `rest_no_route` (404).

How a helper is wired up depends on when it needs to be active:

- **Always-on helpers** are listed in `.wp-env.e2e.json`'s `plugins` array, which mounts the folder **and auto-activates** it. Do not add a manual `wp plugin activate …` line for these. Current always-on helpers:
    - `woocommerce-e2e-test-helper` — the general-purpose helper bundle, covering three concerns in one plugin:
        - **Filter setter** — registers WordPress filters from an `e2e-filters` cookie so tests can override filtered values on the fly.
        - **Process waiting actions** — runs the Action Scheduler queue synchronously when a request carries the `?process-waiting-actions` query param (used by the analytics suite so order data lands in reports immediately).
        - **Test helper REST API** — endpoints (`e2e-feature-flags`, `e2e-options`, `e2e-environment`, `e2e-theme`) for toggling feature flags, setting/deleting options, reading environment info and switching themes during a test.
    - `wc-email-template-sync-test-helper` — see below (email template sync fixtures for RSM-146).
- **Per-test block plugins** live in `tests/e2e/test-plugins/blocks/`, mounted (not auto-activated) via the `woocommerce-blocks-test-plugins` mapping. Each is activated and deactivated by the spec that needs it (e.g. `wp plugin activate woocommerce-blocks-test-plugins/<file>.php`), because they change store behavior globally and must not be on for every test.

`woocommerce-cleanup` also lives under `test-plugins/`, but it is **not** in the wp-env `plugins` array — it's an on-demand site-reset tool installed only by the external (non-wp-env) setup path, `bin/test-env-setup-external.sh`.

### `wc-email-template-sync-test-helper`

Powers the `tests/email-editor/update-propagation/` suite (RSM-146). Exposes:

- Option-driven filter overrides for `woocommerce_email_block_template_html`, `woocommerce_email_template_sync_opted_in_emails`, and `woocommerce_transactional_emails_for_block_editor`.
- A server-side Tracks event recorder, controlled by option `wc_test_tracks_enabled`.
- A fake `WC_Email` subclass (`fake_thirdparty`) gated by option `wc_test_fake_third_party_email_enabled` for third-party-email scope tests.
- REST endpoints under `/wp-json/wc-email-test-helper/v1/` for seeding posts, triggering sweeps and backfill synchronously, draining the Tracks log, and writing typed option values.

The plugin is dormant when its driving options are empty. It has a `WP_DEBUG` plus `X-Playwright` header safety rail to prevent accidental activation outside test contexts.

If a test fails with `404` on `/wp-json/wc-email-test-helper/v1/health`, the plugin isn't loaded — run `pnpm env:e2e:restart`.

The PR-tier subset of these tests can be run locally with:

```sh
pnpm test:e2e:email-update-propagation:pr
```

To run the full suite (the PR-tier plus nightly-only scenarios):

```sh
pnpm test:e2e:email-update-propagation:nightly
```

In CI, the full suite runs as part of the existing "Core e2e tests" job — no separate workflow entry is required.

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
pnpm exec playwright show-report tests/e2e/test-results/playwright-report
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
- You want to generate the `allure-report` folder in `plugins/woocommerce/tests/e2e/test-results`

Then you would need to use the following commands:

```bash
pnpm exec allure generate --clean plugins/woocommerce/tests/e2e/test-results/allure-results --output plugins/woocommerce/tests/e2e/test-results/allure-report
pnpm exec allure open plugins/woocommerce/tests/e2e/test-results/allure-report
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

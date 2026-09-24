# Running E2E Tests

## Table of Contents

- [Overview](#overview)
- [Setup](#setup)
- [Running Tests](#running-tests)
- [Reading Failures](#reading-failures)
- [Avoiding External Network Dependencies](#avoiding-external-network-dependencies)
- [Managing the Environment](#managing-the-environment)
- [Troubleshooting](#troubleshooting)

## Overview

WooCommerce Core end-to-end tests use Playwright. Specs live in `plugins/woocommerce/tests/e2e/tests/` and run against a `wp-env` site at `http://localhost:8086`.

The environment mounts the plugin from the checkout that started it. In a git worktree, install, build, and start the environment from that worktree, or the tests run against different code.

Blocks E2E tests use a separate fixture profile. See `plugins/woocommerce/tests/e2e/README.md` for the Blocks flow.

## Setup

Run once per checkout or worktree, starting from the repository root:

```bash
pnpm install
pnpm --filter='@woocommerce/plugin-woocommerce' build
cd plugins/woocommerce
pnpm env:e2e
```

Before starting the environment, check that port `8086` is free (`docker ps` or `lsof -iTCP:8086 -sTCP:LISTEN`). Another checkout's environment may already be running; do not stop or destroy an environment you did not start.

## Running Tests

Run tests from `plugins/woocommerce` through the package scripts:

```bash
# Specs that run in parallel (most specs)
pnpm test:e2e:core-parallel webhooks.spec.ts

# Specs listed in `serialRunSpecs` in tests/e2e/playwright.config.ts
pnpm test:e2e:core-serial <spec-file>

# Filter to one test by title
pnpm test:e2e:core-parallel webhooks.spec.ts --grep "can be activated"

# All Core projects
pnpm test:e2e:default <spec-file>
```

Extra arguments are passed to `playwright test`, so file filters, `--grep`, and `--workers=1` work as usual.

**Do not call `pnpm playwright test` directly.** The scripts go through `tests/e2e/run-tests-with-env.sh`, which selects the environment config and sets `NODE_OPTIONS=--conditions=wc-source` so workspace packages such as `@woocommerce/e2e-utils-playwright` resolve to their TypeScript source. Without it, Playwright fails with `Cannot find module '.../e2e-utils-playwright/build/index.js'`.

`pnpm test:e2e`, mentioned in older docs, is not a script in `plugins/woocommerce`.

## Reading Failures

Results are written to `plugins/woocommerce/tests/e2e/test-results/`. Each failing test gets a directory containing:

- `error-context.md` - page snapshot at the moment of failure, including any admin notices
- a trace and screenshot for deeper inspection

Read `error-context.md` first. It usually shows what the page displayed instead of the expected state, for example an error notice in place of a success notice.

A failing assertion can mean the product is wrong or the test expectation is wrong. Diagnose which before changing either.

## Avoiding External Network Dependencies

Tests must not depend on the response of a real external URL. Server-side HTTP requests from WordPress (webhook pings, payment API calls) fail or return unexpected status codes in CI.

Stub them with `setFilterValue()` from `tests/e2e/utils/filters.ts`, which applies a WordPress filter for requests made by the browser:

```typescript
import { setFilterValue } from '../../utils/filters';

await setFilterValue( page, 'pre_http_request', {
	response: { code: 200, message: 'OK' },
	body: '',
} );
await page.reload();
```

The filter is passed through a cookie, so:

- it applies only to requests made by the Playwright `page`, not to calls made through the `restApi` fixture
- reload the page before relying on it
- `pre_http_request` short-circuits every outbound request made during that page request

See `tests/e2e/tests/paypal/paypal.spec.ts` for a working example.

## Managing the Environment

```bash
# Stop the environment (keeps data)
pnpm env:e2e:stop

# Reset to a fresh state (destroys and recreates this checkout's environment)
pnpm env:e2e:restart
```

Stop the environment when you finish, so the port is free for other checkouts.

## Troubleshooting

### `Cannot find module '.../build/index.js'`

**Problem:** Playwright was run directly instead of through a `test:e2e:*` script.

**Solution:** Use `pnpm test:e2e:core-parallel` (or another `test:e2e:*` script) as described in [Running Tests](#running-tests).

### Tests Run Against the Wrong Code

**Problem:** The environment was started from a different checkout or worktree.

**Solution:** Run `pnpm env:e2e:stop` in the other checkout, then `pnpm env:e2e` in the one you are testing.

### PR Checks Show No E2E Results

**Problem:** E2E jobs have not run. Pull requests from forks may wait for maintainer approval before CI runs.

**Solution:** Do not treat missing E2E checks as passing. Run the affected specs locally.

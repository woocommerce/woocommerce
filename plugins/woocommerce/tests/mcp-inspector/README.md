# MCP Inspector contract tests

These tests drive WooCommerce's abilities the way an MCP client sees them: [MCP Inspector](https://github.com/modelcontextprotocol/inspector) talks to `wp mcp-adapter serve` in a `wp-env` environment, which runs WooCommerce with the MCP Adapter.

There are two kinds of test:

- **`contract.test.mjs`** checks every exposed `woocommerce/` ability against shared rules:
    - Its `mcp-adapter-get-ability-info` response matches a reviewed snapshot in `snapshots/ability-info/`.
    - No schema description lists its own `enum` values or names its default in prose.
    - Its schema stays within a token budget, estimated at four characters per token.
    - A readonly ability without required input runs with `{}`.
    - A customer is refused input that the admin passes the permission check with, so the refusal comes from the capability. An ability whose check reads a record needs an entry in `PERMISSION_INPUT`.
    - It has a behaviour test file in `abilities/`.
- **`abilities/<name>.test.mjs`** tests one ability's behaviour: what it returns for a given input, and what it refuses.

## Layout

```text
contract.test.mjs   the shared rules
abilities/          one behaviour test file per ability, named after it without the `woocommerce/` prefix
fixtures/           PHP that sets up the data the behaviour tests query
lib/                the harness: the Inspector, ability calls, fixtures, snapshots
snapshots/          reviewed ability info
```

## Writing a behaviour test

A test calls the ability through `lib/abilities.mjs`, and asserts on what it gets back with `node:assert`:

- `execute( ability, parameters )` fails unless the call succeeds, and resolves with the ability's output.
- `refused( ability, parameters, pattern )` fails unless the call returns an error that matches `pattern`.
- `call( ability, parameters )` resolves with `{ isError, payload }` for anything else.
- Pass `{ user: CUSTOMER }` as the last argument to call as the customer instead of the admin.

Data comes from `seed( name, args )` in `lib/fixtures.mjs`. It runs `fixtures/<name>.php`, which returns a closure taking `args`, and resolves with the closure's result, such as the IDs it created. Fixtures:

- Are idempotent: they find what an earlier run created and reset it, since the dev environment is long-lived.
- Run one at a time, under a database lock, because test files run in parallel.
- Mark their data so queries can be limited to it, such as a SKU prefix or a billing email, so other data in the environment cannot change the result.

A test that changes data seeds its own record, for example `seed( 'order', { key: 'order-update-status' } )`, so it never changes another test's data. Remove what a test creates in `after`, and in `before` too in case an earlier run failed halfway.

When an ability is added, the contract test fails until it has a snapshot and a behaviour test file.

## Setup

The suite runs against its own environment, `.wp-env.mcp.json`, which adds the MCP Adapter, creates the `customer` user the permission tests run as, and accepts mail without sending it (`env/accept-mail.php`), so abilities that send email can succeed:

```bash
pnpm env:mcp
```

The schemas take some of their `enum` values from WordPress and from WooCommerce functions outside the abilities, so a change there changes the snapshots:

- WordPress and the MCP Adapter are pinned in `.wp-env.mcp.json`. Bump them on purpose, together with the snapshots.
- The CI job runs on pull requests that change the abilities, the suite, or `includes/wc-core-functions.php`, `wc-order-functions.php` and `wc-product-functions.php`, where the currency, order status, order type, product type and stock status lists come from. A schema that reads from another file needs that file added to the job's `changes` in `package.json`. Pushes to trunk run the job whatever changed.

## Running

```bash
pnpm test:mcp-inspector
```

To run one file, pass it to `node --test`:

```bash
node --test tests/mcp-inspector/abilities/orders-query.test.mjs
```

- `WP_ENV_CWD` points at another checkout whose environment is already running.
- `MCP_INSPECTOR_BIN` sets the Inspector binary, instead of the one `pnpm install` adds.
- `MCP_INSPECTOR_CONCURRENCY` sets how many Inspector calls the contract test runs at once. The default is 4.
- `UPDATE_SNAPSHOTS=1` rewrites the snapshots after an intended change.

A missing snapshot is written and fails the run, so review it before re-running.

The tests run WP-CLI in the environment's `cli` container through Docker Compose, rather than through `wp-env run`. Every `wp-env` command rewrites the environment's cache file without a lock, so parallel commands break each other.

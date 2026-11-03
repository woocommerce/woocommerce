# MCP Inspector contract tests

These tests drive WooCommerce's abilities the way an MCP client sees them: [MCP Inspector](https://github.com/modelcontextprotocol/inspector) talks to `wp mcp-adapter serve` in the `wp-env` development environment, and the suite checks every exposed `woocommerce/` ability against shared rules:

- Its `mcp-adapter-get-ability-info` response matches a reviewed snapshot in `snapshots/ability-info/`.
- No schema description lists its own `enum` values or names its default in prose.
- Its schema stays within a token budget, estimated at four characters per token.
- A readonly ability without required input runs with `{}`.
- A customer is refused.

## Setup

Add the MCP Adapter to the development environment with a `.wp-env.override.json` in `plugins/woocommerce`:

```json
{
    "plugins": [ ".", "https://downloads.wordpress.org/plugin/mcp-adapter.0.7.1.zip" ],
    "config": {
        "WP_MEMORY_LIMIT": "1G",
        "WP_DEBUG_DISPLAY": false
    }
}
```

Then start the environment and create the user the permission tests run as:

```bash
pnpm env:dev:start
pnpm exec wp-env run cli wp user create customer customer@example.com --role=customer
```

## Running

```bash
pnpm test:mcp-inspector
```

- `MCP_INSPECTOR_BIN` sets the Inspector binary.
- `WP_ENV_CWD` points at another checkout whose environment is already running.
- `MCP_INSPECTOR_CONCURRENCY` sets how many Inspector calls run at once. The default is 4.
- `UPDATE_SNAPSHOTS=1` rewrites the snapshots after an intended change.

A missing snapshot is written and fails the run, so review it before re-running.

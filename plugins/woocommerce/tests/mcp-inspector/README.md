# MCP Inspector contract tests

These tests drive WooCommerce's abilities the way an MCP client sees them: [MCP Inspector](https://github.com/modelcontextprotocol/inspector) talks to `wp mcp-adapter serve` in the `wp-env` development environment, and the suite checks every exposed `woocommerce/` ability against shared rules:

- Its `mcp-adapter-get-ability-info` response matches a reviewed snapshot in `snapshots/ability-info/`.
- No schema description lists its own `enum` values or names its default in prose.
- Its schema stays within a token budget, estimated at four characters per token.
- A readonly ability without required input runs with `{}`.
- A customer is refused.

## Setup

The suite runs against its own environment, `.wp-env.mcp.json`, which adds the MCP Adapter and creates the `customer` user the permission tests run as:

```bash
pnpm env:mcp
```

## Running

```bash
pnpm test:mcp-inspector
```

- `WP_ENV_CWD` points at another checkout whose environment is already running.
- `MCP_INSPECTOR_CONCURRENCY` sets how many Inspector calls run at once. The default is 4.
- `UPDATE_SNAPSHOTS=1` rewrites the snapshots after an intended change.

A missing snapshot is written and fails the run, so review it before re-running.

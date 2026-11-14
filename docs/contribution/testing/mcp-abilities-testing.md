---
post_title: Testing abilities over MCP
sidebar_label: MCP abilities testing
category_slug: testing
---

# Testing abilities over MCP

WooCommerce registers its abilities through the WordPress Abilities API, and the [MCP Adapter plugin](https://wordpress.org/plugins/mcp-adapter/) exposes them to Model Context Protocol (MCP) clients. When you change an ability, you can run your local `wp-env` site as an MCP server and call the ability the way an AI client would, using [MCP Inspector](https://github.com/modelcontextprotocol/inspector).

This complements the PHP unit tests: it shows the schema, permission check, and output exactly as an MCP client receives them.

## Prerequisites

A working monorepo checkout and the `wp-env` development environment, as described in [Setting up your development environment](/docs/getting-started/development-environment/). All commands below run from `plugins/woocommerce`.

## Add the MCP Adapter to your environment

WooCommerce does not bundle the MCP Adapter. Add it to your environment with a `.wp-env.override.json` in `plugins/woocommerce`. The file is gitignored, so it only affects your machine:

```json
{
    "plugins": [ ".", "https://downloads.wordpress.org/plugin/mcp-adapter.zip" ],
    "config": {
        "WP_MEMORY_LIMIT": "1G",
        "WP_DEBUG_DISPLAY": false
    }
}
```

- `plugins` in an override replaces the list in `.wp-env.json` instead of extending it, so keep `"."` in it.
- `WP_DEBUG_DISPLAY` is `true` in `.wp-env.json`. Over STDIO, PHP notices would be printed into the JSON-RPC stream and break the client connection. With it set to `false`, notices still go to `wp-content/debug.log`.
- `WP_MEMORY_LIMIT` raises the default 128 MB limit, which a WP-CLI run with WooCommerce loaded can exceed.

Then start (or restart) the environment:

```bash
pnpm env:dev:start
```

## Confirm the server

```bash
pnpm exec wp-env run cli wp mcp-adapter list
```

The output lists `mcp-adapter-default-server`, which is the server that exposes WooCommerce abilities.

Use `pnpm exec wp-env` rather than `pnpm wp-env` whenever a command runs `mcp-adapter serve`. Running wp-env as a package script makes `pnpm` print the command to stdout, which corrupts the MCP stream. `wp-env` itself writes its status messages to stderr.

## Connect MCP Inspector

```bash
npx @modelcontextprotocol/inspector \
  pnpm exec wp-env run cli wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin
```

Run it from `plugins/woocommerce`, because `wp-env` finds the environment from the working directory. The first connection takes several seconds while `wp-env` starts the command.

The default server provides three tools:

- `mcp-adapter-discover-abilities` lists the abilities exposed to MCP.
- `mcp-adapter-get-ability-info` returns the input and output schema of one ability.
- `mcp-adapter-execute-ability` runs an ability. Pass its name as `ability_name` and its input as `parameters`, for example `{ "ability_name": "woocommerce/products-query", "parameters": {} }`.

## Exercise an ability

A fresh environment has no products or orders, so create the data your ability needs first:

```bash
pnpm exec wp-env run cli wp wc product create --name="Test hoodie" --regular_price=45 --user=admin
```

The `--user` flag on `mcp-adapter serve` sets the WordPress user every ability runs as. To check an ability's permission callback, connect a second time as a user who should be refused:

```bash
pnpm exec wp-env run cli wp user create customer customer@example.com --role=customer

npx @modelcontextprotocol/inspector \
  pnpm exec wp-env run cli wp mcp-adapter serve --server=mcp-adapter-default-server --user=customer
```

Executing `woocommerce/products-query` as `customer` returns `Permission denied`.

You can also send messages without Inspector, which is useful for a quick check:

```bash
printf '%s\n' \
  '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"cli","version":"0"}}}' \
  '{"jsonrpc":"2.0","method":"notifications/initialized"}' \
  '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"mcp-adapter-execute-ability","arguments":{"ability_name":"woocommerce/products-query","parameters":{}}}}' \
  | pnpm exec wp-env run cli wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin
```

## Testing instructions in pull requests

When a pull request changes an ability, link to this page from its testing instructions instead of repeating the setup, and list the ability calls and users the reviewer should try. See [Writing high quality testing instructions](/docs/contribution/testing/writing-high-quality-testing-instructions/).

## Troubleshooting

- **Inspector connects but receives no messages, or reports invalid JSON:** check that the command uses `pnpm exec wp-env`, and that `WP_DEBUG_DISPLAY` is `false`.
- **`Allowed memory size … exhausted`:** set `WP_MEMORY_LIMIT` as shown above and restart the environment.
- **`Permission denied`:** the `--user` passed to `mcp-adapter serve` lacks the capability the ability requires.
- **An ability is missing from discovery:** only abilities whose `meta` sets `mcp.public` to `true` are exposed through the default server.

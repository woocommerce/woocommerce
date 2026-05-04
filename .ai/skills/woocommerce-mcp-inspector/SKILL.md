---
name: woocommerce-mcp-inspector
description: E2E test the WooCommerce MCP server using the official MCP Inspector CLI against a local wp-env instance. Use when testing MCP tool calls, listing tools, debugging MCP responses, or verifying WooCommerce abilities behavior.
allowed-tools: Bash, Read, Grep, Glob, Agent, TodoWrite
---

# WooCommerce MCP Inspector Testing Skill

Fully automated E2E test of the WooCommerce MCP server using the **official MCP Inspector CLI** ([github.com/modelcontextprotocol/inspector](https://github.com/modelcontextprotocol/inspector)) against a local `wp-env` environment. All commands run in bash.

**Important constraints:**
- This skill is **read-only / test-only**. It must NEVER modify source code, fix bugs, or apply patches. Its sole purpose is to discover what exists, test how it works, and report problems found.
- After all tests complete, you MUST produce the structured report defined in the "Report Format" section. The report is the deliverable.

## Prerequisites

- Node.js 18+ and `npx`
- Docker running (for wp-env)
- wp-env started: `pnpm --filter=@woocommerce/plugin-woocommerce wp-env start`

## Environment Setup

Before running any MCP Inspector commands, the wp-env environment must be configured. Run these steps once per session.

### 1. Detect wp-env Site URL

```bash
SITE_URL=$(pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp option get siteurl 2>&1 | grep '^http')
echo "Site URL: $SITE_URL"
```

### 2. Enable the MCP Feature Flag

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp option update woocommerce_feature_mcp_integration_enabled yes
```

### 3. Allow Insecure Transport (Required for Local HTTP)

The WooCommerce MCP transport requires HTTPS by default. For local testing over HTTP, install a mu-plugin to bypass this:

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli -- sh -c \
  "mkdir -p /var/www/html/wp-content/mu-plugins && echo '<?php add_filter(\"woocommerce_mcp_allow_insecure_transport\", \"__return_true\");' > /var/www/html/wp-content/mu-plugins/mcp-allow-insecure.php"
```

### 4. Create a WooCommerce REST API Key

WooCommerce MCP uses `X-MCP-API-Key` header authentication with `consumer_key:consumer_secret` format.

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp eval '
global $wpdb;
$ck = "ck_mcp_inspector_test";
$cs = "cs_mcp_inspector_secret";
$existing = $wpdb->get_var($wpdb->prepare(
    "SELECT key_id FROM {$wpdb->prefix}woocommerce_api_keys WHERE consumer_key = %s",
    wc_api_hash($ck)
));
if ($existing) { echo "key_exists"; exit; }
$wpdb->insert(
    $wpdb->prefix . "woocommerce_api_keys",
    array(
        "user_id"         => 1,
        "description"     => "MCP Inspector Test",
        "permissions"     => "read_write",
        "consumer_key"    => wc_api_hash($ck),
        "consumer_secret" => $cs,
        "truncated_key"   => substr($ck, -7),
    )
);
echo "key_created:" . $wpdb->insert_id;
'
```

### 5. Verify Setup

Quick sanity check that the endpoint responds:

```bash
SITE_URL=$(pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp option get siteurl 2>&1 | grep '^http')

curl -s -X POST "$SITE_URL/?rest_route=/woocommerce/mcp" \
  -H "Content-Type: application/json" \
  -H "X-MCP-API-Key: ck_mcp_inspector_test:cs_mcp_inspector_secret" \
  -d '{"jsonrpc":"2.0","method":"initialize","id":1,"params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"setup-check","version":"1.0"}}}'
```

Expected: HTTP 200 with JSON-RPC response containing `serverInfo.name` and `capabilities`.

> **Note:** The `protocolVersion` value must match what the server supports. Check the server's `initialize` response to confirm the current version.

## MCP Endpoint

```
{SITE_URL}/?rest_route=/woocommerce/mcp
```

Uses the WordPress REST API query-string route format. wp-env does not enable pretty permalinks by default.

## Authentication

WooCommerce MCP uses a custom `X-MCP-API-Key` header (not OAuth or Basic Auth):

```
X-MCP-API-Key: ck_mcp_inspector_test:cs_mcp_inspector_secret
```

## Raw curl Session Management

The MCP server requires a `Mcp-Session-Id` header on all calls after `initialize`. The Inspector CLI handles this automatically, but when using raw curl (e.g., to extract response data when the Inspector fails on schema validation), you must manage the session manually:

```bash
SITE_URL=$(pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp option get siteurl 2>&1 | grep '^http')

# Step 1: Initialize and capture session ID from response header
curl -s -D /tmp/mcp_headers -X POST "$SITE_URL/?rest_route=/woocommerce/mcp" \
  -H "Content-Type: application/json" \
  -H "X-MCP-API-Key: ck_mcp_inspector_test:cs_mcp_inspector_secret" \
  -d '{"jsonrpc":"2.0","method":"initialize","id":1,"params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"curl-session","version":"1.0"}}}'

SESSION_ID=$(grep -i 'mcp-session-id' /tmp/mcp_headers | awk '{print $2}' | tr -d '\r')

# Step 2: Send initialized notification
curl -s -X POST "$SITE_URL/?rest_route=/woocommerce/mcp" \
  -H "Content-Type: application/json" \
  -H "X-MCP-API-Key: ck_mcp_inspector_test:cs_mcp_inspector_secret" \
  -H "Mcp-Session-Id: $SESSION_ID" \
  -d '{"jsonrpc":"2.0","method":"notifications/initialized"}'

# Step 3: Make tool calls with the session ID
curl -s -X POST "$SITE_URL/?rest_route=/woocommerce/mcp" \
  -H "Content-Type: application/json" \
  -H "X-MCP-API-Key: ck_mcp_inspector_test:cs_mcp_inspector_secret" \
  -H "Mcp-Session-Id: $SESSION_ID" \
  --output /tmp/mcp_result.bin \
  -d '{"jsonrpc":"2.0","method":"tools/call","id":2,"params":{"name":"woocommerce-products-list","arguments":{"per_page":2}}}'
```

This is especially useful when schema validation errors (`-32602`) prevent the Inspector from returning data — the operation succeeds server-side and the raw response contains `structuredContent` with the actual data.

## Inspector CLI Reference

### Base Command

```bash
SITE_URL=$(pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp option get siteurl 2>&1 | grep '^http')

npx @modelcontextprotocol/inspector@latest --cli \
  "$SITE_URL/?rest_route=/woocommerce/mcp" \
  --transport http \
  --header "X-MCP-API-Key: ck_mcp_inspector_test:cs_mcp_inspector_secret" \
  --header "Content-Type: application/json" \
  --method <method> \
  [method-specific flags]
```

The Inspector handles the full MCP handshake (initialize -> initialized -> method call) automatically.

### Helper Function

For repeated testing, define a shell function:

```bash
SITE_URL=$(pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp option get siteurl 2>&1 | grep '^http')

woo_mcp() {
  npx @modelcontextprotocol/inspector@latest --cli \
    "$SITE_URL/?rest_route=/woocommerce/mcp" \
    --transport http \
    --header "X-MCP-API-Key: ck_mcp_inspector_test:cs_mcp_inspector_secret" \
    --header "Content-Type: application/json" \
    "$@"
}
```

### Supported Methods

| Method | Description | Key Flags |
|--------|-------------|-----------|
| `tools/list` | List all registered tools | — |
| `tools/call` | Execute a tool | `--tool-name`, `--tool-arg 'key=value'` |
| `resources/list` | List available resources | — |
| `resources/read` | Read a specific resource | `--uri` |
| `resources/templates/list` | List resource templates | — |
| `prompts/list` | List available prompts | — |
| `prompts/get` | Get a specific prompt | `--prompt-name` |
| `logging/setLevel` | Set server log level | `--log-level` |

### Tool Argument Typing

The Inspector auto-types `--tool-arg` values: bare numbers like `9.99` are sent as JSON numbers. If the schema expects a string (e.g., `regular_price`), wrap the value in escaped quotes:

```bash
--tool-arg 'regular_price="9.99"'    # sent as string "9.99"
--tool-arg 'per_page=5'               # sent as number 5 (fine for integer fields)
```

## Test Strategy

The test is fully dynamic. **Do not hard-code tool names, resource URIs, or prompt names.** Discover everything at runtime from the server's own responses.

### Phase 1: Server Capabilities

1. Verify the MCP endpoint responds to the `initialize` handshake (use the curl command from setup step 5).
2. Record the server's `protocolVersion`, `serverInfo` (name, version), and `capabilities` (which primitives are supported: tools, resources, prompts).

### Phase 2: Discovery

Test all MCP discovery methods. Each should succeed even if it returns an empty list.

1. `tools/list` — record all tool names, titles, descriptions, and input schemas.
2. `resources/list` — record all resource URIs and descriptions.
3. `resources/templates/list` — record whether supported or returns `-32601 Method not found`.
4. `prompts/list` — record all prompt names and descriptions.

Group discovered tools by resource type. Tool names follow the pattern `woocommerce-{resource}-{operation}` (e.g., `woocommerce-products-list`, `woocommerce-orders-create`). Extract the resource and operation from each name.

### Phase 3: Tool CRUD Testing

For **each resource group** discovered in Phase 2, run a full CRUD cycle:

#### a) List (read, no ID required)
Call the `{resource}-list` tool with `per_page=2`. This tests read operations and output schema validation.

#### b) Create (write, generates an ID)
Call the `{resource}-create` tool with **minimal valid arguments**. Inspect the tool's `inputSchema` to determine required fields and their types. Extract the created resource's `id` from the response for subsequent steps.

#### c) Get (read, requires ID)
Call the `{resource}-get` tool with the `id` from the create step.

#### d) Update (write, requires ID)
Call the `{resource}-update` tool with the `id` and one changed field (e.g., update the name).

#### e) Delete (write, requires ID, cleanup)
Call the `{resource}-delete` tool with the `id` to clean up test data.

#### f) List after delete
Call `{resource}-list` again to verify the deleted resource is gone.

**If a resource group doesn't have all CRUD operations** (e.g., only `list` and `get`), test whatever operations are available.

### Phase 4: Resource Testing

For each resource discovered in Phase 2, call `resources/read` with its URI. Verify the response contains content.

### Phase 5: Prompt Testing

For each prompt discovered in Phase 2, call `prompts/get` with its name. Verify the response contains messages.

### Phase 6: Error Handling

Test how the server handles invalid inputs:

1. Call a `get` tool with a non-existent ID (e.g., `id=999999`) — expect a structured error, not a crash.
2. Call a `create` tool with missing required fields — expect a validation error.
3. Call a `list` tool with invalid filter values (e.g., `status=nonexistent`) — expect an error or empty results.
4. Call `resources/read` with a non-existent URI (if resources exist).
5. Call `resources/templates/list` — record whether the method is supported or returns `-32601 Method not found`.

### Handling Failures

- If a tool call fails, **record the full error message** and continue testing remaining tools.
- If `create` fails, skip `get`, `update`, and `delete` for that resource (they depend on the created ID) but still test `list`.
- Output schema validation errors (`-32602: Structured content does not match the tool's output schema`) mean the operation succeeded server-side but the declared schema doesn't match the actual response. These are bugs in the schema definition.
- **When schema errors prevent the Inspector from returning data**, use raw curl with session management (see "Raw curl Session Management" section above) to extract the `structuredContent` from the response. This lets you get the created resource's `id` and continue the CRUD chain (get, update, delete).
- Protocol errors (`-32601 Method not found`) mean the server doesn't implement that MCP method. Record it.
- **Inspector exit code:** The Inspector CLI exits with code 1 on schema validation errors. To prevent one failing test from blocking subsequent tests, run each tool test as a separate command or append `|| true` to allow the script to continue.

## Report Format

After all tests complete, you MUST produce the structured report below. This is the deliverable of the skill. Do NOT attempt to fix any issues found — only report them. Do NOT modify any source code.

### 1. Environment
- Site URL, MCP server name, server version, protocol version
- Declared capabilities (tools, resources, prompts — which are enabled)

### 2. Discovery Results
- Table of all discovered tools: name, title, operation type (list/get/create/update/delete)
- List of discovered resources (if any)
- List of discovered prompts (if any)
- Unsupported discovery methods (if any returned `-32601`)

### 3. Test Results
- Table with one row per test: test number, tool/resource/prompt name, operation, result (PASS/FAIL), error summary if failed

### 4. Schema Validation Issues
For each output schema error, list:
- The field path (e.g., `data/date_created`)
- What the schema declares (type, format)
- What the API actually returns (observed value/type)
- The source file and line where the schema is defined (if identifiable by searching the codebase)

### 5. Protocol Issues
- Unsupported methods that the server's `capabilities` claim to support
- Methods that return unexpected error codes
- Any initialize handshake anomalies

### 6. Other Issues
- Authentication errors, input validation failures, unexpected responses, server errors

### 7. Summary
- Total: tools discovered, resources discovered, prompts discovered
- Total tests run, pass count, fail count
- Categorized failure breakdown (schema mismatch, input validation, protocol error, server error, etc.)

### 8. Analysis

Write a narrative analysis that goes beyond the tables. This section should:

- **Explain the big picture:** What is the overall health of the MCP server? Is it fundamentally working with surface-level schema issues, or are there deeper problems?
- **Identify root causes:** Group related failures and trace them back to common causes. For example, if 8 tools fail with the same date-time schema error, explain that once clearly instead of listing it 8 times.
- **Assess impact:** Which issues actually block MCP clients from using the server, and which are cosmetic? A schema mismatch that causes the Inspector to reject a valid response is different from a missing capability.
- **Highlight surprises:** Call out anything unexpected — behaviors that contradict the documentation, capabilities advertised but not implemented, error responses with wrong `isError` flags, etc.
- **Suggest priorities:** If someone were to fix these issues, what should they tackle first and why?

The analysis should read like a senior engineer's assessment, not a generated table. Use plain language and be direct about what works and what doesn't.

## Debugging

### Enable WordPress Debug Logging

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp config set WP_DEBUG true --raw
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp config set WP_DEBUG_LOG true --raw
```

### Read Debug Log

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli -- cat /var/www/html/wp-content/debug.log
```

### Check WooCommerce Logs

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli -- ls -la /var/www/html/wp-content/uploads/wc-logs/
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli -- cat /var/www/html/wp-content/uploads/wc-logs/woocommerce-mcp-*.log
```

### Test Authentication Directly (curl)

```bash
SITE_URL=$(pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp option get siteurl 2>&1 | grep '^http')

curl -s -D - -X POST "$SITE_URL/?rest_route=/woocommerce/mcp" \
  -H "Content-Type: application/json" \
  -H "X-MCP-API-Key: ck_mcp_inspector_test:cs_mcp_inspector_secret" \
  -d '{"jsonrpc":"2.0","method":"initialize","id":1,"params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"debug","version":"1.0"}}}'
```

### Check MCP Feature Flag

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp option get woocommerce_feature_mcp_integration_enabled
```

### Verify Classes Are Autoloaded

> **Note:** The class names below come from the `wp-mcp` library and WooCommerce MCP integration. They may change between versions — verify against the current `composer.lock` if results show "MISSING" unexpectedly.

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli -- php -r '
require_once "/var/www/html/wp-content/plugins/woocommerce/vendor/autoload.php";
$classes = [
    "WP\MCP\Core\McpAdapter",
    "WP\MCP\Transport\HttpTransport",
    "WP\MCP\Transport\Contracts\McpRestTransportInterface",
    "Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider",
    "Automattic\WooCommerce\Internal\MCP\Transport\WooCommerceRestTransport",
];
foreach ($classes as $c) {
    echo (class_exists($c) || interface_exists($c) ? "OK" : "MISSING") . ": $c\n";
}
'
```

## Common Issues

### 403 Insecure Transport

```json
{"code":"insecure_transport","message":"HTTPS is required for MCP requests."}
```

The mu-plugin for insecure transport is missing or not loaded. Re-run step 3 of Environment Setup.

### 401 Authentication Failed

```json
{"code":"authentication_failed","message":"Authentication failed."}
```

- The API key was created with the wrong hash. WooCommerce uses `wc_api_hash()` (not plain `sha256`). Re-run step 4 of Environment Setup.
- Check the key format: `X-MCP-API-Key: consumer_key:consumer_secret` (colon-separated).

### 401 Missing API Key

```json
{"code":"missing_api_key","message":"X-MCP-API-Key header required."}
```

The `--header "X-MCP-API-Key: ..."` flag is missing from the Inspector command.

### HTTP 200 Empty Body

The MCP endpoint matched but returned nothing. This usually means:
- The MCP feature flag is disabled. Run step 2 of Environment Setup.
- The request hit the wrong container (tests vs dev). Verify `SITE_URL`.

### Tools List Empty

If `tools/list` returns an empty array, no abilities are registered:
- Check that `AbilitiesRegistry` has WooCommerce abilities
- The MCP feature flag may be enabled but the abilities module is not loaded

### Port Conflicts (Inspector)

If the Inspector reports "Proxy Server PORT IS IN USE":

```bash
pkill -f "@modelcontextprotocol/inspector"
```

### wp-env Not Running

If curl returns connection refused:

```bash
pnpm --filter=@woocommerce/plugin-woocommerce wp-env start
```

## Test Data Cleanup

After testing, clean up any resources that couldn't be deleted via MCP tools (e.g., orders, which have no `delete` tool):

```bash
# Delete test orders by title/status pattern
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp post list --post_type=shop_order --post_status=any --format=ids | xargs -I{} pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp post delete {} --force

# Delete test products (if any remain)
pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp post list --post_type=product --post_status=any --s="MCP Test" --format=ids | xargs -I{} pnpm --filter=@woocommerce/plugin-woocommerce wp-env run cli wp post delete {} --force
```

> **Note:** Orders don't have a `delete` MCP tool, so test orders accumulate across test runs. Always clean up after testing.

## API Key Permissions

The test key is created with `read_write` permissions. WooCommerce MCP enforces permissions per HTTP method:

| Permission | Allowed Methods |
|-----------|----------------|
| `read` | GET, HEAD |
| `write` | POST, PUT, PATCH, DELETE |
| `read_write` | All methods |

To test with restricted permissions, create additional keys with different `permissions` values.

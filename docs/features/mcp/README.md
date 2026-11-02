---
post_title: Model Context Protocol (MCP) Integration
sidebar_label: MCP Integration
category_slug: mcp
---

# Model Context Protocol (MCP) Integration

## Introduction

WooCommerce registers product and order abilities in WordPress. With the MCP Adapter plugin, AI clients can discover and run these store operations through the Model Context Protocol (MCP), subject to WordPress and WooCommerce permissions.

:::info

**Developer Preview Notice**
The MCP implementation in WooCommerce is currently in developer preview. Implementation details, APIs, and integration patterns may change in future releases as the feature matures.

:::

## Enabling MCP Integration

WooCommerce always registers its abilities through the WordPress Abilities API; there is no WooCommerce setting to turn on. To make them available to MCP clients, install and activate the [MCP Adapter plugin](https://wordpress.org/plugins/mcp-adapter/):

```bash
wp plugin install mcp-adapter --activate
```

WooCommerce does not bundle the MCP adapter.

## Authentication and Security

### WordPress Application Password Requirements

Remote HTTP connections authenticate using a WordPress username and an [Application Password](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/#basic-authentication-with-application-passwords). Do not use your account password or a WooCommerce REST API key.

To create an Application Password:

1. In the WordPress dashboard, navigate to **Users → Profile**.
2. Under **Application Passwords**, enter a name for the MCP client.
3. Click **Add New Application Password**.
4. Copy the generated password and store it securely. WordPress only displays it once.

Use the WordPress username and generated password as `WP_API_USERNAME` and `WP_API_PASSWORD` in the remote proxy configuration. Application Passwords can be revoked independently from the user's profile.

### HTTPS Enforcement

Use HTTPS for remote HTTP connections so credentials are encrypted in transit. Local STDIO connections authenticate through WP-CLI using the configured `--user` and do not require an Application Password.

### Permission Validation

The MCP Adapter authenticates requests as the WordPress user associated with the Application Password. The default server requires an authenticated user with the `read` capability, and each WooCommerce ability also enforces its own permission callback. Use a dedicated WordPress user with only the capabilities the MCP client needs.

## Server Endpoint

WooCommerce abilities are available through the WordPress MCP Adapter's default server:

```text
https://yourstore.com/wp-json/mcp/mcp-adapter-default-server
```

The WooCommerce-specific `/wp-json/woocommerce/mcp` endpoint was removed in WooCommerce 11.3. See [Removed in WooCommerce 11.3](#removed-in-woocommerce-113).

## Connecting to the MCP Server

Choose a connection method based on where the WordPress site is running. For a detailed overview and application-specific instructions, see [Connecting AI applications](https://developer.wordpress.org/news/2026/02/from-abilities-to-ai-agents-introducing-the-wordpress-mcp-adapter/#connecting-ai-applications) and the [wordpress/mcp-adaptor repository](https://github.com/wordpress/mcp-adapter).

### Local Sites Using STDIO

For a local WordPress installation, MCP clients can connect directly through the MCP Adapter's WP-CLI command. The client needs access to the WordPress files and a working WP-CLI installation.

#### JSON Configuration

Add the following server to your MCP client configuration. Replace the WordPress path and user with values from your local environment:

```json
{
  "mcpServers": {
    "woocommerce_store": {
      "command": "wp",
      "args": [
        "--path=/path/to/your/wordpress/site",
        "mcp-adapter",
        "serve",
        "--server=mcp-adapter-default-server",
        "--user=admin"
      ]
    }
  }
}
```

Claude Desktop, Claude Code, and Cursor use the `mcpServers` property shown above. For VS Code, use `servers` instead.

#### Command-Line Configuration

Claude Code users can add the same server from the command line:

```bash
claude mcp add woocommerce_store -- \
  wp --path=/path/to/your/wordpress/site \
  mcp-adapter serve \
  --server=mcp-adapter-default-server \
  --user=admin
```

You can verify the server directly with WP-CLI:

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}' | \
  wp --path=/path/to/your/wordpress/site \
  mcp-adapter serve \
  --server=mcp-adapter-default-server \
  --user=admin
```

### Remote Sites Using HTTP

For a remote WordPress installation, use the `@automattic/mcp-wordpress-remote` proxy. The proxy translates the MCP client's local STDIO messages into HTTP requests to the WordPress MCP Adapter.

Before connecting:

1. Create or select a dedicated WordPress user with the minimum required capabilities.
2. Open the user's **Profile** screen and create a WordPress Application Password.
3. Confirm the site uses HTTPS and non-Plain permalinks.

#### JSON Configuration

Add the following server to your MCP client configuration. Replace the example URL, username, and Application Password:

```json
{
  "mcpServers": {
    "woocommerce_store": {
      "command": "npx",
      "args": [
        "-y",
        "@automattic/mcp-wordpress-remote@latest"
      ],
      "env": {
        "WP_API_URL": "https://yourstore.com/wp-json/mcp/mcp-adapter-default-server",
        "WP_API_USERNAME": "your-username",
        "WP_API_PASSWORD": "your-application-password"
      }
    }
  }
}
```

Claude Desktop, Claude Code, and Cursor use the `mcpServers` property shown above. For VS Code, use `servers` instead. Do not commit a configuration containing an Application Password to version control.

#### Command-Line Configuration

Claude Code users can add the remote server from the command line:

```bash
claude mcp add \
  --env WP_API_URL=https://yourstore.com/wp-json/mcp/mcp-adapter-default-server \
  --env WP_API_USERNAME=your-username \
  --env WP_API_PASSWORD='your-application-password' \
  woocommerce_store \
  -- npx -y @automattic/mcp-wordpress-remote@latest
```

**Troubleshooting**: For common setup issues with npx versions or SSL in local environments, see the [mcp-wordpress-remote troubleshooting guide](https://github.com/Automattic/mcp-wordpress-remote/blob/trunk/Docs/troubleshooting.md).

## Discover WooCommerce Abilities

The default MCP server exposes three tools: `mcp-adapter-discover-abilities`, `mcp-adapter-get-ability-info`, and `mcp-adapter-execute-ability`. A `tools/list` response shows these adapter tools, not a separate tool for each WooCommerce ability. Use the discovery tool to list available abilities, then get an ability's schema before executing it.

For a local site, you can check discovery with WP-CLI:

```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"mcp-adapter-discover-abilities","arguments":{}}}' | \
  wp --path=/path/to/your/wordpress/site \
  mcp-adapter serve \
  --server=mcp-adapter-default-server \
  --user=admin
```

On a WooCommerce 11.3 site, the response should include abilities such as `woocommerce/products-query` and `woocommerce/order-notes-query`. Each ability checks permissions when it runs. See the [MCP Adapter default server guide](https://github.com/WordPress/mcp-adapter/blob/trunk/docs/guides/default-server.md) for the discovery and execution flow.

## Background

The Model Context Protocol (MCP) is an open standard that enables AI applications to securely connect to external data sources and tools. Using MCP with WooCommerce relies on two core technologies:

- **[WordPress Abilities API](https://github.com/WordPress/abilities-api)** - A standardized system for registering capabilities in WordPress, included in WordPress core
- **[WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter)** - The core MCP protocol implementation, installed as the [MCP Adapter plugin](https://wordpress.org/plugins/mcp-adapter/)

This architecture allows WooCommerce to expose operations as MCP tools through the flexible WordPress Abilities system while maintaining existing security and permission models.

## What's Available

WooCommerce registers purpose-built abilities for core store operations. These abilities are available through the WordPress Abilities API and can be surfaced through the shared WordPress MCP adapter.

### Purpose-Built WooCommerce Abilities

#### Product Management

- Query products with filtering and pagination
- Create new products
- Update existing products
- Delete products

#### Order Management

- Query orders with filtering and pagination
- Query notes for an order (`woocommerce/order-notes-query`)
- Update order status
- Add order notes

All operations respect WooCommerce's existing permission system. MCP clients authenticate through the MCP Adapter as a WordPress user.

### Removed in WooCommerce 11.3

WooCommerce 11.3 removed the deprecated WooCommerce MCP endpoint and everything that only existed to support it:

- The `/wp-json/woocommerce/mcp` endpoint and its WooCommerce REST API key authentication (`X-MCP-API-Key`). Requests to the endpoint now return HTTP 410 with a JSON-RPC error that explains how to connect through the MCP Adapter.
- The REST-derived product and order abilities (`woocommerce/products-list`, `woocommerce/orders-get`, and similar), along with the `woocommerce_mcp_include_ability`, `woocommerce_mcp_allow_insecure_transport`, and `woocommerce_check_rest_ability_permissions_for_method` filters and the `expose_in_deprecated_woocommerce_mcp` ability metadata.

Custom abilities registered by extensions or custom code that appeared in the removed endpoint don't carry over automatically. Before WooCommerce 10.9, the endpoint included abilities in the `woocommerce/` namespace; from 10.9, it included abilities with `expose_in_deprecated_woocommerce_mcp` set to `true`; and the `woocommerce_mcp_include_ability` filter could add or remove abilities in either version. The MCP Adapter's default server ignores all of these. It only exposes an ability when its `meta.mcp.public` is `true` (if `meta.mcp.public` is not set, the adapter falls back to the top-level `meta.public`). To keep a custom ability available to MCP clients, set that metadata as shown in [Adding Custom Abilities](#adding-custom-abilities).

To keep using WooCommerce with MCP, install the [MCP Adapter plugin](https://wordpress.org/plugins/mcp-adapter/) and connect your MCP client to its default server, as described in [Connecting to the MCP Server](#connecting-to-the-mcp-server). Stores that had the WooCommerce MCP feature enabled see an admin notice about the change.

:::warning

**Data Privacy Notice**
Order and customer operations may expose personally identifiable information (PII) including names, email addresses, physical addresses, and payment details. You are responsible for ensuring compliance with applicable data protection regulations. Connect MCP clients as a dedicated WordPress user with only the capabilities they need, revoke Application Passwords you no longer use, and follow your organization's data retention and handling policies.

:::

## Architecture

### Data Flow Overview

The MCP integration uses a multi-layered architecture to bridge between MCP clients and WordPress:

```text
AI Client (Claude, etc.)
    ↓ (MCP protocol over stdio/JSON-RPC)
Local MCP Proxy (mcp-wordpress-remote)
    ↓ (HTTP/HTTPS requests with authentication)
Remote WordPress MCP Server (mcp-adapter)
    ↓ (WordPress Abilities API)
WooCommerce Abilities
    ↓ (REST API calls or direct operations)
WooCommerce Core
```

### Architecture Components

**Local MCP Proxy** (`mcp-wordpress-remote`)

- Runs locally on the developer's machine as a Node.js process
- Converts MCP protocol messages to HTTP requests
- Handles authentication header injection
- Bridges the protocol gap between MCP clients and WordPress REST endpoints

**Remote WordPress MCP Server** (`mcp-adapter`)

- Is provided by the [MCP Adapter plugin](https://wordpress.org/plugins/mcp-adapter/), which must be installed and active
- Powers MCP tool discovery and execution for WordPress abilities

#### WordPress Abilities System

- Provides a standardized way to register and execute capabilities
- Acts as an abstraction layer between MCP tools and actual operations
- Enables flexible implementation approaches (direct operations, REST bridging, and so on)

### Core Components

**Abilities Registry** ([`AbilitiesRegistry.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/Abilities/AbilitiesRegistry.php))

- Initializes WooCommerce ability categories and loaders
- Bridges WordPress Abilities API with WooCommerce operations
- Enables ability discovery for MCP servers and other Abilities API consumers

**Purpose-Built Domain Abilities** ([`Domain`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/Abilities/Domain))

- Provide WooCommerce product and order abilities backed by domain APIs
- Use shared WordPress MCP adapter metadata (`mcp.public` and `mcp.type`) for MCP exposure
- Keep the ability contract focused on agent-friendly store operations

### Implementation Approach

WooCommerce implements its abilities as purpose-built domain abilities. These abilities use schemas and response shapes designed for agent workflows instead of automatically projecting every REST-shaped operation into MCP.

## Extending MCP Capabilities

Third-party plugins can register additional abilities using the WordPress Abilities API. Abilities can be implemented in various ways, including direct operations, custom logic, REST endpoint bridging, or external integrations.

### Adding Custom Abilities

Register an ability category first, then register the ability during the WordPress Abilities API init hook:

```php
add_action( 'wp_abilities_api_categories_init', function() {
    if ( ! function_exists( 'wp_register_ability_category' ) ) {
        return;
    }

    if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( 'your-plugin' ) ) {
        return;
    }

    wp_register_ability_category(
        'your-plugin',
        array(
            'label'       => __( 'Your Plugin', 'your-plugin' ),
            'description' => __( 'Abilities provided by Your Plugin.', 'your-plugin' ),
        )
    );
});

add_action( 'wp_abilities_api_init', function() {
    if ( ! function_exists( 'wp_register_ability' ) ) {
        return;
    }

    wp_register_ability(
        'your-plugin/custom-operation',
        array(
            'label'               => __( 'Custom Store Operation', 'your-plugin' ),
            'description'         => __( 'Performs a custom store operation.', 'your-plugin' ),
            'category'            => 'your-plugin',
            'execute_callback'    => 'your_custom_ability_handler',
            'permission_callback' => function () {
                return current_user_can( 'manage_woocommerce' );
            },
            'input_schema'        => array(
                'type'       => 'object',
                'properties' => array(
                    'store_id' => array(
                        'type'        => 'integer',
                        'description' => 'Store identifier',
                    ),
                ),
                'required' => array( 'store_id' ),
            ),
            'output_schema' => array(
                'type'       => 'object',
                'properties' => array(
                    'success' => array(
                        'type'        => 'boolean',
                        'description' => 'Operation result',
                    ),
                ),
            ),
            'meta'                => array(
                'show_in_rest' => true,
                'mcp'          => array(
                    'public' => true,
                    'type'   => 'tool',
                ),
            ),
        )
    );
});
```

The `mcp.public` and `mcp.type` metadata tells the shared WordPress MCP adapter that the ability can be exposed as an MCP tool. The `show_in_rest` metadata exposes the ability through the Abilities API REST routes.

## Development Example

For a complete working example, see the [WooCommerce MCP Ability Demo Plugin](https://github.com/woocommerce/wc-mcp-ability). This demonstration plugin shows how third-party developers can:

- Register custom abilities using the WordPress Abilities API
- Define comprehensive input and output schemas
- Implement proper permission handling
- Integrate with MCP through the shared WordPress MCP adapter

The demo plugin creates a `woocommerce-demo/store-info` ability that retrieves store information and statistics, demonstrating the integration patterns for extending WooCommerce MCP capabilities while using a direct implementation approach rather than REST endpoint bridging.

## Troubleshooting

### Common Issues

## MCP Server Not Available

- Verify the MCP Adapter plugin is installed and active
- Confirm the client connects to `/wp-json/mcp/mcp-adapter-default-server`, not the removed `/wp-json/woocommerce/mcp` endpoint

## Authentication Failures

- Confirm `WP_API_USERNAME` matches the WordPress user that created the Application Password
- Confirm `WP_API_PASSWORD` contains an active Application Password, not the user's account password or a WooCommerce REST API key
- Verify the WordPress user has the capabilities required by the ability
- Ensure HTTPS is used for remote HTTP connections

## Ability Not Found

- Confirm the ability category is registered during `wp_abilities_api_categories_init`
- Confirm abilities are registered during `wp_abilities_api_init`
- Verify ability callbacks are accessible

Check **WooCommerce → Status → Logs** for entries with source `woocommerce-abilities`.

## Important Considerations

- **Developer Preview**: This feature is in preview status and may change
- **Implementation Approach**: WooCommerce uses purpose-built domain abilities
- **Breaking Changes**: Future updates may introduce breaking changes
- **Production Testing**: Thoroughly test before deploying to production
- **API Stability**: The WordPress Abilities API and MCP adapter are evolving

## Related Resources

- [WordPress Abilities API Repository](https://github.com/WordPress/abilities-api)
- [WordPress MCP Adapter Repository](https://github.com/WordPress/mcp-adapter)
- [WooCommerce MCP Demo Plugin](https://github.com/woocommerce/wc-mcp-ability)
- [Model Context Protocol Specification](https://modelcontextprotocol.io/)
- [WooCommerce REST API Documentation](/docs/apis/rest-api/)

---
post_title: Model Context Protocol (MCP) Integration
sidebar_label: MCP Integration
category_slug: mcp
---

# Model Context Protocol (MCP) Integration

## Introduction

WooCommerce includes native support for the Model Context Protocol (MCP), enabling AI assistants and tools to interact directly with WooCommerce stores through a standardized protocol. This integration exposes WooCommerce functionality as discoverable tools that AI clients can use to perform store operations with proper authentication and permissions.

:::info

**Developer Preview Notice**
The MCP implementation in WooCommerce is currently in developer preview. Implementation details, APIs, and integration patterns may change in future releases as the feature matures.

:::

## Background

The Model Context Protocol (MCP) is an open standard that enables AI applications to securely connect to external data sources and tools. WooCommerce's MCP integration builds on two core technologies:

- **[WordPress Abilities API](https://github.com/WordPress/abilities-api)** - A standardized system for registering capabilities in WordPress
- **[WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter)** - The core MCP protocol implementation

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
- Update order status
- Add order notes

### Deprecated WooCommerce MCP Endpoint

The deprecated WooCommerce MCP endpoint also exposes REST-derived compatibility abilities for products and orders. These abilities map to existing REST API operations and currently include product list, retrieve, create, update, and delete operations, plus order list, retrieve, create, and update operations.

All operations respect WooCommerce's existing permission system. The deprecated WooCommerce MCP endpoint authenticates using WooCommerce REST API keys; clients using the shared WordPress MCP adapter should follow the adapter's authentication requirements.

:::warning

**Data Privacy Notice**
Order and customer operations may expose personally identifiable information (PII) including names, email addresses, physical addresses, and payment details. You are responsible for ensuring compliance with applicable data protection regulations. Use least-privilege API scopes, rotate and revoke REST API keys regularly, and follow your organization's data retention and handling policies.

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

- Is provided by the bundled WordPress MCP Adapter package
- Powers MCP tool discovery and execution for WordPress abilities
- Creates the deprecated `/wp-json/woocommerce/mcp` endpoint for WooCommerce compatibility

#### WordPress Abilities System

- Provides a standardized way to register and execute capabilities
- Acts as an abstraction layer between MCP tools and actual operations
- Enables flexible implementation approaches (REST bridging, direct DB operations, etc.)

### Core Components

**MCP Adapter Provider** ([`MCPAdapterProvider.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/MCP/MCPAdapterProvider.php))

- Initializes the bundled MCP adapter when the `mcp_integration` feature flag is enabled
- Creates the deprecated WooCommerce MCP endpoint
- Handles feature flag checking (`mcp_integration`)
- Provides deprecated endpoint exposure filtering

**Abilities Registry** ([`AbilitiesRegistry.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/Abilities/AbilitiesRegistry.php))

- Initializes WooCommerce ability categories and loaders
- Bridges WordPress Abilities API with WooCommerce operations
- Enables ability discovery for MCP servers and other Abilities API consumers

**Purpose-Built Domain Abilities** ([`Domain`](https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/src/Internal/Abilities/Domain))

- Provide WooCommerce product and order abilities backed by domain APIs
- Use shared WordPress MCP adapter metadata (`mcp.public` and `mcp.type`) for MCP exposure
- Keep the ability contract focused on agent-friendly store operations

**REST Bridge Implementation** ([`AbilitiesRestBridge.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/Abilities/AbilitiesRestBridge.php))

- Registers REST-derived compatibility abilities only while handling requests to the deprecated WooCommerce MCP endpoint
- Provides explicit ability definitions with schemas for REST product and order operations
- Marks those abilities with `expose_in_deprecated_woocommerce_mcp` metadata

**WooCommerce Transport** ([`WooCommerceRestTransport.php`](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/MCP/Transport/WooCommerceRestTransport.php))

- Handles WooCommerce API key authentication
- Enforces HTTPS requirements
- Validates permissions based on API key scope

### Implementation Approach

WooCommerce's preferred implementation path is purpose-built domain abilities. These abilities use schemas and response shapes designed for agent workflows instead of automatically projecting every REST-shaped operation into MCP.

REST-derived abilities remain available as a compatibility layer for the deprecated WooCommerce MCP endpoint. This keeps existing clients working while allowing new abilities to use the shared WordPress MCP adapter without expanding the deprecated endpoint by namespace alone.

## Enabling MCP Integration

The MCP feature is controlled by the `mcp_integration` feature flag. You can enable it programmatically:

```php
add_filter( 'woocommerce_features', function( $features ) {
    $features['mcp_integration'] = true;
    return $features;
});
```

Alternatively, you can enable it via WooCommerce CLI:

```bash
wp option update woocommerce_feature_mcp_integration_enabled yes
```

## Authentication and Security

### API Key Requirements

The deprecated WooCommerce MCP endpoint authenticates using WooCommerce REST API keys in the `X-MCP-API-Key` header:

```http
X-MCP-API-Key: ck_your_consumer_key:cs_your_consumer_secret
```

To create API keys:

1. Navigate to **WooCommerce → Settings → Advanced → REST API**
2. Click **Add Key**
3. Set appropriate permissions (`read`, `write`, or `read_write`)
4. Generate and securely store the consumer key and secret

### HTTPS Enforcement

Requests to the deprecated WooCommerce MCP endpoint require HTTPS by default. For local development, you can disable this requirement:

```php
add_filter( 'woocommerce_mcp_allow_insecure_transport', '__return_true' );
```

### Permission Validation

For the deprecated WooCommerce MCP endpoint, the transport layer validates operations against API key permissions:

- `read` permissions: Allow GET requests
- `write` permissions: Allow POST, PUT, PATCH, DELETE requests
- `read_write` permissions: Allow all operations

## Server Endpoint

The deprecated WooCommerce MCP server is available at:

```text
https://yourstore.com/wp-json/woocommerce/mcp
```

## Connecting to the MCP Server

The examples below configure clients to use the deprecated WooCommerce MCP endpoint.

### Proxy Architecture

The current MCP implementation uses a **local proxy approach** to connect MCP clients with WordPress servers:

- **MCP Clients** (like Claude Code) communicate using the MCP protocol over stdio/JSON-RPC
- **Local Proxy** (`@automattic/mcp-wordpress-remote`) runs on your machine and translates MCP protocol messages to HTTP requests
- **WordPress MCP Server** receives HTTP requests and processes them through the WordPress Abilities system

This proxy pattern is commonly used in MCP integrations to bridge protocol differences and handle authentication. The `mcp-wordpress-remote` package acts as a protocol translator, converting the stdio-based MCP communication that clients expect into the HTTP REST API calls that WordPress understands.

**Future Evolution**: While this proxy approach provides a robust foundation, future implementations may explore direct MCP protocol support within WordPress or alternative connection methods as the MCP ecosystem evolves.

### Claude Code Integration

To connect Claude Code to your WooCommerce MCP server:

1. Go to **WooCommerce → Settings → Advanced → REST API**
2. Create a new API key with "Read/Write" permissions
3. Configure MCP with your API key using Claude Code:

```bash
claude mcp add woocommerce_mcp \
  --env WP_API_URL=https://yourstore.com/wp-json/woocommerce/mcp \
  --env CUSTOM_HEADERS='{"X-MCP-API-Key": "YOUR_CONSUMER_KEY:YOUR_CONSUMER_SECRET"}' \
  -- npx -y @automattic/mcp-wordpress-remote@latest
```

### Manual MCP Client Configuration

For other MCP clients, add this configuration to your MCP settings. This configuration tells the MCP client to run the `mcp-wordpress-remote` proxy locally, which will handle the communication with your WordPress server:

```json
{
  "mcpServers": {
    "woocommerce_mcp": {
      "type": "stdio",
      "command": "npx",
      "args": [
        "-y",
        "@automattic/mcp-wordpress-remote@latest"
      ],
      "env": {
        "WP_API_URL": "https://yourstore.com/wp-json/woocommerce/mcp",
        "CUSTOM_HEADERS": "{\"X-MCP-API-Key\": \"YOUR_CONSUMER_KEY:YOUR_CONSUMER_SECRET\"}"
      }
    }
  }
}
```

**Important**: Replace `YOUR_CONSUMER_KEY:YOUR_CONSUMER_SECRET` with your actual WooCommerce API credentials.

**Troubleshooting**: For common setup issues with npx versions or SSL in local environments, see the [mcp-wordpress-remote troubleshooting guide](https://github.com/Automattic/mcp-wordpress-remote/blob/trunk/Docs/troubleshooting.md).

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

### Including Custom Abilities in the Deprecated WooCommerce MCP Server

REST-derived WooCommerce abilities include `expose_in_deprecated_woocommerce_mcp` metadata automatically. Custom abilities are not included by namespace alone; set this metadata to boolean `true` when registering the ability to include it in the deprecated WooCommerce MCP server by default:

```php
'meta' => array(
    'show_in_rest' => true,
    'mcp'          => array(
        'public' => true,
        'type'   => 'tool',
    ),
    'expose_in_deprecated_woocommerce_mcp' => true,
),
```

To override the default metadata decision at runtime, use the `woocommerce_mcp_include_ability` filter:

```php
add_filter( 'woocommerce_mcp_include_ability', function( $include, $ability_id ) {
    if ( str_starts_with( $ability_id, 'your-plugin/' ) ) {
        return true;
    }
    return $include;
}, 10, 2 );
```

## Development Example

For a complete working example, see the [WooCommerce MCP Ability Demo Plugin](https://github.com/woocommerce/wc-mcp-ability). This demonstration plugin shows how third-party developers can:

- Register custom abilities using the WordPress Abilities API
- Define comprehensive input and output schemas
- Implement proper permission handling
- Integrate with MCP through the shared WordPress MCP adapter or the deprecated WooCommerce MCP endpoint

The demo plugin creates a `woocommerce-demo/store-info` ability that retrieves store information and statistics, demonstrating the integration patterns for extending WooCommerce MCP capabilities while using a direct implementation approach rather than REST endpoint bridging.

## Troubleshooting

### Common Issues

## MCP Server Not Available

- Verify the `mcp_integration` feature flag is enabled
- Check that the MCP adapter is properly loaded
- Review WooCommerce logs for initialization errors

## Authentication Failures

- Confirm API key format: `consumer_key:consumer_secret`
- Verify API key permissions match operation requirements
- Ensure HTTPS is used or explicitly allowed for development

## Ability Not Found

- Confirm the ability category is registered during `wp_abilities_api_categories_init`
- Confirm abilities are registered during `wp_abilities_api_init`
- For the deprecated WooCommerce MCP endpoint, check the ability's `expose_in_deprecated_woocommerce_mcp` metadata or override inclusion using the `woocommerce_mcp_include_ability` filter
- Verify ability callbacks are accessible

Check **WooCommerce → Status → Logs** for entries with source `woocommerce-mcp`.

## Important Considerations

- **Developer Preview**: This feature is in preview status and may change
- **Implementation Approach**: WooCommerce uses purpose-built domain abilities and retains REST-derived compatibility abilities for the deprecated WooCommerce MCP endpoint
- **Breaking Changes**: Future updates may introduce breaking changes
- **Production Testing**: Thoroughly test before deploying to production
- **API Stability**: The WordPress Abilities API and MCP adapter are evolving

## Related Resources

- [WordPress Abilities API Repository](https://github.com/WordPress/abilities-api)
- [WordPress MCP Adapter Repository](https://github.com/WordPress/mcp-adapter)
- [WooCommerce MCP Demo Plugin](https://github.com/woocommerce/wc-mcp-ability)
- [Model Context Protocol Specification](https://modelcontextprotocol.io/)
- [WooCommerce REST API Documentation](/docs/apis/rest-api/)

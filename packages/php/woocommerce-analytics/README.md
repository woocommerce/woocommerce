# WooCommerce Analytics

## Overview

Enhanced analytics package for WooCommerce stores with comprehensive frontend tracking for Automattic based projects.

### Key Features

-   **Comprehensive Event Tracking**: 25+ predefined events covering e-commerce, navigation, and user interactions
-   **Session Management**: Intelligent session tracking and user engagement metrics
-   **Proxy Tracking**: Optional proxy-based tracking for enhanced privacy and performance

## Installation & Setup

### WordPress Plugin Integration

```php
// Add to your plugin
use Automattic\Woocommerce_Analytics;

add_action(
 'after_setup_theme',
 function() {
  Woocommerce_Analytics::init();
 }
);

```

#### Initialization

The package only starts tracking when:

-   WooCommerce 3.0 or higher is active and installed
-   Jetpack is connected (Phase 1 requirement — will be decoupled in Phase 2)
-   In site page context (not admin, ajax, xmlrpc, login, feed, cli, etc.)

### Requirements

-   WordPress 5.0+
-   WooCommerce 3.0+
-   PHP 7.4+

### Package Installation

This package lives in the WooCommerce monorepo at `packages/php/woocommerce-analytics/`.
It is included as a Composer dependency of the WooCommerce plugin — no separate installation is needed.

For standalone use outside the monorepo, add to your `composer.json`:

```bash
composer require automattic/woocommerce-analytics
```

## Configuration

### Feature Flags

Enable advanced features using WordPress filters:

#### ClickHouse Tracking

```php
// Enable ClickHouse event tracking
add_filter( 'woocommerce_analytics_clickhouse_enabled', '__return_true' );
```

#### Proxy Tracking (Experimental)

```php
// Enable proxy tracking for enhanced privacy and performance
add_filter( 'woocommerce_analytics_experimental_proxy_tracking_enabled', '__return_true' );
```

## Privacy & Consent Management

### WP Consent API Integration

The package integrates with [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) to ensure GDPR and privacy regulation compliance. Tracking only occurs when users have granted consent for statistics collection.

When WP Consent API is not available, the package defaults to allowing all tracking to maintain backward compatibility.

## Tracked Events

### Session Events

| Event Name           | Trigger                       | ClickHouse | Recording Method | Description                                                   |
| -------------------- | ----------------------------- | ---------- | ---------------- | ------------------------------------------------------------- |
| `session_started`    | Page load                     | ✓          | JS               | When user session begins                                      |
| `session_engagement` | Page load (returning session) | ✓          | JS               | When user visits site in existing session (engagement marker) |

### Navigation Events

| Event Name  | Trigger       | ClickHouse | Recording Method | Description                   |
| ----------- | ------------- | ---------- | ---------------- | ----------------------------- |
| `page_view` | Page load     | ✓          | JS               | General page view tracking    |
| `search`    | Search action | ✓          | PHP → JS Queue   | When site search is performed |

### Store Events

| Event Name                | Trigger             | ClickHouse | Recording Method | Description                                        |
| ------------------------- | ------------------- | ---------- | ---------------- | -------------------------------------------------- |
| `product_view`            | Single product page | ✓          | PHP → JS Queue   | When product page is viewed                        |
| `cart_view`               | Cart page           | ✓          | PHP → JS Queue   | When cart page is viewed                           |
| `add_to_cart`             | Add to cart action  | ✓          | PHP (Immediate)  | When products are added to cart                    |
| `remove_from_cart`        | Remove from cart    | ✓          | PHP (Immediate)  | When products are removed from cart                |
| `checkout_view`           | Checkout page       | ✓          | PHP → JS Queue   | When checkout page is viewed                       |
| `product_checkout`        | Checkout page       | ✓          | PHP → JS Queue   | When checkout page is viewed and cart is not empty |
| `product_purchase`        | Order placed        | ✓          | PHP (Immediate)  | When purchase is completed                         |
| `order_confirmation_view` | Thank you page      | ✓          | PHP → JS Queue   | When order confirmation page is viewed             |
| `post_account_creation`   | Account creation    | -          | PHP → JS Queue   | When new account is created during checkout        |

### Account Events

| Event Name                      | Trigger               | ClickHouse | Recording Method | Description                                    |
| ------------------------------- | --------------------- | ---------- | ---------------- | ---------------------------------------------- |
| `my_account_tab_click`          | Tab click             | -          | JS               | When account navigation log out tab is clicked |
| `my_account_page_view`          | Tab view              | -          | PHP → JS Queue   | When account tabs/pages are viewed             |
| `my_account_order_number_click` | Order number click    | -          | PHP → JS Queue   | When order number link is clicked              |
| `my_account_order_action_click` | Order action click    | -          | PHP → JS Queue   | When order action buttons are clicked          |
| `my_account_address_click`      | Address link click    | -          | PHP → JS Queue   | When address edit links are clicked            |
| `my_account_address_save`       | Address update        | -          | PHP (Immediate)  | When user saves address information            |
| `my_account_payment_add`        | Payment method page   | -          | PHP → JS Queue   | When add payment method page is viewed         |
| `my_account_payment_save`       | Payment method add    | -          | PHP (Immediate)  | When payment method is added                   |
| `my_account_payment_delete`     | Payment method delete | -          | PHP (Immediate)  | When payment method is deleted                 |
| `my_account_details_save`       | Profile update        | -          | PHP (Immediate)  | When account details are saved                 |

### Recording Methods Explained

The **Recording Method** column shows how each event is processed:

-   **`JS`** - Pure client-side events recorded directly by JavaScript without server involvement
-   **`PHP → JS Queue`** - Server-side events that are queued during page generation and passed to frontend for transmission
-   **`PHP (Immediate)`** - Server-side events sent immediately when triggered via WordPress hooks/actions

### Event Properties

Each event includes contextual data such as:

-   **Product Information**: ID, name, price, categories, SKU
-   **Cart Details**: Item quantities, totals, currency
-   **User Data**: Customer ID (when available and permitted)
-   **Session Information**: Session ID, engagement metrics
-   **Page Context**: URL, referrer, breadcrumbs
-   **Store Data**: Order ID, payment method, shipping info

## Architecture

### PHP Backend

-   **`Woocommerce_Analytics`** - Main initialization class
-   **`Universal`** - Core tracking logic and WooCommerce hooks
-   **`My_Account`** - Account-specific event tracking
-   **`WC_Analytics_Tracking`** - Event queuing and processing
-   **`Features`** - Feature flag management

### Frontend

-   **`Analytics`** - Main client-side analytics class
-   **`SessionManager`** - Session tracking and management
-   **`ApiClient`** - REST API communication for proxy tracking
-   **Event Listeners** - Page-specific event handlers

### Data Flow

**PHP (Immediate)** events:

1. WooCommerce action/hook triggers PHP method
2. `WC_Analytics_Tracking::record_event()` called directly
3. Event sent immediately to Tracks and/or ClickHouse

**PHP → JS Queue** events:

1. WooCommerce action/hook triggers PHP method
2. Event queued via `enqueue_event()` method
3. Queue injected into page via `window.wcAnalytics.eventQueue`
4. Frontend JavaScript processes queue and sends to `_wca` tracking pixel if proxy tracking is disabled, otherwise sent to Proxy API endpoint

**JS** events:

1. Frontend JavaScript directly records events via `recordEvent()`
2. Events sent to `_wca` tracking pixel if proxy tracking is disabled, otherwise sent to Proxy API endpoint

### User Identification

The package uses a hierarchical approach to identify users for analytics:

**Anonymous User Identification (in order of preference):**

1. **`tk_ai` Cookie**: Primary method using Tracks anonymous identifier cookie
2. **IP-based ID** (when proxy tracking enabled): Generates visitor ID from:
    - Daily rotating salt (privacy-focused, changes daily)
    - Domain name
    - User IP address
    - User agent string
    - Creates SHA256 hash (16-char substring) for anonymous but consistent identification
3. If no `tk_ai` cookie and proxy tracking disabled: `null` (no visitor tracking)

**Session Management:**

-   Session cookies (`woocommerceanalytics_session`) expire after 30 minutes or at midnight UTC (whichever comes first)
-   Used for tracking user journey within a session (separate from user identification)
-   Contains session ID, landing page, and engagement status

## Development

This package is part of the [WooCommerce monorepo](https://github.com/woocommerce/woocommerce).

```bash
# From the monorepo root
pnpm --filter=@automattic/woocommerce-analytics run build
pnpm --filter=@automattic/woocommerce-analytics run typecheck

# Or from within packages/php/woocommerce-analytics/
pnpm run build
pnpm run watch
```

See [package.json](./package.json) for all available commands.

## Advanced Topics

### Proxy Tracking

When proxy tracking is enabled:

-   Events are sent to a local WordPress REST API endpoint to improve event delivery reliability
-   Server-side event validation and processing

#### API Endpoint

-   **URL**: `/wp-json/woocommerce-analytics/v1/track`
-   **Method**: POST
-   **Permission**: No authentication required
-   **Content-Type**: `application/json`

#### Performance Optimizations

**Client-Side Batching Logic:**

-   Events queued until batch size (10 events) or debounce delay (1s) reached
-   Immediate flush on page unload (`beforeunload`, `pagehide` events)
-   Failed events automatically re-queued for retry

**Server-Side Optimizations:**

The package includes an optional MU plugin (`woocommerce-analytics-proxy-speed-module.php`) that significantly improves proxy performance by only loading WooCommerce, WooCommerce Analytics, and Jetpack for proxy requests

### Debugging

Enable debug mode for detailed logging:

```javascript
// In browser console or add to page
localStorage.setItem( 'debug', 'wc-analytics:*' );
```

## Security

Need to report a security vulnerability? Go to [https://automattic.com/security/](https://automattic.com/security/) or directly to our security bug bounty site [https://hackerone.com/automattic](https://hackerone.com/automattic).

## License

woocommerce-analytics is licensed under [GNU General Public License v2 (or later)](./LICENSE.txt)

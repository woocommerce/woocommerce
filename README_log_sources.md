# WooCommerce Log Sources Function

This document describes the modified `get_woocommerce_log_sources()` function that intelligently detects the logging system in use and retrieves the appropriate list of log sources.

## Overview

The function automatically detects which WooCommerce logging system is enabled and retrieves the list of available log sources accordingly. It returns `null` if an unknown logging handler is detected, allowing you to handle errors appropriately.

## Function Signature

```php
function get_woocommerce_log_sources(): array|null
```

## Return Values

- **`array`**: List of unique log sources when successful
- **`null`**: When an unknown logging handler is detected or an error occurs
- **`array()`**: Empty array when logging is disabled or no sources exist

## Supported Logging Systems

### 1. File System V2 (Default)
- **Handler**: `LogHandlerFileV2`
- **Description**: New file-based logging system
- **Source Retrieval**: Uses `FileController::get_file_sources()`

### 2. File System Legacy
- **Handler**: `WC_Log_Handler_File`
- **Description**: Legacy file-based logging system
- **Source Retrieval**: Scans log directory for `.log` files and extracts source names

### 3. Database
- **Handler**: `WC_Log_Handler_DB`
- **Description**: Database logging system
- **Source Retrieval**: Queries `wp_woocommerce_log` table for distinct sources

### 4. Email
- **Handler**: `WC_Log_Handler_Email`
- **Description**: Email logging system
- **Source Retrieval**: Returns empty array (no persistent sources)

## Usage Examples

### Basic Usage

```php
$sources = get_woocommerce_log_sources();

if ( $sources === null ) {
    echo "Error: Unknown logging handler or system error";
} elseif ( empty( $sources ) ) {
    echo "No log sources found or logging is disabled";
} else {
    echo "Found " . count( $sources ) . " log sources:";
    foreach ( $sources as $source ) {
        echo "- " . $source;
    }
}
```

### Check Specific Source

```php
if ( woocommerce_log_source_exists( 'plugin-woocommerce' ) ) {
    echo "WooCommerce core logging is active";
}
```

### Get Source Count

```php
$count = get_woocommerce_log_sources_count();
if ( $count !== null ) {
    echo "Total log sources: " . $count;
}
```

### Comprehensive Logging Info

```php
$info = get_woocommerce_logging_info();

echo "Logging Enabled: " . ( $info['logging_enabled'] ? 'Yes' : 'No' );
echo "Handler: " . $info['handler'];
echo "Type: " . $info['handler_type'];

if ( $info['error'] ) {
    echo "Error: " . $info['error'];
} else {
    echo "Sources: " . implode( ', ', $info['sources'] );
}
```

## Helper Functions

### `get_woocommerce_log_sources_simple()`
Returns an array (never null) for backward compatibility:
```php
$sources = get_woocommerce_log_sources_simple(); // Always returns array
```

### `woocommerce_log_source_exists( $source )`
Checks if a specific source exists:
```php
if ( woocommerce_log_source_exists( 'paypal' ) ) {
    // PayPal logging is active
}
```

### `get_woocommerce_log_sources_count()`
Returns the count of sources or null on error:
```php
$count = get_woocommerce_log_sources_count();
```

### `get_woocommerce_logging_info()`
Returns comprehensive logging information:
```php
$info = get_woocommerce_logging_info();
// Returns array with: logging_enabled, handler, handler_type, sources, error
```

## Error Handling

The function handles various error conditions:

1. **WooCommerce not available**: Returns `null`
2. **Unknown logging handler**: Returns `null`
3. **System errors**: Returns `null`
4. **Logging disabled**: Returns empty array `[]`
5. **No sources found**: Returns empty array `[]`

## Common Log Sources

Based on WooCommerce core, common log sources include:

- `plugin-woocommerce` - WooCommerce core
- `paypal` - PayPal payment gateway
- `maxmind-geolocation` - MaxMind geolocation service
- `geoip` - GeoIP functionality
- `wc-updater` - WooCommerce updater
- `woocommerce-scheduled-actions` - Scheduled actions
- `emogrifier` - Email styling
- `webhooks-delivery` - Webhook deliveries

## Configuration

Logging configuration can be set via:

1. **Constants in wp-config.php** (highest priority):
   ```php
   define( 'WC_LOG_HANDLER', 'WC_Log_Handler_DB' );
   define( 'WC_LOG_THRESHOLD', 'error' );
   ```

2. **Admin Settings**: WooCommerce > Status > Logs > Settings

3. **Default Values**: File system V2 logging

## Performance Considerations

- Database sources are cached in WordPress options
- File system sources are retrieved on-demand
- The function includes proper error handling to prevent performance issues

## Testing

You can test the function using the provided example file:

```bash
php example_usage.php
```

Or via WP-CLI if the function is loaded in a WordPress context:

```bash
wp eval "require_once 'get_woocommerce_log_sources.php'; var_dump(get_woocommerce_log_sources());"
```
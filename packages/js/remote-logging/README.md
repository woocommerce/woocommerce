# WooCommerce Remote Logging

A remote logging package for Automattic based projects. This package provides error tracking and logging capabilities, with support for rate limiting, stack trace formatting, and customizable error filtering.

## Installation

```bash
npm install @woocommerce/remote-logging --save
```

## Description

The WooCommerce Remote Logging package offers the following features:

- Remote error logging with stack trace analysis
- Customizable log severity levels
- Rate limiting to prevent API flooding
- Automatic capture of unhandled errors and promise rejections
- Filtering of errors based on WooCommerce asset paths
- Extensibility through WordPress filters

## Usage

1. Initialize the remote logger at the start of your application. If your plugin depends on WooCommerce plugin, the logger will be initialized in WooCommerce, so you don't need to call this function.

    ```js
    import { init } from '@woocommerce/remote-logging';

    init({
      errorRateLimitMs: 60000 // Set rate limit to 1 minute
    });
    ```

2. Log messages or errors:

    ```js
    import { log, captureException } from '@woocommerce/remote-logging';

    // Log an informational message
    log('info', 'User completed checkout', { extra: { orderId: '12345' } });

    // Log a warning
    log('warning', 'API request failed, retrying...', { extra: { attempts: 3 } });

    // Log an error
    try {
      // Some operation that might throw
    } catch (error) {
      captureException(error, { extra: { orderId: '12345' } });
    }
    ```

## Remote Logging Conditions

Remote logging is subject to the following conditions:

1. **Remote Logging Enabled**: The package checks `window.wcSettings.isRemoteLoggingEnabled` to determine if the feature should be enabled. The value is set via PHP and passed to JS as a boolean. It requires tracks to be enabled and a few other conditions internally. Please see the [RemoteLogger.php](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/src/Internal/Logging/RemoteLogger.php) for more details.

2. **Non-Development Environment**: It also checks `process.env.NODE_ENV` to ensure logging only occurs in non-development environments.

If either of these conditions are not met (Tracks is not enabled or the environment is development), no logs will be transmitted to the remote server.

## API Reference

- `init(config: RemoteLoggerConfig): void`: Initializes the remote logger with the given configuration.
- `log(severity: LogSeverity, message: string, extraData?: object): Promise<boolean>`: Logs a message with the specified severity and optional extra data.
- `captureException(error: Error, extraData?: object): void`: Captures an error and sends it to the remote API.

For more detailed information about types and interfaces, refer to the source code and inline documentation.

## Customization


You can customize the behavior of the remote logger using WordPress filters. Here are the available filters:

### `woocommerce_remote_logging_should_send_error`

Control whether an error should be sent to the remote API.

**Parameters:**

- `shouldSend` (boolean): The default decision on whether to send the error.
- `error` (Error): The error object.
- `stackFrames` (Array): An array of stack frames from the error.

**Return value:** (boolean) Whether the error should be sent.

**Usage example:**

```js
import { addFilter } from '@wordpress/hooks';

addFilter(
  'woocommerce_remote_logging_should_send_error',
  'my-plugin',
  (shouldSend, error, stackFrames) => {
    const containsPluginFrame = stackFrames.some(
      (frame) => frame.url && frame.url.includes( /YOUR_PLUGIN_ASSET_PATH/ )
    );
    // Only send errors that originate from our plugin
    return shouldSend && containsPluginFrame;
  }
);
```

### `woocommerce_remote_logging_error_data`

Modify the error data before sending it to the remote API.

**Parameters:**

- `errorData` (ErrorData): The error data object to be sent.

**Return value:** (ErrorData) The modified error data object.

**Usage example:**

```js
import { addFilter } from '@wordpress/hooks';

addFilter(
  'woocommerce_remote_logging_error_data',
  'my-plugin',
  (errorData) => {
    // Custom logic to modify error data
    errorData.tags = [ ...errorData.tags, 'my-plugin' ];
    return errorData;
  }
);
```

### `woocommerce_remote_logging_log_endpoint`

Modify the URL of the remote logging API endpoint.

**Parameters:**

- `endpoint` (string): The default endpoint URL.

**Return value:** (string) The modified endpoint URL.

**Usage example:**

```js
import { addFilter } from '@wordpress/hooks';

addFilter(
  'woocommerce_remote_logging_log_endpoint',
  'my-plugin',
  (endpoint) => 'https://my-custom-endpoint.com/log'
);
```

### `woocommerce_remote_logging_js_error_endpoint`

Modify the URL of the remote logging API endpoint for JavaScript errors.

**Parameters:**

- `endpoint` (string): The default endpoint URL for JavaScript errors.

**Return value:** (string) The modified endpoint URL for JavaScript errors.

**Usage example:**

```js
import { addFilter } from '@wordpress/hooks';

addFilter(
  'woocommerce_remote_logging_js_error_endpoint',
  'my-plugin',
  (endpoint) => 'https://my-custom-endpoint.com/js-error-log'
);
```

### `woocommerce_remote_logging_request_uri_whitelist`

Modifies the list of whitelisted query parameters that won't be masked in the logged request URI

**Parameters:**

- `whitelist` (string[]): The default whitelist.

**Return value:** (string[]) The modified whitelist.

**Usage example:**

```js
import { addFilter } from '@wordpress/hooks';

addFilter(
  'woocommerce_remote_logging_request_uri_whitelist',
  'my-plugin',
  ( whitelist ) => {
    return [ ...whitelist, 'exampleParam' ]
  }
);
```

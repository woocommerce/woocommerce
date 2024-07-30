# WooCommerce Remote Logging

A remote logging package for Automattic based projects. This package provides error tracking and logging capabilities, with support for rate limiting, stack trace formatting, and customizable error filtering.

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

## Customization

You can customize the behavior of the remote logger using WordPress filters:

- `woocommerce_remote_logging_should_send_error`: Control whether an error should be sent to the remote API.
- `woocommerce_remote_logging_error_data`: Modify the error data before sending it to the remote API.
- `woocommerce_remote_logging_log_endpoint`: Customize the endpoint URL for sending log messages.
- `woocommerce_remote_logging_js_error_endpoint`: Customize the endpoint URL for sending JavaScript errors.

### Example

```js
import { addFilter } from '@wordpress/hooks';

addFilter(
  'woocommerce_remote_logging_should_send_error',
  'my-plugin',
  (shouldSend, error, stackFrames) => {
		const containsPluginFrame = stackFrames.some(
			( frame ) =>
				frame.url && frame.url.includes( '/my-plugin/' );
		);
    // Custom logic to determine if the error should be sent
    return shouldSend && containsPluginFrame;
  }
);
```

### API Reference

- `init(config: RemoteLoggerConfig): void`: Initializes the remote logger with the given configuration.
- `log(severity: LogSeverity, message: string, extraData?: object): Promise<void>`: Logs a message with the specified severity and optional extra data.
- `captureException(error: Error, extraData?: object): void`: Captures an error and sends it to the remote API.

For more detailed information about types and interfaces, refer to the source code and inline documentation.

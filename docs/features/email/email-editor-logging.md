---
post_title: Email editor logging
sidebar_label: Email editor logging
---

# Email Editor Logging

Email editor logging uses a severity threshold to reduce noise during normal operation. By default, only warnings and above are logged. To change the logging level, use the `woocommerce_email_editor_logging_threshold` filter:

```php
add_filter( 'woocommerce_email_editor_logging_threshold', function() {
    return WC_Log_Levels::DEBUG;
} );
```

This will enable logging for all email editor operations, such as email editor initialization, personalization tag registration, etc.

For available log levels and their priority, see the [Logging in WooCommerce](/docs/best-practices/data-management/logging/#level) documentation.

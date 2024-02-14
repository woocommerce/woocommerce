---
post_title: Logging in WooCommerce
---

WooCommerce has its own robust system for logging, which can be used for debugging during development, catching errors on production, or even sending notifications when specific events occur. By default, WooCommerce uses this logger to record errors, warnings, and other notices that may be useful for troubleshooting problems with a store. Many extensions for WooCommerce also make use of the logger for similar purposes.

## Viewing logs

Depending on the log handler(s) used, you can view the entries created by the logger by going to **WooCommerce > Status > Logs**.

![Log file viewer](https://developer.woo.com/wp-content/uploads/2023/12/log-critical.jpg)

## Log levels

Logs have eight different severity levels:

* `emergency`
* `alert`
* `critical`
* `error`
* `warning`
* `notice`
* `info`
* `debug`

Aside from giving a site owner context as to how important a log entry is, these levels also allow logs to be filtered by the handler. If you only want log entries to be recorded for `error` severity and higher, you can set the threshold in the `WC_LOG_THRESHOLD` constant by adding something like this to your `wp-config.php` file:

```php
define( 'WC_LOG_THRESHOLD', 'error' );
```

Note that this threshold will apply to all logs, regardless of which log handler is in use. The `WC_Log_Handler_Email` class, for example, has its own threshold setting, but it is secondary to the global threshold.

## Log handlers

In WooCommerce, a log handler is a PHP class that takes the raw log data and transforms it into a log entry that can be stored or dispatched. WooCommerce ships with three different log handler classes:

* `WC_Log_Handler_File`: The default handler. Records log entries to files. The files are stored in `wp-content/uploads/wc-logs`, but this can be changed by defining the `WC_LOG_DIR` constant in your `wp-config.php` file with a custom path. Log files can be up to 5 MB in size, after which the log file will rotate.
* `WC_Log_Handler_DB`: Records log entries to the database. Entries are stored in the `{$wpdb->prefix}woocommerce_log` table.
* `WC_Log_Handler_Email`: Sends log entries as email messages. Emails are sent to the site admin email address. This handler has [some limitations](https://github.com/woocommerce/woocommerce/blob/fe81a4cf27601473ad5c394a4f0124c785aaa4e6/plugins/woocommerce/includes/log-handlers/class-wc-log-handler-email.php#L15-L27).

### Changing or adding handlers

To switch from the default file log handler to the database handler, you can add an entry like this to your `wp-config.php` file:

```php
define( 'WC_LOG_HANDLER', 'WC_Log_Handler_DB' );
```

In some cases, you may want to have more than one log handler, and/or you might want to modify the settings of a handler. For example, you may want to have most logs saved to files, but log entries that are classified as emergency or critical errors also sent to an email address. For this, you can use the `woocommerce_register_log_handlers` filter hook to create an array of log handler class instances that you want to use. Some handler class constructors have optional parameters that you can use when instantiating the class to change their default behavior.

Example:

```php
function my_wc_log_handlers( $handlers ) {
	$size_limit = 10 * 1024 * 1024; // Make the file size limit 10 MB instead of 5.
	$handlers[] = new WC_Log_Handler_File( $size_limit );
	
	$recipients = array( 'wayne@example.com', 'garth@example.com' ); // Send logs to multiple recipients.
	$threshold  = 'critical'; // Only send emails for logs of this level and higher.
	$handlers[] = new WC_Log_Handler_Email( $recipients, $threshold );
	
	return $handlers;
}
add_filter( 'woocommerce_register_log_handlers', 'my_wc_log_handlers' );
```

### Creating a custom handler

You may want to create your own log handler class in order to send logs somewhere else, such as a Slack channel or perhaps an InfluxDB instance. Your class must extend the [`WC_Log_Handler`](https://woocommerce.github.io/code-reference/classes/WC-Log-Handler.html) abstract class and implement the [`WC_Log_Handler_Interface`](https://woocommerce.github.io/code-reference/classes/WC-Log-Handler-Interface.html) interface. The [`WC_Log_Handler_Email`](https://github.com/woocommerce/woocommerce/blob/6688c60fe47ad42d49deedab8be971288e4786c1/plugins/woocommerce/includes/log-handlers/class-wc-log-handler-email.php) handler class provides a good example of how to set it up.

## Adding logs

Logs are added via methods in the `WC_Logger` class. The class instance is accessed by using the `wc_get_logger()` function. The basic method for adding a log entry is [`WC_Logger::log( $level, $message, $context )`](https://woocommerce.github.io/code-reference/classes/WC-Logger.html#method_log). There are also shortcut methods for each log severity level, for example `WC_Logger::warning( $message, $context )`. Here is [an example](https://github.com/woocommerce/woocommerce/blob/6688c60fe47ad42d49deedab8be971288e4786c1/plugins/woocommerce/src/Admin/RemoteInboxNotifications/OptionRuleProcessor.php#L53-L64) from the codebase:

```php
$logger = wc_get_logger();
$logger->warning(
	sprintf(
		'ComparisonOperation "%s" option value "%s" is not an array, defaulting to empty array.',
		$rule->operation,
		$rule->option_name
	),
	array(
		'option_value' => $option_value,
		'rule'         => $rule,
	)
);
```

## Log sources

Each log entry can include a `source` value, which is intended to provide context about where in the codebase the log was generated, and can be used to filter log entries. A source value can be added to a log by including it in the `context` parameter like so:

```php
$logger->info( 'Time for lunch', array( 'source' => 'your_stomach' ) );
```

Each log handler uses the source information a bit differently.

* `WC_Log_Handler_File`: The source becomes the prefix of the log filename. Thus, log entries with different sources will be stored in different log files. If no source value is given, the handler defaults to `log` as the source.
* `WC_Log_Handler_DB`: The source value is stored in the `source` column in the log database table. When viewing the list table of logs, you can choose a source value from a dropdown as a filter, and only view logs with that source. If no source value is given, the handler uses a stacktrace to determine the name of the file where the log was triggered, and that filename becomes the source.
* `WC_Log_Handler_Email`: This log handler does not use source information.

## Clearing old logs

When WooCommerce is first installed, it sets up a scheduled event to delete logs older than 30 days that runs daily. You can change the log retention period using the `woocommerce_logger_days_to_retain_logs` filter hook:

```php
add_filter( 'woocommerce_logger_days_to_retain_logs', function() { return 90; } );
```

## Turning off noisy logs

If there is a particular log that is recurring frequently and clogging up your log files, you should probably figure out why it keeps getting triggered and resolve the issue. However, if that's not possible, you can add a filter to ignore that particular log while still allowing other logs to get through:

```php
function my_ignored_logs( $message, $level, $context, $handler ) {
	if ( false !== strpos( $message, 'Look, a squirrel!' ) ) {
		return null;
	}
	
	return $message;
}
add_filter( 'woocommerce_logger_log_message', 'my_ignored_logs', 10, 4 );
```

## Debugging with the logger

Sometimes during debugging you need to find out if the runtime reaches a certain line in the code, or you need to see what value a variable contains, and it's not possible to directly observe what's happening with a `var_dump` call or a breakpoint. In these cases you can log the information you need with a one-liner like this:

```php
wc_get_logger()->debug( 'Made it to the conditional!', array( 'source', 'debug-20230825' ) );
```

On the occasion where you need to know what a non-scalar variable (array, object) contains, you may be tempted to put it in the `$context` parameter alongside your `source` value. However, only the database log handler even stores the contents of `$context`, and none of the handlers display it anywhere. Instead, consider outputting it in the `$message` parameter using something like [`wc_print_r`](https://woocommerce.github.io/code-reference/namespaces/default.html#function_wc_print_r):

```php
wc_get_logger()->debug(
	wc_print_r( $my_special_array ),
	array( 'source', 'debug-20230825' )
);
```

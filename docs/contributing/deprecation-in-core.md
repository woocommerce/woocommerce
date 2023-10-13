# Depreciation in core

Deprecation is a method of discouraging usage of a feature, or practice, in favour of something else without breaking backwards compatibility or totally prohibiting it’s usage. To quote the Wikipedia article on Deprecation:

> While a deprecated software feature remains in the software, its use may raise warning messages recommending alternative practices; deprecated status may also indicate the feature will be removed in the future. Features are deprecated rather than immediately removed, to provide backward compatibility and give programmers time to bring affected code into compliance with the new standard.

There are various reasons why a function, method, or feature may be deprecated. To name a few:

- We may want to remove a function we do not use any longer.
- We may decide to change or improve how a function works, but due to breaking backwards compatibility we need to deprecate the old function instead.
- We may need to make naming conventions standardised.
- We may find opportunities to merge similar functions to avoid reusing the same code in difference places.

Whilst notices are not ideal or attractive, they are just warnings. _Store owners; Deprecation warnings do not mean your store is broken, it just serves as a reminder that code will need to be updated._

## How do we deprecate functions?

When we deprecate something in WooCommerce, we take a few actions to make it clear to developers and to maintain backwards compatibility.

1. We add a docblock to the function/method showing what version the function was deprecated. e.g. `@deprecated 2.x.x`
2. We add a warning notice using our own `wc_deprecated_function` function which shows what version, what function, and what replacement is available. More on that in a bit.
3. We remove usage of the deprecated function throughout of codebase.

The function or method itself is not removed from the codebase. This preserves backwards compatibility until removed (usually over a year or several major releases into the future!).

I mentioned `wc_deprecated_function` above – this is our own wrapper for the `_deprecated_function` WordPress function. It works very similar except for that it forces a log entry instead of display regardless of `WP_DEBUG` mode during AJAX events so that AJAX requests are not broken as a result of the notice.

## What happens when a deprecated function is called?

If an extension or theme uses a deprecated function, you may see a warning like the following example:

``` php
Notice: woocommerce_show_messages is deprecated since version 2.1! Use wc_print_notices instead. in /srv/www/wordpress-default/wp-includes/functions.php on line 3783
```

This tells you what is deprecated, when, where, and what replacement is available.

Notices and warnings are usually shown inline but there are some plugins you can use to collect them and shown nicely in the footer of your site. Personally, I Use Query Monitor.

### Warnings in production (store owners read this!)

Showing PHP notices and warnings (or any error for that matter) is highly discouraged on your production stores. They can reveal information about your setup which a malicious user could use. Make sure they are hidden from public view, and optionally logged instead.

In WordPress you can do this by adding or modifying some constants in your wp-config.php file.

``` php
define( 'WP_DEBUG', false );
```

On some hosts, errors may still be visible due to the hosts config. To force them to not display you can add this to your wp-config.php file too:

``` php
@ini_set( 'display_errors', 0 );
```

To log notices instead of displaying them, use:

``` php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

The default location of the WordPress error log is `wp-content/debug.log`

Note that this log can be publicly accessible. To keep it private, you can use a plugin or define it in `wp-config.php`.

``` php
<?php
/**
 * Plugin Name: Custom Debug Log Path
 */

ini_set( 'error_log', '/path/to/site/logs/debug.log' );
```

_From https://woocommerce.wordpress.com/2017/01/17/deprecation-in-woocommerce-core/_
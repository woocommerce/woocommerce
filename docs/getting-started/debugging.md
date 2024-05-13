---
post_title: Resources for debugging
menu_title: Debugging
---

## WordPress

A good place to start is usually the debugging tools built into WordPress itself:

* [Debugging in WordPress](https://wordpress.org/documentation/article/debugging-in-wordpress/)

## Logging

WooCommerce has a logging system that can be very helpful for finding and tracking errors on your site:

* [Logging in WooCommerce](../extension-development/logging.md)

## Xdebug

If you're using `wp-env` to run a local development environment (this is the recommended method for the WooCommerce monorepo), you can activate Xdebug and then use an IDE like VS Code or PhpStorm to set breakpoints and step through the code as it executes:

* [Using Xdebug](https://github.com/WordPress/gutenberg/tree/trunk/packages/env#using-xdebug)

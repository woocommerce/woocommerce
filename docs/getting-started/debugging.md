---
post_title: Resources for debugging
sidebar_label: Debugging
sidebar_position: 7
---

# Resources for debugging

## WordPress

A good place to start is usually the debugging tools built into WordPress itself:

The documentation for [Debugging in WordPress c](https://wordpress.org/documentation/article/debugging-in-wordpress/)overs a number of environment variables you can set to enable WordPress’s core debugging tools.

### Query Monitor

A popular plugin for WordPress developers is [Query Monitor](https://wordpress.org/plugins/query-monitor/), the developer tools for WordPress. Query Monitor enables debugging of database queries, PHP errors, AJAX/Rest requests, hooks and actions, block editor blocks, enqueued scripts and stylesheets, HTTP API calls, and more.

### Email Debugging

When working in a local WooCommerce environment, we recommend enabling tools like Mailpit or Mailhog to redirect all transactional emails to a log rather then being sent.

Alternatively, you can use a plugin like Stop Emails to disable any accidental email triggering.

## WooCommerce

### Logging

WooCommerce has a logging system that can be very helpful for finding and tracking errors on your site:

* [Logging in WooCommerce](/docs/best-practices/data-management/logging)

### Coming Soon Mode

WooCommerce's coming soon mode allows you to temporarily make your site invisible to the public while you work on it.

* [Integrating with coming soon mode](/docs/extensions/extension-onboarding/integrating-coming-soon-mode)

## PHP/JavaScript Debugging

### Xdebug

If you're using `wp-env` to run a local development environment (this is the recommended method for the WooCommerce monorepo), you can activate Xdebug and then use an IDE like VS Code or PhpStorm to set breakpoints and step through the code as it executes:

* [Using Xdebug](https://github.com/WordPress/gutenberg/tree/trunk/packages/env#using-xdebug)

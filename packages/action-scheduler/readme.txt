=== Action Scheduler ===
Contributors: Automattic, wpmuguru, claudiosanches, peterfabian1000, vedjain, jamosova, obliviousharmony, konamiman, sadowski, royho, barryhughes-1
Tags: scheduler, cron
Requires at least: 5.2
Tested up to: 5.7
Stable tag: 3.3.0
License: GPLv3
Requires PHP: 5.6

Action Scheduler - Job Queue for WordPress

== Description ==

Action Scheduler is a scalable, traceable job queue for background processing large sets of actions in WordPress. It's specially designed to be distributed in WordPress plugins.

Action Scheduler works by triggering an action hook to run at some time in the future. Each hook can be scheduled with unique data, to allow callbacks to perform operations on that data. The hook can also be scheduled to run on one or more occassions.

Think of it like an extension to `do_action()` which adds the ability to delay and repeat a hook.

## Battle-Tested Background Processing

Every month, Action Scheduler processes millions of payments for [Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/), webhooks for [WooCommerce](https://wordpress.org/plugins/woocommerce/), as well as emails and other events for a range of other plugins.

It's been seen on live sites processing queues in excess of 50,000 jobs and doing resource intensive operations, like processing payments and creating orders, at a sustained rate of over 10,000 / hour without negatively impacting normal site operations.

This is all on infrastructure and WordPress sites outside the control of the plugin author.

If your plugin needs background processing, especially of large sets of tasks, Action Scheduler can help.

## Learn More

To learn more about how to Action Scheduler works, and how to use it in your plugin, check out the docs on [ActionScheduler.org](https://actionscheduler.org).

There you will find:

* [Usage guide](https://actionscheduler.org/usage/): instructions on installing and using Action Scheduler
* [WP CLI guide](https://actionscheduler.org/wp-cli/): instructions on running Action Scheduler at scale via WP CLI
* [API Reference](https://actionscheduler.org/api/): complete reference guide for all API functions
* [Administration Guide](https://actionscheduler.org/admin/): guide to managing scheduled actions via the administration screen
* [Guide to Background Processing at Scale](https://actionscheduler.org/perf/): instructions for running Action Scheduler at scale via the default WP Cron queue runner

## Credits

Action Scheduler is developed and maintained by [Automattic](http://automattic.com/) with significant early development completed by [Flightless](https://flightless.us/).

Collaboration is cool. We'd love to work with you to improve Action Scheduler. [Pull Requests](https://github.com/woocommerce/action-scheduler/pulls) welcome.

== Changelog ==

= 3.3.0 - 2021-09-15 =
* Enhancement - Adds as_has_scheduled_action() to provide a performant way to test for existing actions. #645
* Fix - Improves compatibility with environments where NO_ZERO_DATE is enabled. #519
* Fix - Adds safety checks to guard against errors when our database tables cannot be created. #645
* Dev - Now supports queries that use multiple statuses. #649
* Dev - Minimum requirements for WordPress and PHP bumped (to 5.2 and 5.6 respectively). #723

= 3.2.1 - 2021-06-21 =
* Fix - Add extra safety/account for different versions of AS and different loading patterns. #714
* Fix - Handle hidden columns (Tools → Scheduled Actions) | #600.

= 3.2.0 - 2021-06-03 =
* Fix - Add "no ordering" option to as_next_scheduled_action().
* Fix - Add secondary scheduled date checks when claiming actions (DBStore) | #634.
* Fix - Add secondary scheduled date checks when claiming actions (wpPostStore) | #634.
* Fix - Adds a new index to the action table, reducing the potential for deadlocks (props: @glagonikas).
* Fix - Fix unit tests infrastructure and adapt tests to PHP 8.
* Fix - Identify in-use data store.
* Fix - Improve test_migration_is_scheduled.
* Fix - PHP notice on list table.
* Fix - Speed up clean up and batch selects.
* Fix - Update pending dependencies.
* Fix - [PHP 8.0] Only pass action arg values through to do_action_ref_array().
* Fix - [PHP 8] Set the PHP version to 7.1 in composer.json for PHP 8 compatibility.
* Fix - add is_initialized() to docs.
* Fix - fix file permissions.
* Fix - fixes #664 by replacing __ with esc_html__.

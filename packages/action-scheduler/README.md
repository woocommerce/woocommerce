# Action Scheduler - Job Queue for WordPress [![Build Status](https://travis-ci.org/woocommerce/action-scheduler.png?branch=master)](https://travis-ci.org/woocommerce/action-scheduler) [![codecov](https://codecov.io/gh/woocommerce/action-scheduler/branch/master/graph/badge.svg)](https://codecov.io/gh/woocommerce/action-scheduler)

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

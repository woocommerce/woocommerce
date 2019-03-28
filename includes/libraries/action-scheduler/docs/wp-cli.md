---
description: Learn how to do WordPress background processing at scale with WP CLI and the Action Scheduler job queue.
---
# WP CLI

Action Scheduler has custom [WP CLI](http://wp-cli.org) commands available for processing actions.

For large sites, WP CLI is a much better choice for running queues of actions than the default WP Cron runner. These are some common cases where WP CLI is a better option:

* long-running tasks - Tasks that take a significant amount of time to run
* large queues - A large number of tasks will naturally take a longer time
* other plugins with extensive WP Cron usage - WP Cron's limited resources are spread across more tasks

With a regular web request, you may have to deal with script timeouts enforced by hosts, or other restraints that make it more challenging to run Action Scheduler tasks. Utilizing WP CLI to run commands directly on the server give you more freedom. This means that you typically don't have the same constraints of a normal web request.

If you choose to utilize WP CLI exclusively, you can disable the normal WP CLI queue runner by installing the [Action Scheduler - Disable Default Queue Runner](https://github.com/Prospress/action-scheduler-disable-default-runner) plugin. Note that if you do this, you **must** run Action Scheduler via WP CLI or another method, otherwise no scheduled actions will be processed.
 
## Commands

These are the commands available to use with Action Scheduler:

* `action-scheduler run`
    
    Options:
    * `--batch-size` - This is the number of actions to run in a single batch. The default is `100`.
    * `--batches` - This is the number of batches to run. Using 0 means that batches will continue running until there are no more actions to run.
    * `--hooks` - Process only actions with specific hook or hooks, like `'woocommerce_scheduled_subscription_payment'`. By default, actions with any hook will be processed. Define multiple hooks as a comma separated string (without spaces), e.g. `--hooks=woocommerce_scheduled_subscription_trial_end,woocommerce_scheduled_subscription_payment,woocommerce_scheduled_subscription_expiration`
    * `--group` - Process only actions in a specific group, like `'woocommerce-memberships'`. By default, actions in any group (or no group) will be processed.
    * `--force` - By default, Action Scheduler limits the number of concurrent batches that can be run at once to ensure the server does not get overwhelmed. Using the `--force` flag overrides this behavior to force the WP CLI queue to run.

The best way to get a full list of commands and their available options is to use WP CLI itself. This can be done by running `wp action-scheduler` to list all Action Scheduler commands, or by including the `--help` flag with any of the individual commands. This will provide all relevant parameters and flags for the command.

## Cautionary Note on Action Dependencies when using `--group` or `--hooks` Options

The `--group` and `--hooks` options should be used with caution if you have an implicit dependency between scheduled actions based on their schedule.

For example, consider two scheduled actions for the same subscription:

* `scheduled_payment` scheduled for `2015-11-13 00:00:00` and
* `scheduled_expiration` scheduled for `2015-11-13 00:01:00`.

Under normal conditions, Action Scheduler will ensure the `scheduled_payment` action is run before the `scheduled_expiration` action. Becuase that's how they are scheduled.

However, when using the `--hooks` option, the `scheduled_payment` and `scheduled_expiration` actions will be processed in separate queues. As a result, this dependency is not guaranteed.

For example, consider a site with both:

* 100,000 `scheduled_payment` actions, scheduled for `2015-11-13 00:00:00`
* 100 `scheduled_expiration` actions, scheduled for `2015-11-13 00:01:00`

If two queue runners are running alongside each other with each runner dedicated to just one of these hooks, the queue runner handling expiration hooks will complete the processing of the expiration hooks more quickly than the queue runner handling all the payment actions.

**Because of this, the `--group` and `--hooks` options should be used with caution to avoid processing actions with an implicit dependency based on their schedule in separate queues.**

## Improving Performance with `--group` or `--hooks`

Being able to run queues for specific hooks or groups of actions is valuable at scale. Why? Because it means you can restrict the concurrency for similar actions.

For example, let's say you have 300,000 actions queued up comprised of:

* 100,000 renewals payments
* 100,000 email notifications
* 100,000 membership status updates

Action Scheduler's default WP Cron queue runner will process them all together. e.g. when it claims a batch of actions, some may be emails, some membership updates and some renewals.

When you add concurrency to that, you can end up with issues. For example, if you have 3 queues running, they may all be attempting to process similar actions at the same time, which can lead to querying the same database tables with similar queries. Depending on the code/queries running, this can lead to database locks or other issues.

If you can batch based on each action's group, then you can improve performance by processing like actions consecutively, but still processing the full set of actions concurrently.

For example, if one queue is created to process emails, another to process membership updates, and another to process renewal payments, then the same queries won't be run at the same time, and 3 separate queues will be able to run more efficiently.

The WP CLI runner can achieve this using the `--group` option.

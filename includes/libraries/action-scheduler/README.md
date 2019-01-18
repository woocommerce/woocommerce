# Action Scheduler [![Build Status](https://travis-ci.org/Prospress/action-scheduler.png?branch=master)](https://travis-ci.org/Prospress/action-scheduler) [![codecov](https://codecov.io/gh/Prospress/action-scheduler/branch/master/graph/badge.svg)](https://codecov.io/gh/Prospress/action-scheduler)

A robust scheduling library for use in WordPress plugins.

## Overview

Action Scheduler uses a WordPress [custom post type](http://codex.wordpress.org/Post_Types), creatively named `scheduled-action`, to store the hook name, arguments and scheduled date for an action that should be triggered at some time in the future.

The scheduler will run every minute by attaching itself as a callback to the `'action_scheduler_run_schedule'` hook, which is scheduled using WordPress's built-in [WP-Cron](http://codex.wordpress.org/Function_Reference/wp_cron) system.

When triggered, Action Scheduler will check for posts of the `scheduled-action` type that have a `post_date` at or before this point in time i.e. actions scheduled to run now or at sometime in the past.

### Batch Processing

If there are actions to be processed, Action Scheduler will stake a unique claim for a batch of 20 actions and begin processing that batch. The PHP process spawned to run the batch will then continue processing batches of 20 actions until it times out or exhausts available memory.

If your site has a large number of actions scheduled to run at the same time, Action Scheduler will process more than one batch at a time. Specifically, when the `'action_scheduler_run_schedule'` hook is triggered approximately one minute after the first batch began processing, a new PHP process will stake a new claim to a batch of actions which were not claimed by the previous process. It will then begin to process that batch.

This will continue until all actions are processed using a maximum of 5 concurrent queues.

### Housekeeping

Before processing a batch, the scheduler will remove any existing claims on actions which have been sitting in a queue for more than five minutes.

Action Scheduler will also trash any actions which were completed more than a month ago.

If an action runs for more than 5 minutes, Action Scheduler will assume the action has timed out and will mark it as failed. However, if all callbacks attached to the action were to successfully complete sometime after that 5 minute timeout, its status would later be updated to completed.

### Record Keeping

Events for each action will be also logged in the [comments table](http://codex.wordpress.org/Database_Description#Table_Overview).

The events logged by default include when an action:
 * is created
 * starts
 * completes
 * fails

Actions can also be grouped together using a custom taxonomy named `action-group`.

## Usage

There are two ways to install Action Scheduler:

1. as a library within your plugin or theme; or
1. as a regular WordPress plugin

### Usage as a Library

To use Action Scheduler as a library:

1. include the Action Scheduler codebase
1. load the library by including the `action-scheduler.php` file

#### Including Action Scheduler Codebase

Using a [subtree in your plugin, theme or site's Git repository](https://www.atlassian.com/blog/git/alternatives-to-git-submodule-git-subtree) to include Action Scheduler is the recommended method. Composer can also be used.

To include Action Scheduler as a git subtree:

##### Step 1. Add the Repository as a Remote

```
git remote add -f subtree-action-scheduler https://github.com/Prospress/action-scheduler.git
```

Adding the subtree as a remote allows us to refer to it in short from via the name `subtree-action-scheduler`, instead of the full GitHub URL.

##### Step 2. Add the Repo as a Subtree

```
git subtree add --prefix libraries/action-scheduler subtree-action-scheduler master --squash
```

This will add the `master` branch of Action Scheduler to your repository in the folder `libraries/action-scheduler`.

You can change the `--prefix` to change where the code is included. Or change the `master` branch to a tag, like `2.1.0` to include only a stable version.

##### Step 3. Update the Subtree

To update Action Scheduler to a new version, use the commands:

```
git fetch subtree-action-scheduler master
git subtree pull --prefix libraries/action-scheduler subtree-action-scheduler master --squash
```

#### Loading Action Scheduler

To load Action Scheduler, you only need to include `action-scheduler.php` file, e.g.

```php
<?php
require_once( plugin_dir_path( __FILE__ ) . '/libraries/action-scheduler/action-scheduler.php' );
```

There is no need to call any functions or do else to initialize Action Scheduler.

When the `action-scheduler.php` file is included, Action Scheduler will register the version in that file and then load the most recent version of itself on the site. It will also load the most recent version of [all API functions](https://github.com/prospress/action-scheduler#api-functions).

#### Load Order

Action Scheduler will register its version on `'plugins_loaded'` with priority `0` - after all other plugin codebases has been loaded. Therefore **the `action-scheduler.php` file must be included before `'plugins_loaded'` priority `0`**.

It is recommended to load it _when the file including it is included_. However, if you need to load it on a hook, then the hook must occur before `'plugins_loaded'`, or you can use `'plugins_loaded'` with negative priority, like `-10`.

Action Scheduler will later initialize itself on `'init'` with priority `1`.  Action Scheduler APIs should not be used until after `'init'` with priority `1`.

### Usage as a Plugin

Action Scheduler includes the necessary file headers to be used as a plugin.

To install it as a plugin:

1. Download the .zip archive of the latest [stable release](https://github.com/Prospress/action-scheduler/releases)
1. Go to the **Plugins > Add New > Upload** administration screen on your WordPress site
1. Select the archive file you just downloaded
1. Click **Install Now**
1. Click **Activate**

Or clone the Git repository into your site's `wp-content/plugins` folder.

Using Action Scheduler as a plugin can be handy for developing against newer versions, rather than having to update the subtree in your codebase. **When installed as a plugin, Action Scheduler does not provide any user interfaces for scheduling actions**. The only way to interact with Action Scheduler is via code.

## Managing Scheduled Actions

Action Scheduler has a built in administration screen for monitoring, debugging and manually triggering scheduled actions.

The administration interface is accesible through both:

1. **Tools > Scheduled Actions**
1. **WooCommerce > Status > Scheduled Actions**, when WooCommerce is installed.

![](https://cldup.com/5BA2BNB1sw.png)

Among other tasks, from the admin screen you can:

* run a pending action
* view the scheduled actions with a specific status, like the all actions which have failed or are in-progress (https://cldup.com/NNTwE88Xl8.png).
* view the log entries for a specific action to find out why it failed.
* sort scheduled actions by hook name, scheduled date, claim ID or group name.

Still have questions? Check out the [FAQ below](#faq).

## WP CLI

Action Scheduler has custom [WP CLI](http://wp-cli.org) commands available for processing actions.

For large sites, WP CLI is a much better choice for running queues of actions than the default WP Cron runner. These are some common cases where WP CLI is a better option:

* long-running tasks - Tasks that take a significant amount of time to run
* large queues - A large number of tasks will naturally take a longer time
* other plugins with extensive WP Cron usage - WP Cron's limited resources are spread across more tasks

With a regular web request, you may have to deal with script timeouts enforced by hosts, or other restraints that make it more challenging to run Action Scheduler tasks. Utilizing WP CLI to run commands directly on the server give you more freedom. This means that you typically don't have the same constraints of a normal web request.

If you choose to utilize WP CLI exclusively, you can disable the normal WP CLI queue runner by installing the [Action Scheduler - Disable Default Queue Runner](https://github.com/Prospress/action-scheduler-disable-default-runner) plugin. Note that if you do this, you **must** run Action Scheduler via WP CLI or another method, otherwise no scheduled actions will be processed.
 
### Commands

These are the commands available to use with Action Scheduler:

* `action-scheduler run`
    
    Options:
    * `--batch-size` - This is the number of actions to run in a single batch. The default is `100`.
    * `--batches` - This is the number of batches to run. Using 0 means that batches will continue running until there are no more actions to run.
    * `--hooks` - Process only actions with specific hook or hooks, like `'woocommerce_scheduled_subscription_payment'`. By default, actions with any hook will be processed. Define multiple hooks as a comma separated string (without spaces), e.g. `--hooks=woocommerce_scheduled_subscription_trial_end,woocommerce_scheduled_subscription_payment,woocommerce_scheduled_subscription_expiration`
    * `--group` - Process only actions in a specific group, like `'woocommerce-memberships'`. By default, actions in any group (or no group) will be processed.
    * `--force` - By default, Action Scheduler limits the number of concurrent batches that can be run at once to ensure the server does not get overwhelmed. Using the `--force` flag overrides this behavior to force the WP CLI queue to run.

The best way to get a full list of commands and their available options is to use WP CLI itself. This can be done by running `wp action-scheduler` to list all Action Scheduler commands, or by including the `--help` flag with any of the individual commands. This will provide all relevant parameters and flags for the command.

### Cautionary Note on Action Dependencies when using `--group` or `--hooks` Options

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

### Improving Performance with `--group` or `--hooks`

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

## API Functions

### Action Scheduler API vs. WP-Cron API

The Action Scheduler API functions are designed to mirror the WordPress [WP-Cron API functions](http://codex.wordpress.org/Category:WP-Cron_Functions).

Functions return similar values and accept similar arguments to their WP-Cron counterparts. The notable differences are:

* `as_schedule_single_action()` & `as_schedule_recurring_action()` will return the post ID of the scheduled action rather than boolean indicating whether the event was scheduled
* `as_schedule_recurring_action()` takes an interval in seconds as the recurring interval rather than an arbitrary string
* `as_schedule_single_action()` & `as_schedule_recurring_action()` can accept a `$group` parameter to group different actions for the one plugin together.
* the `wp_` prefix is substituted with `as_` and the term `event` is replaced with `action`

### API Function Availability

As mentioned in the [Usage - Load Order](#load-order) section, Action Scheduler will initialize itself on the `'init'` hook with priority `1`. While API functions are loaded prior to this and call be called, they should not be called until after `'init'` with priority `1`, because each component, like the data store, has not yet been initialized.

Do not use Action Scheduler API functions prior to `'init'` hook with priority `1`. Doing so could lead to unexpected results, like data being stored in the incorrect location.

#### Function Reference / `as_schedule_single_action()`

##### Description

Schedule an action to run one time.

##### Usage

```php
<?php as_schedule_single_action( $timestamp, $hook, $args, $group ); ?>
````

##### Parameters

- **$timestamp** (integer)(required) The Unix timestamp representing the date you want the action to run. Default: _none_.
- **$hook** (string)(required) Name of the action hook. Default: _none_.
- **$args** (array) Arguments to pass to callbacks when the hook triggers. Default: _`array()`_.
- **$group** (array) The group to assign this job to. Default: _''_.

##### Return value

(integer) the action's ID in the [posts](http://codex.wordpress.org/Database_Description#Table_Overview) table.


#### Function Reference / `as_schedule_recurring_action()`

##### Description

Schedule an action to run repeatedly with a specified interval in seconds.

##### Usage

```php
<?php as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args, $group ); ?>
````

##### Parameters

- **$timestamp** (integer)(required) The Unix timestamp representing the date you want the action to run. Default: _none_.
- **$interval_in_seconds** (integer)(required) How long to wait between runs. Default: _none_.
- **$hook** (string)(required) Name of the action hook. Default: _none_.
- **$args** (array) Arguments to pass to callbacks when the hook triggers. Default: _`array()`_.
- **$group** (array) The group to assign this job to. Default: _''_.

##### Return value

(integer) the action's ID in the [posts](http://codex.wordpress.org/Database_Description#Table_Overview) table.


#### Function Reference / `as_schedule_cron_action()`

##### Description

Schedule an action that recurs on a cron-like schedule.

##### Usage

```php
<?php as_schedule_cron_action( $timestamp, $schedule, $hook, $args, $group ); ?>
````

##### Parameters

- **$timestamp** (integer)(required) The Unix timestamp representing the date you want the action to run. Default: _none_.
- **$schedule** (string)(required) $schedule A cron-link schedule string, see http://en.wikipedia.org/wiki/Cron. Default: _none_.
- **$hook** (string)(required) Name of the action hook. Default: _none_.
- **$args** (array) Arguments to pass to callbacks when the hook triggers. Default: _`array()`_.
- **$group** (array) The group to assign this job to. Default: _''_.

##### Return value

(integer) the action's ID in the [posts](http://codex.wordpress.org/Database_Description#Table_Overview) table.


#### Function Reference / `as_unschedule_action()`

##### Description

Cancel the next occurrence of a job.

##### Usage

```php
<?php as_unschedule_action( $hook, $args, $group ); ?>
````

##### Parameters

- **$hook** (string)(required) Name of the action hook. Default: _none_.
- **$args** (array) Arguments to pass to callbacks when the hook triggers. Default: _`array()`_.
- **$group** (array) The group to assign this job to. Default: _''_.

##### Return value

(null)


#### Function Reference / `as_next_scheduled_action()`

##### Description

Returns the next timestamp for a scheduled action.

##### Usage

```php
<?php as_next_scheduled_action( $hook, $args, $group ); ?>
````

##### Parameters

- **$hook** (string)(required) Name of the action hook. Default: _none_.
- **$args** (array) Arguments to pass to callbacks when the hook triggers. Default: _`array()`_.
- **$group** (array) The group to assign this job to. Default: _''_.

##### Return value

(integer|boolean) The timestamp for the next occurrence, or false if nothing was found.


#### Function Reference / `as_get_scheduled_actions()`

##### Description

Find scheduled actions.

##### Usage

```php
<?php as_get_scheduled_actions( $args, $return_format ); ?>
````

##### Parameters

- **$args** (array) Arguments to search and filter results by. Possible arguments, with their default values:
    * `'hook' => ''` - the name of the action that will be triggered
    * `'args' => NULL` - the args array that will be passed with the action
    * `'date' => NULL` - the scheduled date of the action. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime().
    * `'date_compare' => '<=`' - operator for testing "date". accepted values are '!=', '>', '>=', '<', '<=', '='
    * `'modified' => NULL` - the date the action was last updated. Expects a DateTime object, a unix timestamp, or a string that can parsed with strtotime().
    * `'modified_compare' => '<='` - operator for testing "modified". accepted values are '!=', '>', '>=', '<', '<=', '='
    * `'group' => ''` - the group the action belongs to
    * `'status' => ''` - ActionScheduler_Store::STATUS_COMPLETE or ActionScheduler_Store::STATUS_PENDING
    * `'claimed' => NULL` - TRUE to find claimed actions, FALSE to find unclaimed actions, a string to find a specific claim ID
    * `'per_page' => 5` - Number of results to return
    * `'offset' => 0`
    * `'orderby' => 'date'` - accepted values are 'hook', 'group', 'modified', or 'date'
    * `'order' => 'ASC'`
- **$return_format** (string) The format in which to return the scheduled actions: 'OBJECT', 'ARRAY_A', or 'ids'. Default: _'OBJECT'_.

##### Return value

(array) Array of the actions matching the criteria specified with `$args`.


## Performance Tuning

By default, Action Scheduler will process a minimum of 1,200 actions per hour. On servers which allow long running PHP processes, this will be significantly higher as processes will be able loop over queues indefinitely.

The batch size and number of concurrent queues that may be processed simultaneously is low by default to ensure the scheduler runs on low powered servers; however, you can configure these settings to increase performance on your site.

#### Increasing Batch Size

By default, Action Scheduler will claim a batch of 20 actions. This small batch size is to minimise the risk of causing a fatal error due to memory exhaustion.

If you know the callbacks attached to your actions use very little memory, or you've tested the number of actions you can process before memory limits are exceeded, you can increase the batch size using the `'action_scheduler_queue_runner_batch_size'` filter.

For example, to increase the batch size to 100, we can use the following function:

```
<?php
function eg_increase_action_scheduler_batch_size( $batch_size ) {
	return 100;
}
add_filter( 'action_scheduler_queue_runner_batch_size', 'eg_increase_action_scheduler_batch_size' );
?>
```

### Increasing Concurrent Batches

By default, Action Scheduler will run up to 5 concurrent batches of actions. This is to prevent consuming all the available connections or processes on your webserver.

However, your server may allow a large number of connection, for example, because it has a high value for Apache's `MaxClients` setting or PHP-FPM's `pm.max_children` setting.

If this is the case, you can use the `'action_scheduler_queue_runner_concurrent_batches'` filter to increase the number of conncurrent batches allowed, and therefore speed up processing large numbers of actions scheduled to be processed simultaneously.

For example, to increase the allowed number of concurrent queues to 25, we can use the following code:

```
<?php
function eg_increase_action_scheduler_concurrent_batches( $concurrent_batches ) {
	return 25;
}
add_filter( 'action_scheduler_queue_runner_concurrent_batches', 'eg_increase_action_scheduler_concurrent_batches' );
?>
```

## FAQ

### Is it safe to release Action Scheduler in my plugin? Won't its functions conflict with another copy of the library?

Action Scheduler is designed to be used and released in plugins. It avoids redeclaring public API functions when more than one copy of the library is being loaded by different plugins. It will also load only the most recent version of itself (by checking registered versions after all plugins are loaded on the `'plugins_loaded'` hook).

To use it in your plugin, simply require the `action-scheduler/action-scheduler.php` file. Action Scheduler will take care of the rest.

### I don't want to use WP-Cron. Does Action Scheduler depend on WP-Cron?

By default, Action Scheduler is initiated by WP-Cron. However, it has no dependency on the WP-Cron system. You can initiate the Action Scheduler queue in other ways with just one or two lines of code.

For example, you can start a queue directly by calling:

```php
ActionScheduler::runner()->run();
```

Or trigger the `'action_scheduler_run_queue'` hook and let Action Scheduler do it for you:

```php
do_action( 'action_scheduler_run_queue' );
```

Further customization can be done by extending the `ActionScheduler_Abstract_QueueRunner` class to create a custom Queue Runner. For an example of a customized queue runner, see the [`ActionScheduler_WPCLI_QueueRunner`](https://github.com/Prospress/action-scheduler/blob/master/classes/ActionScheduler_WPCLI_QueueRunner.php), which is used when running WP CLI.

Want to create some other method for initiating Action Scheduler? [Open a new issue](https://github.com/Prospress/action-scheduler/issues/new), we'd love to help you with it.

### I don't want to use WP-Cron, ever. Does Action Scheduler replace WP-Cron?

By default, Action Scheduler is designed to work alongside WP-Cron and not change any of its behaviour. This helps avoid unexpectedly overriding WP-Cron on sites installing your plugin, which may have nothing to do with WP-Cron.

However, we can understand why you might want to replace WP-Cron completely in environments within you control, especially as it gets you the advantages of Action Scheduler. This should be possible without too much code.

You could use the `'schedule_event'` hook in WordPress to use Action Scheduler for only newly scheduled WP-Cron jobs and map the `$event` param to Action Scheduler API functions.

Alternatively, you can use a combination of the `'pre_update_option_cron'` and  `'pre_option_cron'` hooks to override all new and previously scheduled WP-Cron jobs (similar to the way [Cavalcade](https://github.com/humanmade/Cavalcade) does it).

If you'd like to create a plugin to do this automatically and want to share your work with others, [open a new issue to let us know](https://github.com/Prospress/action-scheduler/issues/new), we'd love to help you with it.

### Eww gross, Custom Post Types! That's _so_ 2010. Can I use a different storage scheme?

Of course! Action Scheduler data storage is completely swappable, and always has been.

You can store scheduled actions in custom tables in the WordPress site's database. Some sites using it already are. You can actually store them anywhere for that matter, like in a remote storage service from Amazon Web Services.

To implement a custom store:

1. extend the abstract `ActionScheduler_Store` class, being careful to implement each of its methods
2. attach a callback to `'action_scheduler_store_class'` to tell Action Scheduler your class is the one which should be used to manage storage, e.g.

```
function eg_define_custom_store( $existing_storage_class ) {
	return 'My_Radical_Action_Scheduler_Store';
}
add_filter( 'action_scheduler_store_class', 'eg_define_custom_store', 10, 1 );
```

Take a look at the `ActionScheduler_wpPostStore` class for an example implementation of `ActionScheduler_Store`.

If you'd like to create a plugin to do this automatically and release it publicly to help others, [open a new issue to let us know](https://github.com/Prospress/action-scheduler/issues/new), we'd love to help you with it.

> Note: we're also moving Action Scheduler itself to use [custom tables for better scalability](https://github.com/Prospress/action-scheduler/issues/77).

### Can I use a different storage scheme just for logging?

Of course! Action Scheduler's logger is completely swappable, and always has been. You can also customise where logs are stored, and the storage mechanism.

To implement a custom logger:

1. extend the abstract `ActionScheduler_Logger` class, being careful to implement each of its methods
2. attach a callback to `'action_scheduler_logger_class'` to tell Action Scheduler your class is the one which should be used to manage logging, e.g.

```
function eg_define_custom_logger( $existing_storage_class ) {
	return 'My_Radical_Action_Scheduler_Logger';
}
add_filter( 'action_scheduler_logger_class', 'eg_define_custom_logger', 10, 1 );
```

Take a look at the `ActionScheduler_wpCommentLogger` class for an example implementation of `ActionScheduler_Logger`.

### I want to run Action Scheduler only on a dedicated application server in my cluster. Can I do that?

Wow, now you're really asking the tough questions. In theory, yes, this is possible. The `ActionScheduler_QueueRunner` class, which is responsible for running queues, is swappable via the `'action_scheduler_queue_runner_class'` filter.

Because of this, you can effectively customise queue running however you need. Whether that means tweaking minor things, like not using WP-Cron at all to initiate queues by overriding `ActionScheduler_QueueRunner::init()`, or completely changing how and where queues are run, by overriding `ActionScheduler_QueueRunner::run()`.

### Is Action Scheduler safe to use on my production site?

Yes, absolutely! Action Scheduler is actively used on tens of thousands of production sites already. Right now it's responsible for scheduling everything from emails to payments.

In fact, every month, Action Scheduler processes millions of payments as part of the [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) extension.

It requires no setup, and won't override any WordPress APIs (unless you want it to).

### How does Action Scheduler work on WordPress Multisite?

Action Scheduler is designed to manage the scheduled actions on a single site. It has no special handling for running queues across multiple sites in a multisite network. That said, because it's storage and Queue Runner are completely swappable, it would be possible to write multisite handling classes to use with it.

If you'd like to create a multisite plugin to do this and release it publicly to help others, [open a new issue to let us know](https://github.com/Prospress/action-scheduler/issues/new), we'd love to help you with it.

## Credits

This extension is developed and maintained as a collaboration between the teams at [Prospress](http://prospress.com/) and [Flightless](https://flightless.us/).

Collaboration is cool. We'd love to work with you to improve Action Scheduler. Pull Requests welcome.

---

<p align="center">
<img src="https://cloud.githubusercontent.com/assets/235523/11986380/bb6a0958-a983-11e5-8e9b-b9781d37c64a.png" width="160">
</p>

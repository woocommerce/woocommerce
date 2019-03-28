---
description: Learn how to use the Action Scheduler background processing job queue for WordPress in your WordPress plugin.
---
# Usage

Using Action Scheduler requires:

1. installing the library
1. scheduling and action
1. attaching a callback to that action

## Scheduling an Action

To schedule an action, call the [API function](/api/) for the desired schedule type passing in the required parameters.

The example code below shows everything needed to schedule a function to run at midnight, if it's not already scheduled:

```php
require_once( plugin_dir_path( __FILE__ ) . '/libraries/action-scheduler/action-scheduler.php' );

/**
 * Schedule an action with the hook 'eg_midnight_log' to run at midnight each day
 * so that our callback is run then.
 */
function eg_log_action_data() {
	if ( false === as_next_scheduled_action( 'eg_midnight_log' ) ) {
		as_schedule_recurring_action( strtotime( 'midnight tonight' ), DAY_IN_SECONDS, 'eg_midnight_log' );
	}
}
add_action( 'init', 'eg_log_action_data' );

/**
 * A callback to run when the 'eg_midnight_log' scheduled action is run.
 */
function eg_log_action_data() {
	error_log( 'It is just after midnight on ' . date( 'Y-m-d' ) );
}
add_action( 'eg_midnight_log', 'eg_log_action_data' );
```

For more details on all available API functions, and the data they accept, refer to the [API Reference](/api/).

## Installation

There are two ways to install Action Scheduler:

1. regular WordPress plugin; or
1. a library within your plugin's codebase.

### Usage as a Plugin

Action Scheduler includes the necessary file headers to be used as a standard WordPress plugin.

To install it as a plugin:

1. Download the .zip archive of the latest [stable release](https://github.com/Prospress/action-scheduler/releases)
1. Go to the **Plugins > Add New > Upload** administration screen on your WordPress site
1. Select the archive file you just downloaded
1. Click **Install Now**
1. Click **Activate**

Or clone the Git repository into your site's `wp-content/plugins` folder.

Using Action Scheduler as a plugin can be handy for developing against newer versions, rather than having to update the subtree in your codebase. **When installed as a plugin, Action Scheduler does not provide any user interfaces for scheduling actions**. The only way to interact with Action Scheduler is via code.

### Usage as a Library

To use Action Scheduler as a library:

1. include the Action Scheduler codebase
1. load the library by including the `action-scheduler.php` file

Using a [subtree in your plugin, theme or site's Git repository](https://www.atlassian.com/blog/git/alternatives-to-git-submodule-git-subtree) to include Action Scheduler is the recommended method. Composer can also be used.

To include Action Scheduler as a git subtree:

#### Step 1. Add the Repository as a Remote

```
git remote add -f subtree-action-scheduler https://github.com/Prospress/action-scheduler.git
```

Adding the subtree as a remote allows us to refer to it in short from via the name `subtree-action-scheduler`, instead of the full GitHub URL.

#### Step 2. Add the Repo as a Subtree

```
git subtree add --prefix libraries/action-scheduler subtree-action-scheduler master --squash
```

This will add the `master` branch of Action Scheduler to your repository in the folder `libraries/action-scheduler`.

You can change the `--prefix` to change where the code is included. Or change the `master` branch to a tag, like `2.1.0` to include only a stable version.

#### Step 3. Update the Subtree

To update Action Scheduler to a new version, use the commands:

```
git fetch subtree-action-scheduler master
git subtree pull --prefix libraries/action-scheduler subtree-action-scheduler master --squash
```

### Loading Action Scheduler

Regardless of how it is installed, to load Action Scheduler, you only need to include the `action-scheduler.php` file, e.g.

```php
<?php
require_once( plugin_dir_path( __FILE__ ) . '/libraries/action-scheduler/action-scheduler.php' );
```

There is no need to call any functions or do else to initialize Action Scheduler.

When the `action-scheduler.php` file is included, Action Scheduler will register the version in that file and then load the most recent version of itself on the site. It will also load the most recent version of [all API functions](https://github.com/prospress/action-scheduler#api-functions).

### Load Order

Action Scheduler will register its version on `'plugins_loaded'` with priority `0` - after all other plugin codebases has been loaded. Therefore **the `action-scheduler.php` file must be included before `'plugins_loaded'` priority `0`**.

It is recommended to load it _when the file including it is included_. However, if you need to load it on a hook, then the hook must occur before `'plugins_loaded'`, or you can use `'plugins_loaded'` with negative priority, like `-10`.

Action Scheduler will later initialize itself on `'init'` with priority `1`.  Action Scheduler APIs should not be used until after `'init'` with priority `1`.

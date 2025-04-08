---
post_title: Managing extension deactivation and uninstallation
menu_title: Manage deactivation and uninstallation
tags: how-to
---

## Introduction

There are a number of cleanup tasks you'll need to handle when a merchant deactivates or uninstalls your extension. This guide provides a brief overview of WooCommerce-specific items you'll want to make sure you account for when defining your extension's deactivation and uninstallation logic.

## Removing Scheduled Actions

If your extension uses Action Scheduler to queue any background jobs, it's important to unschedule those actions when your extension is uninstalled or deactivated.

`as_unschedule_all_actions( $hook, $args, $group );`

You can read more about using Action Scheduler for managing background processing in the [Action Scheduler API Reference](https://actionscheduler.org/api/).

## Removing Admin Notes

If you have created any Notes for merchants, you should delete those notes when your extension is deactivated or, at the very least, when it is uninstalled.

```php
function my_great_extension_deactivate() {
    ExampleNote::possibly_delete_note();
}
register_deactivation_hook( __FILE__, 'my_great_extension_deactivate' );

```

The example above assumes that you have followed the pattern this guide recommends for creating Notes as dedicated classes that include the `NoteTraits` trait included with WooCommerce Admin. This approach provides your Note with some baked in functionality that streamlines note operations such as creation and deletion.

## Removing Admin Tasks

When your extension is deactivated or uninstalled, you should take care to unregister any tasks that your extension created for merchants.

```php
// Unregister task.
function my_extension_deactivate_task() {
    remove_filter( 'woocommerce_get_registered_extended_tasks', 'my_extension_register_the_task', 10, 1 );
}
 
register_deactivation_hook( __FILE__, 'my_extension_deactivate_task' );
```

Keep in mind that merchant tasks are managed via a hybrid approach that involves both PHP and JavaScript, so the client-side registration only happens when your extension's JavaScript runs.

## Unregistering navigation

When your extension deactivates and uninstalls, any registration you've done with the WooCommerce Navigation will be handled automatically.

## WordPress cleanup tasks

There are additional measures you may need to consider when your extension is deactivated or uninstalled, depending on the types of modifications it makes to the underlying WordPress environment when it activates and runs. You can read more about handling deactivation and uninstallation in the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/intro/).

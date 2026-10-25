---
post_title: Uninstall and remove all WooCommerce Data
sidebar_label: Uninstalling and removing data

current wccom url: https://woocommerce.com/document/installing-uninstalling-woocommerce/#uninstalling-woocommerce
---

# Uninstall and remove all WooCommerce Data

The WooCommerce plugin can be uninstalled like any other WordPress plugin. By default, the WooCommerce data is left in place though. 

If you need to remove *all* WooCommerce data as well, including products, order data, coupons, etc., you need to to modify the site's `wp-config.php` *before* deactivating and deleting the WooCommerce plugin.

As this action is destructive and permanent, the information is provided as is. WooCommerce Support cannot help with this process or anything that happens as a result. 

To fully remove all WooCommerce data from your WordPress site, open `wp-config.php`, scroll down to the bottom of the file, and add the following constant on its own line above `/* That's all, stop editing. */`.

```php
define( 'WC_REMOVE_ALL_DATA', true );

/* That's all, stop editing! Happy publishing. */ 
```

Then, once the changes are saved to the file, when you deactivate and delete WooCommerce, all of its data is removed from your WordPress site database.

![Uninstall WooCommerce WPConfig](https://woocommerce.com/wp-content/uploads/2020/03/uninstall_wocommerce_plugin_wpconfig.png)

## Removing the Action Scheduler tables

WooCommerce uses [Action Scheduler](https://actionscheduler.org/) to run background tasks. Action Scheduler is a shared library that other plugins can also bundle, so its database tables (`actionscheduler_actions`, `actionscheduler_claims`, `actionscheduler_groups` and `actionscheduler_logs`) are **not** removed by `WC_REMOVE_ALL_DATA`, even when the constant is set to `true`. Keeping them avoids deleting scheduled tasks that another active plugin might still rely on.

If you are certain that no other plugin on the site uses Action Scheduler, you can also remove those tables by adding the `WC_REMOVE_ACTION_SCHEDULER` constant alongside `WC_REMOVE_ALL_DATA` in `wp-config.php`:

```php
define( 'WC_REMOVE_ALL_DATA', true );
define( 'WC_REMOVE_ACTION_SCHEDULER', true );

/* That's all, stop editing! Happy publishing. */
```

Both constants are required: `WC_REMOVE_ALL_DATA` triggers the removal of WooCommerce data, and `WC_REMOVE_ACTION_SCHEDULER` additionally drops the Action Scheduler tables.

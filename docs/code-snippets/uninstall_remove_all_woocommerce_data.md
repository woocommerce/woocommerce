---
post_title: Uninstall and remove all WooCommerce Data
menu_title: Uninstalling and removing data
tags: code-snippet
current wccom url: https://woocommerce.com/document/installing-uninstalling-woocommerce/#uninstalling-woocommerce
---

# Uninstall and remove all WooCommerce Data

The WooCommerce plugin can be uninstalled like any other WordPress plugin. By default, the WooCommerce data is left in place though. 

If you need to remove *all* WooCommerce data as well, including products, order data, coupons, etc., you need to to modify the site’s `wp-config.php` *before* deactivating and deleting the WooCommerce plugin.

As this action is destructive and permanent, the information is provided as is. WooCommerce Support cannot help with this process or anything that happens as a result. 

To fully remove all WooCommerce data from your WordPress site, open `wp-config.php`, scroll down to the bottom of the file, and add the following constant on its own line above `/* That’s all, stop editing. */`.

```php
define( 'WC_REMOVE_ALL_DATA', true );

/* That’s all, stop editing! Happy publishing. */ 
```

Then, once the changes are saved to the file, when you deactivate and delete WooCommerce, all of its data is removed from your WordPress site database.

![Uninstall WooCommerce WPConfig](https://woocommerce.com/wp-content/uploads/2020/03/uninstall_wocommerce_plugin_wpconfig.png)

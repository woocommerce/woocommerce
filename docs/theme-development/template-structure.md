---
post_title: Template structure & Overriding templates via a theme
---

---

**NOTE** This document makes reference to classic themes which use PHP templates. If you are working on a block theme with HTML templates, [please check the Theming docs for block themes](../block-theme-development/theming-woo-blocks.md).
Overview

---


## Overview

WooCommerce template files contain the markup and template structure for the frontend and HTML emails of your store.

Below is video walkthrough showing how one may go about updating the template files. 


[![Documentation for Template structure & Overriding templates via a theme](https://embed-ssl.wistia.com/deliveries/a2f57c5896505b39952aa8411a474066.jpg?image_play_button_size=2x&amp;image_crop_resized=960x540&amp;image_play_button=1&amp;image_play_button_color=694397e0)](https://woocommerce.com/document/template-structure/?wvideo=8mvl4bro0g)


## Template list

The various template files on your WooCommerce site can be found via an FTP client or your hosts file manager, in `/wp-content/plugins/woocommerce/templates/`. Alternatively, you can find the [template files on our repository on GitHub](https://github.com/woocommerce/woocommerce/blob/trunk/docs/theme-development/template-structure.md).

Note: if you are looking for the template files of older versions, you can find them in these paths:

* Versions 6.0.0 and later: `https://github.com/woocommerce/woocommerce/tree/[VERSION_NUMBER]/plugins/woocommerce/templates`
    * For example, to find the template files for WooCommerce 9.4.0, you would navigate to [https://github.com/woocommerce/woocommerce/tree/9.4.0/plugins/woocommerce/templates](https://github.com/woocommerce/woocommerce/tree/9.4.0/plugins/woocommerce/templates).
* Versions prior to 6.0.0: `https://github.com/woocommerce/woocommerce/tree/[VERSION_NUMBER]/templates`
    * For example, to find the template files for WooCommerce 5.9.0, you would navigate to [https://github.com/woocommerce/woocommerce/tree/5.9.0/templates](https://github.com/woocommerce/woocommerce/tree/5.9.0/templates).
             
## Changing Templates via Hooks

When you open a template file, you will notice they all contain _hooks_ that allow you to add/move content without needing to edit template files themselves. Hooks are a way for one piece of code to interact/modify another piece of code at specific, pre-defined spots. This method allows implementing a code snippet that "hooks" into a particular a theme location. It avoids upgrade issues, as the template files can be left completely untouched and doesn't require a child theme to be configured.

Let's take a look at [/wp-content/plugins/woocommerce/templates/emails/admin-new-order.php](https://github.com/woocommerce/woocommerce/blob/8.9.0/plugins/woocommerce/templates/emails/admin-new-order.php) and see what a hook looks like. Starting on line 30, we see the following code, which is responsible for producing the order details section of the New Order email.

```php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
```

The code above outputs the block highlighted in red in the image below, which is the New Order email a shop manager receives following a successful order on their site: 

![image](https://woocommerce.com/wp-content/uploads/2020/05/templating-using-hooks.webp) 

A code below can be used as a starting point to build out desired functionality. It can then be added to a code snippets plugin to modify the output at that particular location in the template, without having to edit the template itself. The same goes for other hooks, wherever in the templates they may appear. 

```php
add_action( 'woocommerce_email_order_details', 'my_custom_woo_function');
function my_custom_woo_function() { 
    /* Your code goes here */
}
```

## Changing Templates by Editing the Files

Editing files directly in a plugin or a parent theme creates the risk of causing errors that could bring a site to a grinding halt. But more importantly, any changes made in this way will disappear when the plugin or theme updates itself; a process that entirely deletes the old version and replaces it with a fresh, updated copy.

Instead, the recommended approach is to [set up a child theme](https://developer.woocommerce.com/docs/how-to-set-up-and-use-a-child-theme/), which creates a safe directory where to make overriding changes that will not be automatically updated.

For this example, let's call our child theme `storefront-child`. With `storefront-child` in place, edits can be made in an upgrade-safe way by using overrides. Copy the template into a directory within your child theme named `/storefront-child/woocommerce/` keeping the same file structure but removing the `/templates/` subdirectory.

To override the admin order notification in our example, copy `wp-content/plugins/woocommerce/templates/emails/admin-new-order.php` to `wp-content/themes/storefront-child/woocommerce/emails/admin-new-order.php`

The copied file will now override the WooCommerce default template file, so you can make any changes you wish to the copied file, and see it reflected in the resulting output.

---

**Note** A (desirable) side-effect of your templates being upgrade-safe is that WooCommerce core templates will update, but your custom overrides will not. You may occassionally see notices in your System Status report that says, e.g. "version 3.5.0 is out of date. The core version is 3.7.0". Should that happen, follow the Fixing Outdated WooCommerce Templates guide to bring them in line.

---

## Declare Theme Support for Custom Templates

If you are a theme developer or using a theme with custom templates, you must declare WooCommerce theme support using the `add_theme_support` function. See [Declaring WooCommerce Support in Themes](https://github.com/woocommerce/woocommerce/wiki/Declaring-WooCommerce-support-in-themes) at GitHub.

If your theme has `woocommerce.php`, you will be unable to override `woocommerce/archive-product.php` custom template in your theme, as `woocommerce.php` has priority over other template files. This is intended to prevent display issues.

---

Need support with editing your Woo store? WooExpert agencies are here to help. They are trusted agencies with a proven track record of building highly customized, scalable online stores.
[Hire an Expert](https://woocommerce.com/customizations/).

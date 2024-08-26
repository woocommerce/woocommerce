---
post_title: Template structure & Overriding templates via a theme
---

---

**NOTE** This document makes reference to classic themes which use PHP templates. If you are working on a block theme with HTML templates, [please check the Theming docs for block themes](https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce-blocks/docs/designers/theming/README.md).
Overview

---


## Overview

WooCommerce template files contain the markup and template structure for the frontend and HTML emails of your store.

Below is video walkthrough showing how one may go about updating the template files. 


[![Documentation for Template structure & Overriding templates via a theme](https://embed-ssl.wistia.com/deliveries/a2f57c5896505b39952aa8411a474066.jpg?image_play_button_size=2x&amp;image_crop_resized=960x540&amp;image_play_button=1&amp;image_play_button_color=694397e0)](https://woocommerce.com/document/template-structure/?wvideo=8mvl4bro0g)


## Template list

The various template files on your WooCommerce site can be found via an FTP client or your hosts file manager, in `/wp-content/plugins/woocommerce/templates/`. Below are links to the current and earlier versions of the WooCommerce template files on Github, where you can view the code exactly as it appears in those files:

| Latest Version | Files |
| -------------- | ----- | 
| 8.9            | [View template files](https://github.com/woocommerce/woocommerce/tree/8.9.0/plugins/woocommerce/templates) |

Below are the links to the files of all major previous WooCommerce versions: 

| Version        | Files |
| -------------- | ----- | 
| 8.8.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.8.0/plugins/woocommerce/templates) |
| 8.7.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.7.0/plugins/woocommerce/templates) |
| 8.6.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.6.0/plugins/woocommerce/templates) |
| 8.5.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.5.0/plugins/woocommerce/templates) |
| 8.4.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.4.0/plugins/woocommerce/templates) |
| 8.3.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.3.0/plugins/woocommerce/templates) |
| 8.2.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.2.0/plugins/woocommerce/templates) |
| 8.1.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.1.0/plugins/woocommerce/templates) |
| 8.0.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/8.0.0/plugins/woocommerce/templates) |
| 7.9.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.9.0/plugins/woocommerce/templates) |
| 7.8.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.8.0/plugins/woocommerce/templates) |
| 7.7.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.7.0/plugins/woocommerce/templates) |
| 7.6.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.6.0/plugins/woocommerce/templates) |
| 7.5.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.5.0/plugins/woocommerce/templates) |
| 7.4.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.4.0/plugins/woocommerce/templates) |
| 7.3.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.3.0/plugins/woocommerce/templates) |
| 7.2.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.2.0/plugins/woocommerce/templates) |
| 7.1.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.1.0/plugins/woocommerce/templates) |
| 7.0.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/7.0.0/plugins/woocommerce/templates) |
| 6.9.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.9.0/plugins/woocommerce/templates) |
| 6.8.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.8.0/plugins/woocommerce/templates) |
| 6.7.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.7.0/plugins/woocommerce/templates) |
| 6.6.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.6.0/plugins/woocommerce/templates) |
| 6.5.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.5.0/plugins/woocommerce/templates) |
| 6.4.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.4.0/plugins/woocommerce/templates) |
| 6.3.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.3.0/plugins/woocommerce/templates) |
| 6.2.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.2.0/plugins/woocommerce/templates) |
| 6.1.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.1.0/plugins/woocommerce/templates) |
| 6.0.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/6.0.0/plugins/woocommerce/templates) |
| 5.9.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.9.0/templates) |
| 5.8.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.8.0/templates) | 
| 5.7.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.7.0/templates) |
| 5.6.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.6.0/templates) |
| 5.5.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.5.0/templates) |
| 5.4.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.4.0/templates) |
| 5.3.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.3.0/templates) |
| 5.2.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.2.0/templates) |
| 5.1.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.1.0/templates) |
| 5.0.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/5.0.0/templates) |
| 4.9.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.9.0/templates) |
| 4.8.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.8.0/templates) |
| 4.7.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.7.0/templates) |
| 4.6.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.6.0/templates) |
| 4.5.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.5.0/templates) |
| 4.4.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.4.0/templates) |
| 4.3.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.3.0/templates) |
| 4.2.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.2.0/templates) |
| 4.1.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.1.0/templates) |
| 4.0.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/4.0.0/templates) |
| 3.9.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.9.0/templates) |
| 3.8.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.8.0/templates) |
| 3.7.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.7.0/templates) |
| 3.6.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.6.0/templates) |
| 3.5.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.5.0/templates) |
| 3.4.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.4.0/templates) |
| 3.3.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.3.0/templates) |
| 3.2.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.2.0/templates) |
| 3.1.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.1.0/templates) |
| 3.0.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/3.0.0/templates) |
| 2.6.0          | [View template files](https://github.com/woocommerce/woocommerce/tree/2.6.0/templates) |

             
## Changing Templates via Hooks

When you open a template file, you will notice they all contain _hooks_ that allow you to add/move content without needing to edit template files themselves. Hooks are a way for one piece of code to interact/modify another piece of code at specific, pre-defined spots. This method allows implementing a code snippet that “hooks” into a particular a theme location. It avoids upgrade issues, as the template files can be left completely untouched and doesn't require a child theme to be configured.

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

**Note** A (desirable) side-effect of your templates being upgrade-safe is that WooCommerce core templates will update, but your custom overrides will not. You may occassionally see notices in your System Status report that says, e.g. “version 3.5.0 is out of date. The core version is 3.7.0″. Should that happen, follow the Fixing Outdated WooCommerce Templates guide to bring them in line.

---

## Declare Theme Support for Custom Templates

If you are a theme developer or using a theme with custom templates, you must declare WooCommerce theme support using the `add_theme_support` function. See [Declaring WooCommerce Support in Themes](https://github.com/woocommerce/woocommerce/wiki/Declaring-WooCommerce-support-in-themes) at GitHub.

If your theme has `woocommerce.php`, you will be unable to override `woocommerce/archive-product.php` custom template in your theme, as `woocommerce.php` has priority over other template files. This is intended to prevent display issues.

---

Need support with editing your Woo store? WooExpert agencies are here to help. They are trusted agencies with a proven track record of building highly customized, scalable online stores.
[Hire an Expert](https://woocommerce.com/customizations/).

---
post_title: Template structure & Overriding templates via a theme
---

---

**Note:** this document is geared toward template development for classic themes. For the recommended modern approach,
visit [Develop Your First Low-Code Block Theme](https://learn.wordpress.org/course/develop-your-first-low-code-block-theme/)
to learn about block theme development, and explore
the [Create Block Theme plugin](https://wordpress.org/plugins/create-block-theme/) tool when you're ready to create a
new theme.  
We are unable to provide support for customizations under our [Support Policy](http://woocommerce.com/support-policy/). If you
need to further customize a snippet, or extend its functionality, we highly
recommend [Codeable](https://codeable.io/?ref=z4Hnp), or a [Certified WooExpert](https://woo.com/experts/).

---

## Overview

---

WooCommerce template files contain the **markup** and **template structure** for **frontend and HTML emails** of your
store.

[![Documentation for Template structure & Overriding templates via a theme](https://embed-ssl.wistia.com/deliveries/a2f57c5896505b39952aa8411a474066.jpg?image_play_button_size=2x&amp;image_crop_resized=960x540&amp;image_play_button=1&amp;image_play_button_color=694397e0)](https://woo.com/document/template-structure/?wvideo=8mvl4bro0g)

When you open these files, you will notice they all contain **hooks** that allow you to add/move content without needing
to edit template files themselves. This method protects against upgrade issues, as the template files can be left
completely untouched.

## Template List

---

Template files can be found within the **/woocommerce/templates/** directory:

| Latest version | Files                                                                                                      |
|:---------------|:-----------------------------------------------------------------------------------------------------------|
| v8.4.0         | [View template files](https://github.com/woocommerce/woocommerce/tree/8.4.0/plugins/woocommerce/templates) |

---
<!-- markdownlint-disable MD033 -->
<details>
<summary>Expand to view files of all major previous versions</summary>

| Version | Files                                                                                                      |
|---------|------------------------------------------------------------------------------------------------------------|
| v8.3.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/8.3.0/plugins/woocommerce/templates) |
| v8.2.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/8.2.0/plugins/woocommerce/templates) |
| v.8.1.0 | [View template files](https://github.com/woocommerce/woocommerce/tree/8.1.0/plugins/woocommerce/templates) |
| v8.0.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/8.0.0/plugins/woocommerce/templates) |
| v7.9.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.9.0/plugins/woocommerce/templates) |
| v7.8.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.8.0/plugins/woocommerce/templates) |
| v7.7.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.7.0/plugins/woocommerce/templates) |
| v7.6.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.6.0/plugins/woocommerce/templates) |
| v7.5.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.5.0/plugins/woocommerce/templates) |
| v7.4.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.4.0/plugins/woocommerce/templates) |
| v7.3.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.3.0/plugins/woocommerce/templates) |
| v7.2.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.2.0/plugins/woocommerce/templates) |
| v7.1.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.1.0/plugins/woocommerce/templates) |
| v7.0.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/7.0.0/plugins/woocommerce/templates) |
| v6.9.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.9.0/plugins/woocommerce/templates) |
| v6.8.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.8.0/plugins/woocommerce/templates) |
| v6.7.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.7.0/plugins/woocommerce/templates) |
| v6.6.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.6.0/plugins/woocommerce/templates) |
| v6.5.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.5.0/plugins/woocommerce/templates) |
| v6.4.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.4.0/plugins/woocommerce/templates) |
| v6.3.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.3.0/plugins/woocommerce/templates) |
| v6.2.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.2.0/plugins/woocommerce/templates) |
| v6.1.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.1.0/plugins/woocommerce/templates) |
| v6.0.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/6.0.0/plugins/woocommerce/templates) |
| v5.9.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.9.0/templates)                     |
| v5.8.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.8.0/templates)                     |
| v5.7.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.7.0/templates)                     |
| v5.6.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.6.0/templates)                     |
| v5.5.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.5.0/templates)                     |
| v5.4.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.4.0/templates)                     |
| v5.3.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.3.0/templates)                     |
| v5.2.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.2.0/templates)                     |
| v5.1.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.1.0/templates)                     |
| v5.0.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/5.0.0/templates)                     |
| v4.9.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.9.0/templates)                     |
| v4.8.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.8.0/templates)                     |
| v4.7.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.7.0/templates)                     |
| v4.6.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.6.0/templates)                     |
| v4.5.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.5.0/templates)                     |
| v4.4.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.4.0/templates)                     |
| v4.3.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.3.0/templates)                     |
| v4.2.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.2.0/templates)                     |
| v4.1.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.1.0/templates)                     |
| v4.0.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/4.0.0/templates)                     |
| v3.9.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.9.0/templates)                     |
| v3.8.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.8.0/templates)                     |
| v3.7.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.9.0/templates)                     |
| v3.6.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.6.0/templates)                     |
| v3.5.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.5.0/templates)                     |
| v3.4.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.4.0/templates)                     |
| v3.3.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.3.0/templates)                     |
| v3.2.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.2.0/templates)                     |
| v3.1.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.1.0/templates)                     |
| v3.0.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/3.0.0/templates)                     |
| v2.6.0  | [View template files](https://github.com/woocommerce/woocommerce/tree/2.6.0/templates)                     |
</details>
<!-- markdownlint-enable MD033 -->

---

## How to Edit Files

---

Edit files in an **upgrade-safe** way using **overrides**. Copy the template into a directory within your theme named `/woocommerce` keeping the same file structure but removing the `/templates/` subdirectory.

Example: To override the admin order notification, copy: `wp-content/plugins/woocommerce/templates/emails/admin-new-order.php` to `wp-content/themes/yourtheme/woocommerce/emails/admin-new-order.php`.

The copied file will now override the WooCommerce default template file.

**Warning:** Do not edit these files within the core plugin itself as they are overwritten during the upgrade process and any customizations will be lost. For more detailed information, see [Fixing Outdated WooCommerce Templates](https://woo.com/document/fix-outdated-templates-woocommerce/).

## For Custom Templates

If you are a theme developer or using a theme with custom templates, you must declare WooCommerce theme support using the `add_theme_support` function. See [Declaring WooCommerce Support in Themes](https://github.com/woocommerce/woocommerce/wiki/Declaring-WooCommerce-support-in-themes) at GitHub.

If your theme has a `woocommerce.php` file, you will be unable to override the `woocommerce/archive-product.php` custom template in your theme, as `woocommerce.php` has priority over other template files. This is intended to prevent display issues.

---

Need support with editing your Woo store? WooExpert agencies are here to help. They are trusted agencies with a proven track record of building highly customized, scalable online stores.
[Hire an Expert](https://woo.com/customizations/).

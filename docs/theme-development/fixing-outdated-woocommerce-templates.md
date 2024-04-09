---
post_title: How to fix outdated WooCommerce templates
menu_title: Fix outdated templates
tags: how-to
---

## Template Updates and Changes

We sometimes update the default templates when a new version of WooCommerce is released. This applies to major releases (WooCommerce 2.6, 3.0, and 4.0) but also to minor releases (WooCommerce 3.8.0).

Starting in WooCommerce version 3.3, most themes look great with WooCommerce. 

[Our developer-focused blog](https://developer.woocommerce.com/blog/) will list any template file changes with each release. You may need to update templates yourself or contact the theme author for an update if:

- you are using a theme with older templates or an older version of WooCommerce, or
- you modified templates or are using a child theme.

Most theme authors fix themes in a timely manner, so you only need to update your theme to get the updated templates.

Alternatively, you can select and use a different theme that already uses current WooCommerce templates.

## How to Update Outdated Templates

You need to determine what templates to update, make a backup of the old templates, and then restore any customizations.

1. Go to WooCommerce > Status > System Status. Scroll to the end of the page where there is a list of templates overridden by your theme/child theme and a warning message that they need to be updated. In the example below, the templates `form-pay.php` and `form-login.php` are outdated:
 ![An example for outdated templates.](https://woo-docs-multi-com.go-vip.net/wp-content/uploads/2023/12/fix_outdated_theme_templates.png)
2. Save a backup of the outdated template.
3. Copy the default template from `wp-content/plugins/woocommerce/templates/[path-to-the-template]` and paste it in your theme folder found at `wp-content/themes/[path-to-theme]`.
4. Open the template you pasted into the theme folder with a text editor, such as Sublime, Visual Code, BBEdit, Notepad++, and replicate any changes that you had to the previous template in your new, updated template file.

We recognize that it can be time-consuming. This is why we try to avoid changing WooCommerce templates, but sometimes it is wise to break backward compatibility.

## FAQ

### Where can I find the latest version of WooCommerce?

If you are looking for the default templates to use for updating, you want to use the latest version of WooCommerce. There are a few easy ways to get the templates:

- Access the files via FTP if your current WooCommerce installation is up to date.
- Find the templates per WooCommerce version in our [Template Structure documentation](https://woocommerce.com/document/template-structure/).
- Download the latest version from [the WordPress.org plugin page](https://wordpress.org/plugins/woocommerce/).
- Download the latest release from [the GitHub repository](https://github.com/woocommerce/woocommerce/releases).

### Why don't you make a button to click and update everything?

It is impossible to make a video or a one-click update. Why? Because there are thousands of themes, and every theme is coded differently. One size does not fit all.

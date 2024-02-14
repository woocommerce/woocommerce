---
post_title: How to set up and use a child theme
menu_title: Set up and use a child theme
tags: how-to
---

**Note:** This document is intended for creating and using classic child themes. For a comprehensive guide on creating a child block theme and understanding the differences between a classic and block theme, please refer to [this detailed documentation](https://learn.wordpress.org/lesson-plan/create-a-basic-child-theme-for-block-themes/).


Sometimes, you might need to customize your theme or WooCommerce beyond what is possible via the options. These guidelines will teach you the basics of how to go about customizing your site by using a child theme.

## What is a child theme?

Before we start it's important that you understand what a child theme is. In short, a child theme is a layer that you put on top of the parent theme to make alterations without having to develop a new theme from scratch. There are two major reasons to use child themes:

- Theme developers can use child themes as a way to offer variations on a theme, similar to what we do with the [Storefront child themes](https://woo.com/products/storefront/)
- Developers can use child themes to host customizations of the parent theme or any plugin on the site since the child theme will get priority over the plugins and parent theme

Read [this guide from the WordPress Codex](https://developer.wordpress.org/themes/advanced-topics/child-themes/).

## Make a backup

Before customizing a website, you should always ensure that you have a backup of your site in case anything goes wrong. More info at: [Backing up WordPress content](https://woo.com/document/backup-wordpress-content/).

## Getting started

To get started, we need to prepare a child theme.

### Making the child theme

First, we need to create a new stylesheet for our child theme. Create a new file called `style.css` and put this code in it:

```css
/*
Theme Name: Child Theme
Version: 1.0
Description: Child theme for Woo.
Author: Woo
Author URI: https://woo.com
Template: themedir
*/
```

Next, we need to change the **Template** field to point to our installed WooTheme. In this example, we'll use the Storefront theme, which is installed under `wp-content/themes/storefront/`. The result will look like this:

```css
/*
Theme Name: Storefront Child
Version: 1.0
Description: Child theme for Storefront.
Author: Woo
Author URI: https://woo.com
Template: storefront
*/

/* --------------- Theme customization starts here ----------------- */
```

**Note:** With Storefront, you do not need to enqueue any of the parent theme style files with PHP from the theme's `functions.php` file or `@import` these into the child themes `style.css` file as the main parent Storefront theme does this for you.

With Storefront, a child theme only requires a blank `functions.php` file and a `style.css` file to get up and running.

## Uploading and activating

You can upload the child theme either through your FTP client, or using the Add New theme option in WordPress.

- **Through FTP.** If you're using FTP, it means that you go directly to the folders of your website. That means you'll need **FTP access** to your host, so you can upload the new child theme. If you don't have this, you should talk to your host and they can give you your FTP login details, and then download an FTP program to upload your files.
- **Through the WP Dashboard.** If you create a .zip file of your child theme folder you can then simply upload that to your site from the **WordPress > Appearance > Themes > Add New** section.

Once you've done that, your child theme will be uploaded to a new folder in `wp-content/themes/`, for example, `wp-content/themes/storefront-child/`. Once uploaded, we can go to our **WP Dashboard > Appearance > Themes** and activate the child theme.

## Customizing design and functionality

Your child theme is now ready to be modified. Currently, it doesn't hold any customization, so let's look at a couple of examples of how we can customize the child theme without touching the parent theme.

### Design customization

Let's do an example together where we change the color of the site title. Add this to your `/storefront-child/style.css`:

```css
.site-branding h1 a {
    color: red;
}
```

After saving the file and refreshing our browser, you will now see that the color of the site title has changed!

### Template changes

**Note:** This doesn't apply to Storefront child themes. Any customizations to a Storefront child theme's files will be lost when updating. Instead of customizing the Storefront child theme's files directly, we recommended that you add code snippets to a customization plugin. We've created one to do just this. Download [Theme Customizations](https://github.com/woocommerce/theme-customisations) for free.

But wait, there's more! You can do the same with the template files (`*.php`) in the theme folder. For example if w, wanted to modify some code in the header, we need to copy header.php from our parent theme folder `wp-content/themes/storefront/header.php` to our child theme folder `wp-content/themes/storefront-child/header.php`. Once we have copied it to our child theme, we edit `header.php` and customize any code we want. The `header.php` in the child theme will be used instead of the parent theme's `header.php`.

The same goes for WooCommerce templates. If you create a new folder in your child theme called "WooCommerce", you can make changes to the WooCommerce templates there to make it more in line with the overall design of your website. More on WooCommerce's template structure [can be found here](https://woo.com/document/template-structure/).

### Functionality changes

**NOTE**: The functions.php in your child theme should be **empty** and not include anything from the parent theme's functions.php.

The `functions.php` in your child theme is loaded **before** the parent theme's `functions.php`. If a function in the parent theme is **pluggable**, it allows you to copy a function from the parent theme into the child theme's `functions.php` and have it replace the one in your parent theme. The only requirement is that the parent theme's function is **pluggable**, which basically means it is wrapped in a conditional if statement e.g:

```php
if ( ! function_exists( "parent_function_name" ) ) {
    parent_function_name() {
        ...
    }
}
```

If the parent theme function is **pluggable**, you can copy it to the child theme `functions.php` and modify the function to your liking.

## Template directory vs stylesheet directory

WordPress has a few things that it handles differently in child themes. If you have a template file in your child theme, you have to modify how WordPress includes files. `get_template_directory()` will reference the parent theme. To make it use the file in the child theme, you need to change use `get_stylesheet_directory();`.

[More info on this from the WP Codex](https://developer.wordpress.org/themes/advanced-topics/child-themes/#referencing-or-including-other-files)

## Child theme support

Although we do offer basic child theme support that can easily be answered, it still falls under theme customization, so please refer to our [support policy](https://woo.com/support-policy/) to see the extent of support we give. We highly advise anybody confused with child themes to use the [WordPress forums](https://wordpress.org/support/forums/) for help.

## Sample child theme

Download the sample child theme at the top of this article to get started. Place the child theme in your **wp-content/themes/** folder along with your parent theme.

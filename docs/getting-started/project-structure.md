---
post_title: Project structure
sidebar_label: Project structure
sidebar_position: 1
---

# Project Structure

## Anatomy of a WordPress development environment

While development environments can vary, the basic file structure for a WordPress environment should be consistent.

When developing a WooCommerce extension, you'll usually be doing most of your work within the `public_html/` directory of your local server. Take some time to familiarize yourself with a few key paths:

* `wp-content/debug.log` is the file where WordPress writes the important output such as errors and other messages that can be useful for debugging.  
* `wp-content/plugins`/ is the directory on the server where WordPress plugin folders live.  
* `wp-content/themes/` is the directory on the server where WordPress theme folders live.  
* `wp-config.php` is the WordPress environment file where you can declare configurations and private PHP constants
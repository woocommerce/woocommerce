---
post_title: Example WordPress plugin header comment for WooCommerce extensions
menu_title: Plugin header comments
tags: reference
---

This is a WordPress plugin header comment. It's used to provide WordPress with metadata about a plugin. 

```php
/**
* Plugin Name: WooCommerce Extension
* Plugin URI: https://woo.com/products/woocommerce-extension/
* Description: Your extension's description text.
* Version: 1.0.0
* Author: Your Name
* Author URI: http://yourdomain.com/
* Developer: Your Name
* Developer URI: http://yourdomain.com/
* Text Domain: woocommerce-extension
* Domain Path: /languages
*
* Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
* WC requires at least: 8.0
* WC tested up to: 8.3
*
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
```

Here's what each line should contain:

* Plugin Name: The name of your plugin.
* Plugin URI: The home page of the plugin.
* Description: A short description of the plugin.
* Version: The current version number of the plugin.
* Author: The name of the plugin author.
* Author URI: The author's website or profile page.
* Developer: The name of the developer if different from the author.
* Developer URI: The developer's website or profile page.
* Text Domain: The text domain is used for internationalization.
* Domain Path: The domain path is used to show where the MO files are located.
* Woo: A unique identifier for the plugin.
* WC requires at least: The minimum version of WooCommerce required for the plugin to work.
* WC tested up to: The latest version of WooCommerce that the plugin has been tested with.
* License: The license of the plugin.
* License URI: The URL where the license is explained in detail.

This header comment is placed at the top of the main plugin file, so WordPress can read it.

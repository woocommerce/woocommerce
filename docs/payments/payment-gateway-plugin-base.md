---
post_title: WooCommerce payment gateway plugin base
tags: code-snippet
---

This code can be used as a base to create your own simple custom payment gateway for WooCommerce. If not used in a custom plugin, you need to add this code to your child theme's functions.php file or via a plugin that allows custom functions to be added, such as the [Code snippets](https://wordpress.org/plugins/code-snippets/) plugin. Please don't add custom code directly to your parent theme's functions.php file as this will be wiped entirely when you update the theme.


```php
<?php
/*
Plugin Name: WooCommerce <enter name> Gateway
Plugin URI: https://woothemes.com/woocommerce
Description: Extends WooCommerce with an <enter name> gateway.
Version: 1.0
Author: WooThemes
Author URI: https://woothemes.com/
	Copyright: © 2009-2011 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
add_action('plugins_loaded', 'woocommerce_gateway_name_init', 0);
function woocommerce_gateway_name_init() {
	if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	/**
 	 * Localisation
	 */
	load_plugin_textdomain('wc-gateway-name', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
    
	/**
 	 * Gateway class
 	 */
	class WC_Gateway_Name extends WC_Payment_Gateway {
	
		// Go wild in here
	}
	
	/**
 	* Add the Gateway to WooCommerce
 	**/
	function woocommerce_add_gateway_name_gateway($methods) {
		$methods[] = 'WC_Gateway_Name';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_name_gateway' );
}
```

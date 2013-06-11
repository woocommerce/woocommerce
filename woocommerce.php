<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: http://www.woothemes.com/woocommerce/
 * Description: An e-commerce toolkit that helps you sell anything. Beautifully.
 * Version: 2.1-bleeding
 * Author: WooThemes
 * Author URI: http://woothemes.com
 * Requires at least: 3.5
 * Tested up to: 3.5
 *
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce
 * @category Core
 * @author WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce' ) ) {
	require_once( 'classes/class-woocommerce.php' );
	$GLOBALS['woocommerce'] = new WooCommerce();

}
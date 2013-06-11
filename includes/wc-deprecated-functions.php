<?php
/**
 * Deprecated functions
 *
 * Where functions come to die.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


function woocommerce_show_messages() {
	_deprecated_function( 'woocommerce_show_messages', '2.1', 'wc_print_messages' );
	wc_show_messages();
}
<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simplify Commerce Gateway for Subscriptions < 2.0
 *
 * @class 		WC_Addons_Gateway_Simplify_Commerce_Deprecated
 * @extends		WC_Addons_Gateway_Simplify_Commerce
 * @since       2.4.0
 * @version		1.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Addons_Gateway_Simplify_Commerce_Deprecated extends WC_Addons_Gateway_Simplify_Commerce {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}
}

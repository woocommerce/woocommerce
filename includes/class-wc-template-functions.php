# Hook to initialize the WC_Template_Functions class
<?php
/**
 * Class WC_Template_Functions.
 *
 * @package WooCommerce\Includes
 */

defined( 'ABSPATH' ) || exit;

class WC_Template_Functions {
	// ...

	public static function init_hooks() {
		add_action( 'woocommerce_init', array( 'WC_Template_Functions', 'init' ) );
	}
}

WC_Template_Functions::init_hooks();
<?php
/**
 * WooCommerce settings page example.
 *
 * @package  WooCommerce\Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings for API.
 */
if ( class_exists( 'WC_Settings_My_Example', false ) ) {
	return new WC_Settings_My_Example();
}

/**
 * WC_Settings_My_Example.
 */
class WC_Settings_My_Example extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'my-example';
		$this->label = __( 'My Example', 'woocommerce' );
		$this->is_modern = true;

		parent::__construct();
	}

    /**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			'dashboard'      => __( 'Dashboard', 'woocommerce' ),
			'tab-a' => __( 'Tab A', 'woocommerce' ),
			'tab-b' => __( 'Tab B', 'woocommerce' ),
		);
	}
}


return new WC_Settings_Site_Visibility();

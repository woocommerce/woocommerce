<?php
/**
 * WooCommerce Integrations class
 *
 * Loads Integrations into WooCommerce.
 *
 * @version 2.3.0
 * @package WooCommerce/Classes/Integrations
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integrations class.
 */
class WC_Integrations {

	/**
	 * Array of integrations.
	 *
	 * @var array
	 */
	public $integrations = array();

	/**
	 * Initialize integrations.
	 */
	public function __construct() {

		do_action( 'woocommerce_integrations_init' );

		$load_integrations = apply_filters( 'woocommerce_integrations', array() );

		// Load integration classes.
		foreach ( $load_integrations as $integration ) {

			$load_integration = new $integration();

			$this->integrations[ $load_integration->id ] = $load_integration;
		}
	}

	/**
	 * Return loaded integrations.
	 *
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}
}

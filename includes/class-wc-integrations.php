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

		$load_integrations = apply_filters( 'woocommerce_integrations', $this->get_default_integrations() );

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

	/**
	 * Fetches all of the default integrations.
	 *
	 * @return array
	 */
	private function get_default_integrations() {
		$default_integrations = array();

		include_once WC_ABSPATH . 'includes/integrations/maxmind-geolocation/class-wc-maxmind-geolocation-integration.php';
		$default_integrations[] = 'WC_MaxMind_Geolocation_Integration';

		return $default_integrations;
	}
}

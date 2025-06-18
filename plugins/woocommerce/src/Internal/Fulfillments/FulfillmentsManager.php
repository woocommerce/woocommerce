<?php
/**
 * WooCommerce Fulfillment Hooks
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

/**
 * FulfillmentsManager class.
 *
 * This class is responsible for adding hooks related to fulfillments in WooCommerce.
 *
 * @since 9.9.0
 * @package WooCommerce\Internal\Fulfillments
 */
class FulfillmentsManager {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'wc_fulfillment_shipping_providers', array( $this, 'get_initial_shipping_providers' ), 10, 1 );
		add_filter( 'wc_fulfillment_translate_meta_key', array( $this, 'translate_fulfillment_meta_key' ), 10, 1 );
	}

	/**
	 * Translate fulfillment meta keys.
	 *
	 * @param string $meta_key The meta key to translate.
	 * @return string Translated meta key.
	 */
	public function translate_fulfillment_meta_key( $meta_key ) {
		/**
		 * Filter to translate fulfillment meta keys.
		 *
		 * This filter allows us to translate fulfillment meta keys
		 * to make them more user-friendly in the admin interface and emails.
		 *
		 * @since 9.9.0
		 */
		$meta_key_translations = apply_filters(
			'wc_fulfillment_meta_key_translations',
			array(
				'fulfillment_status' => __( 'Fulfillment Status', 'woocommerce' ),
				'shipment_tracking'  => __( 'Shipment Tracking', 'woocommerce' ),
				'shipment_provider'  => __( 'Shipment Provider', 'woocommerce' ),
			)
		);
		return isset( $meta_key_translations[ $meta_key ] ) ? $meta_key_translations[ $meta_key ] : $meta_key;
	}

	/**
	 * Get initial shipping providers.
	 *
	 * This method provides the initial shipping providers that feeds the `wc_fulfillment_shipping_providers` filter,
	 * which is used to populate the list of available shipping providers on the fulfillment UI.
	 *
	 * @param array $shipping_providers The current list of shipping providers.
	 *
	 * @return array The modified list of shipping providers.
	 */
	public function get_initial_shipping_providers( $shipping_providers ) {
		if ( ! is_array( $shipping_providers ) ) {
			$shipping_providers = array();
		}

		$shipping_providers = array_merge(
			$shipping_providers,
			include __DIR__ . '/ShippingProviders.php'
		);

		return $shipping_providers;
	}
}

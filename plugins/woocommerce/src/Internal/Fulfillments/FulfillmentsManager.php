<?php
/**
 * WooCommerce Fulfillment Hooks
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use WooCommerce\Internal\Fulfillments\Providers\AbstractShippingProvider;

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
		add_filter( 'wc_fulfillment_parse_tracking_number', array( $this, 'try_parse_tracking_number' ), 10, 3 );

		$this->init_fulfillment_status_hooks();
	}

	/**
	 * Hook fulfillment status events.
	 *
	 * This method hooks into the fulfillment status events to update the order fulfillment status
	 * when a fulfillment is created, updated, or deleted.
	 */
	private function init_fulfillment_status_hooks() {
		// Update order fulfillment status when a fulfillment is created, updated, or deleted.
		add_action( 'wc_fulfillment_after_create', array( $this, 'update_order_fulfillment_status_on_fulfillment_update' ), 10, 1 );
		add_action( 'wc_fulfillment_after_update', array( $this, 'update_order_fulfillment_status_on_fulfillment_update' ), 10, 1 );
		add_action( 'wc_fulfillment_after_delete', array( $this, 'update_order_fulfillment_status_on_fulfillment_update' ), 10, 1 );
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

	/**
	 * Update order fulfillment status after a fulfillment is created, updated, or deleted.
	 *
	 * @param Fulfillment $data The fulfillment data.
	 */
	public function update_order_fulfillment_status_on_fulfillment_update( Fulfillment $data ) {
		if ( ! $data instanceof Fulfillment ) {
			return;
		}

		$order = $data->get_order();
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		/**
		 * Get the FulfillmentsDataStore instance.
		 *
		 * @var FulfillmentsDataStore $fulfillments_data_store
		 */
		$fulfillments_data_store = wc_get_container()->get( FulfillmentsDataStore::class );
		// Read all fulfillments for the order.
		$fulfillments = $fulfillments_data_store->read_fulfillments( \WC_Order::class, (string) $order->get_id() );

		// Update the fulfillment status of the order.
		$last_status = FulfillmentUtils::calculate_order_fulfillment_status( $order, $fulfillments );
		if ( 'no_fulfillments' === $last_status ) {
			$order->delete_meta_data( '_fulfillment_status' );
		} else {
			// Update the fulfillment status meta data.
			$order->update_meta_data( '_fulfillment_status', $last_status );
		}

		$order->save();
	}

	/**
	 * Try to parse the tracking number with additional parameters.
	 *
	 * @param string $tracking_number The tracking number.
	 * @param string $shipping_from The country code from which the shipment is sent.
	 * @param string $shipping_to The country code to which the shipment is sent.
	 *
	 * @return array|null An array containing the provider name and tracking URL if successful, null otherwise.
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): ?array {
		/**
		 * Filter to get the shipping provider classes.
		 *
		 * This filter allows us to retrieve the list of shipping provider classes that can parse tracking numbers.
		 *
		 * @since 9.9.0
		 */
		$shipping_providers = apply_filters( 'wc_fulfillment_shipping_providers', array() );
		foreach ( $shipping_providers as $provider ) {
			if ( class_exists( $provider ) && is_subclass_of( $provider, AbstractShippingProvider::class ) ) {
				/**
				 * Instantiate the shipping provider class.
				 *
				 * @var AbstractShippingProvider $provider_instance
				 */
				$provider_instance = new $provider();
			} else {
				continue; // Skip if the provider class does not exist or is not a valid shipping provider.
			}

				$tracking_url = $provider_instance->try_parse_tracking_number( $tracking_number, $shipping_from, $shipping_to );
			if ( ! is_null( $tracking_url ) ) {
				return array(
					'provider'     => $provider_instance->get_name(),
					'tracking_url' => $tracking_url,
				);
			}
		}

		// If no provider could parse the tracking number, return null.
		return null;
	}
}

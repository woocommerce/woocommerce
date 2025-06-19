<?php
/**
 * WooCommerce Fulfillment Hooks
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;

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
		// Update order fulfillment status when a fulfillment is created, updated, or deleted.
		add_action( 'wc_fulfillment_after_create', array( $this, 'update_order_fulfillment_status_on_fulfillment_update' ), 10, 1 );
		add_action( 'wc_fulfillment_after_update', array( $this, 'update_order_fulfillment_status_on_fulfillment_update' ), 10, 1 );
		add_action( 'wc_fulfillment_after_delete', array( $this, 'update_order_fulfillment_status_on_fulfillment_update' ), 10, 1 );
		// Update order fulfillment status when a new order is created.
		add_action( 'woocommerce_new_order', array( $this, 'update_order_fulfillment_status_on_new_order' ), 10, 1 );
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
		$order->update_meta_data( '_fulfillment_status', FulfillmentUtils::calculate_order_fulfillment_status( $order, $fulfillments ) );
		$order->save();
	}

	/**
	 * Update order fulfillment status when a new order is created.
	 *
	 * This method is called when a new order is created to ensure that the fulfillment status
	 * is set correctly for the order, even if no fulfillments are present at the time of creation.
	 *
	 * @param int $order_id The ID of the newly created order.
	 */
	public function update_order_fulfillment_status_on_new_order( int $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		// Initialize the fulfillment status to 'pending' if no fulfillments exist.
		$fulfillments_data_store = wc_get_container()->get( FulfillmentsDataStore::class );
		$fulfillments            = $fulfillments_data_store->read_fulfillments( \WC_Order::class, (string) $order->get_id() );

		if ( empty( $fulfillments ) ) {
			/**
			 * Filter to set the initial fulfillment status for new orders.
			 *
			 * @since 9.9.0
			 *
			 * @param string $status The initial fulfillment status.
			 */
			$order->update_meta_data( '_fulfillment_status', apply_filters( 'wc_get_initial_order_fulfillment_status', 'unfulfilled' ) );
			$order->save();
			return;
		}

		// Update the fulfillment status based on existing fulfillments.
		$order->update_meta_data( '_fulfillment_status', FulfillmentUtils::calculate_order_fulfillment_status( $order, $fulfillments ) );
		$order->save();
	}
}

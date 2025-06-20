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
	 * Get fulfillment statuses.
	 *
	 * @param string $status_key The key of the fulfillment status to retrieve.
	 *
	 * @return array An array of fulfillment statuses.
	 */
	public function get_fulfillment_status( string $status_key ): array {
		$core_fulfillment_statuses = array(
			'unfulfilled'         => array(
				'key'          => 'unfulfilled',
				'label'        => __( 'Unfulfilled', 'woocommerce' ),
				'is_fulfilled' => false,
			),
			'partially_fulfilled' => array(
				'key'          => 'partially_fulfilled',
				'label'        => __( 'Partially fulfilled', 'woocommerce' ),
				'is_fulfilled' => false,
			),
			'fulfilled'           => array(
				'key'          => 'fulfilled',
				'label'        => __( 'Fulfilled', 'woocommerce' ),
				'is_fulfilled' => true,
			),
			'no_fulfillments'     => array(
				'key'          => 'no_fulfillments',
				'label'        => __( 'No fulfillments', 'woocommerce' ),
				'is_fulfilled' => false,
			),
		);

		/**
		 * Filter to modify the list of default fulfillment statuses.
		 *
		 * This filter allows us to add or modify fulfillment statuses
		 * that can be used in the WooCommerce fulfillment system.
		 *
		 * @since 9.9.0
		 */
		$fulfillment_statuses = apply_filters( 'wc_custom_fulfillment_statuses', $core_fulfillment_statuses );

		// Ensure that the default statuses are always included.
		foreach ( $core_fulfillment_statuses as $key => $status ) {
			if ( ! isset( $fulfillment_statuses[ $key ] ) ) {
				$fulfillment_statuses[ $key ] = $status;
			}
		}

		return isset( $fulfillment_statuses[ $status_key ] ) ? $fulfillment_statuses[ $status_key ] : array(
			'key'          => 'unknown',
			'label'        => __( 'Unknown', 'woocommerce' ),
			'is_fulfilled' => false,
		);
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
}

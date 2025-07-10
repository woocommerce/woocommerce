<?php
/**
 * WooCommerce Fulfillment Hooks
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\Fulfillments\Providers\AbstractShippingProvider;

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
		add_filter( 'woocommerce_order_refunded', array( $this, 'update_fulfillments_after_refund' ), 10, 1 );
		add_filter( 'woocommerce_delete_order_refund', array( $this, 'update_fulfillment_status_after_refund_deleted' ), 10, 1 );

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

		ksort( $shipping_providers );

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

		$this->update_fulfillment_status( $order, $fulfillments );
	}

	/**
	 * Update fulfillment status after a refund is deleted.
	 *
	 * This method updates the fulfillment status after a refund is deleted to ensure that the fulfillment status
	 * and items are correctly adjusted based on the refund deletion.
	 *
	 * @param int $refund_id The ID of the refund being deleted.
	 *
	 * @return void
	 */
	public function update_fulfillment_status_after_refund_deleted( int $refund_id ): void {
		$refund = wc_get_order( $refund_id );
		if ( ! $refund instanceof \WC_Order ) {
			return; // If the refund is not a valid order, do nothing.
		}

		$order_id = $refund->get_parent_id();
		if ( ! $order_id ) {
			return; // If the refund does not have a parent order, do nothing.
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return; // If the order is not valid, do nothing.
		}

		$fulfillments_data_store = wc_get_container()->get( FulfillmentsDataStore::class );
		$fulfillments            = $fulfillments_data_store->read_fulfillments( \WC_Order::class, (string) $order_id );

		$this->update_fulfillment_status( $order, $fulfillments );
	}


	/**
	 * Update fulfillments after an order is refunded.
	 *
	 * This method updates the fulfillments after an order is refunded to ensure that the fulfillment status
	 * and items are correctly adjusted based on the refund.
	 *
	 * @param int $order_id The ID of the order being refunded.
	 *
	 * @return void
	 */
	public function update_fulfillments_after_refund( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$fulfillments_data_store = wc_get_container()->get( FulfillmentsDataStore::class );
		$fulfillments            = $fulfillments_data_store->read_fulfillments( \WC_Order::class, (string) $order_id );
		if ( empty( $fulfillments ) ) {
			return; // No fulfillments found for the order.
		}

		foreach ( $fulfillments as $fulfillment ) {
			if ( ! $fulfillment instanceof Fulfillment ) {
				continue; // Skip if the fulfillment is not an instance of Fulfillment.
			}

			if ( $fulfillment->get_is_fulfilled() ) {
				continue; // Skip if the fulfillment is already fulfilled.
			}

			// Get the items from the fulfillment.
			$items = $fulfillment->get_items();
			if ( empty( $items ) ) {
				continue; // Skip if there are no items in the fulfillment.
			}

			// Adjust the items based on the refund.
			$new_items = array();
			foreach ( $items as $item ) {
				if ( isset( $item['qty'] ) && isset( $item['item_id'] ) ) {
					$item['qty'] += $order->get_qty_refunded_for_item( $item['item_id'] );
					if ( $item['qty'] < 0 ) {
						$item['qty'] = 0; // Ensure quantity does not go negative.
					}
					$new_items[] = $item; // Add the adjusted item to the new items array.
				}
			}

			$new_items = array_filter(
				$new_items,
				function ( $item ) {
					return isset( $item['qty'] ) && $item['qty'] > 0; // Only keep items with a positive quantity.
				}
			);

			if ( empty( $new_items ) ) {
				// If no items remain after adjustment, delete the fulfillment.
				$fulfillment->delete();
			} else {
				// Update the fulfillment items with the new items.
				$fulfillment->set_items( $new_items );
				$fulfillment->save();
			}
		}

		$this->update_fulfillment_status( $order, $fulfillments );
	}

	/**
	 * Update the fulfillment status for the order.
	 *
	 * @param \WC_Order $order The order object.
	 * @param array     $fulfillments The fulfillments data store.
	 *
	 * This method updates the fulfillment status for the order based on the fulfillments data store.
	 */
	private function update_fulfillment_status( $order, $fulfillments = array() ) {
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
	 * @return array An array containing the provider as key, and the parsing results.
	 */
	public function try_parse_tracking_number( string $tracking_number, string $shipping_from, string $shipping_to ): array {
		// Validate the tracking number format and length.
		if ( ! is_string( $tracking_number ) || empty( $tracking_number ) || strlen( $tracking_number ) > 50 ) {
			$tracking_number = is_string( $tracking_number ) && ! empty( $tracking_number ) ? substr( $tracking_number, 0, 50 ) : '';
			return array(
				'tracking_number'   => $tracking_number,
				'shipping_provider' => '',
				'tracking_url'      => '',
			);
		}

		// Normalize the tracking number to uppercase.
		$tracking_number = strtoupper( $tracking_number );
		$tracking_number = preg_replace( '/[^A-Z0-9]/', '', $tracking_number ); // Remove non-alphanumeric characters.

		$shipping_providers = FulfillmentUtils::get_shipping_providers();
		$results            = array();
		foreach ( $shipping_providers as $provider ) {
			if ( class_exists( $provider ) && is_subclass_of( $provider, AbstractShippingProvider::class ) ) {
				try {
					/**
					 * Instantiate the shipping provider class.
					 *
					 * @var AbstractShippingProvider $provider_instance
					 */
					$provider_instance = wc_get_container()->get( $provider );
				} catch ( \Throwable $e ) {
					$logger = wc_get_logger();
					$logger->error(
						sprintf(
							'Error instantiating shipping provider class %s: %s',
							$provider,
							$e->getMessage()
						),
						array( 'source' => 'woocommerce-fulfillments' )
					);
					continue; // Skip if the provider class cannot be instantiated.
				}
			} else {
				continue; // Skip if the provider class does not exist or is not a valid shipping provider.
			}

			$parsing_result = $provider_instance->try_parse_tracking_number( $tracking_number, $shipping_from, $shipping_to );
			if ( ! is_null( $parsing_result ) ) {
				$results[ $provider_instance->get_key() ] = $parsing_result;
			}
		}

		if ( 1 === count( $results ) ) {
			$result  = reset( $results );
			$key     = key( $results );
			$results = array(
				'tracking_number'   => $tracking_number,
				'shipping_provider' => $key,
				'tracking_url'      => $result['url'] ?? '',
			);
		} elseif ( 1 < count( $results ) ) {
			// If multiple providers could parse the tracking number, find the one with the highest ambiguity score.
			$possibilities            = $results;
			$results                  = $this->get_best_parsing_result( $results, $tracking_number );
			$results['possibilities'] = $possibilities; // Include all possibilities for reference.
		}

		return $results;
	}

	/**
	 * Get the best parsing result from multiple results.
	 *
	 * This method finds the provider with the highest ambiguity score from the results.
	 *
	 * @param array  $results The results from multiple providers.
	 * @param string $tracking_number The tracking number being parsed.
	 *
	 * @return array The best parsing result.
	 */
	private function get_best_parsing_result( array $results, string $tracking_number ): array {
		$best_result   = null;
		$best_provider = '';
		$best_score    = 0;
		foreach ( $results as $provider_key => $result ) {
			if ( ! isset( $result['ambiguity_score'] ) || ! is_numeric( $result['ambiguity_score'] ) ) {
				continue; // Skip if ambiguity score is not set or not numeric.
			}

			if ( is_null( $best_result ) || $result['ambiguity_score'] > $best_score ) {
				$best_result   = $result;
				$best_provider = $provider_key;
				$best_score    = $result['ambiguity_score'];
			}
		}
		return is_null( $best_result ) ? array() : array(
			'tracking_number'   => $tracking_number,
			'shipping_provider' => $best_provider,
			'tracking_url'      => $best_result['url'],
		);
	}
}

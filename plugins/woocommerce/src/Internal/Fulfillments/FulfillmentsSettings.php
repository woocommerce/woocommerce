<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments;

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use WC_Order;

/**
 * FulfillmentsSettings class.
 */
class FulfillmentsSettings {

	/**
	 * Registers the hooks related to fulfillments settings.
	 */
	public function register() {
		add_filter( 'admin_init', array( $this, 'init_settings_auto_fulfill' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'auto_fulfill_items_on_processing' ), 10, 2 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'auto_fulfill_items_on_completed' ), 10, 2 );
	}

	/**
	 * Initialize settings for auto-fulfill options.
	 */
	public function init_settings_auto_fulfill() {
		add_filter( 'woocommerce_get_settings_products', array( $this, 'add_auto_fulfill_settings' ), 10, 2 );
	}

	/**
	 * Add auto-fulfill settings to the WooCommerce settings.
	 *
	 * @param array       $settings The existing settings.
	 * @param string|null $current_section The current section being viewed.
	 *
	 * @return array Modified settings with auto-fulfill options added.
	 */
	public function add_auto_fulfill_settings( array $settings, $current_section ): array {
		if ( ! empty( $current_section ) ) {
			return $settings;
		}

		$insertion_index = null;

		// Find the index of the sectionend for 'Shop pages'.
		foreach ( $settings as $index => $setting ) {
			if (
			isset( $setting['type'], $setting['id'] ) &&
			'sectionend' === $setting['type'] &&
			'catalog_options' === $setting['id'] // Woo core's ID for Shop pages section.
			) {
				$insertion_index = $index + 1; // Insert after the sectionend.
				break;
			}
		}

		if ( is_null( $insertion_index ) ) {
			return $settings; // fallback if not found.
		}

		$auto_fulfill_settings = array(
			array(
				'title' => 'Auto-fulfill items',
				'desc'  => '',
				'type'  => 'title',
				'id'    => 'auto_fulfill_options',
			),
			array(
				'title'         => 'Virtual and downloadable items',
				'desc'          => 'Automatically mark downloadable items as fulfilled when the order is created.',
				'id'            => 'auto_fulfill_downloadable',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'default'       => 'yes',
			),
			array(
				'title'         => 'Auto-fulfill items',
				'desc'          => 'Automatically mark virtual (non-downloadable) items as fulfilled when the order is created.',
				'id'            => 'auto_fulfill_virtual',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'default'       => 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'auto_fulfill_options',
			),
		);

		array_splice( $settings, $insertion_index, 0, $auto_fulfill_settings );

		return $settings;
	}

	/**
	 * Automatically fulfill items in the order on the processing state.
	 *
	 * @param int      $order_id The ID of the order being created.
	 * @param WC_Order $order The order object.
	 */
	public function auto_fulfill_items_on_processing( int $order_id, $order ): void {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );

		if ( ! $order || empty( $order->get_items() ) ) {
			return;
		}
		$auto_fulfill_downloadable = 'yes' === get_option( 'auto_fulfill_downloadable', 'yes' );
		$auto_fulfill_virtual      = 'yes' === get_option( 'auto_fulfill_virtual', 'no' );

		/**
		 * Filter to get the list of the item, or variant ID's that should be auto-fulfilled.
		 *
		 * @since 10.1.0
		 *
		 * @param array $auto_fulfill_items List of product or variant ID's to auto-fulfill.
		 * @param \WC_Order $order The order object.
		 *
		 * @return array Filtered list of product or variant ID's to auto-fulfill
		 */
		$auto_fulfill_product_ids = apply_filters( 'woocommerce_fulfillments_auto_fulfill_products', array(), $order );
		$auto_fulfill_items       = array();

		foreach ( $order->get_items() as $item ) {
			/**
			 * Get the product associated with the item.
			 *
			 * @var \WC_Order_Item_Product $item
			 * @var \WC_Product $product
			 */
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}

			if ( ( $product->is_downloadable() && $auto_fulfill_downloadable )
				|| ( $product->is_virtual() && $auto_fulfill_virtual )
				|| in_array( $product->get_id(), $auto_fulfill_product_ids, true ) ) {
				$auto_fulfill_items[] = $item;
			}
		}

		if ( ! empty( $auto_fulfill_items ) ) {
			$fulfillment = new Fulfillment();
			$fulfillment->set_entity_type( WC_Order::class );
			$fulfillment->set_entity_id( (string) $order_id );
			$fulfillment->set_status( 'fulfilled' );
			$fulfillment->set_items(
				array_map(
					function ( $item ) {
						return array(
							'item_id' => $item->get_id(),
							'qty'     => $item->get_quantity(),
						);
					},
					$auto_fulfill_items
				)
			);
			$fulfillment->save();
		}

		$order->update_meta_data( '_auto_fulfill_processed', true );
	}

	/**
	 * Automatically fulfill items in the order for orders that skip the processing state.
	 *
	 * @param int      $order_id The ID of the order being created.
	 * @param WC_Order $order The order object.
	 */
	public function auto_fulfill_items_on_completed( int $order_id, $order ): void {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
		if ( ! $order || empty( $order->get_items() ) ) {
			return;
		}

		// If auto-fulfill already processed, skip.
		if ( $order->get_meta( '_auto_fulfill_processed', true ) ) {
			return;
		}

		// If fulfillments already exist, skip auto-fulfillment.
		$fulfillments = wc_get_container()->get( FulfillmentsDataStore::class )->read_fulfillments( \WC_Order::class, (string) $order_id );
		if ( ! empty( $fulfillments ) ) {
			return;
		}

		// Auto-fulfill items.
		$this->auto_fulfill_items_on_processing( $order_id, $order );
	}
}

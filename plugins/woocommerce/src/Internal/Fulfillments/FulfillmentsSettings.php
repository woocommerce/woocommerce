<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Fulfillments;

use WC_Order;

/**
 * FulfillmentsSettings class.
 */
class FulfillmentsSettings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_get_settings_products', array( $this, 'add_auto_fulfill_settings' ), 10, 2 );
		add_action( 'woocommerce_new_order', array( $this, 'auto_fulfill_items' ), 10, 1 );

		$this->initialize_options();
	}

	/**
	 * Initialize settings options.
	 */
	private function initialize_options(): void {
		if ( ! get_option( 'auto_fulfill_downloadable', false ) ) {
			add_option( 'auto_fulfill_downloadable', 'yes' );
		}
		if ( ! get_option( 'auto_fulfill_virtual', false ) ) {
			add_option( 'auto_fulfill_virtual', 'no' );
		}
	}

	/**
	 * Add auto-fulfill settings to the WooCommerce settings.
	 *
	 * @param array  $settings The existing settings.
	 * @param string $current_section The current section being viewed.
	 *
	 * @return array Modified settings with auto-fulfill options added.
	 */
	public function add_auto_fulfill_settings( array $settings, string $current_section ): array {
		if ( '' !== $current_section ) {
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
	 * Automatically fulfill items in the order.
	 *
	 * @param int $order_id The ID of the order being created.
	 */
	public function auto_fulfill_items( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$auto_fulfill_downloadable = 'yes' === get_option( 'auto_fulfill_downloadable', 'yes' );
		$auto_fulfill_virtual      = 'yes' === get_option( 'auto_fulfill_virtual', 'no' );
		$auto_fulfill_items        = array();

		foreach ( $order->get_items() as $item_id => $item ) {
			$product = method_exists( $item, 'get_product' ) ? $item->get_product() : null;
			if ( ! $product ) {
				continue;
			}

			if ( ( $product->is_downloadable() && $auto_fulfill_downloadable ) || ( $product->is_virtual() && $auto_fulfill_virtual ) ) {
				// Fulfill downloadable items.
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
	}
}

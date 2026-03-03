<?php declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Fulfillments\Helpers;

use Automattic\WooCommerce\Internal\Fulfillments\Fulfillment;
use WC_Order;

/**
 * Helper class for creating and managing fulfillments in tests.
 *
 * This helper class should ONLY be used for unit tests!.
 *
 * @since 9.0.0
 */
class FulfillmentsHelper {
	/**
	 * Helper function to create a fulfillment.
	 *
	 * @param array $args Arguments to create the fulfillment.
	 * @param array $metadata Metadata to add to the fulfillment.
	 *
	 * @return Fulfillment The created fulfillment object.
	 */
	public static function create_fulfillment( array $args = array(), array $metadata = array() ) {
		$fulfillment = new Fulfillment();
		$fulfillment->set_props(
			array_merge(
				array(
					'id'           => 0,
					'entity_type'  => WC_Order::class,
					'entity_id'    => 123,
					'status'       => 'unfulfilled',
					'is_fulfilled' => false,
				),
				$args
			)
		);

		if ( $metadata ) {
			foreach ( $metadata as $key => $value ) {
				$fulfillment->add_meta_data(
					$key,
					$value,
					true
				);
			}
		} else {
			$fulfillment->add_meta_data(
				'test_meta_key',
				'test_meta_value',
				true
			);

			$fulfillment->set_items(
				array(
					array(
						'item_id' => 1,
						'qty'     => 2,
					),
					array(
						'item_id' => 2,
						'qty'     => 3,
					),
				)
			);
		}

		$fulfillment->save();

		return $fulfillment;
	}
}

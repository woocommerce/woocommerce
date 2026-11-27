<?php
/**
 * TestOrderStatusAbility class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\ActionableAbility;
use Automattic\WooCommerce\Abilities\WCDataSaveTrait;

/**
 * An extension's write ability that changes an order status.
 */
class TestOrderStatusAbility extends ActionableAbility {

	use WCDataSaveTrait;

	/**
	 * Resource that the ability changes.
	 *
	 * @return string
	 */
	public function get_resource(): string {
		return 'order';
	}

	/**
	 * Load the order.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order|\WP_Error
	 */
	protected function load( array $input ) {
		$order = wc_get_order( $input['id'] );
		return $order ? $order : new \WP_Error( 'test_not_found', 'Not found.', array( 'status' => 404 ) );
	}

	/**
	 * Set the status in memory.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null
	 */
	protected function change( $subject, array $input ) {
		$subject->set_status( $input['status'] );
		return null;
	}

	/**
	 * The ID of an order, or zero when it is not saved yet.
	 *
	 * @param \WC_Order $subject An order.
	 * @return int
	 */
	protected static function get_object_id( $subject ): int {
		return $subject->get_id();
	}

	/**
	 * The type of the object, which the `object_types` of a field are compared with.
	 *
	 * @param \WC_Order $subject An order.
	 * @return string
	 */
	protected static function get_object_type( $subject ): string {
		return $subject->get_type();
	}

	/**
	 * The saved order status.
	 *
	 * @param \WC_Order $subject Saved order.
	 * @return array
	 */
	protected function prepare_response( $subject ) {
		return array( 'status' => $subject->get_status() );
	}
}

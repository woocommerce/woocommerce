<?php
/**
 * TestOrderStatusAbility class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\ActionableAbility;

/**
 * An extension's write ability that changes an order status.
 */
class TestOrderStatusAbility extends ActionableAbility {

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
	public function load( array $input ) {
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
	public function change( $subject, array $input ) {
		$subject->set_status( $input['status'] );
		return null;
	}

	/**
	 * The saved order status.
	 *
	 * @param \WC_Order $subject Saved order.
	 * @return array
	 */
	public function prepare_response( $subject ) {
		return array( 'status' => $subject->get_status() );
	}
}

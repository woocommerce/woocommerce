<?php
/**
 * TestCancelSubscriptionAbility class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

use Automattic\WooCommerce\Abilities\ActionableAbility;

/**
 * An extension's own ability that cancels a subscription.
 */
class TestCancelSubscriptionAbility extends ActionableAbility {

	/**
	 * Object type.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return 'subscription';
	}

	/**
	 * Load the subscription.
	 *
	 * @param array $input Ability input.
	 * @return \WC_Order
	 */
	public function load( array $input ) {
		return wc_get_order( $input['id'] );
	}

	/**
	 * Cancel the subscription.
	 *
	 * @param \WC_Order $subject Subscription.
	 * @param array     $input   Ability input.
	 * @return null
	 */
	public function change( $subject, array $input ) {
		$subject->set_status( 'cancelled' );
		return null;
	}

	/**
	 * Output for the saved subscription.
	 *
	 * @param \WC_Order $subject Subscription.
	 * @return array
	 */
	public function prepare_response( $subject ) {
		return array( 'status' => $subject->get_status() );
	}
}

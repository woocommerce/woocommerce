<?php
/**
 * TestOrderStageAbility class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * An extension's write ability that stages the payment of an order.
 */
class TestOrderStageAbility extends TestOrderStatusAbility {

	/**
	 * Stage the payment.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return null
	 */
	public function change( $subject, array $input ) {
		$subject->stage_payment_complete();
		return null;
	}
}

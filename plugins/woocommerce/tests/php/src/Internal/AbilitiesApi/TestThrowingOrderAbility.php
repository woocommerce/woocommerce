<?php
/**
 * TestThrowingOrderAbility class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\AbilitiesApi;

/**
 * An extension's write ability whose change step throws.
 */
class TestThrowingOrderAbility extends TestOrderStatusAbility {

	/**
	 * Throw an internal exception.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return never
	 * @throws \RuntimeException Always.
	 */
	protected function change( $subject, array $input ) {
		throw new \RuntimeException( 'Ledger table is locked.' );
	}
}

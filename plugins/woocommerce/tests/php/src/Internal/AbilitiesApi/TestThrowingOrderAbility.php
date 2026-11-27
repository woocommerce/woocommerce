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
	 * Failure to throw during the change step.
	 *
	 * @var \Throwable
	 */
	public $failure;

	/**
	 * Throw the configured failure.
	 *
	 * @param \WC_Order $subject Order.
	 * @param array     $input   Ability input.
	 * @return never
	 * @throws \Throwable Configured failure.
	 */
	protected function change( $subject, array $input ) {
		throw $this->failure;
	}
}

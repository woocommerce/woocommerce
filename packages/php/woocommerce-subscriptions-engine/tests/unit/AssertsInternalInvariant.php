<?php
/**
 * Assertion helper for internal invariants.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit;

use LogicException;

/**
 * Asserts that an operation breaks an internal invariant: a plain LogicException, not a
 * validation error (which is a LogicException subclass too).
 */
trait AssertsInternalInvariant {

	/**
	 * Run the operation and assert it throws exactly a LogicException.
	 *
	 * @param callable $operation Operation expected to throw.
	 * @return LogicException The caught exception, for further assertions.
	 */
	protected function assert_internal_invariant( callable $operation ): LogicException {
		try {
			$operation();
		} catch ( LogicException $e ) {
			$this->assertSame( LogicException::class, get_class( $e ), 'An internal invariant throws a plain LogicException.' );
			return $e;
		}

		$this->fail( 'Expected a LogicException.' );
	}
}

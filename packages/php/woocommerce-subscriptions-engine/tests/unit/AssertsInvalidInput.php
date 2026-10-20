<?php
/**
 * Assertion helper for Core validation errors.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;

/**
 * Asserts that an operation is refused with a given code, field and params.
 */
trait AssertsInvalidInput {

	/**
	 * Run the operation and assert it throws an InvalidInputException with the given code, field and params.
	 *
	 * @param callable             $operation Operation expected to throw.
	 * @param string               $code      Expected error code.
	 * @param string               $field     Expected field.
	 * @param array<string, mixed> $params    Expected params.
	 * @return InvalidInputException The caught exception, for further assertions.
	 */
	protected function assert_invalid_input( callable $operation, string $code, string $field, array $params = array() ): InvalidInputException {
		try {
			$operation();
		} catch ( InvalidInputException $e ) {
			$this->assertSame( $code, $e->get_error_code(), 'Error code' );
			$this->assertSame( $field, $e->get_field(), 'Field' );
			$this->assertSame( $params, $e->get_params(), 'Params' );
			return $e;
		}

		$this->fail( sprintf( 'Expected an InvalidInputException with code "%s" on "%s".', $code, $field ) );
	}
}

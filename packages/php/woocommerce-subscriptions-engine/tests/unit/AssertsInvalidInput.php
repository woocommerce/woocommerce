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
 * Asserts that an operation is refused with given codes, fields and params.
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

	/**
	 * Run the operation and assert it throws an InvalidInputException carrying exactly the
	 * expected errors, in order. Each expected entry is `array( code, field )`, or
	 * `array( code, field, params )` to compare the params too.
	 *
	 * @param callable          $operation Operation expected to throw.
	 * @param array<int, mixed> $expected  Expected errors.
	 * @return InvalidInputException The caught exception, for further assertions.
	 */
	protected function assert_invalid_inputs( callable $operation, array $expected ): InvalidInputException {
		try {
			$operation();
		} catch ( InvalidInputException $e ) {
			$this->assertSame( $expected, self::describe_errors( $e, $expected ), 'Errors' );
			return $e;
		}

		$this->fail( sprintf( 'Expected an InvalidInputException with %d errors.', count( $expected ) ) );
	}

	/**
	 * The exception's errors in the expected entries' shape.
	 *
	 * @param InvalidInputException $e        Caught exception.
	 * @param array<int, mixed>     $expected Expected errors, for which entries compare params.
	 * @return array<int, array<int, mixed>>
	 */
	private static function describe_errors( InvalidInputException $e, array $expected ): array {
		$actual = array();
		foreach ( $e->get_errors() as $index => $error ) {
			$entry = array( $error->get_error_code(), $error->get_field() );
			if ( isset( $expected[ $index ] ) && is_array( $expected[ $index ] ) && array_key_exists( 2, $expected[ $index ] ) ) {
				$entry[] = $error->get_params();
			}
			$actual[] = $entry;
		}

		return $actual;
	}
}

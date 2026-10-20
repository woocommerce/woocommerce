<?php
/**
 * Assertion helper for facade validation errors.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration;

use WP_Error;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ValidationException;

/**
 * Asserts that a facade call is refused with given codes, fields and params.
 */
trait AssertsValidationErrors {

	/**
	 * Run the operation and assert it throws a ValidationException carrying exactly the
	 * expected errors, in `WP_Error` order (codes in first-seen order, then each code's
	 * errors in addition order). Each expected entry is `array( code, field )`, or
	 * `array( code, field, params )` to compare the params too; a fieldless error has a
	 * null field.
	 *
	 * @param callable          $operation Operation expected to throw.
	 * @param array<int, mixed> $expected  Expected errors.
	 * @return ValidationException The caught exception, for further assertions.
	 */
	protected function assert_validation_errors( callable $operation, array $expected ): ValidationException {
		try {
			$operation();
		} catch ( ValidationException $e ) {
			$this->assertSame( $expected, self::describe_validation_errors( $e->get_errors(), $expected ), 'Errors' );
			return $e;
		}

		$this->fail( sprintf( 'Expected a ValidationException with %d errors.', count( $expected ) ) );
	}

	/**
	 * The errors in the expected entries' shape, each message paired with its own data.
	 *
	 * @param WP_Error          $errors   Validation errors.
	 * @param array<int, mixed> $expected Expected errors, for which entries compare params.
	 * @return array<int, array<int, mixed>>
	 */
	private static function describe_validation_errors( WP_Error $errors, array $expected ): array {
		$actual = array();
		foreach ( $errors->get_error_codes() as $code ) {
			$all_data = $errors->get_all_error_data( $code );
			foreach ( $errors->get_error_messages( $code ) as $index => $message ) {
				self::assertNotSame( '', $message, sprintf( 'An error "%s" has no message.', $code ) );

				$data  = $all_data[ $index ] ?? null;
				$entry = array( $code, is_array( $data ) && isset( $data['field'] ) ? $data['field'] : null );
				$slot  = $expected[ count( $actual ) ] ?? null;
				if ( is_array( $slot ) && array_key_exists( 2, $slot ) ) {
					$entry[] = is_array( $data ) && isset( $data['params'] ) ? $data['params'] : null;
				}
				$actual[] = $entry;
			}
		}

		return $actual;
	}
}

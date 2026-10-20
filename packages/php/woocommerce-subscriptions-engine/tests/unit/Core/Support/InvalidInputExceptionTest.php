<?php
/**
 * Unit tests for the Core validation exception.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Support;

use DomainException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException
 */
class InvalidInputExceptionTest extends TestCase {

	/**
	 * @testdox The getters return the constructor values and the message stays English.
	 */
	public function test_getters_return_the_constructor_values(): void {
		$e = new InvalidInputException(
			ValidationErrorCode::OUT_OF_BOUNDS,
			'trial_duration[length]',
			'BillingPolicy: trial length must be positive.',
			array( 'min' => 1 )
		);

		$this->assertInstanceOf( DomainException::class, $e );
		$this->assertSame( 'out_of_bounds', $e->get_error_code() );
		$this->assertSame( 'trial_duration[length]', $e->get_field() );
		$this->assertSame( array( 'min' => 1 ), $e->get_params() );
		$this->assertSame( 'BillingPolicy: trial length must be positive.', $e->getMessage() );
	}

	/**
	 * @testdox Params default to an empty array.
	 */
	public function test_params_default_to_empty(): void {
		$e = new InvalidInputException( ValidationErrorCode::REQUIRED, 'name', 'Plan: name is required.' );

		$this->assertSame( array(), $e->get_params() );
	}
}

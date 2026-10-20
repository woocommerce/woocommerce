<?php
/**
 * Unit tests for the Core validation exception.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Unit\Core\Support;

use DomainException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ValidationErrorCode;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Core\Support\InvalidInputException
 */
class InvalidInputExceptionTest extends TestCase {

	/**
	 * @testdox The getters return the constructor values and the message stays English.
	 *
	 * @throws InvalidInputException Thrown and caught as a DomainException.
	 */
	public function test_getters_return_the_constructor_values(): void {
		try {
			throw new InvalidInputException(
				ValidationErrorCode::OUT_OF_BOUNDS,
				'trial_duration.length',
				'BillingPolicy: trial length must be positive.',
				array( 'min' => 1 )
			);
		} catch ( DomainException $caught ) {
			$e = $caught;
		}

		$this->assertInstanceOf( InvalidInputException::class, $e, 'Callers catching DomainException keep working.' );
		$this->assertSame( 'out_of_bounds', $e->get_error_code() );
		$this->assertSame( 'trial_duration.length', $e->get_field() );
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

	/**
	 * @testdox A single exception lists itself as its only error.
	 */
	public function test_a_single_exception_lists_itself(): void {
		$e = new InvalidInputException( ValidationErrorCode::REQUIRED, 'name', 'Plan: name is required.' );

		$this->assertSame( array( $e ), $e->get_errors() );
	}

	/**
	 * @testdox from_errors returns a single error as is.
	 */
	public function test_from_errors_returns_a_single_error_as_is(): void {
		$e = new InvalidInputException( ValidationErrorCode::REQUIRED, 'name', 'Plan: name is required.' );

		$this->assertSame( $e, InvalidInputException::from_errors( array( $e ) ) );
	}

	/**
	 * @testdox from_errors keeps the first error's getters, joins the messages and lists every error in order.
	 */
	public function test_from_errors_combines_several_errors(): void {
		$name     = new InvalidInputException( ValidationErrorCode::REQUIRED, 'name', 'Plan: name is required.' );
		$interval = new InvalidInputException( ValidationErrorCode::OUT_OF_BOUNDS, 'interval', 'Interval too low.', array( 'min' => 1 ) );

		$e = InvalidInputException::from_errors( array( $name, $interval ) );

		$this->assertSame( ValidationErrorCode::REQUIRED, $e->get_error_code() );
		$this->assertSame( 'name', $e->get_field() );
		$this->assertSame( array(), $e->get_params() );
		$this->assertSame( 'Plan: name is required. Interval too low.', $e->getMessage() );
		$this->assertSame( array( $name, $interval ), $e->get_errors() );
	}

	/**
	 * @testdox from_errors flattens a combined error.
	 */
	public function test_from_errors_flattens_a_combined_error(): void {
		$a = new InvalidInputException( ValidationErrorCode::REQUIRED, 'a', 'A.' );
		$b = new InvalidInputException( ValidationErrorCode::REQUIRED, 'b', 'B.' );
		$c = new InvalidInputException( ValidationErrorCode::REQUIRED, 'c', 'C.' );

		$e = InvalidInputException::from_errors( array( InvalidInputException::from_errors( array( $a, $b ) ), $c ) );

		$this->assertSame( array( $a, $b, $c ), $e->get_errors() );
		$this->assertSame( 'A. B. C.', $e->getMessage() );
	}

	/**
	 * @testdox from_errors refuses an empty list as an engine bug.
	 */
	public function test_from_errors_refuses_an_empty_list(): void {
		try {
			InvalidInputException::from_errors( array() );
			$this->fail( 'Expected a LogicException.' );
		} catch ( LogicException $e ) {
			$this->assertSame( LogicException::class, get_class( $e ) );
		}
	}
}

<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Queries\Coupons;

use Automattic\WooCommerce\Api\Queries\Coupons\GetCoupon;
use WC_Helper_Coupon;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see GetCoupon}.
 */
class GetCouponTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var GetCoupon
	 */
	private GetCoupon $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new GetCoupon();
	}

	/**
	 * @testdox execute() throws InvalidArgumentException when neither id nor code is provided.
	 */
	public function test_execute_rejects_missing_arguments(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Exactly one of "id" or "code" must be provided.' );

		$this->sut->execute();
	}

	/**
	 * @testdox execute() throws InvalidArgumentException when both id and code are provided.
	 */
	public function test_execute_rejects_both_arguments(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Exactly one of "id" or "code" must be provided.' );

		$this->sut->execute( id: 1, code: 'something' );
	}

	/**
	 * @testdox execute() returns the mapped Coupon DTO for a valid ID.
	 */
	public function test_execute_returns_coupon_for_valid_id(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'happy-friday' );

		$result = $this->sut->execute( id: $coupon->get_id() );

		$this->assertIsObject( $result );
		$this->assertSame( $coupon->get_id(), $result->id );
		$this->assertSame( 'happy-friday', $result->code );
	}

	/**
	 * @testdox execute() returns the mapped Coupon DTO for a valid code.
	 */
	public function test_execute_returns_coupon_for_valid_code(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'spring-sale' );

		$result = $this->sut->execute( code: 'spring-sale' );

		$this->assertIsObject( $result );
		$this->assertSame( $coupon->get_id(), $result->id );
		$this->assertSame( 'spring-sale', $result->code );
	}

	/**
	 * @testdox execute() returns null when the ID does not exist.
	 */
	public function test_execute_returns_null_for_missing_id(): void {
		$this->assertNull( $this->sut->execute( id: 999999 ) );
	}

	/**
	 * @testdox execute() returns null when the code does not exist.
	 */
	public function test_execute_returns_null_for_missing_code(): void {
		$this->assertNull( $this->sut->execute( code: 'does-not-exist' ) );
	}
}

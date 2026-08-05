<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Mutations\Coupons;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Mutations\Coupons\DeleteCoupon;
use Automattic\WooCommerce\Api\Types\Coupons\DeleteCouponResult;
use WC_Helper_Coupon;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see DeleteCoupon}.
 */
class DeleteCouponTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var DeleteCoupon
	 */
	private DeleteCoupon $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new DeleteCoupon();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_pre_delete_data' );
		parent::tearDown();
	}

	/**
	 * @testdox execute() throws NOT_FOUND when the coupon ID does not exist.
	 */
	public function test_execute_throws_not_found_for_missing_coupon(): void {
		try {
			$this->sut->execute( 999999 );
			$this->fail( 'Expected ApiException was not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'Coupon not found.', $e->getMessage() );
			$this->assertSame( 'NOT_FOUND', $e->getErrorCode() );
			$this->assertSame( 404, $e->getStatusCode() );
		}
	}

	/**
	 * @testdox execute() trashes the coupon when force=false and returns DeleteCouponResult.
	 */
	public function test_execute_trashes_coupon_without_force(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'to-trash' );
		$id     = $coupon->get_id();

		$result = $this->sut->execute( $id, false );

		$this->assertInstanceOf( DeleteCouponResult::class, $result );
		$this->assertSame( $id, $result->id );
		$this->assertTrue( $result->deleted );
		$this->assertSame( 'trash', get_post_status( $id ) );
	}

	/**
	 * @testdox execute() permanently deletes the coupon when force=true.
	 */
	public function test_execute_force_deletes_coupon(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'to-delete' );
		$id     = $coupon->get_id();

		$result = $this->sut->execute( $id, true );

		$this->assertInstanceOf( DeleteCouponResult::class, $result );
		$this->assertSame( $id, $result->id );
		$this->assertTrue( $result->deleted );
		$this->assertNull( get_post( $id ) );
	}

	/**
	 * @testdox execute() defaults to non-force deletion (trash).
	 */
	public function test_execute_defaults_to_trash(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'default-trash' );
		$id     = $coupon->get_id();

		$result = $this->sut->execute( $id );

		$this->assertInstanceOf( DeleteCouponResult::class, $result );
		$this->assertSame( $id, $result->id );
		$this->assertTrue( $result->deleted );
		$this->assertSame( 'trash', get_post_status( $id ) );
	}

	/**
	 * @testdox execute() returns deleted=false when woocommerce_pre_delete_data short-circuits to false.
	 */
	public function test_execute_returns_false_when_pre_delete_filter_returns_false(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'kept' );
		$id     = $coupon->get_id();

		add_filter( 'woocommerce_pre_delete_data', '__return_false' );

		$result = $this->sut->execute( $id, true );

		$this->assertInstanceOf( DeleteCouponResult::class, $result );
		$this->assertSame( $id, $result->id );
		$this->assertFalse( $result->deleted );
	}

	/**
	 * @testdox execute() surfaces a WP_Error from woocommerce_pre_delete_data as an INTERNAL_ERROR ApiException.
	 */
	public function test_execute_translates_wp_error_to_api_exception(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'failing' );

		add_filter(
			'woocommerce_pre_delete_data',
			static function () {
				return new \WP_Error( 'wc_delete_failed', 'Coupon delete failed.' );
			}
		);

		try {
			$this->sut->execute( $coupon->get_id(), true );
			$this->fail( 'Expected ApiException was not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'Coupon delete failed.', $e->getMessage() );
			$this->assertSame( 'INTERNAL_ERROR', $e->getErrorCode() );
			$this->assertSame( 500, $e->getStatusCode() );
		}
	}
}

<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Mutations\Coupons;

use Automattic\WooCommerce\Api\ApiException;
use Automattic\WooCommerce\Api\Enums\Coupons\CouponStatus;
use Automattic\WooCommerce\Api\Enums\Coupons\DiscountType;
use Automattic\WooCommerce\Api\InputTypes\Coupons\UpdateCouponInput;
use Automattic\WooCommerce\Api\Mutations\Coupons\UpdateCoupon;
use WC_Coupon;
use WC_Helper_Coupon;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see UpdateCoupon}.
 */
class UpdateCouponTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var UpdateCoupon
	 */
	private UpdateCoupon $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new UpdateCoupon();
	}

	/**
	 * @testdox execute() throws NOT_FOUND when the coupon ID does not exist.
	 */
	public function test_execute_throws_not_found_for_missing_coupon(): void {
		$input     = new UpdateCouponInput();
		$input->id = 999999;

		try {
			$this->sut->execute( $input );
			$this->fail( 'Expected ApiException was not thrown.' );
		} catch ( ApiException $e ) {
			$this->assertSame( 'Coupon not found.', $e->getMessage() );
			$this->assertSame( 'NOT_FOUND', $e->getErrorCode() );
			$this->assertSame( 404, $e->getStatusCode() );
		}
	}

	/**
	 * @testdox execute() updates only fields that were marked provided.
	 */
	public function test_execute_updates_only_provided_fields(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'original-code' );
		$coupon->set_description( 'Original description.' );
		$coupon->save();

		$input              = new UpdateCouponInput();
		$input->id          = $coupon->get_id();
		$input->description = 'Updated description.';
		$input->mark_provided( 'description' );

		$this->sut->execute( $input );

		$reloaded = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( 'Updated description.', $reloaded->get_description() );
		$this->assertSame( 'original-code', $reloaded->get_code() );
	}

	/**
	 * @testdox execute() does not touch fields that were not marked provided, even if set on the DTO.
	 */
	public function test_execute_ignores_fields_not_marked_provided(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'original-code' );

		$input       = new UpdateCouponInput();
		$input->id   = $coupon->get_id();
		$input->code = 'should-not-apply';

		$this->sut->execute( $input );

		$reloaded = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( 'original-code', $reloaded->get_code() );
	}

	/**
	 * @testdox execute() applies a non-null discount_type enum.
	 */
	public function test_execute_applies_discount_type_enum(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'fixed-cart-coupon' );

		$input                = new UpdateCouponInput();
		$input->id            = $coupon->get_id();
		$input->discount_type = DiscountType::Percent;
		$input->mark_provided( 'discount_type' );

		$this->sut->execute( $input );

		$reloaded = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( 'percent', $reloaded->get_discount_type() );
	}

	/**
	 * @testdox execute() skips set_discount_type() when discount_type is provided as null.
	 */
	public function test_execute_skips_discount_type_when_provided_null(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'fixed-cart-coupon' );

		$input                = new UpdateCouponInput();
		$input->id            = $coupon->get_id();
		$input->discount_type = null;
		$input->mark_provided( 'discount_type' );

		$this->sut->execute( $input );

		$reloaded = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( 'fixed_cart', $reloaded->get_discount_type() );
	}

	/**
	 * @testdox execute() applies a non-null status enum.
	 */
	public function test_execute_applies_status_enum(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'status-coupon' );

		$input         = new UpdateCouponInput();
		$input->id     = $coupon->get_id();
		$input->status = CouponStatus::Draft;
		$input->mark_provided( 'status' );

		$this->sut->execute( $input );

		$reloaded = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( 'draft', $reloaded->get_status() );
	}

	/**
	 * @testdox execute() skips set_status() when status is provided as null.
	 */
	public function test_execute_skips_status_when_provided_null(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'status-coupon' );

		$input         = new UpdateCouponInput();
		$input->id     = $coupon->get_id();
		$input->status = null;
		$input->mark_provided( 'status' );

		$this->sut->execute( $input );

		$reloaded = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( 'publish', $reloaded->get_status() );
	}

	/**
	 * @testdox execute() updates scalar amount fields when marked provided.
	 */
	public function test_execute_updates_amount(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'amount-coupon' );

		$input         = new UpdateCouponInput();
		$input->id     = $coupon->get_id();
		$input->amount = 25.5;
		$input->mark_provided( 'amount' );

		$this->sut->execute( $input );

		$reloaded = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( '25.5', $reloaded->get_amount() );
	}

	/**
	 * @testdox execute() updates array fields when marked provided.
	 */
	public function test_execute_updates_array_fields(): void {
		$coupon = WC_Helper_Coupon::create_coupon( 'array-update-coupon' );

		$input                              = new UpdateCouponInput();
		$input->id                          = $coupon->get_id();
		$input->product_ids                 = array( 100, 200 );
		$input->excluded_product_ids        = array( 300 );
		$input->product_categories          = array( 4 );
		$input->excluded_product_categories = array( 5, 6 );
		$input->email_restrictions          = array( 'a@example.com' );
		$input->mark_provided( 'product_ids' );
		$input->mark_provided( 'excluded_product_ids' );
		$input->mark_provided( 'product_categories' );
		$input->mark_provided( 'excluded_product_categories' );
		$input->mark_provided( 'email_restrictions' );

		$this->sut->execute( $input );

		$reloaded = new WC_Coupon( $coupon->get_id() );
		$this->assertSame( array( 100, 200 ), $reloaded->get_product_ids() );
		$this->assertSame( array( 300 ), $reloaded->get_excluded_product_ids() );
		$this->assertSame( array( 4 ), $reloaded->get_product_categories() );
		$this->assertSame( array( 5, 6 ), $reloaded->get_excluded_product_categories() );
		$this->assertSame( array( 'a@example.com' ), $reloaded->get_email_restrictions() );
	}
}

<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Api\Mutations\Coupons;

use Automattic\WooCommerce\Api\Enums\Coupons\CouponStatus;
use Automattic\WooCommerce\Api\Enums\Coupons\DiscountType;
use Automattic\WooCommerce\Api\InputTypes\Coupons\CreateCouponInput;
use Automattic\WooCommerce\Api\Mutations\Coupons\CreateCoupon;
use WC_Coupon;
use WC_Unit_Test_Case;

/**
 * Unit tests for {@see CreateCoupon}.
 */
class CreateCouponTest extends WC_Unit_Test_Case {
	/**
	 * The system under test.
	 *
	 * @var CreateCoupon
	 */
	private CreateCoupon $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CreateCoupon();
	}

	/**
	 * @testdox execute() creates a coupon with the given code and returns its DTO.
	 */
	public function test_execute_creates_coupon_with_required_fields(): void {
		$input       = new CreateCouponInput();
		$input->code = 'welcome-2026';

		$result = $this->sut->execute( $input );

		$this->assertIsObject( $result );
		$this->assertSame( 'welcome-2026', $result->code );
		$this->assertGreaterThan( 0, $result->id );

		$wc_coupon = new WC_Coupon( $result->id );
		$this->assertSame( 'welcome-2026', $wc_coupon->get_code() );
	}

	/**
	 * @testdox execute() persists optional scalar fields when provided.
	 */
	public function test_execute_persists_optional_scalar_fields(): void {
		$input                         = new CreateCouponInput();
		$input->code                   = 'spring-sale';
		$input->description            = 'Spring sale discount.';
		$input->amount                 = 15.5;
		$input->individual_use         = true;
		$input->usage_limit            = 100;
		$input->usage_limit_per_user   = 1;
		$input->limit_usage_to_x_items = 3;
		$input->free_shipping          = true;
		$input->exclude_sale_items     = true;
		$input->minimum_amount         = 20.0;
		$input->maximum_amount         = 200.0;
		$input->date_expires           = '2026-12-31T23:59:59+00:00';

		$result = $this->sut->execute( $input );

		$wc_coupon = new WC_Coupon( $result->id );
		$this->assertSame( 'Spring sale discount.', $wc_coupon->get_description() );
		$this->assertSame( '15.5', $wc_coupon->get_amount() );
		$this->assertTrue( $wc_coupon->get_individual_use() );
		$this->assertSame( 100, $wc_coupon->get_usage_limit() );
		$this->assertSame( 1, $wc_coupon->get_usage_limit_per_user() );
		$this->assertSame( 3, $wc_coupon->get_limit_usage_to_x_items() );
		$this->assertTrue( $wc_coupon->get_free_shipping() );
		$this->assertTrue( $wc_coupon->get_exclude_sale_items() );
		$this->assertSame( '20', $wc_coupon->get_minimum_amount() );
		$this->assertSame( '200', $wc_coupon->get_maximum_amount() );
		$this->assertSame( '2026-12-31', $wc_coupon->get_date_expires()->format( 'Y-m-d' ) );
	}

	/**
	 * @testdox execute() persists array fields (product/category IDs, email restrictions).
	 */
	public function test_execute_persists_array_fields(): void {
		$input                              = new CreateCouponInput();
		$input->code                        = 'array-coupon';
		$input->product_ids                 = array( 10, 20 );
		$input->excluded_product_ids        = array( 30 );
		$input->product_categories          = array( 1 );
		$input->excluded_product_categories = array( 2, 3 );
		$input->email_restrictions          = array( 'foo@example.com', 'bar@example.com' );

		$result = $this->sut->execute( $input );

		$wc_coupon = new WC_Coupon( $result->id );
		$this->assertSame( array( 10, 20 ), $wc_coupon->get_product_ids() );
		$this->assertSame( array( 30 ), $wc_coupon->get_excluded_product_ids() );
		$this->assertSame( array( 1 ), $wc_coupon->get_product_categories() );
		$this->assertSame( array( 2, 3 ), $wc_coupon->get_excluded_product_categories() );
		$this->assertSame( array( 'foo@example.com', 'bar@example.com' ), $wc_coupon->get_email_restrictions() );
	}

	/**
	 * @testdox execute() applies the discount_type enum when provided.
	 */
	public function test_execute_applies_discount_type_enum(): void {
		$input                = new CreateCouponInput();
		$input->code          = 'percent-off';
		$input->discount_type = DiscountType::Percent;

		$result = $this->sut->execute( $input );

		$wc_coupon = new WC_Coupon( $result->id );
		$this->assertSame( 'percent', $wc_coupon->get_discount_type() );
	}

	/**
	 * @testdox execute() persists fixed discount type enums when provided.
	 *
	 * @dataProvider fixed_discount_type_provider
	 *
	 * @param DiscountType $discount_type The discount type enum.
	 * @param string       $expected      The expected stored discount type.
	 */
	public function test_execute_persists_fixed_discount_type_enums( DiscountType $discount_type, string $expected ): void {
		$input                = new CreateCouponInput();
		$input->code          = 'coupon-' . str_replace( '_', '-', $expected );
		$input->discount_type = $discount_type;

		$result = $this->sut->execute( $input );

		$wc_coupon = new WC_Coupon( $result->id );
		$this->assertSame( $expected, $wc_coupon->get_discount_type() );
	}

	/**
	 * Data provider for fixed discount types.
	 *
	 * @return array<string, array{0: DiscountType, 1: string}>
	 */
	public function fixed_discount_type_provider(): array {
		return array(
			'fixed cart'    => array( DiscountType::FixedCart, 'fixed_cart' ),
			'fixed product' => array( DiscountType::FixedProduct, 'fixed_product' ),
		);
	}

	/**
	 * @testdox execute() skips set_discount_type() when discount_type is null.
	 */
	public function test_execute_skips_discount_type_when_provided_null(): void {
		$input                = new CreateCouponInput();
		$input->code          = 'null-discount-type';
		$input->discount_type = null;

		$result = $this->sut->execute( $input );

		$wc_coupon = new WC_Coupon( $result->id );
		$this->assertSame( 'fixed_cart', $wc_coupon->get_discount_type() );
	}

	/**
	 * @testdox execute() applies the status enum when provided.
	 */
	public function test_execute_applies_status_enum(): void {
		$input         = new CreateCouponInput();
		$input->code   = 'draft-coupon';
		$input->status = CouponStatus::Draft;

		$result = $this->sut->execute( $input );

		$wc_coupon = new WC_Coupon( $result->id );
		$this->assertSame( 'draft', $wc_coupon->get_status() );
	}

	/**
	 * @testdox execute() skips set_status() when status is null.
	 */
	public function test_execute_skips_status_when_provided_null(): void {
		$input         = new CreateCouponInput();
		$input->code   = 'null-status';
		$input->status = null;

		$result = $this->sut->execute( $input );

		$wc_coupon = new WC_Coupon( $result->id );
		$this->assertSame( 'publish', $wc_coupon->get_status() );
	}
}

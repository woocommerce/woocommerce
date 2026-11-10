<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\EmailEditor;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Coupon_Code_Generator;
use Automattic\WooCommerce\EmailEditor\Integrations\WooCommerce\Renderer\Blocks\Coupon_Code;
use WC_Unit_Test_Case;

/**
 * Tests for the Coupon_Code_Generator class, which creates the coupons sent in emails.
 */
class CouponCodeGeneratorTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Coupon_Code_Generator
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Coupon_Code_Generator();
	}

	/**
	 * @testdox Returns a code that another filter already provided.
	 */
	public function test_returns_existing_code(): void {
		$this->assertSame( 'GIVEN', $this->sut->generate_coupon( 'GIVEN', array(), $this->context() ) );
	}

	/**
	 * @testdox Generates a code when an earlier filter returned something other than a string.
	 */
	public function test_non_string_code_from_filter_generates_a_code(): void {
		$code = $this->sut->filter_coupon_code( null, 'not-an-array', $this->context() );

		$this->assertNotSame( 0, wc_get_coupon_id_by_code( $code ) );
	}

	/**
	 * @testdox Handles a non-string code on the filter it registers.
	 */
	public function test_registered_filter_accepts_non_string_code(): void {
		remove_all_filters( 'woocommerce_coupon_code_block_auto_generate' );
		$this->sut->init();

		$code = apply_filters( 'woocommerce_coupon_code_block_auto_generate', null, array(), $this->context() );

		$this->assertNotSame( 0, wc_get_coupon_id_by_code( $code ) );
	}

	/**
	 * @testdox Keeps a numeric code from an earlier filter as a string.
	 */
	public function test_keeps_numeric_code(): void {
		$this->assertSame( '123', $this->sut->filter_coupon_code( 123, array(), $this->context() ) );
	}

	/**
	 * @testdox Returns no code when the filter gets no rendering context.
	 */
	public function test_without_rendering_context_returns_empty(): void {
		$this->assertSame( '', $this->sut->filter_coupon_code( '', array(), null ) );
	}

	/**
	 * @testdox Returns the placeholder in a preview and creates no coupon.
	 */
	public function test_preview_returns_placeholder(): void {
		$code = $this->sut->generate_coupon( '', array(), $this->context( array( 'is_user_preview' => true ) ) );

		$this->assertSame( Coupon_Code::COUPON_CODE_PLACEHOLDER, $code );
		$this->assertSame( 0, wc_get_coupon_id_by_code( $code ) );
	}

	/**
	 * @testdox Creates a coupon with the settings from the block.
	 */
	public function test_creates_coupon_from_block_settings(): void {
		$code = $this->sut->generate_coupon(
			'',
			array(
				'discountType'      => 'fixed_cart',
				'amount'            => '15',
				'expiryDay'         => 7,
				'freeShipping'      => true,
				'minimumAmount'     => '50',
				'individualUse'     => true,
				'usageLimit'        => '1',
				'usageLimitPerUser' => 'many',
				'productIds'        => array( array( 'id' => 12 ), 'not-an-item' ),
			),
			$this->context()
		);

		$this->assertMatchesRegularExpression( '/^[a-z0-9]{4}-[a-z0-9]{6}-[a-z0-9]{4}$/i', $code );
		$coupon = new \WC_Coupon( $code );
		$this->assertSame( 'fixed_cart', $coupon->get_discount_type() );
		$this->assertSame( 15.0, (float) $coupon->get_amount() );
		$this->assertTrue( $coupon->get_free_shipping() );
		$this->assertSame( 50.0, (float) $coupon->get_minimum_amount() );
		$this->assertTrue( $coupon->get_individual_use() );
		$this->assertSame( 1, $coupon->get_usage_limit() );
		$this->assertSame( 0, $coupon->get_usage_limit_per_user(), 'A non-numeric limit means no limit.' );
		$this->assertSame( array( 12 ), $coupon->get_product_ids(), 'Items without an ID are dropped.' );
		$this->assertEqualsWithDelta( time() + 7 * DAY_IN_SECONDS, $coupon->get_date_expires()->getTimestamp(), 60 );
	}

	/**
	 * @testdox Uses a percentage discount when the block's discount type is unknown.
	 */
	public function test_unknown_discount_type_becomes_percent(): void {
		$code = $this->sut->generate_coupon( '', array( 'discountType' => 'free_money' ), $this->context() );

		$this->assertSame( 'percent', ( new \WC_Coupon( $code ) )->get_discount_type() );
	}

	/**
	 * @testdox Limits the coupon to valid listed emails and to the recipient.
	 */
	public function test_restricts_coupon_to_valid_emails_and_recipient(): void {
		$code = $this->sut->generate_coupon(
			'',
			array( 'emailRestrictions' => 'one@example.com, not-an-email ,two@example.com' ),
			$this->context( array( 'recipient_email' => 'customer@example.com' ) )
		);

		$this->assertSame(
			array( 'one@example.com', 'two@example.com', 'customer@example.com' ),
			array_values( ( new \WC_Coupon( $code ) )->get_email_restrictions() )
		);
	}

	/**
	 * @testdox Returns no code and creates no coupon when every generated code is taken.
	 */
	public function test_returns_empty_when_no_unique_code_is_found(): void {
		$existing = new \WC_Coupon();
		$existing->set_code( 'TAKEN' );
		$existing->save();
		add_filter(
			'woocommerce_get_coupon_id_from_code',
			function () use ( $existing ) {
				return $existing->get_id();
			}
		);
		$coupons_before = wp_count_posts( 'shop_coupon' )->publish;

		$this->assertSame( '', $this->sut->generate_coupon( '', array(), $this->context() ) );
		$this->assertSame( $coupons_before, wp_count_posts( 'shop_coupon' )->publish );
	}

	/**
	 * Rendering context.
	 *
	 * @param array $email_context Email context values.
	 */
	private function context( array $email_context = array() ): Rendering_Context {
		return new Rendering_Context( new \WP_Theme_JSON(), $email_context );
	}
}

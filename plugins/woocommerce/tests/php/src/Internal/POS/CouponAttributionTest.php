<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS;

use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\CouponAttribution;
use Automattic\WooCommerce\Internal\POS\OrderAttribution;
use WC_Coupon;
use WC_Unit_Test_Case;
use WP_REST_Request;

/**
 * Tests for the CouponAttribution lifecycle hooks.
 *
 * Hooks are exercised directly (rather than through the full REST stack) to keep the
 * test focused on validation behavior. The attribution meta keys are reused from
 * {@see OrderAttribution} to keep the mobile wire shape uniform across resources.
 */
class CouponAttributionTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CouponAttribution
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CouponAttribution();
	}

	/**
	 * Create a user with the given POS preset.
	 *
	 * @param string $pos_preset One of Capabilities::POS_PRESET_* constants.
	 * @param array  $user_args Optional overrides for the user factory.
	 * @return int              The created user ID.
	 */
	private function make_pos_user( string $pos_preset, array $user_args = array() ): int {
		$user_id = self::factory()->user->create( array_merge( array( 'role' => 'subscriber' ), $user_args ) );
		Capabilities::set_pos_preset( $user_id, $pos_preset );
		return $user_id;
	}

	/**
	 * Build a draft coupon with the given POS meta applied.
	 *
	 * @param array $meta Map of meta key → value.
	 * @return WC_Coupon
	 */
	private function make_coupon_with_meta( array $meta ): WC_Coupon {
		$coupon = new WC_Coupon();
		$coupon->set_code( 'test-' . uniqid() );
		foreach ( $meta as $key => $value ) {
			$coupon->update_meta_data( $key, $value );
		}
		return $coupon;
	}

	/**
	 * @testdox Should pass coupons without any POS meta through unchanged.
	 */
	public function test_pre_insert_passes_coupons_without_pos_meta(): void {
		$coupon = $this->make_coupon_with_meta( array() );
		$result = $this->sut->handle_pre_insert( $coupon, new WP_REST_Request(), true );

		$this->assertSame( $coupon, $result );
	}

	/**
	 * @testdox Should return a WP_Error when _pos_staff_user_id references a missing user.
	 */
	public function test_pre_insert_rejects_unknown_staff_user(): void {
		$coupon = $this->make_coupon_with_meta(
			array( OrderAttribution::META_KEY_STAFF_USER_ID => 99999999 )
		);

		$result = $this->sut->handle_pre_insert( $coupon, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_attribution', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should return a WP_Error when the staff user lacks POS access.
	 */
	public function test_pre_insert_rejects_staff_user_without_pos_access(): void {
		$customer = self::factory()->user->create( array( 'role' => 'customer' ) );
		$coupon   = $this->make_coupon_with_meta(
			array( OrderAttribution::META_KEY_STAFF_USER_ID => $customer )
		);

		$result = $this->sut->handle_pre_insert( $coupon, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_invalid_attribution', $result->get_error_code() );

		wp_delete_user( $customer );
	}

	/**
	 * @testdox Should accept a coupon with valid attribution and no override.
	 */
	public function test_pre_insert_accepts_valid_attribution(): void {
		$manager = $this->make_pos_user( Capabilities::POS_PRESET_MANAGER );
		$coupon  = $this->make_coupon_with_meta(
			array( OrderAttribution::META_KEY_STAFF_USER_ID => $manager )
		);

		$result = $this->sut->handle_pre_insert( $coupon, new WP_REST_Request(), true );

		$this->assertSame( $coupon, $result );

		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should reject a self-override on a coupon.
	 */
	public function test_pre_insert_rejects_self_override(): void {
		$manager = $this->make_pos_user( Capabilities::POS_PRESET_MANAGER );
		$coupon  = $this->make_coupon_with_meta(
			array(
				OrderAttribution::META_KEY_STAFF_USER_ID => $manager,
				OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID => $manager,
			)
		);

		$result = $this->sut->handle_pre_insert( $coupon, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_self_override', $result->get_error_code() );

		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should reject coupon override when approver lacks create_coupons.
	 */
	public function test_pre_insert_rejects_forbidden_approver(): void {
		$cashier         = $this->make_pos_user( Capabilities::POS_PRESET_CASHIER );
		$another_cashier = $this->make_pos_user( Capabilities::POS_PRESET_CASHIER );

		$coupon = $this->make_coupon_with_meta(
			array(
				OrderAttribution::META_KEY_STAFF_USER_ID => $cashier,
				OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID => $another_cashier,
			)
		);

		$result = $this->sut->handle_pre_insert( $coupon, new WP_REST_Request(), true );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_pos_override_forbidden', $result->get_error_code() );

		wp_delete_user( $cashier );
		wp_delete_user( $another_cashier );
	}

	/**
	 * @testdox Should accept a valid coupon override (approver holds create_coupons).
	 */
	public function test_pre_insert_accepts_valid_override(): void {
		$cashier = $this->make_pos_user( Capabilities::POS_PRESET_CASHIER );
		$manager = $this->make_pos_user( Capabilities::POS_PRESET_MANAGER );

		$coupon = $this->make_coupon_with_meta(
			array(
				OrderAttribution::META_KEY_STAFF_USER_ID => $cashier,
				OrderAttribution::META_KEY_OVERRIDE_STAFF_USER_ID => $manager,
			)
		);

		$result = $this->sut->handle_pre_insert( $coupon, new WP_REST_Request(), true );

		$this->assertSame( $coupon, $result );

		wp_delete_user( $cashier );
		wp_delete_user( $manager );
	}

	/**
	 * @testdox Should be a no-op at post-insert when no attribution meta is present.
	 */
	public function test_post_insert_noop_without_attribution(): void {
		$coupon = $this->make_coupon_with_meta( array() );
		$coupon->save();

		// No exception, no fatal — coupons have no order-note timeline to assert against.
		$this->sut->handle_post_insert( $coupon, new WP_REST_Request(), true );

		$this->assertTrue( true );
	}
}

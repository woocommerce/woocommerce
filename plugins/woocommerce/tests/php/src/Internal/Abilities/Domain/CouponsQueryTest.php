<?php
/**
 * Coupon query ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\Domain\CouponsQuery;

/**
 * Tests coupon reads through the ability API.
 */
class CouponsQueryTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox Should find scheduled coupons by code without returning customer email restrictions.
	 */
	public function test_scheduled_coupon_lookup(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'scheduled-code' );
		$coupon->set_date_created( time() + DAY_IN_SECONDS );
		$coupon->set_status( 'future' );
		$coupon->set_email_restrictions( array( 'customer@example.test' ) );
		$coupon->save();

		$ability = new \WP_Ability( CouponsQuery::get_name(), CouponsQuery::get_registration_args() );
		$result  = $ability->execute( array( 'code' => 'scheduled-code' ) );

		$this->assertNotWPError( $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( $coupon->get_id(), $result['coupons'][0]['id'] );
		$this->assertTrue( $result['coupons'][0]['scheduled'] );
		$this->assertFalse( $result['coupons'][0]['redeemable'] );
		$this->assertArrayHasKey( 'starts_at', $result['coupons'][0] );
		$this->assertArrayNotHasKey( 'email_restrictions', $result['coupons'][0] );
	}

	/**
	 * @testdox Should create and update a coupon while preserving its usage count.
	 */
	public function test_writes_preserve_usage(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute(
			array(
				'code'   => 'write-test',
				'amount' => '10',
			)
		);
		$this->assertNotWPError( $result );
		$coupon = new \WC_Coupon( $result['coupon']['id'] );
		$coupon->set_usage_count( 2 );
		$coupon->save();
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute(
			array(
				'id'     => $coupon->get_id(),
				'amount' => '20',
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( '20', $result['coupon']['amount'] );
		$this->assertSame( 2, $result['coupon']['usage_count'] );
	}

	/**
	 * @testdox Should page included coupons and filter by discount type.
	 */
	public function test_pagination_and_filters(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$ids = array();
		foreach ( array( 'percent', 'percent', 'fixed_cart' ) as $index => $type ) {
			$coupon = new \WC_Coupon();
			$coupon->set_code( 'page-' . $index );
			$coupon->set_discount_type( $type );
			$coupon->save();
			$ids[] = $coupon->get_id();
		}
		$ability = new \WP_Ability( CouponsQuery::get_name(), CouponsQuery::get_registration_args() );
		$input   = array(
			'include'       => $ids,
			'discount_type' => 'percent',
			'per_page'      => 1,
		);
		$first   = $ability->execute( $input );
		$second  = $ability->execute( $input + array( 'page' => 2 ) );
		$this->assertSame( 2, $first['total'] );
		$this->assertSame( 2, $first['total_pages'] );
		$this->assertCount( 1, $first['coupons'] );
		$this->assertNotSame( $first['coupons'][0]['id'], $second['coupons'][0]['id'] );
	}

	/**
	 * @testdox Should refuse coupon access to customers.
	 */
	public function test_permissions(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$ability = new \WP_Ability( CouponsQuery::get_name(), CouponsQuery::get_registration_args() );
		$this->assertWPError( $ability->execute( array() ) );
	}
}

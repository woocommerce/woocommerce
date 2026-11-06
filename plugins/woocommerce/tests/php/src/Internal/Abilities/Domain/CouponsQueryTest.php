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
		$this->assertArrayNotHasKey( 'email_restrictions', $ability->get_input_schema()['properties'] );
		$result = $ability->execute(
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
		$this->assertArrayNotHasKey( 'email_restrictions', $ability->get_input_schema()['properties'] );
		$result = $ability->execute(
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

	/**
	 * @testdox Should require publication permission for published, scheduled and private coupons.
	 * @testWith ["publish"]
	 *           ["future"]
	 *           ["private"]
	 * @param string $status Requested status.
	 */
	public function test_publication_permission( string $status ): void {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );
		wp_get_current_user()->add_cap( 'publish_shop_coupons', false );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'restricted-status' );
		$coupon->set_status( 'draft' );
		$coupon->save();
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$this->assertArrayNotHasKey( 'email_restrictions', $ability->get_input_schema()['properties'] );
		$this->assertWPError(
			$ability->execute(
				array(
					'id'     => $coupon->get_id(),
					'status' => $status,
				)
			)
		);
		$this->assertSame( 'draft', ( new \WC_Coupon( $coupon->get_id() ) )->get_status() );
	}

	/**
	 * @testdox Should reject invalid mutations without changing stored coupon data.
	 * @testWith [{"status":"future"}]
	 *           [{"date_expires":"not-a-date"}]
	 *           [{"discount_type":"percent"}]
	 * @param array $input Mutation.
	 */
	public function test_invalid_mutations( array $input ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'invalid-mutation' );
		$coupon->set_amount( '150' );
		$coupon->set_date_expires( '2030-01-01' );
		$coupon->save();
		$before  = ( new \WC_Coupon( $coupon->get_id() ) )->get_data();
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$this->assertWPError( $ability->execute( $input + array( 'id' => $coupon->get_id() ) ) );
		$this->assertEquals( $before, ( new \WC_Coupon( $coupon->get_id() ) )->get_data() );
	}

	/**
	 * @testdox Should reject duplicate draft codes and unscheduled future creation.
	 * @testWith [{"code":"existing-draft"}]
	 *           [{"code":"new-future","status":"future"}]
	 * @param array $input Creation input.
	 */
	public function test_invalid_creation( array $input ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'existing-draft' );
		$coupon->set_status( 'draft' );
		$coupon->save();
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$this->assertWPError( $ability->execute( $input ) );
	}

	/**
	 * @testdox Should let an editor retain the existing publication status.
	 */
	public function test_unchanged_status(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		wp_get_current_user()->add_cap( 'publish_shop_coupons', false );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'unchanged-status' );
		$coupon->save();
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute(
			array(
				'id'     => $coupon->get_id(),
				'status' => 'publish',
				'amount' => '20',
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( '20', ( new \WC_Coupon( $coupon->get_id() ) )->get_amount() );
	}

	/**
	 * @testdox Should preserve expiry instants when the store uses a non-UTC timezone.
	 * @testWith ["2030-01-01T12:00:00.123+01:00", 1893495600]
	 *           ["20300101", 1893452400]
	 * @param string $expiry Expiry input.
	 * @param int    $timestamp Expected stored instant.
	 */
	public function test_expiry_normalization( string $expiry, int $timestamp ): void {
		update_option( 'timezone_string', 'Europe/Madrid' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute(
			array(
				'code'         => 'normalized-expiry',
				'date_expires' => $expiry,
			)
		);
		$this->assertNotWPError( $result );
		$coupon = new \WC_Coupon( $result['coupon']['id'] );
		$this->assertSame( $timestamp, $coupon->get_date_expires()->getTimestamp() );
	}

	/**
	 * @testdox Should publish a scheduled coupon in storage as well as in its response.
	 */
	public function test_publish_scheduled_coupon(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'publish-scheduled' );
		$coupon->set_date_created( time() + DAY_IN_SECONDS );
		$coupon->set_status( 'future' );
		$coupon->save();
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute(
			array(
				'id'     => $coupon->get_id(),
				'status' => 'publish',
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( 'publish', $result['coupon']['status'] );
		$this->assertSame( 'publish', ( new \WC_Coupon( $coupon->get_id() ) )->get_status() );
	}

	/**
	 * @testdox Should retain totals when the requested page is beyond the last match.
	 */
	public function test_out_of_range_totals(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'one-match' );
		$coupon->save();
		$ability = new \WP_Ability( CouponsQuery::get_name(), CouponsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'include'  => array( $coupon->get_id() ),
				'page'     => 2,
				'per_page' => 1,
			)
		);
		$this->assertSame( array(), $result['coupons'] );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 1, $result['total_pages'] );
	}
}

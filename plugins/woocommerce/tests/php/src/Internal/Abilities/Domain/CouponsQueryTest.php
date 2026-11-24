<?php
/**
 * Coupon query ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;
use Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate;
use Automattic\WooCommerce\Internal\Abilities\Domain\CouponsQuery;
use Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate;

/**
 * Tests coupon reads through the ability API.
 */
class CouponsQueryTest extends \WC_Unit_Test_Case {
	/**
	 * @testdox Should find scheduled coupons by code with their email restrictions.
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
		$this->assertSame( array( 'customer@example.test' ), $result['coupons'][0]['email_restrictions'] );
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
	 * @testdox Should return saved email restrictions on create, update and query.
	 */
	public function test_email_restriction_writes(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$class  = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		$create = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result = $create->execute(
			array(
				'code'               => 'restricted-emails',
				'email_restrictions' => array( ' Customer@example.test ', 'other@example.test' ),
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( array( 'customer@example.test', 'other@example.test' ), $result['coupon']['email_restrictions'] );
		$id = $result['coupon']['id'];
		$this->assertSame( array( 'customer@example.test', 'other@example.test' ), ( new \WC_Coupon( $id ) )->get_email_restrictions() );

		$class  = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$update = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result = $update->execute(
			array(
				'id'                 => $id,
				'email_restrictions' => array( 'replacement@example.test' ),
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( array( 'replacement@example.test' ), $result['coupon']['email_restrictions'] );
		$this->assertSame( array( 'replacement@example.test' ), ( new \WC_Coupon( $id ) )->get_email_restrictions() );

		$result = $update->execute(
			array(
				'id'     => $id,
				'amount' => '15',
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( array( 'replacement@example.test' ), ( new \WC_Coupon( $id ) )->get_email_restrictions() );
		$query  = new \WP_Ability( CouponsQuery::get_name(), CouponsQuery::get_registration_args() );
		$result = $query->execute( array( 'include' => array( $id ) ) );
		$this->assertNotWPError( $result );
		$this->assertSame( array( 'replacement@example.test' ), $result['coupons'][0]['email_restrictions'] );

		$result = $update->execute(
			array(
				'id'                 => $id,
				'email_restrictions' => array(),
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( array(), $result['coupon']['email_restrictions'] );
		$this->assertSame( array(), ( new \WC_Coupon( $id ) )->get_email_restrictions() );
	}

	/**
	 * @testdox Should reject invalid emails before saving any coupon changes.
	 * @testWith [["not-an-email"]]
	 *           [["customer name@example.test"]]
	 *           [[""]]
	 *           [["valid@example.test", "invalid"]]
	 * @param array $emails Supplied restrictions.
	 */
	public function test_invalid_email_restrictions( array $emails ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$class  = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		$create = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result = $create->execute(
			array(
				'code'               => 'invalid-email-create',
				'email_restrictions' => $emails,
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_coupon_email_invalid', $result->get_error_code() );
		$this->assertSame( 0, wc_get_coupon_id_by_code( 'invalid-email-create' ) );

		$coupon = new \WC_Coupon();
		$coupon->set_code( 'invalid-email-update' );
		$coupon->set_amount( '10' );
		$coupon->set_email_restrictions( array( 'original@example.test' ) );
		$coupon->save();
		$before = ( new \WC_Coupon( $coupon->get_id() ) )->get_data();
		$class  = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$update = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result = $update->execute(
			array(
				'id'                 => $coupon->get_id(),
				'amount'             => '20',
				'email_restrictions' => $emails,
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_coupon_email_invalid', $result->get_error_code() );
		$this->assertEquals( $before, ( new \WC_Coupon( $coupon->get_id() ) )->get_data() );
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
		$coupon->set_date_created( time() + DAY_IN_SECONDS );
		$coupon->save();
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$before  = ( new \WC_Coupon( $coupon->get_id() ) )->get_data();
		$result  = $ability->execute(
			array(
				'id'     => $coupon->get_id(),
				'status' => $status,
				'amount' => '20',
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_coupon_publish_forbidden', $result->get_error_code() );
		$this->assertEquals( $before, ( new \WC_Coupon( $coupon->get_id() ) )->get_data() );
	}

	/**
	 * @testdox Should authorize publication against the coupon loaded after the permission callback.
	 */
	public function test_publication_permission_after_status_change(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		wp_get_current_user()->add_cap( 'publish_shop_coupons', false );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'publication-status-change' );
		$coupon->save();
		$class = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$input = array(
			'id'     => $coupon->get_id(),
			'status' => 'publish',
		);
		$this->assertTrue( $class::can_update_coupon( $input ) );
		$coupon->set_status( 'draft' );
		$coupon->save();
		$before  = ( new \WC_Coupon( $coupon->get_id() ) )->get_data();
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute_change( $input );
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_coupon_publish_forbidden', $result->get_error_code() );
		$this->assertEquals( $before, ( new \WC_Coupon( $coupon->get_id() ) )->get_data() );
	}

	/**
	 * @testdox Should require REST creation permission even for a draft coupon.
	 */
	public function test_draft_creation_permission(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		wp_get_current_user()->add_cap( 'publish_shop_coupons', false );
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute(
			array(
				'code'   => 'forbidden-draft',
				'status' => 'draft',
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		$this->assertSame( 0, wc_get_coupon_id_by_code( 'forbidden-draft' ) );
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
	 * @testWith [{"code":"existing-draft"}, "woocommerce_ability_coupon_code_exists"]
	 *           [{"code":"new-future","status":"future"}, "ability_invalid_input"]
	 * @param array  $input Creation input.
	 * @param string $error_code Expected rejection.
	 */
	public function test_invalid_creation( array $input, string $error_code ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'existing-draft' );
		$coupon->set_status( 'draft' );
		$coupon->save();
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute( $input );
		$this->assertWPError( $result );
		$this->assertSame( $error_code, $result->get_error_code() );
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
	 * @testdox Should authorize implicit publication while allowing unrelated overdue coupon edits.
	 * @testWith [{"amount":"15","email_restrictions":["customer@example.com"]}, false, "future"]
	 *           [{"description":"changed","amount":"15"}, false, null]
	 *           [{"code":"changed-code","amount":"15"}, false, null]
	 *           [{"description":"changed"}, true, "publish"]
	 *           [{"code":"changed-code"}, true, "publish"]
	 *           [{"description":"","code":"overdue-scheduled","amount":"15"}, false, "future"]
	 *           [{"description":"changed","status":"draft"}, false, "draft"]
	 *           [{"description":"changed"}, false, null, 30]
	 * @param array       $input Update fields.
	 * @param bool        $can_publish Whether publication is allowed.
	 * @param string|null $expected_status Saved status, or null for a rejected update.
	 * @param int         $publication_offset Seconds until the publication date.
	 */
	public function test_update_overdue_scheduled_coupon( array $input, bool $can_publish, ?string $expected_status, int $publication_offset = -DAY_IN_SECONDS ): void {
		global $wpdb;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		wp_get_current_user()->add_cap( 'publish_shop_coupons', $can_publish );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'overdue-scheduled' );
		$coupon->set_amount( '10' );
		$coupon->set_date_created( time() + DAY_IN_SECONDS );
		$coupon->set_status( 'future' );
		$coupon->save();
		$overdue = time() + $publication_offset;
		// Simulate a publication that WP-Cron has not processed yet.
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'     => wp_date( 'Y-m-d H:i:s', $overdue ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $overdue ),
			),
			array( 'ID' => $coupon->get_id() )
		);
		clean_post_cache( $coupon->get_id() );
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$before  = ( new \WC_Coupon( $coupon->get_id() ) )->get_data();
		$result  = $ability->execute( $input + array( 'id' => $coupon->get_id() ) );
		$stored  = new \WC_Coupon( $coupon->get_id() );
		if ( null === $expected_status ) {
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_coupon_publish_forbidden', $result->get_error_code() );
			$this->assertEquals( $before, $stored->get_data() );
			return;
		}
		$this->assertNotWPError( $result );
		$this->assertSame( $expected_status, $stored->get_status() );
		$this->assertSame( $expected_status, $result['coupon']['status'] );
		$this->assertSame( $overdue, $stored->get_date_created()->getTimestamp() );
		foreach ( $input as $field => $value ) {
			$this->assertSame( $value, $stored->{ 'get_' . $field }() );
		}
		if ( 'future' === $expected_status ) {
			$before = $stored->get_data();
			$result = $ability->execute(
				array(
					'id'     => $coupon->get_id(),
					'status' => 'future',
					'amount' => '20',
				)
			);
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_coupon_invalid_schedule', $result->get_error_code() );
			$this->assertEquals( $before, ( new \WC_Coupon( $coupon->get_id() ) )->get_data() );
		}
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
	/**
	 * @testdox Should validate spending limits against their final values without partial saves.
	 * @testWith [{"minimum_amount":"30","maximum_amount":"40"}, "30", "40", true]
	 *           [{"minimum_amount":"1","maximum_amount":"5"}, "1", "5", true]
	 *           [{"minimum_amount":"30","maximum_amount":"0"}, "30", "0", true]
	 *           [{"minimum_amount":"30","maximum_amount":""}, "30", "0", true]
	 *           [{"minimum_amount":"","maximum_amount":"5"}, "0", "5", true]
	 *           [{"minimum_amount":"30","maximum_amount":"25"}, "10", "20", false]
	 *           [{"minimum_amount":"30"}, "10", "20", false]
	 *           [{"maximum_amount":"5"}, "10", "20", false]
	 * @param array  $input Requested limits.
	 * @param string $minimum Expected minimum.
	 * @param string $maximum Expected maximum.
	 * @param bool   $valid Whether the final pair is valid.
	 */
	public function test_spending_limit_updates( array $input, string $minimum, string $maximum, bool $valid ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'spending-limit-update' );
		$coupon->set_minimum_amount( '10' );
		$coupon->set_maximum_amount( '20' );
		$coupon->save();
		$before = ( new \WC_Coupon( $coupon->get_id() ) )->get_data();
		$class  = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		$update = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result = $update->execute( $input + array( 'id' => $coupon->get_id() ) );
		if ( $valid ) {
			$this->assertNotWPError( $result );
		} else {
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_coupon_invalid_data', $result->get_error_code() );
			$this->assertEquals( $before, ( new \WC_Coupon( $coupon->get_id() ) )->get_data() );
		}
		$stored = new \WC_Coupon( $coupon->get_id() );
		$this->assertSame( $minimum, $stored->get_minimum_amount() );
		$this->assertSame( $maximum, $stored->get_maximum_amount() );
	}

	/**
	 * @testdox Should retain a zero search term instead of returning every included coupon.
	 */
	public function test_zero_search(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$ids = array();
		foreach ( array( 'no-match', 'match-0' ) as $code ) {
			$coupon = new \WC_Coupon();
			$coupon->set_code( $code );
			$coupon->save();
			$ids[] = $coupon->get_id();
		}
		$ability = new \WP_Ability( CouponsQuery::get_name(), CouponsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'include' => $ids,
				'search'  => '0',
			)
		);
		$this->assertNotWPError( $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( array( $ids[1] ), array_column( $result['coupons'], 'id' ) );
	}

	/**
	 * @testdox Should reject exact codes that normalize to empty instead of listing unrelated coupons.
	 * @testWith [""]
	 *           ["   "]
	 *           ["\u0000"]
	 * @param string $code Exact code input.
	 */
	public function test_empty_exact_code( string $code ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$coupon = new \WC_Coupon();
		$coupon->set_code( 'unrelated-coupon' );
		$coupon->save();
		$ability = new \WP_Ability( CouponsQuery::get_name(), CouponsQuery::get_registration_args() );
		$result  = $ability->execute(
			array(
				'include' => array( $coupon->get_id() ),
				'code'    => $code,
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_coupon_code_required', $result->get_error_code() );
	}
	/**
	 * @testdox Should report the stored status when a creation filter changes it.
	 */
	public function test_create_response_uses_saved_coupon(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter(
			'woocommerce_new_coupon_data',
			static function ( $data ) {
				$data['post_status'] = 'draft';
				return $data;
			}
		);
		$class   = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute( array( 'code' => 'filtered-creation' ) );
		$this->assertNotWPError( $result );
		$this->assertSame( 'draft', ( new \WC_Coupon( $result['coupon']['id'] ) )->get_status() );
		$this->assertSame( 'draft', $result['coupon']['status'] );
		$this->assertFalse( $result['coupon']['redeemable'] );
	}

	/**
	 * @testdox Should reload saved coupon responses without treating their IDs as virtual codes.
	 * @testWith ["create"]
	 *           ["update"]
	 * @param string $operation Mutation to execute.
	 */
	public function test_saved_response_bypasses_virtual_coupon_filter( string $operation ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$input = array( 'amount' => '15' );
		if ( 'update' === $operation ) {
			$coupon = new \WC_Coupon();
			$coupon->set_code( 'saved-real-coupon' );
			$coupon->save();
			$input['id'] = $coupon->get_id();
			$class       = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponUpdate::class;
		} else {
			$input['code'] = 'saved-real-coupon';
			$class         = \Automattic\WooCommerce\Internal\Abilities\Domain\CouponCreate::class;
		}
		$saved_id = 0;
		add_action(
			'create' === $operation ? 'woocommerce_new_coupon' : 'woocommerce_update_coupon',
			static function ( $id ) use ( &$saved_id ) {
				$saved_id = $id;
				add_filter(
					'woocommerce_get_shop_coupon_data',
					static function ( $data, $code ) use ( $id ) {
						return (string) $id === (string) $code ? array( 'amount' => '99' ) : $data;
					},
					10,
					2
				);
			}
		);
		$ability = new $class( $class::ABILITY_NAME, $class::get_registration_args() );
		$result  = $ability->execute( $input );
		$this->assertNotWPError( $result );
		$this->assertGreaterThan( 0, $saved_id );
		$this->assertSame( $saved_id, $result['coupon']['id'] );
		$this->assertSame( 'saved-real-coupon', $result['coupon']['code'] );
		$this->assertSame( '15', $result['coupon']['amount'] );
		$this->assertSame( 'saved-real-coupon', get_post_field( 'post_title', $saved_id ) );
		$this->assertSame( '15', get_post_meta( $saved_id, 'coupon_amount', true ) );
	}

	/**
	 * @testdox Should read, write, validate, sync and filter extension fields on coupons while the extensible abilities feature is on.
	 */
	public function test_extension_fields_and_filters(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$saved = array();
		foreach ( array( 'resources', 'fields', 'filters', 'validators', 'syncs' ) as $name ) {
			$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
			$property->setAccessible( true );
			$saved[ $name ] = $property->getValue();
			$property->setValue( null, array() );
		}
		try {
			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'yes' );
			add_action( 'woocommerce_extensible_abilities_resources_init', array( AbilitiesLoader::class, 'register_core_resources' ), 0 );
			do_action( 'woocommerce_extensible_abilities_resources_init' );
			$synced = array();
			add_action(
				'woocommerce_extensible_abilities_init',
				static function () use ( &$synced ) {
					ExtensibleAbilities::register_field(
						array(
							'resource'      => 'coupon',
							'namespace'     => 'test-ext',
							'field'         => 'credit',
							'output_schema' => array( 'type' => 'integer' ),
							'get_callback'  => static fn( \WC_Coupon $coupon ) => '' === $coupon->get_meta( 'test_credit' ) ? null : $coupon->get_meta( 'test_credit' ),
							'input_schema'  => array( 'type' => 'integer' ),
							'set_callback'  => static fn( \WC_Coupon $coupon, int $value ) => $coupon->update_meta_data( 'test_credit', $value ),
						)
					);
					ExtensibleAbilities::register_validator(
						array(
							'resource'  => 'coupon',
							'namespace' => 'test-ext',
							'callback'  => static fn( \WC_Coupon $coupon ) => (int) $coupon->get_meta( 'test_credit' ) > 100 ? new \WP_Error( 'test_credit_too_high', 'Too much credit.' ) : null,
						)
					);
					ExtensibleAbilities::register_sync(
						array(
							'resource'  => 'coupon',
							'namespace' => 'test-ext',
							'callback'  => static function ( array $objects ) use ( &$synced ) {
								$synced[] = $objects[0]->get_id();
							},
						)
					);
					ExtensibleAbilities::register_query_filter(
						array(
							'resource'  => 'coupon',
							'namespace' => 'test-ext',
							'filter'    => 'has_credit',
							'schema'    => array( 'type' => 'boolean' ),
							'callback'  => static fn( bool $value ) => array(
								'meta_query' => array(
									array(
										'key'     => 'test_credit',
										'compare' => $value ? 'EXISTS' : 'NOT EXISTS',
									),
								),
							),
						)
					);
				}
			);
			do_action( 'woocommerce_extensible_abilities_init' );
			$create = new CouponCreate( CouponCreate::ABILITY_NAME, CouponCreate::get_registration_args() );
			$update = new CouponUpdate( CouponUpdate::ABILITY_NAME, CouponUpdate::get_registration_args() );
			$query  = new \WP_Ability( CouponsQuery::get_name(), CouponsQuery::get_registration_args() );

			$result = $create->execute(
				array(
					'code'       => 'credit-coupon',
					'extensions' => array( 'test-ext' => array( 'credit' => 50 ) ),
				)
			);
			$this->assertNotWPError( $result );
			$id = $result['coupon']['id'];
			$this->assertSame( 50, $result['coupon']['extensions']['test-ext']['credit'] );
			$this->assertSame( array( $id ), $synced );

			$refused = $update->execute(
				array(
					'id'         => $id,
					'extensions' => array( 'test-ext' => array( 'credit' => 500 ) ),
				)
			);
			$this->assertSame( 'test-ext:test_credit_too_high', $refused->get_error_code() );
			$this->assertSame( '50', ( new \WC_Coupon( $id ) )->get_meta( 'test_credit' ), 'A refused update saves nothing.' );

			$result = $update->execute(
				array(
					'id'         => $id,
					'extensions' => array( 'test-ext' => array( 'credit' => 75 ) ),
				)
			);
			$this->assertSame( 75, $result['coupon']['extensions']['test-ext']['credit'] );

			$plain = new \WC_Coupon();
			$plain->set_code( 'plain-coupon' );
			$plain_id = $plain->save();
			$filter   = static fn( bool $value, array $input = array() ) => $input + array( 'filters' => array( 'extensions' => array( 'test-ext' => array( 'has_credit' => $value ) ) ) );

			$result = $query->execute( $filter( true ) );
			$this->assertSame( array( $id ), array_column( $result['coupons'], 'id' ) );
			$this->assertSame( 75, $result['coupons'][0]['extensions']['test-ext']['credit'] );
			$this->assertSame( 'integer', $query->get_output_schema()['properties']['coupons']['items']['properties']['extensions']['properties']['test-ext']['properties']['credit']['type'] );
			$this->assertSame( array( $plain_id ), array_column( $query->execute( $filter( false ) )['coupons'], 'id' ) );
			$this->assertSame( array(), $query->execute( $filter( false, array( 'include' => array( $id ) ) ) )['coupons'], 'An extension filter applies together with include.' );
		} finally {
			update_option( 'woocommerce_feature_extensible_abilities_enabled', 'no' );
			foreach ( $saved as $name => $value ) {
				$property = new \ReflectionProperty( ExtensibleAbilities::class, $name );
				$property->setAccessible( true );
				$property->setValue( null, $value );
			}
		}
	}
}

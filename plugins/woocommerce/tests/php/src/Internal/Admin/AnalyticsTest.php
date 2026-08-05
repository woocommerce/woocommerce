<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Analytics;
use WC_Unit_Test_Case;

/**
 * Tests for the Analytics feature class.
 */
class AnalyticsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var Analytics
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new Analytics();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		unset( $_GET['page'] );
		parent::tearDown();
	}

	/**
	 * @testdox Preload endpoints should be added on the wc-admin page for an administrator.
	 */
	public function test_preload_endpoints_added_for_administrator(): void {
		$user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );
		$_GET['page'] = 'wc-admin';

		$endpoints = $this->sut->add_preload_endpoints( array( 'existing' => '/existing' ) );

		$this->assertSame( '/existing', $endpoints['existing'], 'Existing endpoint entries should be preserved' );
		$this->assertSame( '/wc-analytics/reports/performance-indicators/allowed', $endpoints['performanceIndicators'] );
		$this->assertSame( '/wc-analytics/leaderboards/allowed', $endpoints['leaderboards'] );
	}

	/**
	 * @testdox Preload endpoints should be added for a reports-only user, who reaches wc-admin through the standalone Analytics menu.
	 */
	public function test_preload_endpoints_added_for_reports_only_user(): void {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		$user    = get_user_by( 'id', $user_id );
		$user->add_cap( 'view_woocommerce_reports' );
		wp_set_current_user( $user_id );
		$_GET['page'] = 'wc-admin';

		$endpoints = $this->sut->add_preload_endpoints( array() );

		$this->assertArrayHasKey( 'performanceIndicators', $endpoints, 'Reports-only users need the indicator data preloaded' );
		$this->assertArrayHasKey( 'leaderboards', $endpoints, 'Reports-only users need the leaderboards data preloaded' );
	}

	/**
	 * @testdox Preload endpoints should not be added for a user without the view_woocommerce_reports capability.
	 */
	public function test_preload_endpoints_not_added_without_reports_capability(): void {
		$user = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user );
		$_GET['page'] = 'wc-admin';

		$endpoints = $this->sut->add_preload_endpoints( array() );

		$this->assertSame( array(), $endpoints, 'Users failing the endpoints REST permission should get no preload entries' );
	}

	/**
	 * @testdox Preload endpoints should not be added outside the wc-admin page.
	 */
	public function test_preload_endpoints_not_added_outside_wc_admin_page(): void {
		$user = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user );
		$_GET['page'] = 'wc-settings';

		$endpoints = $this->sut->add_preload_endpoints( array() );

		$this->assertSame( array(), $endpoints, 'Preload entries are only needed on the WC Admin app page' );
	}
}

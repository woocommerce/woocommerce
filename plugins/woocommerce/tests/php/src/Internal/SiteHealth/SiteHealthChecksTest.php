<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\SiteHealth;

use Automattic\WooCommerce\Internal\SiteHealth\SiteHealthChecks;
use WC_Unit_Test_Case;

/**
 * SiteHealthChecksTest class.
 */
class SiteHealthChecksTest extends WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var SiteHealthChecks
	 */
	private SiteHealthChecks $checks;

	public function setUp(): void {
		parent::setUp();
		$this->checks = wc_get_container()->get( SiteHealthChecks::class );
		$this->checks->register();
	}

	public function tearDown(): void {
		remove_filter( 'site_status_tests', array( $this->checks, 'register_tests' ) );
		parent::tearDown();
	}

	public function test_register_attaches_site_status_tests_filter() {
		// Proves register() actually wired up the callback — has_filter() returns
		// false when the callback is absent, or the integer priority when present.
		$this->assertNotFalse(
			has_filter( 'site_status_tests', array( $this->checks, 'register_tests' ) )
		);
	}

	// -------------------------------------------------------------------------
	// Task 3: Pending DB update check.
	// -------------------------------------------------------------------------

	public function test_pending_db_update_check_critical_when_update_needed() {
		update_option( 'woocommerce_db_version', '0.0.1' );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$entry  = $tests['direct']['woocommerce_pending_db_update'] ?? null;
		$this->assertNotNull( $entry );
		$result = call_user_func( $entry['test'] );
		$this->assertSame( 'critical', $result['status'] );
		$this->assertSame( 'woocommerce_pending_db_update', $result['test'] );
	}

	public function test_pending_db_update_check_good_when_up_to_date() {
		update_option( 'woocommerce_db_version', WC()->version );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_pending_db_update']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Task 4: Required pages check.
	// -------------------------------------------------------------------------

	public function test_required_pages_check_critical_when_page_missing() {
		update_option( 'woocommerce_shop_page_id', 0 );
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_required_pages']['test'] );
		$this->assertSame( 'critical', $result['status'] );
	}

	public function test_required_pages_check_good_when_all_published() {
		foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $key ) {
			$page = wp_insert_post( array( 'post_title' => $key, 'post_type' => 'page', 'post_status' => 'publish' ) );
			update_option( "woocommerce_{$key}_page_id", $page );
		}
		$tests  = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );
		$result = call_user_func( $tests['direct']['woocommerce_required_pages']['test'] );
		$this->assertSame( 'good', $result['status'] );
	}
}

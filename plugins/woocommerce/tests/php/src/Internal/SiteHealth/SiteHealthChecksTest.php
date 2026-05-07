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

	public function test_registers_with_site_status_tests_filter() {
		// This filter is documented in wp-admin/includes/class-wp-site-health.php.
		$tests = apply_filters(
			'site_status_tests',
			array(
				'direct' => array(),
				'async'  => array(),
			)
		);

		$this->assertIsArray( $tests );
		$this->assertArrayHasKey( 'direct', $tests );
		$this->assertArrayHasKey( 'async', $tests );
	}
}

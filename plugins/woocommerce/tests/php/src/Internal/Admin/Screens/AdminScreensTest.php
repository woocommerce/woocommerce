<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Screens;

use Automattic\WooCommerce\Internal\Admin\Screens\AdminScreens;
use WC_Unit_Test_Case;

/**
 * Tests for the AdminScreens class.
 */
class AdminScreensTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var AdminScreens
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new AdminScreens();
		set_current_screen( 'dashboard' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		set_current_screen( 'front' );
		parent::tearDown();
	}

	/**
	 * @testdox Should not load the build while the flag is off.
	 */
	public function test_does_not_load_build_when_flag_is_off(): void {
		if ( $this->is_build_loaded() ) {
			$this->markTestSkipped( 'The build was already loaded by an earlier test.' );
		}
		add_filter(
			'woocommerce_admin_features',
			static function ( $features ) {
				return array_values( array_diff( $features, array( AdminScreens::FEATURE_FLAG ) ) );
			}
		);

		$this->sut->handle_init();

		$this->assertFalse( $this->is_build_loaded(), 'The build should not load while the flag is off' );
	}

	/**
	 * @testdox Should load the build in admin while the flag is on.
	 */
	public function test_loads_build_when_flag_is_on(): void {
		if ( ! is_readable( WC_ABSPATH . 'assets/client/routes/build.php' ) ) {
			$this->markTestSkipped( 'The admin-screens build is not available.' );
		}
		add_filter(
			'woocommerce_admin_features',
			static function ( $features ) {
				$features[] = AdminScreens::FEATURE_FLAG;
				return $features;
			}
		);

		$this->sut->handle_init();

		$this->assertTrue( $this->is_build_loaded(), 'The build should load while the flag is on' );
	}

	/**
	 * Whether the wp-build entry point has been loaded in this process.
	 *
	 * @return bool
	 */
	private function is_build_loaded(): bool {
		return in_array( realpath( WC_ABSPATH . 'assets/client/routes/build.php' ), get_included_files(), true );
	}
}

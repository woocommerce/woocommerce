<?php
declare( strict_types = 1 );

/**
 * Tests for WC_REST_System_Status_V2_Controller.
 *
 * @since 10.6.0
 */
class WC_REST_System_Status_V2_Controller_Test extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_REST_System_Status_V2_Controller
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new WC_REST_System_Status_V2_Controller();
		delete_transient( 'wc_system_status_theme_info' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wc_get_template' );
		delete_transient( 'wc_system_status_theme_info' );
	}

	/**
	 * @testdox WP memory limit should be reported as `-1` (unlimited) when the PHP ini value is `-1`.
	 *
	 * Regression test for GH #32961: WooCommerce used to coerce `-1` to `0`
	 * via wc_let_to_num(), which then made the Status page display the small
	 * WP_MEMORY_LIMIT value and raise a spurious low-memory warning. PHP and
	 * WordPress core both treat `-1` as "no limit"; we now mirror that.
	 */
	public function test_get_environment_info_treats_ini_memory_limit_minus_one_as_unlimited(): void {
		if ( ! function_exists( 'memory_get_usage' ) ) {
			$this->markTestSkipped( 'memory_get_usage() not available; controller path under test is skipped.' );
		}

		$original = @ini_get( 'memory_limit' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		// phpcs:ignore WordPress.PHP.IniSet.memory_limit_Disallowed,WordPress.PHP.NoSilencedErrors.Discouraged -- Test fixture, restored in finally.
		$set = @ini_set( 'memory_limit', '-1' );
		if ( false === $set ) {
			$this->markTestSkipped( 'Unable to set memory_limit ini at runtime; cannot exercise the -1 path here.' );
		}

		try {
			$env = $this->sut->get_environment_info_per_fields( array( 'environment' ) );
			$this->assertSame( -1, $env['wp_memory_limit'], 'A `-1` PHP memory limit must surface as `-1`, not a coerced small value.' );
		} finally {
			// phpcs:ignore WordPress.PHP.IniSet.memory_limit_Disallowed,WordPress.PHP.NoSilencedErrors.Discouraged -- Restoring original value.
			@ini_set( 'memory_limit', (string) $original );
		}
	}

	/**
	 * @testdox Should detect template override via wc_get_template filter.
	 */
	public function test_get_theme_info_detects_wc_get_template_filter_override(): void {
		$template_to_override = 'cart/cart.php';
		$override_path        = WC()->plugin_path() . '/includes/class-woocommerce.php';

		add_filter(
			'wc_get_template',
			function ( $template, $template_name ) use ( $template_to_override, $override_path ) {
				if ( $template_to_override === $template_name ) {
					return $override_path;
				}
				return $template;
			},
			10,
			2
		);

		$theme_info = $this->sut->get_theme_info();

		$override_files = array_column( $theme_info['overrides'], 'file' );
		$this->assertContains(
			str_replace( ABSPATH, '', $override_path ),
			$override_files,
			'Template overridden via wc_get_template filter should appear in overrides'
		);
	}
}

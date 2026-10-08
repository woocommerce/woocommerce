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

	/**
	 * @testdox Should report the PHP memory limit in bytes, using 32 GB when PHP has no limit.
	 *
	 * @testWith ["-1", 34359738368]
	 *           ["2G", 2147483648]
	 *           ["268435456", 268435456]
	 *
	 * @param string $php_memory_limit The PHP memory_limit ini value.
	 * @param int    $expected         The expected reported memory limit in bytes.
	 */
	public function test_get_environment_info_reports_php_memory_limit( string $php_memory_limit, int $expected ): void {
		$this->register_legacy_proxy_function_mocks(
			array(
				'ini_get' => fn() => $php_memory_limit,
			)
		);

		$environment = $this->sut->get_environment_info_per_fields( array() );

		$this->assertSame( $expected, $environment['wp_memory_limit'] );
	}
}

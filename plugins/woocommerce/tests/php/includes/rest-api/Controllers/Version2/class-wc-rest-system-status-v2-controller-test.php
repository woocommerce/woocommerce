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
	 * @testdox Should preserve exact numeric-string counts through the status endpoint.
	 */
	public function test_post_type_counts_remain_exact(): void {
		self::factory()->post->create_many( 3, array( 'post_type' => 'count_test' ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'GET', '/wc/v3/system_status' );
		$request->set_param( '_fields', 'post_type_counts' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '3', array_column( $response->get_data()['post_type_counts'], 'count', 'type' )['count_test'] );
	}

	/**
	 * @testdox Should omit the aggregation when the caller requests other fields.
	 */
	public function test_field_selection_skips_post_counts(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$aggregations = 0;
		add_filter(
			'query',
			static function ( $query ) use ( &$aggregations ) {
				if ( false !== strpos( $query, 'GROUP BY post_type' ) ) {
					++$aggregations;
				}
				return $query;
			}
		);
		$request = new WP_REST_Request( 'GET', '/wc/v3/system_status' );
		$request->set_param( '_fields', 'settings' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayNotHasKey( 'post_type_counts', $response->get_data() );
		$this->assertSame( 0, $aggregations, 'Selecting other fields should avoid the count query.' );
	}

	/**
	 * @testdox Should preserve exact counts for each site after switching sites.
	 * @group ms-required
	 */
	public function test_post_type_counts_are_site_scoped(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}
		self::factory()->post->create( array( 'post_type' => 'count_test' ) );
		$site_id = self::factory()->blog->create();
		switch_to_blog( $site_id );
		try {
			self::factory()->post->create_many( 2, array( 'post_type' => 'count_test' ) );
			$this->assertSame( '2', array_column( $this->sut->get_post_type_counts(), 'count', 'type' )['count_test'] );
		} finally {
			restore_current_blog();
		}
		$this->assertSame( '1', array_column( $this->sut->get_post_type_counts(), 'count', 'type' )['count_test'] );
	}

	/**
	 * @testdox Should request deferred counts only when explicitly needed or required by extension callbacks.
	 * @param bool $explicit Whether counts were explicitly requested.
	 * @param bool $extension Whether a report callback is attached.
	 * @param bool $expected Whether counts should be requested immediately.
	 * @testWith [false, false, false]
	 *           [true, false, true]
	 *           [false, true, true]
	 */
	public function test_admin_report_count_compatibility( bool $explicit, bool $extension, bool $expected ): void {
		remove_all_actions( 'woocommerce_system_status_report' );
		add_action( 'woocommerce_system_status_report', array( Automattic\WooCommerce\Internal\Admin\SystemStatusReport::get_instance(), 'system_status_report' ) );
		add_action( 'woocommerce_system_status_report', array( new ActionScheduler_AdminView(), 'system_status_report' ) );
		if ( $extension ) {
			add_action( 'woocommerce_system_status_report', '__return_null' );
		}
		$requested_fields = null;
		add_filter(
			'rest_pre_dispatch',
			static function ( $result, $server, $request ) use ( &$requested_fields ) {
				if ( '/wc/v3/system_status' === $request->get_route() ) {
					$requested_fields = $request->get_param( '_fields' );
					return new WP_REST_Response( array() );
				}
				return $result;
			},
			10,
			3
		);

		WC_Admin_Status::get_report_data( $explicit );
		if ( $expected ) {
			$this->assertNull( $requested_fields, 'Existing callbacks and explicit requests should receive the complete report.' );
		} else {
			$this->assertContains( 'environment', $requested_fields );
			$this->assertContains( 'database', $requested_fields );
			$this->assertNotContains( 'post_type_counts', $requested_fields );
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

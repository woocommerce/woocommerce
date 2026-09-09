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
	 * @testdox Should include stored post types without running an exact aggregation.
	 */
	public function test_get_post_type_counts_uses_estimates(): void {
		global $wpdb;

		$post_id = self::factory()->post->create( array( 'post_status' => 'trash' ) );
		$wpdb->update( $wpdb->posts, array( 'post_type' => "count'test" ), array( 'ID' => $post_id ) );
		self::factory()->post->create(
			array(
				'post_type'   => 'count_draft',
				'post_status' => 'draft',
			)
		);
		$empty_type_id = self::factory()->post->create();
		$wpdb->update( $wpdb->posts, array( 'post_type' => '' ), array( 'ID' => $empty_type_id ) );
		$queries = array();
		add_filter(
			'query',
			static function ( $query ) use ( &$queries ) {
				$queries[] = $query;
				return $query;
			}
		);

		$counts = array_column( $this->sut->get_post_type_counts(), 'count', 'type' );
		$this->assertArrayHasKey( "count'test", $counts, 'Unregistered post types and trashed posts should be included.' );
		$this->assertArrayHasKey( '', $counts, 'An empty stored type should not end discovery before other types.' );
		$this->assertArrayHasKey( 'count_draft', $counts, 'Drafts should be included.' );
		$this->assertIsString( $counts['count_draft'], 'Counts should retain their numeric-string representation.' );
		$this->assertCount( count( $counts ) + 2, $queries, 'Discovery should use one lookup per type plus an end lookup and one batched EXPLAIN.' );
		$this->assertStringStartsWith( 'EXPLAIN ', end( $queries ), 'The UNION must be explained, never executed.' );
		$this->assertStringNotContainsString( 'COUNT(', implode( ' ', $queries ), 'An exact aggregation should not run.' );
	}

	/**
	 * @testdox Should associate query estimates with their post types and preserve the REST response shape.
	 */
	public function test_get_post_type_counts_returns_database_estimates(): void {
		add_filter(
			'query',
			static function ( $query ) {
				if ( 0 === strpos( $query, 'SELECT post_type FROM' ) ) {
					if ( false === strpos( $query, 'WHERE' ) ) {
						return "SELECT 'product' AS post_type";
					}
					return false !== strpos( $query, "'product'" ) ? "SELECT 'revision' AS post_type" : 'SELECT NULL AS post_type WHERE 1 = 0';
				}
				if ( 0 === strpos( $query, 'EXPLAIN ' ) ) {
					return 'SELECT 2 AS id, 500000 AS `rows` UNION ALL SELECT 1, 174000';
				}
				return $query;
			}
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$request = new WP_REST_Request( 'GET', '/wc/v3/system_status' );
		$request->set_param( '_fields', 'post_type_counts' );
		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status(), 'The status endpoint should remain available.' );
		$this->assertSame(
			array(
				'post_type_counts' => array(
					array(
						'type'  => 'product',
						'count' => '174000',
					),
					array(
						'type'  => 'revision',
						'count' => '500000',
					),
				),
			),
			json_decode( wp_json_encode( $response->get_data() ), true ),
			'The endpoint should return estimates in the existing type/count structure.'
		);
	}

	/**
	 * @testdox Should return an empty array when either database query fails and retry on the next call.
	 * @param string $prefix Query prefix to fail.
	 * @testWith ["SELECT post_type FROM"]
	 *           ["EXPLAIN "]
	 */
	public function test_get_post_type_counts_handles_query_errors( string $prefix ): void {
		global $wpdb;

		self::factory()->post->create( array( 'post_type' => 'count_error' ) );
		$fail_query = static function ( $query ) use ( $prefix ) {
			return 0 === strpos( $query, $prefix ) ? 'SELECT * FROM nonexistent_status_count_table' : $query;
		};
		add_filter( 'query', $fail_query );
		$suppress_errors = $wpdb->suppress_errors();
		try {
			$this->assertSame( array(), $this->sut->get_post_type_counts(), 'Database failures should not produce invented counts.' );
		} finally {
			remove_filter( 'query', $fail_query );
			$wpdb->suppress_errors( $suppress_errors );
		}
		$this->assertArrayHasKey( 'count_error', array_column( $this->sut->get_post_type_counts(), 'count', 'type' ), 'The next call should retry.' );
	}

	/**
	 * @testdox Should return no counts without explaining an empty query when there are no post types.
	 */
	public function test_get_post_type_counts_handles_empty_table(): void {
		global $wpdb;

		add_filter(
			'query',
			static function ( $query ) {
				return 0 === strpos( $query, 'SELECT post_type FROM' ) ? 'SELECT NULL AS post_type WHERE 1 = 0' : $query;
			}
		);
		$before = $wpdb->num_queries;
		$this->assertSame( array(), $this->sut->get_post_type_counts(), 'An empty posts table should have no counts.' );
		$this->assertSame( $before + 1, $wpdb->num_queries, 'There should be no EXPLAIN for an empty type list.' );
	}

	/**
	 * @testdox Should discover types from the current site after switching sites.
	 * @group ms-required
	 */
	public function test_get_post_type_counts_are_site_scoped(): void {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'This test requires multisite.' );
		}

		self::factory()->post->create( array( 'post_type' => 'count_original' ) );
		$site_id = self::factory()->blog->create();
		switch_to_blog( $site_id );
		try {
			self::factory()->post->create( array( 'post_type' => 'count_other' ) );
			$counts = array_column( $this->sut->get_post_type_counts(), 'count', 'type' );
			$this->assertArrayHasKey( 'count_other', $counts, 'Counts should use the switched site posts table.' );
			$this->assertArrayNotHasKey( 'count_original', $counts, 'Other sites should not leak into the result.' );
		} finally {
			restore_current_blog();
		}
		$counts = array_column( $this->sut->get_post_type_counts(), 'count', 'type' );
		$this->assertArrayHasKey( 'count_original', $counts, 'Restoring the site should restore its type list.' );
		$this->assertArrayNotHasKey( 'count_other', $counts, 'The switched site should not leak into the result.' );
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

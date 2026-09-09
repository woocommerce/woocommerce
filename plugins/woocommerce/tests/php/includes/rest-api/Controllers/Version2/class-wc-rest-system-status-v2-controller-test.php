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
		delete_transient( 'wc_system_status_post_type_counts' );
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
	 * @testdox Should reuse post counts without another aggregation and refresh them after cache eviction.
	 */
	public function test_get_post_type_counts_caches_and_refreshes_counts(): void {
		self::factory()->post->create( array( 'post_type' => 'status_count_test' ) );
		$query_count = 0;
		add_filter(
			'query',
			function ( $query ) use ( &$query_count ) {
				if ( false !== strpos( $query, 'GROUP BY post_type' ) ) {
					++$query_count;
				}
				return $query;
			}
		);

		$counts = $this->sut->get_post_type_counts();
		$this->assertSame( '1', array_column( $counts, 'count', 'type' )['status_count_test'], 'Counts should include custom post types.' );

		self::factory()->post->create( array( 'post_type' => 'status_count_test' ) );
		$this->assertEquals( $counts, ( new WC_REST_System_Status_V2_Controller() )->get_post_type_counts(), 'New controller instances should reuse cached counts.' );
		$this->assertSame( 1, $query_count, 'Cached reads should not repeat the aggregation.' );

		delete_transient( 'wc_system_status_post_type_counts' );
		$counts = $this->sut->get_post_type_counts();
		$this->assertSame( '2', array_column( $counts, 'count', 'type' )['status_count_test'], 'Missing cache should refresh counts from the database.' );
		$this->assertSame( 2, $query_count, 'Refreshing should run one more aggregation.' );
	}

	/**
	 * @testdox Should retry a failed post-count query instead of caching its empty result.
	 */
	public function test_get_post_type_counts_does_not_cache_query_errors(): void {
		global $wpdb;

		$fail_query = static function ( $query ) {
			return false !== strpos( $query, 'GROUP BY post_type' ) ? 'SELECT * FROM nonexistent_status_count_table' : $query;
		};
		add_filter( 'query', $fail_query );
		$suppress_errors = $wpdb->suppress_errors();
		try {
			$this->assertSame( array(), $this->sut->get_post_type_counts(), 'Query errors should preserve the empty-array response.' );
			$this->assertFalse( get_transient( 'wc_system_status_post_type_counts' ), 'A failed query should not be cached.' );
		} finally {
			remove_filter( 'query', $fail_query );
			$wpdb->suppress_errors( $suppress_errors );
		}

		self::factory()->post->create( array( 'post_type' => 'status_count_test' ) );
		$counts = $this->sut->get_post_type_counts();
		$this->assertSame( '1', array_column( $counts, 'count', 'type' )['status_count_test'], 'The next request should retry the query.' );
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

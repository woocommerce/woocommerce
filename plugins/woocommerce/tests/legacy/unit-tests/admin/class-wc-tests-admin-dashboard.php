<?php
/**
 * Class WC_Tests_Admin_Dashboard file.
 *
 * @package WooCommerce\Tests\Admin
 */

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Tests for the WC_Admin_Report class.
 */
class WC_Tests_Admin_Dashboard extends WC_Unit_Test_Case {

	/**
	 * Set up for tests.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Mock http request to performance endpoint.
		add_filter( 'rest_pre_dispatch', array( $this, 'mock_rest_responses' ), 10, 3 );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_filter( 'rest_pre_dispatch', array( $this, 'mock_rest_responses' ), 10 );
	}

	/**
	 * Test: recent reviews widget placeholder.
	 */
	public function test_recent_reviews_widget_placeholder() {
		wp_set_current_user( $this->user );
		( new WC_Admin_Dashboard() )->recent_reviews();
		$this->expectOutputRegex( '/Loading reviews data.../' );
		$this->expectOutputRegex( '/wc-dashboard-widget-loading/' );
		$this->expectOutputRegex( '/wc-recent-reviews-widget-loading/' );
		$this->expectOutputRegex( '/wc-recent-reviews-widget-content/' );
	}

	/**
	 * Test: recent reviews widget content (new).
	 */
	public function test_recent_reviews_widget_content_new_version() {
		$product     = WC_Helper_Product::create_simple_product();
		$product_id  = $product->get_id();
		$comment_id1 = WC_Helper_Product::create_product_review( $product_id );
		WC_Helper_Product::create_product_review( $product_id );
		WC_Helper_Product::create_product_review( $product_id );
		WC_Helper_Product::create_product_review( $product_id );
		$comment_id5 = WC_Helper_Product::create_product_review( $product_id );

		wp_set_current_user( $this->user );
		( new WC_Admin_Dashboard() )->recent_reviews_content();
		$this->expectOutputRegex( "/#comment-{$comment_id1}/" );
		$this->expectOutputRegex( "/#comment-{$comment_id5}/" );
		$this->expectOutputRegex( '/reviewed by/' );

		$product->delete();
	}

	/**
	 * Test: recent reviews widget truncates long product and reviewer names.
	 */
	public function test_recent_reviews_widget_content_truncates_long_product_and_reviewer_names() {
		$product_name = 'Extra long dashboard review stress test product name with multiple descriptive words';
		$author_name  = 'Alexandria Montgomery-Silverstein With A Very Long Reviewer Name';
		$product      = WC_Helper_Product::create_simple_product();
		$product->set_name( $product_name );
		$product->save();

		$comment_id = WC_Helper_Product::create_product_review( $product->get_id(), 'Review content here' );
		wp_update_comment(
			array(
				'comment_ID'       => $comment_id,
				'comment_author'   => $author_name,
				'comment_date'     => current_time( 'mysql' ),
				'comment_date_gmt' => current_time( 'mysql', true ),
			)
		);

		wp_set_current_user( $this->user );
		ob_start();
		( new WC_Admin_Dashboard() )->recent_reviews_content();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'title="' . esc_attr( $product_name ) . '"', $html );
		$this->assertStringContainsString( '>' . esc_html( wc_trim_string( $product_name, 40 ) ) . '</a>', $html );
		$this->assertStringContainsString( 'title="' . esc_attr( $author_name ) . '"', $html );
		$this->assertStringContainsString( '>' . esc_html( wc_trim_string( $author_name, 24 ) ) . '</span>', $html );

		$product->delete();
	}

	/**
	 * Test: recent reviews widget content (legacy).
	 */
	public function test_recent_reviews_widget_content_legacy_version() {
		add_filter( 'woocommerce_report_recent_reviews_query_from', fn( string $sql ) => $sql );
		$this->expected_deprecated = array_merge( $this->expected_deprecated, array( 'woocommerce_report_recent_reviews_query_from' ) );

		$product     = WC_Helper_Product::create_simple_product();
		$product_id  = $product->get_id();
		$comment_id1 = WC_Helper_Product::create_product_review( $product_id );
		WC_Helper_Product::create_product_review( $product_id );
		WC_Helper_Product::create_product_review( $product_id );
		WC_Helper_Product::create_product_review( $product_id );
		$comment_id5 = WC_Helper_Product::create_product_review( $product_id );

		wp_set_current_user( $this->user );
		( new WC_Admin_Dashboard() )->recent_reviews_content();
		$this->expectOutputRegex( "/#comment-{$comment_id1}/" );
		$this->expectOutputRegex( "/#comment-{$comment_id5}/" );
		$this->expectOutputRegex( '/reviewed by/' );

		$product->delete();
	}

	/**
	 * Test: status_widget placeholder
	 */
	public function test_status_widget_placeholder() {
		$this->skip_if_hpos_enabled( 'We don\'t support legacy reports on HPOS' );
		wp_set_current_user( $this->user );
		( new WC_Admin_Dashboard() )->status_widget();
		$this->expectOutputRegex( '/Loading status data.../' );
		$this->expectOutputRegex( '/<div id="wc-status-widget-loading" class="wc-dashboard-widget-loading wc-status-widget-loading" aria-busy="true">/' );
	}

	/**
	 * Test: get_status_widget
	 */
	public function test_status_widget_content() {
		wp_set_current_user( $this->user );
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$this->expectOutputRegex( '/98,765\.00/' );

		( new WC_Admin_Dashboard() )->status_widget_content();

		$widget_output = $this->getActualOutput();

		$this->assertMatchesRegularExpression( '/page\=wc-admin\&\#038\;path\=\%2Fanalytics\%2Frevenue/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-admin\&\#038\;filter\=single_product/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-admin\&\#038\;type\=lowstock/', $widget_output );
		$this->assertMatchesRegularExpression( '/page\=wc-admin\&\#038\;type\=outofstock/', $widget_output );
		$this->assertDoesNotMatchRegularExpression( '/page\=wc-reports/', $widget_output, 'No status widget link should point to legacy Reports while Analytics is enabled.' );
	}

	/**
	 * Ways Analytics can be turned off.
	 *
	 * @return array<string, array{callable}>
	 */
	public function provide_analytics_disabled_setups(): array {
		return array(
			'woocommerce_admin_disabled filter' => array(
				function () {
					add_filter( 'woocommerce_admin_disabled', '__return_true' );
				},
			),
			'analytics feature turned off'      => array(
				function () {
					// A filter, not update_option(): saving the option makes Analytics redirect on the next settings save.
					add_filter( 'pre_option_woocommerce_analytics_enabled', fn() => 'no' );
				},
			),
		);
	}

	/**
	 * @testdox Should hide the sales rows, but keep the order and stock rows, when Analytics is disabled.
	 * @dataProvider provide_analytics_disabled_setups
	 *
	 * @param callable $disable_analytics Turns Analytics off.
	 */
	public function test_status_widget_hides_sales_rows_with_analytics_disabled( callable $disable_analytics ) {
		wp_set_current_user( $this->user );
		update_option( 'woocommerce_manage_stock', 'yes' );
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$disable_analytics();

		ob_start();
		( new WC_Admin_Dashboard() )->status_widget_content();
		$widget_output = ob_get_clean();

		$this->assertStringNotContainsString( 'sales-this-month', $widget_output, 'Net sales row should be hidden without Analytics.' );
		$this->assertStringNotContainsString( 'best-seller-this-month', $widget_output, 'Top seller row should be hidden without Analytics.' );
		$this->assertStringContainsString( 'processing-orders', $widget_output );
		$this->assertStringContainsString( 'on-hold-orders', $widget_output );
		$this->assertStringContainsString( 'page=wc-reports&#038;tab=stock&#038;report=low_in_stock', $widget_output );
		$this->assertStringContainsString( 'page=wc-reports&#038;tab=stock&#038;report=out_of_stock', $widget_output );
		$this->assertStringNotContainsString( 'path=%2Fanalytics', $widget_output, 'No status widget link should point to Analytics while it is disabled.' );
	}

	/**
	 * @testdox Should show sales rows, linked to legacy Reports, when a filter supplies only sales data while Analytics is disabled.
	 */
	public function test_status_widget_reports_filter_still_supplies_sales_rows_with_analytics_disabled() {
		wp_set_current_user( $this->user );
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		add_filter( 'woocommerce_admin_disabled', '__return_true' );
		add_filter( 'woocommerce_dashboard_status_widget_reports', array( $this, 'mock_replace_dashboard_status_widget_reports' ) );

		ob_start();
		( new WC_Admin_Dashboard() )->status_widget_content();
		$widget_output = ob_get_clean();

		$this->assertStringContainsString( '50.00 worth in the', $widget_output );
		$this->assertStringContainsString( 'page=wc-reports&#038;tab=orders&#038;range=month', $widget_output );
		$this->assertStringContainsString( 'page=wc-reports&#038;tab=orders&#038;report=sales_by_product', $widget_output );
	}

	/**
	 * @testdox Should show legacy sales data adjusted by a reports filter callback when Analytics is disabled.
	 */
	public function test_status_widget_reports_filter_can_adjust_legacy_net_sales_with_analytics_disabled() {
		wp_set_current_user( $this->user );
		add_filter( 'woocommerce_admin_disabled', '__return_true' );
		include_once WC_ABSPATH . 'includes/admin/class-wc-admin-reports.php';
		WC_Admin_Reports::register_hook_handlers();
		add_filter(
			'woocommerce_dashboard_status_widget_reports',
			function ( $reports ) {
				$reports['report_data']->net_sales = 4321;
				return $reports;
			},
			20
		);

		ob_start();
		( new WC_Admin_Dashboard() )->status_widget_content();
		$widget_output = ob_get_clean();

		$this->assertStringContainsString( 'sales-this-month', $widget_output );
		$this->assertStringContainsString( '4,321.00', $widget_output );
	}

	/**
	 * Helper method to mock rest_do_request method.
	 *
	 * @param false           $response Request arguments.
	 * @param WP_REST_Server  $rest_server rest server class.
	 * @param WP_REST_Request $request incoming request.
	 *
	 * @return WP_REST_Response|false mocked response or false to let WP perform a regular request.
	 */
	public function mock_rest_responses( $response, $rest_server, $request ) {
		if ( '/wc-analytics/reports/performance-indicators' === $request->get_route() ) {
			$response = new WP_REST_Response(
				array(
					'status' => 200,
				)
			);
			$response->set_data(
				array(
					array(
						'chart' => 'net_revenue',
						'value' => 98765.0,
					),
				)
			);
		} elseif ( '/wc-analytics/reports/revenue/stats' === $request->get_route() ) {
			$response = new WP_REST_Response(
				array(
					'status' => 200,
				)
			);
			$response->set_data(
				array(
					'intervals' => array(),
				)
			);
		}

		return $response;
	}

	/**
	 * Helper method to replace the data to to display in the status widget.
	 *
	 * @param array $status_widget_reports The data to display in the status widget.
	 */
	public function mock_replace_dashboard_status_widget_reports( $status_widget_reports ) {
		$report_data            = new stdClass();
		$report_data->net_sales = 123;

		$status_widget_reports['report_data']         = $report_data;
		$status_widget_reports['get_sales_sparkline'] = function () {
			return array(
				'total' => 50,
				'data'  => array(),
			);
		};

		return $status_widget_reports;
	}
}

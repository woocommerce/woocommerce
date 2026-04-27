<?php
/**
 * REST controller for Back in Stock Notifications analytics.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Admin\Analytics;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;

defined( 'ABSPATH' ) || exit;

/**
 * REST controller exposing analytics aggregations for Back in Stock Notifications.
 *
 * All routes are gated behind WOOCOMMERCE_BIS_ALPHA_ENABLED and require manage_woocommerce.
 *
 * @internal
 */
class RestController extends \WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'back-in-stock';

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/summary',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_summary' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'page'     => array(
							'description' => __( 'Current page of the collection.', 'woocommerce' ),
							'type'        => 'integer',
							'default'     => 1,
							'minimum'     => 1,
						),
						'per_page' => array(
							'description' => __( 'Maximum number of items to return per page.', 'woocommerce' ),
							'type'        => 'integer',
							'default'     => 25,
							'minimum'     => 1,
							'maximum'     => 100,
						),
					),
				),
				'schema' => array( $this, 'get_summary_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/timeseries',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_timeseries' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'days' => array(
							'description' => __( 'Number of days to include, ending today (GMT).', 'woocommerce' ),
							'type'        => 'integer',
							'default'     => 30,
							'minimum'     => 1,
							'maximum'     => 90,
						),
					),
				),
				'schema' => array( $this, 'get_timeseries_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/top-demand',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_top_demand' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'limit'   => array(
							'description' => __( 'Maximum number of products to return.', 'woocommerce' ),
							'type'        => 'integer',
							'default'     => 10,
							'minimum'     => 1,
							'maximum'     => 50,
						),
						'sort_by' => array(
							'description' => __( 'Ranking metric.', 'woocommerce' ),
							'type'        => 'string',
							'default'     => 'active_signups',
							'enum'        => array( 'active_signups', 'period_signups', 'most_overdue' ),
						),
						'window'  => array(
							'description' => __( 'Time window for the period_signups sort. Ignored for other sort_by values.', 'woocommerce' ),
							'type'        => 'string',
							'default'     => 'month',
							'enum'        => array( 'week', 'month', 'quarter' ),
						),
					),
				),
				'schema' => array( $this, 'get_top_demand_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/recent',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_recent' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'limit' => array(
							'description' => __( 'Maximum number of recent notifications to return.', 'woocommerce' ),
							'type'        => 'integer',
							'default'     => 10,
							'minimum'     => 1,
							'maximum'     => 50,
						),
					),
				),
				'schema' => array( $this, 'get_recent_schema' ),
			)
		);
	}

	/**
	 * Permission check shared by all routes.
	 *
	 * @return true|\WP_Error
	 */
	public function check_permission() {
		if ( ! Constants::is_true( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' ) ) {
			return new \WP_Error(
				'woocommerce_rest_bis_disabled',
				__( 'Back in Stock Notifications analytics are disabled.', 'woocommerce' ),
				array( 'status' => 404 )
			);
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new \WP_Error(
				'woocommerce_rest_cannot_view',
				__( 'Sorry, you cannot view these reports.', 'woocommerce' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * GET /summary
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_summary( \WP_REST_Request $request ): \WP_REST_Response {
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );

		$now           = time();
		$today_gmt     = gmdate( 'Y-m-d 00:00:00', $now );
		$one_week_ago  = gmdate( 'Y-m-d H:i:s', $now - ( 7 * DAY_IN_SECONDS ) );
		$one_month_ago = gmdate( 'Y-m-d H:i:s', $now - ( 30 * DAY_IN_SECONDS ) );

		$all_time    = NotificationQuery::get_totals();
		$this_month  = NotificationQuery::get_totals( $one_month_ago );
		$this_week   = NotificationQuery::get_totals( $one_week_ago );
		$today       = NotificationQuery::get_totals( $today_gmt );
		$per_product = NotificationQuery::get_per_product_summary( $per_page, $page );

		$response = rest_ensure_response(
			array(
				'totals'   => array(
					'all_time'   => $all_time,
					'this_month' => $this_month,
					'this_week'  => $this_week,
					'today'      => $today,
				),
				'products' => $this->prepare_product_rows( $per_product['rows'] ),
				'page'     => $page,
				'per_page' => $per_page,
				'total'    => $per_product['total'],
			)
		);

		$total_pages = $per_page > 0 ? (int) ceil( $per_product['total'] / $per_page ) : 0;
		$response->header( 'X-WP-Total', (string) $per_product['total'] );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * GET /timeseries
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_timeseries( \WP_REST_Request $request ): \WP_REST_Response {
		$days = (int) $request->get_param( 'days' );
		$days = $days > 0 ? $days : 30;

		$end_gmt   = gmdate( 'Y-m-d' );
		$start_gmt = gmdate( 'Y-m-d', time() - ( ( $days - 1 ) * DAY_IN_SECONDS ) );

		$rows = NotificationQuery::get_timeseries( $start_gmt, $end_gmt );

		// Backfill zero rows so clients always get a dense series.
		$by_date = array();
		foreach ( $rows as $row ) {
			$by_date[ $row['date'] ] = $row;
		}

		$dense = array();
		for ( $i = 0; $i < $days; $i++ ) {
			$date    = gmdate( 'Y-m-d', strtotime( $start_gmt . ' +' . $i . ' day' ) );
			$dense[] = $by_date[ $date ] ?? array(
				'date'               => $date,
				'signups'            => 0,
				'notifications_sent' => 0,
			);
		}

		return rest_ensure_response(
			array(
				'start_gmt' => $start_gmt,
				'end_gmt'   => $end_gmt,
				'days'      => $days,
				'rows'      => $dense,
			)
		);
	}

	/**
	 * GET /top-demand
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_top_demand( \WP_REST_Request $request ): \WP_REST_Response {
		$limit   = (int) $request->get_param( 'limit' );
		$sort_by = (string) $request->get_param( 'sort_by' );
		$window  = (string) $request->get_param( 'window' );

		switch ( $sort_by ) {
			case 'most_overdue':
				$rows = NotificationQuery::get_most_overdue( $limit );
				break;

			case 'period_signups':
				$rows = NotificationQuery::get_top_signups_in_window(
					$limit,
					$this->window_to_since_gmt( $window )
				);
				break;

			case 'active_signups':
			default:
				$rows = NotificationQuery::get_top_demand( $limit );
				break;
		}

		return rest_ensure_response(
			array(
				'rows'    => $this->prepare_product_rows( $rows ),
				'sort_by' => $sort_by,
				'window'  => 'period_signups' === $sort_by ? $window : null,
			)
		);
	}

	/**
	 * Translate a window slug into a `since_gmt` lower bound.
	 *
	 * @param string $window One of 'week', 'month', 'quarter'.
	 * @return string GMT datetime in `Y-m-d H:i:s` format.
	 */
	protected function window_to_since_gmt( string $window ): string {
		$days = array(
			'week'    => 7,
			'month'   => 30,
			'quarter' => 90,
		);
		$d    = $days[ $window ] ?? 30;
		return gmdate( 'Y-m-d H:i:s', time() - ( $d * DAY_IN_SECONDS ) );
	}

	/**
	 * GET /recent
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_recent( \WP_REST_Request $request ): \WP_REST_Response {
		$limit = (int) $request->get_param( 'limit' );
		$rows  = NotificationQuery::get_recent_activity( $limit );

		$prepared = array();
		foreach ( $rows as $row ) {
			$product_id = (int) $row['product_id'];
			$prepared[] = array_merge(
				$row,
				array(
					'product_name'      => $this->get_product_name( $product_id ),
					'product_edit_link' => $this->get_product_edit_link( $product_id ),
					'date_notified'     => $row['date_notified_gmt'] ? mysql_to_rfc3339( $row['date_notified_gmt'] ) : null,
				)
			);
		}

		return rest_ensure_response(
			array(
				'rows' => $prepared,
			)
		);
	}

	/**
	 * Hydrate product display fields onto the aggregation rows.
	 *
	 * @param array $rows Rows keyed by product_id and integer counts.
	 * @return array
	 */
	protected function prepare_product_rows( array $rows ): array {
		$prepared = array();
		foreach ( $rows as $row ) {
			$product_id = (int) $row['product_id'];
			$prepared[] = array_merge(
				$row,
				array(
					'product_name'      => $this->get_product_name( $product_id ),
					'product_edit_link' => $this->get_product_edit_link( $product_id ),
				)
			);
		}
		return $prepared;
	}

	/**
	 * Best-effort product name lookup; falls back to a placeholder for deleted products.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	protected function get_product_name( int $product_id ): string {
		if ( $product_id <= 0 ) {
			return '';
		}
		$product = wc_get_product( $product_id );
		if ( $product instanceof \WC_Product ) {
			return $product->get_name();
		}
		/* translators: %d: product ID */
		return sprintf( __( 'Product #%d (removed)', 'woocommerce' ), $product_id );
	}

	/**
	 * Return a wp-admin edit link for the product.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	protected function get_product_edit_link( int $product_id ): string {
		if ( $product_id <= 0 ) {
			return '';
		}
		return admin_url( 'post.php?post=' . $product_id . '&action=edit' );
	}

	/**
	 * Schema for /summary.
	 *
	 * @return array
	 */
	public function get_summary_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bis_summary',
			'type'       => 'object',
			'properties' => array(
				'totals'   => array(
					'type'       => 'object',
					'properties' => array(
						'all_time'  => array( 'type' => 'object' ),
						'this_week' => array( 'type' => 'object' ),
					),
				),
				'products' => array( 'type' => 'array' ),
				'page'     => array( 'type' => 'integer' ),
				'per_page' => array( 'type' => 'integer' ),
				'total'    => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Schema for /timeseries.
	 *
	 * @return array
	 */
	public function get_timeseries_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bis_timeseries',
			'type'       => 'object',
			'properties' => array(
				'start_gmt' => array( 'type' => 'string' ),
				'end_gmt'   => array( 'type' => 'string' ),
				'days'      => array( 'type' => 'integer' ),
				'rows'      => array( 'type' => 'array' ),
			),
		);
	}

	/**
	 * Schema for /top-demand.
	 *
	 * @return array
	 */
	public function get_top_demand_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bis_top_demand',
			'type'       => 'object',
			'properties' => array(
				'rows' => array( 'type' => 'array' ),
			),
		);
	}

	/**
	 * Schema for /recent.
	 *
	 * @return array
	 */
	public function get_recent_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bis_recent',
			'type'       => 'object',
			'properties' => array(
				'rows' => array( 'type' => 'array' ),
			),
		);
	}
}

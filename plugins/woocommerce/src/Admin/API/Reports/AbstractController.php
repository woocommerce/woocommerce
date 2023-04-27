<?php
namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * WC REST API Reports controller extended
 * to be shared as a generic base for all Analytics controllers.
 *
 * @internal
 * @extends WC_REST_Reports_Controller
 */
abstract class AbstractController extends \WC_REST_Reports_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-analytics';


	/**
	 * Add pagination headers and links.
	 *
	 * @param WP_REST_Request        $request   Request data.
	 * @param WP_REST_Response|array $response  Response data.
	 * @param int                    $total     Total results.
	 * @param int                    $page      Current page.
	 * @param int                    $max_pages Total amount of pages.
	 * @return WP_REST_Response
	 */
	public function add_pagination_headers( $request, $response, int $total, int $page, int $max_pages ) {
		$response = rest_ensure_response( $response );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $max_pages );

		$base = add_query_arg(
			$request->get_query_params(),
			rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) )
		);

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}
}

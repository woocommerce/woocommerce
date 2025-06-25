<?php
/**
 * REST API Reports controller extended to handle requests to the reports endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\GenericController;
use Automattic\WooCommerce\Admin\API\Reports\OrderAwareControllerTrait;

/**
 * Reports controller class.
 *
 * Controller that handles the endpoint that returns all available analytics endpoints.
 *
 * @internal
 * @extends GenericController
 */
class Controller extends GenericController {

	use OrderAwareControllerTrait;

	/**
	 * Get all reports.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return array|WP_Error
	 */
	public function get_items( $request ) {
		$data    = array();
		$reports = array(
			array(
				'slug'        => 'performance-indicators',
				'description' => __( 'Batch endpoint for getting specific performance indicators from `stats` endpoints.', 'woocommerce' ),
			),
			array(
				'slug'        => 'revenue/stats',
				'description' => __( 'Stats about revenue.', 'woocommerce' ),
			),
			array(
				'slug'        => 'orders/stats',
				'description' => __( 'Stats about orders.', 'woocommerce' ),
			),
			array(
				'slug'        => 'products',
				'description' => __( 'Products detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'products/stats',
				'description' => __( 'Stats about products.', 'woocommerce' ),
			),
			array(
				'slug'        => 'variations',
				'description' => __( 'Variations detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'variations/stats',
				'description' => __( 'Stats about variations.', 'woocommerce' ),
			),
			array(
				'slug'        => 'categories',
				'description' => __( 'Product categories detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'categories/stats',
				'description' => __( 'Stats about product categories.', 'woocommerce' ),
			),
			array(
				'slug'        => 'coupons',
				'description' => __( 'Coupons detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'coupons/stats',
				'description' => __( 'Stats about coupons.', 'woocommerce' ),
			),
			array(
				'slug'        => 'taxes',
				'description' => __( 'Taxes detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'taxes/stats',
				'description' => __( 'Stats about taxes.', 'woocommerce' ),
			),
			array(
				'slug'        => 'downloads',
				'description' => __( 'Product downloads detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'downloads/files',
				'description' => __( 'Product download files detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'downloads/stats',
				'description' => __( 'Stats about product downloads.', 'woocommerce' ),
			),
			array(
				'slug'        => 'customers',
				'description' => __( 'Customers detailed reports.', 'woocommerce' ),
			),
			array(
				'slug'        => 'customers/stats',
				'description' => __( 'Stats about groups of customers.', 'woocommerce' ),
			),
		);

		/**
		 * Filter the list of allowed reports, so that data can be loaded from third party extensions in addition to WooCommerce core.
		 * Array items should be in format of array( 'slug' => 'downloads/stats', 'description' =>  '',
		 * 'url' => '', and 'path' => '/wc-ext/v1/...'.
		 *
		 * @param array $endpoints The list of allowed reports..
		 */
		$reports = apply_filters( 'woocommerce_admin_reports', $reports );

		foreach ( $reports as $report ) {
			// Silently skip non-compliant reports. Like the ones for WC_Admin_Reports::get_reports().
			if ( empty( $report['slug'] ) ) {
				continue;
			}

			if ( empty( $report['path'] ) ) {
				$report['path'] = '/' . $this->namespace . '/reports/' . $report['slug'];
			}

			// Allows a different admin page to be loaded here,
			// or allows an empty url if no report exists for a set of performance indicators.
			if ( ! isset( $report['url'] ) ) {
				if ( '/stats' === substr( $report['slug'], -6 ) ) {
					$url_slug = substr( $report['slug'], 0, -6 );
				} else {
					$url_slug = $report['slug'];
				}

				$report['url'] = '/analytics/' . $url_slug;
			}

			$item   = $this->prepare_item_for_response( (object) $report, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a report object for serialization.
	 *
	 * @param stdClass        $report  Report data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$data = array(
			'slug'        => $report->slug,
			'description' => $report->description,
			'path'        => $report->path,
		);

		// Wrap the data in a response object.
		$response = parent::prepare_item_for_response( $data, $request );
		$response->add_links(
			array(
				'self'       => array(
					'href' => rest_url( $report->path ),
				),
				'report'     => array(
					'href' => $report->url,
				),
				'collection' => array(
					'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
				),
			)
		);

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report', $response, $report, $request );
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @override WP_REST_Controller::get_item_schema()
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report',
			'type'       => 'object',
			'properties' => array(
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'A human-readable description of the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'path'        => array(
					'description' => __( 'API path.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}

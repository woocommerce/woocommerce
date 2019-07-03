<?php
/**
 * REST API Reports Export Controller
 *
 * Handles requests to:
 * - /reports/[report]/export
 * - /reports/[report]/export/[id]/status
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reports Export controller.
 *
 * @package WooCommerce Admin/API
 * @extends WC_REST_Data_Controller
 */
class WC_Admin_REST_Reports_Export_Controller extends WC_Admin_REST_Reports_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v4';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/(?P<type>[a-z]+)/export';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'export_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_export_collection_params(),
				),
				'schema' => array( $this, 'get_export_public_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<export_id>[a-z0-9]+)/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'export_status' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_export_status_public_schema' ),
			)
		);
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_export_collection_params() {
		$params                = array();
		$params['report_args'] = array(
			'description'       => __( 'Parameters to pass on to the exported report.', 'woocommerce-admin' ),
			'type'              => 'object',
			'validate_callback' => 'rest_validate_request_arg', // @todo: use each controller's schema?
		);
		return $params;
	}

	/**
	 * Get the Report Export's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_export_public_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_export',
			'type'       => 'object',
			'properties' => array(
				'status'  => array(
					'description' => __( 'Regeneration status.', 'woocommerce-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'message' => array(
					'description' => __( 'Regenerate data message.', 'woocommerce-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the Export status schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_export_status_public_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_export_status',
			'type'       => 'object',
			'properties' => array(
				'percent_complete' => array(
					'description' => __( 'Percentage complete.', 'woocommerce-admin' ),
					'type'        => 'int',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'download_url'     => array(
					'description' => __( 'Export download URL.', 'woocommerce-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Export data based on user request params.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function export_items( $request ) {
		$report_type = $request['type'];
		$report_args = empty( $request['report_args'] ) ? array() : $request['report_args'];
		$export_id   = str_replace( '.', '', microtime( true ) );

		$total_rows = WC_Admin_Report_Exporter::queue_report_export( $export_id, $report_type, $report_args );

		if ( 0 === $total_rows ) {
			$response = rest_ensure_response(
				array(
					'status'  => 'error',
					'message' => __( 'There is no data to export for the given request.', 'woocommerce-admin' ),
				)
			);

		} else {
			$response = rest_ensure_response(
				array(
					'status'  => 'success',
					'message' => __( 'Your report file is being generated.', 'woocommerce-admin' ),
				)
			);

			// Include a link to the export status endpoint.
			$response->add_links(
				array(
					'status' => array(
						'href' => rest_url( sprintf( '%s/reports/%s/export/%s/status', $this->namespace, $report_type, $export_id ) ),
					),
				)
			);

			WC_Admin_Report_Exporter::update_export_percentage_complete( $report_type, $export_id, 0 );
		}

		$data = $this->prepare_response_for_collection( $response );

		return rest_ensure_response( $data );
	}

	/**
	 * Export status based on user request params.
	 *
	 * @param  WP_REST_Request $request Request data.
	 * @return WP_Error|WP_REST_Response
	 */
	public function export_status( $request ) {
		$report_type = $request['type'];
		$export_id   = $request['export_id'];
		$percentage  = WC_Admin_Report_Exporter::get_export_percentage_complete( $report_type, $export_id );

		if ( false === $percentage ) {
			return new WP_Error(
				'woocommerce_admin_reports_export_invalid_id',
				__( 'Sorry, there is no export with that ID.', 'woocommerce-admin' ),
				array( 'status' => 404 )
			);
		}

		$result = array(
			'percent_complete' => $percentage,
		);

		// @todo - add thing in the links below instead?
		if ( 100 === $percentage ) {
			$query_args = array(
				'action'   => WC_Admin_Report_Exporter::DOWNLOAD_EXPORT_ACTION,
				'filename' => "wc-{$report_type}-report-export-{$export_id}",
			);

			$result['download_url'] = add_query_arg( $query_args, admin_url() );
		}

		// Wrap the data in a response object.
		$response = rest_ensure_response( $result );
		// Include a link to the export status endpoint.
		$response->add_links(
			array(
				'self' => array(
					'href' => rest_url( sprintf( '%s/reports/%s/export/%s/status', $this->namespace, $report_type, $export_id ) ),
				),
			)
		);

		$data = $this->prepare_response_for_collection( $response );

		return rest_ensure_response( $data );
	}
}

<?php
/**
 * REST API Reports taxes controller
 *
 * Handles requests to the /reports/taxes endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Taxes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\ExportableInterface;
use Automattic\WooCommerce\Admin\API\Reports\ExportableTraits;
use Automattic\WooCommerce\Admin\API\Reports\GenericController;
use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API Reports taxes controller class.
 *
 * @internal
 * @extends GenericController
 */
class Controller extends GenericController implements ExportableInterface {
	/**
	 * Exportable traits.
	 */
	use ExportableTraits;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/taxes';

	/**
	 * Get data from `'taxes'` Query.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'taxes' );
		return $query->get_data();
	}

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args                        = array();
		$args['before']              = $request['before'];
		$args['after']               = $request['after'];
		$args['page']                = $request['page'];
		$args['per_page']            = $request['per_page'];
		$args['orderby']             = $request['orderby'];
		$args['order']               = $request['order'];
		$args['taxes']               = $request['taxes'];
		$args['force_cache_refresh'] = $request['force_cache_refresh'];

		return $args;
	}

	/**
	 * Prepare a report data item for serialization.
	 *
	 * @param mixed           $report  Report data item as returned from Data Store.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		$response = parent::prepare_item_for_response( $report, $request );

		// Map to `object` for backwards compatibility.
		$report = (object) $report;
		$response->add_links( $this->prepare_links( $report ) );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_taxes', $response, $report, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Reports_Query $object Object data.
	 * @return array
	 */
	protected function prepare_links( $object ) {
		$links = array(
			'tax' => array(
				'href' => rest_url( sprintf( '/%s/taxes/%d', $this->namespace, $object->tax_rate_id ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_taxes',
			'type'       => 'object',
			'properties' => array(
				'tax_rate_id'  => array(
					'description' => __( 'Tax rate ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'         => array(
					'description' => __( 'Tax rate name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'tax_rate'     => array(
					'description' => __( 'Tax rate.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'country'      => array(
					'description' => __( 'Country / Region.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'state'        => array(
					'description' => __( 'State.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'priority'     => array(
					'description' => __( 'Priority.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_tax'    => array(
					'description' => __( 'Total tax.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'order_tax'    => array(
					'description' => __( 'Order tax.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_tax' => array(
					'description' => __( 'Shipping tax.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'orders_count' => array(
					'description' => __( 'Number of orders.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
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
		$params                       = parent::get_collection_params();
		$params['orderby']['default'] = 'tax_rate_id';
		$params['orderby']['enum']    = array(
			'name',
			'tax_rate_id',
			'tax_code',
			'rate',
			'order_tax',
			'total_tax',
			'shipping_tax',
			'orders_count',
		);
		$params['taxes']              = array(
			'description'       => __( 'Limit result set to items assigned one or more tax rates.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'string',
			),
		);

		return $params;
	}

	/**
	 * Get the column names for export.
	 *
	 * @return array Key value pair of Column ID => Label.
	 */
	public function get_export_columns() {
		return array(
			'tax_code'     => __( 'Tax code', 'woocommerce' ),
			'rate'         => __( 'Rate', 'woocommerce' ),
			'total_tax'    => __( 'Total tax', 'woocommerce' ),
			'order_tax'    => __( 'Order tax', 'woocommerce' ),
			'shipping_tax' => __( 'Shipping tax', 'woocommerce' ),
			'orders_count' => __( 'Orders', 'woocommerce' ),
		);
	}

	/**
	 * Get the column values for export.
	 *
	 * @param array $item Single report item/row.
	 * @return array Key value pair of Column ID => Row Value.
	 */
	public function prepare_item_for_export( $item ) {
		return array(
			'tax_code'     => \WC_Tax::get_rate_code( $item['tax_rate_id'] ),
			'rate'         => $item['tax_rate'],
			'total_tax'    => self::csv_number_format( $item['total_tax'] ),
			'order_tax'    => self::csv_number_format( $item['order_tax'] ),
			'shipping_tax' => self::csv_number_format( $item['shipping_tax'] ),
			'orders_count' => $item['orders_count'],
		);
	}
}

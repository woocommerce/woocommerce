<?php
/**
 * REST API Reports categories controller
 *
 * Handles requests to the /reports/categories endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Categories;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\ExportableInterface;
use Automattic\WooCommerce\Admin\API\Reports\GenericController;
use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\OrderAwareControllerTrait;

/**
 * REST API Reports categories controller class.
 *
 * @internal
 * @extends \Automattic\WooCommerce\Admin\API\Reports\GenericController
 */
class Controller extends GenericController implements ExportableInterface {

	use OrderAwareControllerTrait;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/categories';

	/**
	 * Get data from `'categories'` Query.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'categories' );
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
		$args['interval']            = $request['interval'];
		$args['page']                = $request['page'];
		$args['per_page']            = $request['per_page'];
		$args['orderby']             = $request['orderby'];
		$args['order']               = $request['order'];
		$args['extended_info']       = $request['extended_info'];
		$args['category_includes']   = (array) $request['categories'];
		$args['status_is']           = (array) $request['status_is'];
		$args['status_is_not']       = (array) $request['status_is_not'];
		$args['force_cache_refresh'] = $request['force_cache_refresh'];

		return $args;
	}

	/**
	 * Prepare a report data item for serialization.
	 *
	 * @param mixed            $report  Report data item as returned from Data Store.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		// Wrap the data in a response object.
		$response = parent::prepare_item_for_response( $report, $request );
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
		return apply_filters( 'woocommerce_rest_prepare_report_categories', $response, $report, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param \Automattic\WooCommerce\Admin\API\Reports\GenericQuery $object Object data.
	 * @return array
	 */
	protected function prepare_links( $object ) {
		$links = array(
			'category' => array(
				'href' => rest_url( sprintf( '/%s/products/categories/%d', $this->namespace, $object['category_id'] ) ),
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
			'title'      => 'report_categories',
			'type'       => 'object',
			'properties' => array(
				'category_id'    => array(
					'description' => __( 'Category ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'items_sold'     => array(
					'description' => __( 'Amount of items sold.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'net_revenue'    => array(
					'description' => __( 'Total sales.', 'woocommerce' ),
					'type'        => 'number',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'orders_count'   => array(
					'description' => __( 'Number of orders.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'products_count' => array(
					'description' => __( 'Amount of products.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'extended_info'  => array(
					'name' => array(
						'type'        => 'string',
						'readonly'    => true,
						'context'     => array( 'view', 'edit' ),
						'description' => __( 'Category name.', 'woocommerce' ),
					),
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
		$params['orderby']['default'] = 'category_id';
		$params['orderby']['enum']    = array(
			'category_id',
			'items_sold',
			'net_revenue',
			'orders_count',
			'products_count',
			'category',
		);
		$params['interval']           = array(
			'description'       => __( 'Time interval to use for buckets in the returned data.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'week',
			'enum'              => array(
				'hour',
				'day',
				'week',
				'month',
				'quarter',
				'year',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['status_is']          = array(
			'description'       => __( 'Limit result set to items that have the specified order status.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_slug_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'enum' => self::get_order_statuses(),
				'type' => 'string',
			),
		);
		$params['status_is_not']      = array(
			'description'       => __( 'Limit result set to items that don\'t have the specified order status.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_slug_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'enum' => self::get_order_statuses(),
				'type' => 'string',
			),
		);
		$params['categories']         = array(
			'description'       => __( 'Limit result set to all items that have the specified term assigned in the categories taxonomy.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['extended_info']      = array(
			'description'       => __( 'Add additional piece of info about each category to the report.', 'woocommerce' ),
			'type'              => 'boolean',
			'default'           => false,
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get the column names for export.
	 *
	 * @return array Key value pair of Column ID => Label.
	 */
	public function get_export_columns() {
		$export_columns = array(
			'category'       => __( 'Category', 'woocommerce' ),
			'items_sold'     => __( 'Items sold', 'woocommerce' ),
			'net_revenue'    => __( 'Net Revenue', 'woocommerce' ),
			'products_count' => __( 'Products', 'woocommerce' ),
			'orders_count'   => __( 'Orders', 'woocommerce' ),
		);

		/**
		 * Filter to add or remove column names from the categories report for
		 * export.
		 *
		 * @since 1.6.0
		 */
		return apply_filters(
			'woocommerce_report_categories_export_columns',
			$export_columns
		);
	}

	/**
	 * Get the column values for export.
	 *
	 * @param array $item Single report item/row.
	 * @return array Key value pair of Column ID => Row Value.
	 */
	public function prepare_item_for_export( $item ) {
		$export_item = array(
			'category'       => $item['extended_info']['name'],
			'items_sold'     => $item['items_sold'],
			'net_revenue'    => $item['net_revenue'],
			'products_count' => $item['products_count'],
			'orders_count'   => $item['orders_count'],
		);

		/**
		 * Filter to prepare extra columns in the export item for the
		 * categories export.
		 *
		 * @since 1.6.0
		 */
		return apply_filters(
			'woocommerce_report_categories_prepare_export_item',
			$export_item,
			$item
		);
	}
}

<?php
/**
 * REST API Reports downloads controller
 *
 * Handles requests to the /reports/downloads endpoint.
 */

namespace Automattic\WooCommerce\Admin\API\Reports\Downloads;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\API\Reports\ExportableInterface;
use Automattic\WooCommerce\Admin\API\Reports\GenericController;
use Automattic\WooCommerce\Admin\API\Reports\GenericQuery;
use Automattic\WooCommerce\Admin\API\Reports\OrderAwareControllerTrait;

/**
 * REST API Reports downloads controller class.
 *
 * @internal
 * @extends Automattic\WooCommerce\Admin\API\Reports\GenericController
 */
class Controller extends GenericController implements ExportableInterface {

	use OrderAwareControllerTrait;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'reports/downloads';

	/**
	 * Get data from `'downloads'` GenericQuery.
	 *
	 * @override GenericController::get_datastore_data()
	 *
	 * @param array $query_args Query arguments.
	 * @return mixed Results from the data store.
	 */
	protected function get_datastore_data( $query_args = array() ) {
		$query = new GenericQuery( $query_args, 'downloads' );
		return $query->get_data();
	}

	/**
	 * Prepare a report data item for serialization.
	 *
	 * @param Array           $report  Report data item as returned from Data Store.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $report, $request ) {
		// Wrap the data in a response object.
		$response = parent::prepare_item_for_response( $report, $request );
		$response->add_links( $this->prepare_links( $report ) );

		$response->data['date'] = get_date_from_gmt( $report['date_gmt'], 'Y-m-d H:i:s' );

		// Figure out file name.
		// Matches https://github.com/woocommerce/woocommerce/blob/4be0018c092e617c5d2b8c46b800eb71ece9ddef/includes/class-wc-download-handler.php#L197.
		$product_id = intval( $report['product_id'] );
		$_product   = wc_get_product( $product_id );

		// Make sure the product hasn't been deleted.
		if ( $_product ) {
			$file_path                   = $_product->get_file_download_path( $report['download_id'] );
			$filename                    = basename( $file_path );
			$response->data['file_name'] = apply_filters( 'woocommerce_file_download_filename', $filename, $product_id );
			$response->data['file_path'] = $file_path;
		} else {
			$response->data['file_name'] = '';
			$response->data['file_path'] = '';
		}

		$customer                       = new \WC_Customer( $report['user_id'] );
		$response->data['username']     = $customer->get_username();
		$response->data['order_number'] = $this->get_order_number( $report['order_id'] );

		/**
		 * Filter a report returned from the API.
		 *
		 * Allows modification of the report data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $report   The original report object.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_report_downloads', $response, $report, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param Array $object Object data.
	 * @return array        Links for the given post.
	 */
	protected function prepare_links( $object ) {
		$links = array(
			'product' => array(
				'href'       => rest_url( sprintf( '/%s/%s/%d', $this->namespace, 'products', $object['product_id'] ) ),
				'embeddable' => true,
			),
		);

		return $links;
	}

	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args       = array();
		$registered = array_keys( $this->get_collection_params() );
		foreach ( $registered as $param_name ) {
			if ( isset( $request[ $param_name ] ) ) {
				$args[ $param_name ] = $request[ $param_name ];
			}
		}
		return $args;
	}
	/**
	 * Get the Report's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'report_downloads',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'ID.', 'woocommerce' ),
				),
				'product_id'   => array(
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Product ID.', 'woocommerce' ),
				),
				'date'         => array(
					'description' => __( "The date of the download, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_gmt'     => array(
					'description' => __( 'The date of the download, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'download_id'  => array(
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Download ID.', 'woocommerce' ),
				),
				'file_name'    => array(
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'File name.', 'woocommerce' ),
				),
				'file_path'    => array(
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'File URL.', 'woocommerce' ),
				),
				'order_id'     => array(
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Order ID.', 'woocommerce' ),
				),
				'order_number' => array(
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Order Number.', 'woocommerce' ),
				),
				'user_id'      => array(
					'type'        => 'integer',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'User ID for the downloader.', 'woocommerce' ),
				),
				'username'     => array(
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'User name of the downloader.', 'woocommerce' ),
				),
				'ip_address'   => array(
					'type'        => 'string',
					'readonly'    => true,
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'IP address for the downloader.', 'woocommerce' ),
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
		$params                        = parent::get_collection_params();
		$params['orderby']['enum']     = $this->apply_custom_orderby_filters(
			array(
				'date',
				'product',
			)
		);
		$params['match']               = array(
			'description'       => __( 'Indicates whether all the conditions should be true for the resulting set, or if any one of them is sufficient. Match affects the following parameters: products, orders, username, ip_address.', 'woocommerce' ),
			'type'              => 'string',
			'default'           => 'all',
			'enum'              => array(
				'all',
				'any',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['product_includes']    = array(
			'description'       => __( 'Limit result set to items that have the specified product(s) assigned.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['product_excludes']    = array(
			'description'       => __( 'Limit result set to items that don\'t have the specified product(s) assigned.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'validate_callback' => 'rest_validate_request_arg',
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['order_includes']      = array(
			'description'       => __( 'Limit result set to items that have the specified order ids.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['order_excludes']      = array(
			'description'       => __( 'Limit result set to items that don\'t have the specified order ids.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['customer_includes']   = array(
			'description'       => __( 'Limit response to objects that have the specified user ids.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['customer_excludes']   = array(
			'description'       => __( 'Limit response to objects that don\'t have the specified user ids.', 'woocommerce' ),
			'type'              => 'array',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'integer',
			),
		);
		$params['ip_address_includes'] = array(
			'description'       => __( 'Limit response to objects that have a specified ip address.', 'woocommerce' ),
			'type'              => 'array',
			'validate_callback' => 'rest_validate_request_arg',
			'items'             => array(
				'type' => 'string',
			),
		);
		$params['ip_address_excludes'] = array(
			'description'       => __( 'Limit response to objects that don\'t have a specified ip address.', 'woocommerce' ),
			'type'              => 'array',
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
		$export_columns = array(
			'date'         => __( 'Date', 'woocommerce' ),
			'product'      => __( 'Product title', 'woocommerce' ),
			'file_name'    => __( 'File name', 'woocommerce' ),
			'order_number' => __( 'Order #', 'woocommerce' ),
			'user_id'      => __( 'User Name', 'woocommerce' ),
			'ip_address'   => __( 'IP', 'woocommerce' ),
		);

		/**
		 * Filter to add or remove column names from the downloads report for
		 * export.
		 *
		 * @since 1.6.0
		 */
		return apply_filters(
			'woocommerce_filter_downloads_export_columns',
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
			'date'         => $item['date'],
			'product'      => $item['_embedded']['product'][0]['name'],
			'file_name'    => $item['file_name'],
			'order_number' => $item['order_number'],
			'user_id'      => $item['username'],
			'ip_address'   => $item['ip_address'],
		);

		/**
		 * Filter to prepare extra columns in the export item for the downloads
		 * report.
		 *
		 * @since 1.6.0
		 */
		return apply_filters(
			'woocommerce_report_downloads_prepare_export_item',
			$export_item,
			$item
		);
	}
}

<?php
/**
 * REST API Attribute Suggestion Controller
 *
 * Handles requests to /wc-admin/wooai/product-data-suggestions
 *
 * @package Woo_AI
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\AI\Completion\Jetpack_Completion_Service;
use Automattic\WooCommerce\AI\ProductDataSuggestion\Product_Data_Suggestion_Prompt_Generator;
use Automattic\WooCommerce\AI\ProductDataSuggestion\Product_Data_Suggestion_Request;
use Automattic\WooCommerce\AI\ProductDataSuggestion\Product_Data_Suggestion_Service;
use Automattic\WooCommerce\AI\PromptFormatter\Json_Request_Formatter;
use Automattic\WooCommerce\AI\PromptFormatter\Product_Attribute_Formatter;
use Automattic\WooCommerce\AI\PromptFormatter\Product_Category_Formatter;
use Exception;
use WC_REST_Data_Controller;
use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Attribute Suggestion API controller.
 *
 * @internal
 * @extends WC_REST_Data_Controller
 */
class Product_Data_Suggestion_API extends WC_REST_Data_Controller {
	/**
	 * The product data suggestion service.
	 *
	 * @var Product_Data_Suggestion_Service
	 */
	protected $product_data_suggestion_service;

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wooai';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'product-data-suggestions';

	/**
	 * Constructor
	 */
	public function __construct() {
		$json_request_formatter      = new Json_Request_Formatter();
		$product_category_formatter  = new Product_Category_Formatter();
		$product_attribute_formatter = new Product_Attribute_Formatter();
		$prompt_generator            = new Product_Data_Suggestion_Prompt_Generator( $product_category_formatter, $product_attribute_formatter, $json_request_formatter );
		$completion_service          = new Jetpack_Completion_Service();

		$this->product_data_suggestion_service = new Product_Data_Suggestion_Service( $prompt_generator, $completion_service );

		$this->register_routes();
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_response' ),
					'permission_callback' => array( $this, 'get_response_permission_check' ),
					'args'                => array(
						'requested_data' => array(
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'sanitize_text_field',
							'required'          => true,
						),
						'name'           => array(
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'description'    => array(
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => '',
						),
						'categories'     => array(
							'type'              => 'array',
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'wp_parse_id_list',
							'items'             => array(
								'type' => 'integer',
							),
							'default'           => array(),
						),
						'tags'           => array(
							'type'              => 'array',
							'validate_callback' => 'rest_validate_request_arg',
							'items'             => array(
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
							),
							'default'           => array(),
						),
						'attributes'     => array(
							'type'              => 'array',
							'validate_callback' => 'rest_validate_request_arg',
							'default'           => array(),
							'items'             => array(
								'type'       => 'object',
								'properties' => array(
									'key'   => array(
										'type' => 'string',
										'sanitize_callback' => 'sanitize_text_field',
									),
									'value' => array(
										'type' => 'string',
										'sanitize_callback' => 'sanitize_text_field',
									),
								),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Check if a given request has access to create a product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_response_permission_check( WP_REST_Request $request ) {
		if ( ! wc_rest_check_post_permissions( 'product', 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get the product-data suggestions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_response( WP_REST_Request $request ) {
		$requested_data = $request->get_param( 'requested_data' );
		$name           = $request->get_param( 'name' );
		$description    = $request->get_param( 'description' );
		$categories     = $request->get_param( 'categories' );
		$tags           = $request->get_param( 'tags' );
		$attributes     = $request->get_param( 'attributes' );

		// Strip HTML tags from the description.
		if ( ! empty( $description ) ) {
			$description = wp_strip_all_tags( $request->get_param( 'description' ) );
		}

		// Check if enough data is provided in the name and description to get suggestions.
		if ( strlen( $name ) < 10 && strlen( $description ) < 50 ) {
			return new WP_Error( 'error', __( 'Please provide a name with at least 10 characters, or a description with at least 50 characters.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		try {
			$product_data_request = new Product_Data_Suggestion_Request( $requested_data, $name, $description, $tags, $categories, $attributes );
			$suggestions          = $this->product_data_suggestion_service->get_suggestions( $product_data_request );
		} catch ( Exception $e ) {
			return new WP_Error( 'error', $e->getMessage(), array( 'status' => 400 ) );
		}

		return rest_ensure_response( $suggestions );

	}

}

new Product_Data_Suggestion_API();

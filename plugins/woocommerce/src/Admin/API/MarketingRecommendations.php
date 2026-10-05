<?php
/**
 * REST API MarketingRecommendations Controller
 *
 * Handles requests to /marketing/recommendations.
 */

namespace Automattic\WooCommerce\Admin\API;

use Automattic\WooCommerce\Admin\Features\MarketingRecommendations\Init as MarketingRecommendationsInit;
use WC_REST_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * MarketingRecommendations Controller.
 *
 * @internal
 * @extends WC_REST_Controller
 * @since 7.4.0
 */
class MarketingRecommendations extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-admin';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'marketing/recommendations';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'category' => array(
							'type'              => 'string',
							'validate_callback' => 'rest_validate_request_arg',
							'sanitize_callback' => 'sanitize_title_with_dashes',
							'enum'              => array( 'channels', 'extensions' ),
							'required'          => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check whether a given request has permission to view marketing recommendations.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view marketing channels.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves a collection of recommendations.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$category = $request->get_param( 'category' );
		if ( 'channels' === $category ) {
			$items = MarketingRecommendationsInit::get_recommended_marketing_channels();
		} elseif ( 'extensions' === $category ) {
			$items = MarketingRecommendationsInit::get_recommended_marketing_extensions_excluding_channels();
		} else {
			return new WP_Error( 'woocommerce_rest_invalid_category', __( 'The specified category for recommendations is invalid. Allowed values: "channels", "extensions".', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$responses = array();
		foreach ( $items as $item ) {
			$response    = $this->prepare_item_for_response( $item, $request );
			$responses[] = $this->prepare_response_for_collection( $response );
		}

		return rest_ensure_response( $responses );
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @param array           $item    WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'marketing_recommendation',
			'type'       => 'object',
			'properties' => array(
				'title'          => array(
					'type'     => 'string',
					'context'  => array( 'view' ),
					'readonly' => true,
				),
				'description'    => array(
					'type'     => 'string',
					'context'  => array( 'view' ),
					'readonly' => true,
				),
				'url'            => array(
					'type'     => 'string',
					'context'  => array( 'view' ),
					'readonly' => true,
				),
				'direct_install' => array(
					'type'     => 'string',
					'context'  => array( 'view' ),
					'readonly' => true,
				),
				'icon'           => array(
					'type'     => 'string',
					'context'  => array( 'view' ),
					'readonly' => true,
				),
				'product'        => array(
					'type'     => 'string',
					'context'  => array( 'view' ),
					'readonly' => true,
				),
				'plugin'         => array(
					'type'     => 'string',
					'context'  => array( 'view' ),
					'readonly' => true,
				),
				'categories'     => array(
					'type'     => 'array',
					'context'  => array( 'view' ),
					'readonly' => true,
					'items'    => array(
						'type' => 'string',
					),
				),
				'subcategories'  => array(
					'type'     => 'array',
					'context'  => array( 'view' ),
					'readonly' => true,
					'items'    => array(
						'type'       => 'object',
						'context'    => array( 'view' ),
						'readonly'   => true,
						'properties' => array(
							'slug' => array(
								'type'     => 'string',
								'context'  => array( 'view' ),
								'readonly' => true,
							),
							'name' => array(
								'type'     => 'string',
								'context'  => array( 'view' ),
								'readonly' => true,
							),
						),
					),
				),
				'tags'           => array(
					'type'     => 'array',
					'context'  => array( 'view' ),
					'readonly' => true,
					'items'    => array(
						'type'       => 'object',
						'context'    => array( 'view' ),
						'readonly'   => true,
						'properties' => array(
							'slug' => array(
								'type'     => 'string',
								'context'  => array( 'view' ),
								'readonly' => true,
							),
							'name' => array(
								'type'     => 'string',
								'context'  => array( 'view' ),
								'readonly' => true,
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}

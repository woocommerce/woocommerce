<?php
/**
 * REST API Product Shipping Classes controller
 *
 * Handles requests to the products/shipping_classes endpoint.
 *
 * @package WooCommerce\RestApi
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Shipping Classes controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Shipping_Classes_V2_Controller
 */
class WC_REST_Product_Shipping_Classes_Controller extends WC_REST_Product_Shipping_Classes_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Register the routes for product reviews.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/slug-suggestion',
			array(
				'args'   => array(
					'name' => array(
						'description' => __( 'Suggest a slug for the term.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'suggest_slug' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Callback fuction for the slug-suggestion endpoint.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return string          The suggested slug.
	 */
	public function suggest_slug( $request ) {
		$name = $request['name'];
		$slug = sanitize_title( $name ); // potential slug.
		$term = get_term_by( 'slug', $slug, $this->taxonomy );

		/*
		 * If the term exists, creates a unique slug
		 * based on the name provided.
		 * Otherwise, returns the sanitized name.
		 */
		if ( isset( $term->slug ) ) {
			/*
			 * Pass a Term object that has only the taxonomy property,
			 * to induce the wp_unique_term_slug() function to generate a unique slug.
			 * Otherwise, the function will return the same slug.
			 * @see https://core.trac.wordpress.org/browser/tags/6.5/src/wp-includes/taxonomy.php#L3130
			 * @see https://github.com/WordPress/wordpress-develop/blob/a1b1e0339eb6dfa72a30933cac2a1c6ad2bbfe96/src/wp-includes/taxonomy.php#L3078-L3156
			 */
			$slug = wp_unique_term_slug( $slug, (object) array( 'taxonomy' => $this->taxonomy ) );
		}

		return rest_ensure_response( $slug );
	}
}

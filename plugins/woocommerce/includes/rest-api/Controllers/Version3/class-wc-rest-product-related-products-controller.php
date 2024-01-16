<?php
/**
 * REST API related-products controller
 *
 * Handles requests to the /products/<product_id>/related-products endpoints.
 *
 * @package WooCommerce\RestApi
 * @since   3.0.0
 */

use Automattic\WooCommerce\Utilities\I18nUtil;

defined( 'ABSPATH' ) || exit;

use Automattic\Jetpack\Constants;

/**
 * REST API related-products controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Product_Related_Products_V2_Controller
 */
class WC_REST_Product_Related_Products_Controller extends WC_REST_Products_V2_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/(?P<product_id>[\d]+)/related-products';

	/**
	 * Register the routes for products.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace, '/' . $this->rest_base,
			array(
				'args'   => array(
					'product_id'     => array(
						'description' => __( 'Unique identifier for the variable product.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	public function get_items( $request ) {
		$product_id  = $request->get_param( 'product_id' );
		$object      = wc_get_product( $product_id );
		$related_ids = wc_get_related_products( $product_id );

		return (object) array(
			'related_product_ids' => $related_ids,
		);
	}
}

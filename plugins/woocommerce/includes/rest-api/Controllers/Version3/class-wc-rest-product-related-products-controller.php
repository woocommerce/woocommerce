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
	 * Related product categories.
	 *
	 * @var array
	 */
	protected $categories = array();

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
					'categories'     => array(
						'description'       => __( 'Limit result set to specific product categorie ids.', 'woocommerce' ),
						'type'              => 'array',
						'items'             => array(
							'type' => 'integer',
						),
						'default'           => array(),
						'sanitize_callback' => 'wp_parse_id_list',
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

	/**
	 * Get the related product categories.
	 */
	public function get_related_product_cat_terms( $terms, $product_id ) {
		return $this->categories;
	}

	public function get_items( $request ) {
		$product_id  = $request->get_param( 'product_id' );
		$object      = wc_get_product( $product_id );

		$categories = $request->get_param( 'categories' );

		// Filter the related product categories.
		if ( ! empty( $categories ) ) {
			$this->categories = $categories;
			add_filter(
				'woocommerce_get_related_product_cat_terms',
				array( $this, 'get_related_product_cat_terms' ),
				100, 2
			);
		}

		$ids = wc_get_related_products( $product_id );

		// Remove the product categories filter.
		remove_filter( 'woocommerce_get_related_product_cat_terms', array( $this, 'get_related_product_cat_terms' ), 100, 2 );

		return (object) array(
			'ids'        => $ids,
		);
	}
}

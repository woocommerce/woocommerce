<?php
/**
 * REST API related-products controller
 *
 * Handles requests to the /products/<id>/related-products endpoints.
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
class WC_REST_Product_Related_Products_Controller extends WC_REST_CRUD_Controller {

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
	protected $rest_base = 'products/(?P<id>[\d]+)/related-products';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'product';

	/**
	 * Related product categories.
	 *
	 * @var array
	 */
	protected $categories = array();

	/**
	 * Related product tags.
	 *
	 * @var array
	 */
	protected $tags = array();

	/**
	 * Register the routes for products.
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace, '/' . $this->rest_base,
			array(
				'args'   => array(
					'id'     => array(
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

	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['categories'] = array(
			'description'       => __( 'Limit result set to specific product categorie ids.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['tags']       = array(
			'description'       => __( 'Limit result set to specific product tag ids.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'validate_callback' => 'rest_validate_request_arg',
			'sanitize_callback' => 'wp_parse_id_list',
		);

		return $params;
	}

	/**
	 * Get the related product categories.
	 */
	public function get_related_product_cat_terms( $cats, $id ) {
		/*
		 * Merge the given terms with the related product categories
		 * ensuring to remove any duplicates.
		 */
		return array_unique( array_merge( $cats, $this->categories ) );
	}

	/**
	 * Get the related product tags.
	 */
	public function get_related_product_tag_terms( $tags, $id ) {
		return array_unique( array_merge( $tags, $this->tags ) );
	}

	/**
	 * Get the related products.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return object
	 */
	public function get_items( $request ) {
		$id     = $request->get_param( 'id' );
		$object = wc_get_product( $id );

		$categories = $request->get_param( 'categories' );
		$tags       = $request->get_param( 'tags' );

		/*
		 * If categories param is defined,
		 * filter the related products by the categories.
		 */
		if ( ! empty( $categories ) ) {
			$this->categories = $categories;
			add_filter(
				'woocommerce_get_related_product_cat_terms',
				array( $this, 'get_related_product_cat_terms' ),
				100, 2
			);
		}

		/*
		 * If tags param is defined,
		 * filter the related products by the tags.
		 */
		if ( ! empty( $tags ) ) {
			$this->tags = $tags;
			add_filter(
				'woocommerce_get_related_product_tag_terms',
				array( $this, 'get_related_product_tag_terms' ),
				100, 2
			);
		}

		$ids = wc_get_related_products( $id );

		// Remove the product categories filter.
		remove_filter( 'woocommerce_get_related_product_cat_terms', array( $this, 'get_related_product_cat_terms' ), 100, 2 );

		// Remove the product tags filter.
		remove_filter( 'woocommerce_get_related_product_tag_terms', array( $this, 'get_related_product_tag_terms' ), 100, 2 );

		return (object) array(
			'ids' => $ids,
		);
	}
}

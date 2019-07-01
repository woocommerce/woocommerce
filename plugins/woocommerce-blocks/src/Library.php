<?php
/**
 * Initializes blocks in WordPress.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Library class.
 */
class Library {

	/**
	 * Initialize block library features.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 */
	public static function register_blocks() {
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-grid-base.php';
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-featured-product.php';

		register_block_type(
			'woocommerce/handpicked-products',
			array(
				'render_callback' => array( __CLASS__, 'render_handpicked_products' ),
				'editor_script'   => 'wc-handpicked-products',
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
				'attributes'      => array(
					'align'             => self::get_schema_align(),
					'alignButtons'      => self::get_schema_boolean( false ),
					'className'         => self::get_schema_string(),
					'columns'           => self::get_schema_number( wc_get_theme_support( 'product_blocks::default_columns', 3 ) ),
					'editMode'          => self::get_schema_boolean( true ),
					'orderby'           => self::get_schema_orderby(),
					'products'          => self::get_schema_list_ids(),
					'contentVisibility' => self::get_schema_content_visibility(),
				),
			)
		);
		register_block_type(
			'woocommerce/product-best-sellers',
			array(
				'render_callback' => array( __CLASS__, 'render_product_best_sellers' ),
				'editor_script'   => 'wc-product-best-sellers',
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
				'attributes'      => self::get_shared_attributes(),
			)
		);
		register_block_type(
			'woocommerce/product-category',
			array(
				'render_callback' => array( __CLASS__, 'render_product_category' ),
				'editor_script'   => 'wc-product-category',
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
				'attributes'      => array_merge(
					self::get_shared_attributes(),
					array(
						'className' => self::get_schema_string(),
						'orderby'   => self::get_schema_orderby(),
						'editMode'  => self::get_schema_boolean( true ),
					)
				),
			)
		);
		register_block_type(
			'woocommerce/product-new',
			array(
				'render_callback' => array( __CLASS__, 'render_product_new' ),
				'editor_script'   => 'wc-product-new',
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
				'attributes'      => self::get_shared_attributes(),
			)
		);
		register_block_type(
			'woocommerce/product-on-sale',
			array(
				'render_callback' => array( __CLASS__, 'render_product_on_sale' ),
				'editor_script'   => 'wc-product-on-sale',
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
				'attributes'      => array_merge(
					self::get_shared_attributes(),
					array(
						'className' => self::get_schema_string(),
						'orderby'   => self::get_schema_orderby(),
					)
				),
			)
		);
		register_block_type(
			'woocommerce/product-top-rated',
			array(
				'render_callback' => array( __CLASS__, 'render_product_top_rated' ),
				'editor_script'   => 'wc-product-top-rated',
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
				'attributes'      => self::get_shared_attributes(),
			)
		);
		register_block_type(
			'woocommerce/products-by-attribute',
			array(
				'render_callback' => array( __CLASS__, 'render_products_by_attribute' ),
				'editor_script'   => 'wc-products-attribute',
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
				'attributes'      => array(
					'align'             => self::get_schema_align(),
					'alignButtons'      => self::get_schema_boolean( false ),
					'attributes'        => array(
						'type'    => 'array',
						'items'   => array(
							'type'       => 'object',
							'properties' => array(
								'id'        => array(
									'type' => 'number',
								),
								'attr_slug' => array(
									'type' => 'string',
								),
							),
						),
						'default' => array(),
					),
					'attrOperator'      => array(
						'type'    => 'string',
						'default' => 'any',
					),
					'className'         => self::get_schema_string(),
					'columns'           => self::get_schema_number( wc_get_theme_support( 'product_blocks::default_columns', 3 ) ),
					'contentVisibility' => self::get_schema_content_visibility(),
					'editMode'          => self::get_schema_boolean( true ),
					'orderby'           => self::get_schema_orderby(),
					'rows'              => self::get_schema_number( wc_get_theme_support( 'product_blocks::default_rows', 1 ) ),
				),
			)
		);
		register_block_type(
			'woocommerce/featured-product',
			array(
				'render_callback' => array( 'WGPB_Block_Featured_Product', 'render' ),
				'editor_script'   => 'wc-featured-product',
				'editor_style'    => 'wc-block-editor',
				'style'           => 'wc-block-style',
			)
		);
		register_block_type(
			'woocommerce/product-categories',
			array(
				'editor_script' => 'wc-product-categories',
				'editor_style'  => 'wc-block-editor',
				'style'         => 'wc-block-style',
				'script'        => 'wc-frontend',
			)
		);
	}

	/**
	 * Get the schema for the contentVisibility attribute
	 *
	 * @return array List of block attributes with type and defaults.
	 */
	protected static function get_schema_content_visibility() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'title'  => self::get_schema_boolean( true ),
				'price'  => self::get_schema_boolean( true ),
				'rating' => self::get_schema_boolean( true ),
				'button' => self::get_schema_boolean( true ),
			),
		);
	}

	/**
	 * Get the schema for the orderby attribute.
	 *
	 * @return array Property definition of `orderby` attribute.
	 */
	protected static function get_schema_orderby() {
		return array(
			'type'    => 'string',
			'enum'    => array( 'date', 'popularity', 'price_asc', 'price_desc', 'rating', 'title', 'menu_order' ),
			'default' => 'date',
		);
	}

	/**
	 * Get the schema for the alignment property.
	 *
	 * @return array Property definition for align.
	 */
	protected static function get_schema_align() {
		return array(
			'type' => 'string',
			'enum' => array( 'left', 'center', 'right', 'wide', 'full' ),
		);
	}

	/**
	 * Get the schema for a list of IDs.
	 *
	 * @return array Property definition for a list of numeric ids.
	 */
	protected static function get_schema_list_ids() {
		return array(
			'type'    => 'array',
			'items'   => array(
				'type' => 'number',
			),
			'default' => array(),
		);
	}

	/**
	 * Get the schema for a boolean value.
	 *
	 * @param  string $default  The default value.
	 * @return array Property definition.
	 */
	protected static function get_schema_boolean( $default = true ) {
		return array(
			'type'    => 'boolean',
			'default' => $default,
		);
	}

	/**
	 * Get the schema for a numeric value.
	 *
	 * @param  string $default  The default value.
	 * @return array Property definition.
	 */
	protected static function get_schema_number( $default ) {
		return array(
			'type'    => 'number',
			'default' => $default,
		);
	}

	/**
	 * Get the schema for a string value.
	 *
	 * @param  string $default  The default value.
	 * @return array Property definition.
	 */
	protected static function get_schema_string( $default = '' ) {
		return array(
			'type'    => 'string',
			'default' => $default,
		);
	}

	/**
	 * Get a set of attributes shared across most of the grid blocks.
	 *
	 * @return array List of block attributes with type and defaults.
	 */
	protected static function get_shared_attributes() {
		return array(
			'className'         => self::get_schema_string(),
			'columns'           => self::get_schema_number( wc_get_theme_support( 'product_blocks::default_columns', 3 ) ),
			'rows'              => self::get_schema_number( wc_get_theme_support( 'product_blocks::default_rows', 1 ) ),
			'categories'        => self::get_schema_list_ids(),
			'catOperator'       => array(
				'type'    => 'string',
				'default' => 'any',
			),
			'contentVisibility' => self::get_schema_content_visibility(),
			'align'             => self::get_schema_align(),
			'alignButtons'      => self::get_schema_boolean( false ),
		);
	}

	/**
	 * New products: Include and render the dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public static function render_product_new( $attributes, $content ) {
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-product-new.php';

		$block = new \WGPB_Block_Product_New( $attributes, $content );
		return $block->render();
	}

	/**
	 * Sale products: Include and render the dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public static function render_product_on_sale( $attributes, $content ) {
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-product-on-sale.php';

		$block = new \WGPB_Block_Product_On_Sale( $attributes, $content );
		return $block->render();
	}

	/**
	 * Products by category: Include and render the dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public static function render_product_category( $attributes, $content ) {
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-product-category.php';

		$block = new \WGPB_Block_Product_Category( $attributes, $content );
		return $block->render();
	}

	/**
	 * Products by attribute: Include and render the dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public static function render_products_by_attribute( $attributes, $content ) {
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-products-by-attribute.php';

		$block = new \WGPB_Block_Products_By_Attribute( $attributes, $content );
		return $block->render();
	}

	/**
	 * Top rated products: Include and render the dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public static function render_product_top_rated( $attributes, $content ) {
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-product-top-rated.php';

		$block = new \WGPB_Block_Product_Top_Rated( $attributes, $content );
		return $block->render();
	}

	/**
	 * Best Selling Products: Include and render the dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public static function render_product_best_sellers( $attributes, $content ) {
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-product-best-sellers.php';

		$block = new \WGPB_Block_Product_Best_Sellers( $attributes, $content );
		return $block->render();
	}

	/**
	 * Hand-picked Products: Include and render the dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public static function render_handpicked_products( $attributes, $content ) {
		require_once dirname( __DIR__ ) . '/assets/php/class-wgpb-block-handpicked-products.php';

		$block = new \WGPB_Block_Handpicked_Products( $attributes, $content );
		return $block->render();
	}
}

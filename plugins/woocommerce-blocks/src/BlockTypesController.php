<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\BlockTypes\AtomicBlock;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * BlockTypesController class.
 *
 * @since 5.0.0
 * @internal
 */
final class BlockTypesController {

	/**
	 * Instance of the asset API.
	 *
	 * @var AssetApi
	 */
	protected $asset_api;

	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	protected $asset_data_registry;

	/**
	 * Constructor.
	 *
	 * @param AssetApi          $asset_api Instance of the asset API.
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetApi $asset_api, AssetDataRegistry $asset_data_registry ) {
		$this->asset_api           = $asset_api;
		$this->asset_data_registry = $asset_data_registry;
		$this->init();
	}

	/**
	 * Initialize class features.
	 */
	protected function init() {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'render_block', array( $this, 'add_data_attributes' ), 10, 2 );
		add_action( 'woocommerce_login_form_end', array( $this, 'redirect_to_field' ) );
		add_filter( 'widget_types_to_hide_from_legacy_widget_block', array( $this, 'hide_legacy_widgets_with_block_equivalent' ) );
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 */
	public function register_blocks() {
		$block_types = $this->get_block_types();

		foreach ( $block_types as $block_type ) {
			$block_type_class    = __NAMESPACE__ . '\\BlockTypes\\' . $block_type;
			$block_type_instance = new $block_type_class( $this->asset_api, $this->asset_data_registry, new IntegrationRegistry() );
		}

		foreach ( self::get_atomic_blocks() as $block_type ) {
			$block_type_instance = new AtomicBlock( $this->asset_api, $this->asset_data_registry, new IntegrationRegistry(), $block_type );
		}
	}

	/**
	 * Add data- attributes to blocks when rendered if the block is under the woocommerce/ namespace.
	 *
	 * @param string $content Block content.
	 * @param array  $block Parsed block data.
	 * @return string
	 */
	public function add_data_attributes( $content, $block ) {
		$block_name      = $block['blockName'];
		$block_namespace = strtok( $block_name, '/' );

		/**
		 * WooCommerce Blocks Namespaces
		 *
		 * This hook defines which block namespaces should have block name and attribute data- attributes appended on render.
		 *
		 * @param array $allowed_namespaces List of namespaces.
		 */
		$allowed_namespaces = array_merge( [ 'woocommerce', 'woocommerce-checkout' ], (array) apply_filters( '__experimental_woocommerce_blocks_add_data_attributes_to_namespace', [] ) );

		/**
		 * WooCommerce Blocks Block Names
		 *
		 * This hook defines which block names should have block name and attribute data- attributes appended on render.
		 *
		 * @param array $allowed_namespaces List of namespaces.
		 */
		$allowed_blocks = (array) apply_filters( '__experimental_woocommerce_blocks_add_data_attributes_to_block', [] );

		if ( ! in_array( $block_namespace, $allowed_namespaces, true ) && ! in_array( $block_name, $allowed_blocks, true ) ) {
			return $content;
		}

		$attributes              = (array) $block['attrs'];
		$escaped_data_attributes = [
			'data-block-name="' . esc_attr( $block['blockName'] ) . '"',
		];

		foreach ( $attributes as $key => $value ) {
			if ( is_bool( $value ) ) {
				$value = $value ? 'true' : 'false';
			}
			if ( ! is_scalar( $value ) ) {
				$value = wp_json_encode( $value );
			}
			$escaped_data_attributes[] = 'data-' . esc_attr( strtolower( preg_replace( '/(?<!\ )[A-Z]/', '-$0', $key ) ) ) . '="' . esc_attr( $value ) . '"';
		}

		return preg_replace( '/^<div /', '<div ' . implode( ' ', $escaped_data_attributes ) . ' ', trim( $content ) );
	}

	/**
	 * Adds a redirect field to the login form so blocks can redirect users after login.
	 */
	public function redirect_to_field() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['redirect_to'] ) ) {
			return;
		}
		echo '<input type="hidden" name="redirect" value="' . esc_attr( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Hide legacy widgets with a feature complete block equivalent in the inserter
	 * and prevent them from showing as an option in the Legacy Widget block.
	 *
	 * @param array $widget_types An array of widgets hidden in core.
	 * @return array $widget_types An array inluding the WooCommerce widgets to hide.
	 */
	public function hide_legacy_widgets_with_block_equivalent( $widget_types ) {
		array_push( $widget_types, 'woocommerce_product_search', 'woocommerce_product_categories', 'woocommerce_recent_reviews' );
		return $widget_types;
	}

	/**
	 * Get list of block types.
	 *
	 * @return array
	 */
	protected function get_block_types() {
		global $wp_version, $pagenow;

		$block_types = [
			'AllReviews',
			'FeaturedCategory',
			'FeaturedProduct',
			'HandpickedProducts',
			'ProductBestSellers',
			'ProductCategories',
			'ProductCategory',
			'ProductNew',
			'ProductOnSale',
			'ProductsByAttribute',
			'ProductTopRated',
			'ReviewsByProduct',
			'ReviewsByCategory',
			'ProductSearch',
			'ProductTag',
			'AllProducts',
			'PriceFilter',
			'AttributeFilter',
			'StockFilter',
			'ActiveFilters',
		];

		if ( Package::feature()->is_feature_plugin_build() ) {
			$block_types[] = 'Checkout';
			$block_types[] = 'Cart';
		}

		if ( Package::feature()->is_experimental_build() ) {
			$block_types[] = 'SingleProduct';
			$block_types[] = 'CheckoutI2';
			$block_types[] = 'MiniCart';
		}

		/**
		 * This disables specific blocks in Widget Areas by not registering them.
		 */
		if ( in_array( $pagenow, [ 'widgets.php', 'themes.php', 'customize.php' ], true ) ) {
			$block_types = array_diff(
				$block_types,
				[
					'AllProducts',
					'PriceFilter',
					'AttributeFilter',
					'StockFilter',
					'ActiveFilters',
					'Cart',
					'Checkout',
				]
			);
		}

		return $block_types;
	}

	/**
	 * Get atomic blocks types.
	 *
	 * @return array
	 */
	protected function get_atomic_blocks() {
		return [
			'product-title',
			'product-button',
			'product-image',
			'product-price',
			'product-rating',
			'product-sale-badge',
			'product-summary',
			'product-sku',
			'product-category-list',
			'product-tag-list',
			'product-stock-indicator',
			'product-add-to-cart',
		];
	}
}

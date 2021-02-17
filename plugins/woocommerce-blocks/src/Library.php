<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\BlockTypes\AtomicBlock;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;

/**
 * Library class.
 * Initializes blocks in WordPress.
 *
 * @internal
 */
class Library {

	/**
	 * Initialize block library features.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'init', array( __CLASS__, 'define_tables' ) );
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	public static function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'wc_reserved_stock' => 'wc_reserved_stock',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 */
	public static function register_blocks() {
		global $wp_version, $pagenow;

		$blocks = [
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
			'ActiveFilters',
		];

		if ( Package::feature()->is_feature_plugin_build() ) {
			$blocks[] = 'Checkout';
			$blocks[] = 'Cart';
		}

		if ( Package::feature()->is_experimental_build() ) {
			$blocks[] = 'SingleProduct';
		}

		/**
		 * This disables specific blocks in Widget Areas by not registering them.
		 */
		if ( 'themes.php' === $pagenow ) {
			$blocks = array_diff(
				$blocks,
				[
					'AllProducts',
					'PriceFilter',
					'AttributeFilter',
					'ActiveFilters',
				]
			);
		}

		// Provide block types access to assets, data registry, and integration registry.
		$asset_api     = Package::container()->get( AssetApi::class );
		$data_registry = Package::container()->get( AssetDataRegistry::class );

		foreach ( $blocks as $block_type ) {
			$block_type_class    = __NAMESPACE__ . '\\BlockTypes\\' . $block_type;
			$block_type_instance = new $block_type_class( $asset_api, $data_registry, new IntegrationRegistry() );
		}

		foreach ( self::get_atomic_blocks() as $block_type ) {
			$block_type_instance = new AtomicBlock( $asset_api, $data_registry, new IntegrationRegistry(), $block_type );
		}
	}

	/**
	 * Get atomic blocks types.
	 *
	 * @return array
	 */
	protected static function get_atomic_blocks() {
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

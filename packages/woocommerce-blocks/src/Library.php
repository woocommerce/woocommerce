<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Package;

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
		global $wp_version;
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
		];
		// Note: as a part of refactoring dynamic block registration, this will be moved
		// to block level config.
		if ( version_compare( $wp_version, '5.3', '>=' ) ) {
			$blocks[] = 'AllProducts';
			$blocks[] = 'PriceFilter';
			$blocks[] = 'AttributeFilter';
			$blocks[] = 'ActiveFilters';

			if ( Package::is_feature_plugin_build() ) {
				$blocks[] = 'Checkout';
				$blocks[] = 'Cart';
			}
		}
		if ( Package::is_experimental_build() ) {
			$blocks[] = 'SingleProduct';
		}
		foreach ( $blocks as $class ) {
			$class    = __NAMESPACE__ . '\\BlockTypes\\' . $class;
			$instance = new $class();
			$instance->register_block_type();
		}
		self::register_atomic_blocks();
	}

	/**
	 * Register atomic blocks on the PHP side.
	 */
	protected static function register_atomic_blocks() {
		$atomic_blocks = [
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
		foreach ( $atomic_blocks as $atomic_block ) {
			$instance = new \Automattic\WooCommerce\Blocks\BlockTypes\AtomicBlock( $atomic_block );
			$instance->register_block_type();
		}
	}
}

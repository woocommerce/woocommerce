<?php
/**
 * Initializes block assets.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Package;

/**
 * Assets class.
 */
class Assets {

	/**
	 * Initialize class features on init.
	 *
	 * @since 2.5.0
	 * Moved most initialization to BootStrap and AssetDataRegistry
	 * classes as a part of ongoing refactor
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
		add_action( 'body_class', array( __CLASS__, 'add_theme_body_class' ), 1 );
		add_action( 'admin_body_class', array( __CLASS__, 'add_theme_admin_body_class' ), 1 );
		add_filter( 'woocommerce_shared_settings', array( __CLASS__, 'get_wc_block_data' ) );
	}

	/**
	 * Register block scripts & styles.
	 *
	 * @since 2.5.0
	 * Moved data related enqueuing to new AssetDataRegistry class
	 * as part of ongoing refactoring.
	 */
	public static function register_assets() {
		self::register_style( 'wc-block-vendors-style', plugins_url( self::get_block_asset_build_path( 'vendors-style', 'css' ), __DIR__ ), [] );
		self::register_style( 'wc-block-editor', plugins_url( self::get_block_asset_build_path( 'editor', 'css' ), __DIR__ ), array( 'wp-edit-blocks' ) );
		wp_style_add_data( 'wc-block-editor', 'rtl', 'replace' );
		self::register_style( 'wc-block-style', plugins_url( self::get_block_asset_build_path( 'style', 'css' ), __DIR__ ), array( 'wc-block-vendors-style' ) );
		wp_style_add_data( 'wc-block-style', 'rtl', 'replace' );

		// Shared libraries and components across all blocks.
		self::register_script( 'wc-blocks-middleware', plugins_url( 'build/wc-blocks-middleware.js', __DIR__ ), [], false );
		self::register_script( 'wc-blocks-data-store', plugins_url( 'build/wc-blocks-data.js', __DIR__ ), [ 'wc-blocks-middleware' ], false );
		self::register_script( 'wc-blocks', plugins_url( self::get_block_asset_build_path( 'blocks' ), __DIR__ ), [], false );
		self::register_script( 'wc-vendors', plugins_url( self::get_block_asset_build_path( 'vendors' ), __DIR__ ), [], false );
		self::register_script( 'wc-blocks-registry', plugins_url( 'build/wc-blocks-registry.js', __DIR__ ), [], false );
		self::register_script( 'wc-shared-context', plugins_url( 'build/wc-shared-context.js', __DIR__ ), [], false );

		// Inline data.
		wp_add_inline_script(
			'wc-blocks-middleware',
			"var wcStoreApiNonce = '" . esc_js( wp_create_nonce( 'wc_store_api' ) ) . "';",
			'before'
		);

		// Individual blocks.
		$block_dependencies = array( 'wc-vendors', 'wc-blocks' );

		self::register_script( 'wc-handpicked-products', plugins_url( self::get_block_asset_build_path( 'handpicked-products' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-product-best-sellers', plugins_url( self::get_block_asset_build_path( 'product-best-sellers' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-product-category', plugins_url( self::get_block_asset_build_path( 'product-category' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-product-new', plugins_url( self::get_block_asset_build_path( 'product-new' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-product-on-sale', plugins_url( self::get_block_asset_build_path( 'product-on-sale' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-product-top-rated', plugins_url( self::get_block_asset_build_path( 'product-top-rated' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-products-by-attribute', plugins_url( self::get_block_asset_build_path( 'products-by-attribute' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-featured-product', plugins_url( self::get_block_asset_build_path( 'featured-product' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-featured-category', plugins_url( self::get_block_asset_build_path( 'featured-category' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-product-categories', plugins_url( self::get_block_asset_build_path( 'product-categories' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-product-tag', plugins_url( self::get_block_asset_build_path( 'product-tag' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-all-reviews', plugins_url( self::get_block_asset_build_path( 'all-reviews' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-reviews-by-product', plugins_url( self::get_block_asset_build_path( 'reviews-by-product' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-reviews-by-category', plugins_url( self::get_block_asset_build_path( 'reviews-by-category' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-product-search', plugins_url( self::get_block_asset_build_path( 'product-search' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-all-products', plugins_url( self::get_block_asset_build_path( 'all-products' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-price-filter', plugins_url( self::get_block_asset_build_path( 'price-filter' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-attribute-filter', plugins_url( self::get_block_asset_build_path( 'attribute-filter' ), __DIR__ ), $block_dependencies );
		self::register_script( 'wc-active-filters', plugins_url( self::get_block_asset_build_path( 'active-filters' ), __DIR__ ), $block_dependencies );

		if ( Package::is_experimental_build() ) {
			self::register_script( 'wc-single-product-block', plugins_url( self::get_block_asset_build_path( 'single-product' ), __DIR__ ), $block_dependencies );
		}

		if ( Package::is_feature_plugin_build() ) {
			self::register_script( 'wc-checkout-block', plugins_url( self::get_block_asset_build_path( 'checkout' ), __DIR__ ), $block_dependencies );
			self::register_script( 'wc-cart-block', plugins_url( self::get_block_asset_build_path( 'cart' ), __DIR__ ), $block_dependencies );
		}
	}

	/**
	 * Add body classes.
	 *
	 * @param array $classes Array of CSS classnames.
	 * @return array Modified array of CSS classnames.
	 */
	public static function add_theme_body_class( $classes = [] ) {
		$classes[] = 'theme-' . get_template();
		return $classes;
	}

	/**
	 * Add theme class to admin body.
	 *
	 * @param array $classes String with the CSS classnames.
	 * @return array Modified string of CSS classnames.
	 */
	public static function add_theme_admin_body_class( $classes = '' ) {
		$classes .= ' theme-' . get_template();
		return $classes;
	}

	/**
	 * Returns block-related data for enqueued wc-block-settings script.
	 *
	 * This is used to map site settings & data into JS-accessible variables.
	 *
	 * @param array $settings The original settings array from the filter.
	 *
	 * @since 2.4.0
	 * @since 2.5.0 returned merged data along with incoming $settings
	 */
	public static function get_wc_block_data( $settings ) {
		$tag_count      = wp_count_terms( 'product_tag' );
		$product_counts = wp_count_posts( 'product' );
		$page_ids       = [
			'shop'     => wc_get_page_id( 'shop' ),
			'cart'     => wc_get_page_id( 'cart' ),
			'checkout' => wc_get_page_id( 'checkout' ),
			'privacy'  => wc_privacy_policy_page_id(),
			'terms'    => wc_terms_and_conditions_page_id(),
		];

		// Global settings used in each block.
		return array_merge(
			$settings,
			[
				'currentUserIsAdmin'            => is_user_logged_in() && current_user_can( 'manage_woocommerce' ),
				'min_columns'                   => wc_get_theme_support( 'product_blocks::min_columns', 1 ),
				'max_columns'                   => wc_get_theme_support( 'product_blocks::max_columns', 6 ),
				'default_columns'               => wc_get_theme_support( 'product_blocks::default_columns', 3 ),
				'min_rows'                      => wc_get_theme_support( 'product_blocks::min_rows', 1 ),
				'max_rows'                      => wc_get_theme_support( 'product_blocks::max_rows', 6 ),
				'default_rows'                  => wc_get_theme_support( 'product_blocks::default_rows', 3 ),
				'thumbnail_size'                => wc_get_theme_support( 'thumbnail_image_width', 300 ),
				'placeholderImgSrc'             => wc_placeholder_img_src(),
				'min_height'                    => wc_get_theme_support( 'featured_block::min_height', 500 ),
				'default_height'                => wc_get_theme_support( 'featured_block::default_height', 500 ),
				'isLargeCatalog'                => $product_counts->publish > 100,
				'limitTags'                     => $tag_count > 100,
				'hasTags'                       => $tag_count > 0,
				'taxesEnabled'                  => wc_tax_enabled(),
				'couponsEnabled'                => wc_coupons_enabled(),
				'shippingEnabled'               => wc_shipping_enabled(),
				'displayItemizedTaxes'          => 'itemized' === get_option( 'woocommerce_tax_total_display' ),
				'displayShopPricesIncludingTax' => 'incl' === get_option( 'woocommerce_tax_display_shop' ),
				'displayCartPricesIncludingTax' => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
				'checkoutShowLoginReminder'     => 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ),
				'showAvatars'                   => '1' === get_option( 'show_avatars' ),
				'reviewRatingsEnabled'          => wc_review_ratings_enabled(),
				'productCount'                  => array_sum( (array) $product_counts ),
				'attributes'                    => array_values( wc_get_attribute_taxonomies() ),
				'isShippingCalculatorEnabled'   => filter_var( get_option( 'woocommerce_enable_shipping_calc' ), FILTER_VALIDATE_BOOLEAN ),
				'isShippingCostHidden'          => filter_var( get_option( 'woocommerce_shipping_cost_requires_address' ), FILTER_VALIDATE_BOOLEAN ),
				'wcBlocksAssetUrl'              => plugins_url( 'assets/', __DIR__ ),
				'restApiRoutes'                 => [
					'/wc/store' => array_keys( Package::container()->get( RestApi::class )->get_routes_from_namespace( 'wc/store' ) ),
				],
				'homeUrl'                       => esc_url( home_url( '/' ) ),
				'storePages'                    => [
					'shop'     => self::format_page_resource( $page_ids['shop'] ),
					'cart'     => self::format_page_resource( $page_ids['cart'] ),
					'checkout' => self::format_page_resource( $page_ids['checkout'] ),
					'privacy'  => self::format_page_resource( $page_ids['privacy'] ),
					'terms'    => self::format_page_resource( $page_ids['terms'] ),
				],
				'checkoutAllowsGuest'           => 'yes' === get_option( 'woocommerce_enable_guest_checkout' ),
				'checkoutAllowsSignup'          => 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' ),
				'baseLocation'                  => wc_get_base_location(),
				'woocommerceBlocksPhase'        => WOOCOMMERCE_BLOCKS_PHASE,

				/*
				 * translators: If your word count is based on single characters (e.g. East Asian characters),
				 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
				 * Do not translate into your own language.
				 */
				'wordCountType'                 => _x( 'words', 'Word count type. Do not translate!', 'woocommerce' ),
			]
		);
	}

	/**
	 * Format a page object into a standard array of data.
	 *
	 * @param WP_Post|int $page Page object or ID.
	 * @return array
	 */
	protected static function format_page_resource( $page ) {
		if ( is_numeric( $page ) && $page > 0 ) {
			$page = get_post( $page );
		}
		if ( ! is_a( $page, '\WP_Post' ) ) {
			return [
				'id'        => 0,
				'title'     => '',
				'permalink' => false,
			];
		}
		return [
			'id'        => $page->ID,
			'title'     => $page->post_title,
			'permalink' => get_permalink( $page->ID ),
		];
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected static function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( \Automattic\WooCommerce\Blocks\Package::get_path() . $file ) ) {
			return filemtime( \Automattic\WooCommerce\Blocks\Package::get_path() . $file );
		}
		return \Automattic\WooCommerce\Blocks\Package::get_version();
	}

	/**
	 * Registers a script according to `wp_register_script`, additionally loading the translations for the file.
	 *
	 * @since 2.0.0
	 *
	 * @param string $handle       Name of the script. Should be unique.
	 * @param string $src          Full URL of the script, or path of the script relative to the WordPress root directory.
	 * @param array  $dependencies Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param bool   $has_i18n     Optional. Whether to add a script translation call to this file. Default 'true'.
	 */
	protected static function register_script( $handle, $src, $dependencies = [], $has_i18n = true ) {
		$relative_src = str_replace( plugins_url( '/', __DIR__ ), '', $src );
		$asset_path   = dirname( __DIR__ ) . '/' . str_replace( '.js', '.asset.php', $relative_src );

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$dependencies = isset( $asset['dependencies'] ) ? array_merge( $asset['dependencies'], $dependencies ) : $dependencies;
			$version      = ! empty( $asset['version'] ) ? $asset['version'] : self::get_file_version( $relative_src );
		} else {
			$version = self::get_file_version( $relative_src );
		}

		wp_register_script( $handle, $src, apply_filters( 'woocommerce_blocks_register_script_dependencies', $dependencies, $handle ), $version, true );

		if ( $has_i18n && function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $handle, 'woocommerce', dirname( __DIR__ ) . '/languages' );
		}
	}

	/**
	 * Queues a block script.
	 *
	 * @since 2.3.0
	 * @since 2.6.0 Changed $name to $script_name and added $handle argument.
	 *
	 * @param string $script_name  Name of the script used to identify the file inside build folder.
	 * @param string $handle       Optional. Provided if the handle should be different than the script name. `wc-` prefix automatically added.
	 * @param array  $dependencies Optional. An array of registered script handles this script depends on. Default empty array.
	 */
	public static function register_block_script( $script_name, $handle = '', $dependencies = [] ) {
		$handle = '' !== $handle ? $handle : $script_name;
		self::register_script( 'wc-' . $handle, plugins_url( self::get_block_asset_build_path( $script_name ), __DIR__ ), $dependencies );
		wp_enqueue_script( 'wc-' . $handle );
	}

	/**
	 * Registers a style according to `wp_register_style`.
	 *
	 * @since 2.0.0
	 *
	 * @param string $handle Name of the stylesheet. Should be unique.
	 * @param string $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 * @param array  $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string $media  Optional. The media for which this stylesheet has been defined. Default 'all'. Accepts media types like
	 *                       'all', 'print' and 'screen', or media queries like '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	protected static function register_style( $handle, $src, $deps = [], $media = 'all' ) {
		$filename = str_replace( plugins_url( '/', __DIR__ ), '', $src );
		$ver      = self::get_file_version( $filename );
		wp_register_style( $handle, $src, $deps, $ver, $media );
	}

	/**
	 * Returns the appropriate asset path for loading either legacy builds or
	 * current builds.
	 *
	 * @param   string $filename  Filename for asset path (without extension).
	 * @param   string $type      File type (.css or .js).
	 *
	 * @return  string             The generated path.
	 */
	protected static function get_block_asset_build_path( $filename, $type = 'js' ) {
		global $wp_version;
		$suffix = version_compare( $wp_version, '5.2', '>' )
			? ''
			: '-legacy';
		return "build/$filename$suffix.$type";
	}
}

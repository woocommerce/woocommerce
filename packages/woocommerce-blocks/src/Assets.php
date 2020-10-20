<?php
namespace Automattic\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Assets\Api as AssetApi;

/**
 * Assets class.
 * Initializes block assets.
 *
 * @internal
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
		$asset_api = Package::container()->get( AssetApi::class );

		// @todo Remove fix to load our stylesheets after editor CSS.
		// See #3068 for the rationale of this fix. It should be no longer
		// necessary when the editor is loaded in an iframe (https://github.com/WordPress/gutenberg/issues/20797).
		if ( is_admin() ) {
			$block_style_dependencies = array( 'wp-edit-post' );
		} else {
			$block_style_dependencies = array();
		}

		self::register_style( 'wc-block-vendors-style', plugins_url( $asset_api->get_block_asset_build_path( 'vendors-style', 'css' ), __DIR__ ), $block_style_dependencies );
		self::register_style( 'wc-block-editor', plugins_url( $asset_api->get_block_asset_build_path( 'editor', 'css' ), __DIR__ ), array( 'wp-edit-blocks' ) );
		wp_style_add_data( 'wc-block-editor', 'rtl', 'replace' );
		self::register_style( 'wc-block-style', plugins_url( $asset_api->get_block_asset_build_path( 'style', 'css' ), __DIR__ ), array( 'wc-block-vendors-style' ) );
		wp_style_add_data( 'wc-block-style', 'rtl', 'replace' );

		// Shared libraries and components across all blocks.
		$asset_api->register_script( 'wc-blocks-middleware', 'build/wc-blocks-middleware.js', [], false );
		$asset_api->register_script( 'wc-blocks-data-store', 'build/wc-blocks-data.js', [ 'wc-blocks-middleware' ], false );
		$asset_api->register_script( 'wc-blocks', $asset_api->get_block_asset_build_path( 'blocks' ), [], false );
		$asset_api->register_script( 'wc-vendors', $asset_api->get_block_asset_build_path( 'vendors' ), [], false );
		$asset_api->register_script( 'wc-blocks-registry', 'build/wc-blocks-registry.js', [], false );
		$asset_api->register_script( 'wc-shared-context', 'build/wc-shared-context.js', [], false );
		$asset_api->register_script( 'wc-shared-hocs', 'build/wc-shared-hocs.js', [], false );

		// Inline data.
		wp_add_inline_script(
			'wc-blocks-middleware',
			"var wcStoreApiNonce = '" . esc_js( wp_create_nonce( 'wc_store_api' ) ) . "';",
			'before'
		);

		// Individual blocks.
		$block_dependencies = array( 'wc-vendors', 'wc-blocks' );

		$asset_api->register_script( 'wc-handpicked-products', $asset_api->get_block_asset_build_path( 'handpicked-products' ), $block_dependencies );
		$asset_api->register_script( 'wc-product-best-sellers', $asset_api->get_block_asset_build_path( 'product-best-sellers' ), $block_dependencies );
		$asset_api->register_script( 'wc-product-category', $asset_api->get_block_asset_build_path( 'product-category' ), $block_dependencies );
		$asset_api->register_script( 'wc-product-new', $asset_api->get_block_asset_build_path( 'product-new' ), $block_dependencies );
		$asset_api->register_script( 'wc-product-on-sale', $asset_api->get_block_asset_build_path( 'product-on-sale' ), $block_dependencies );
		$asset_api->register_script( 'wc-product-top-rated', $asset_api->get_block_asset_build_path( 'product-top-rated' ), $block_dependencies );
		$asset_api->register_script( 'wc-products-by-attribute', $asset_api->get_block_asset_build_path( 'products-by-attribute' ), $block_dependencies );
		$asset_api->register_script( 'wc-featured-product', $asset_api->get_block_asset_build_path( 'featured-product' ), $block_dependencies );
		$asset_api->register_script( 'wc-featured-category', $asset_api->get_block_asset_build_path( 'featured-category' ), $block_dependencies );
		$asset_api->register_script( 'wc-product-categories', $asset_api->get_block_asset_build_path( 'product-categories' ), $block_dependencies );
		$asset_api->register_script( 'wc-product-tag', $asset_api->get_block_asset_build_path( 'product-tag' ), $block_dependencies );
		$asset_api->register_script( 'wc-all-reviews', $asset_api->get_block_asset_build_path( 'all-reviews' ), $block_dependencies );
		$asset_api->register_script( 'wc-reviews-by-product', $asset_api->get_block_asset_build_path( 'reviews-by-product' ), $block_dependencies );
		$asset_api->register_script( 'wc-reviews-by-category', $asset_api->get_block_asset_build_path( 'reviews-by-category' ), $block_dependencies );
		$asset_api->register_script( 'wc-product-search', $asset_api->get_block_asset_build_path( 'product-search' ), $block_dependencies );
		$asset_api->register_script( 'wc-all-products', $asset_api->get_block_asset_build_path( 'all-products' ), $block_dependencies );
		$asset_api->register_script( 'wc-price-filter', $asset_api->get_block_asset_build_path( 'price-filter' ), $block_dependencies );
		$asset_api->register_script( 'wc-attribute-filter', $asset_api->get_block_asset_build_path( 'attribute-filter' ), $block_dependencies );
		$asset_api->register_script( 'wc-active-filters', $asset_api->get_block_asset_build_path( 'active-filters' ), $block_dependencies );

		if ( Package::is_experimental_build() ) {
			$asset_api->register_script( 'wc-single-product-block', $asset_api->get_block_asset_build_path( 'single-product' ), $block_dependencies );
		}

		if ( Package::is_feature_plugin_build() ) {
			$asset_api->register_script( 'wc-checkout-block', $asset_api->get_block_asset_build_path( 'checkout' ), $block_dependencies );
			$asset_api->register_script( 'wc-cart-block', $asset_api->get_block_asset_build_path( 'cart' ), $block_dependencies );
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
				'wcBlocksBuildUrl'              => plugins_url( 'build/', __DIR__ ),
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
				'checkoutAllowsGuest'           => filter_var( get_option( 'woocommerce_enable_guest_checkout' ), FILTER_VALIDATE_BOOLEAN ),
				'checkoutAllowsSignup'          => filter_var( get_option( 'woocommerce_enable_signup_and_login_from_checkout' ), FILTER_VALIDATE_BOOLEAN ),
				'baseLocation'                  => wc_get_base_location(),
				'woocommerceBlocksPhase'        => WOOCOMMERCE_BLOCKS_PHASE,
				'hasDarkEditorStyleSupport'     => current_theme_supports( 'dark-editor-style' ),
				'loginUrl'                      => wp_login_url(),

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
	 * Queues a block script in the frontend.
	 *
	 * @since 2.3.0
	 * @since 2.6.0 Changed $name to $script_name and added $handle argument.
	 * @since 2.9.0 Made it so scripts are not loaded in admin pages.
	 *
	 * @param string $script_name  Name of the script used to identify the file inside build folder.
	 * @param string $handle       Optional. Provided if the handle should be different than the script name. `wc-` prefix automatically added.
	 * @param array  $dependencies Optional. An array of registered script handles this script depends on. Default empty array.
	 */
	public static function register_block_script( $script_name, $handle = '', $dependencies = [] ) {
		$asset_api = Package::container()->get( AssetApi::class );
		$asset_api->register_block_script( $script_name, $handle, $dependencies );
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
}

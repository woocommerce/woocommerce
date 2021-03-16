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
		add_action( 'woocommerce_login_form_end', array( __CLASS__, 'redirect_to_field' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_filter( 'script_loader_tag', array( __CLASS__, 'async_script_loader_tags' ), 10, 3 );
	}

	/**
	 * Register block scripts & styles.
	 *
	 * @since 2.5.0
	 * Moved data related enqueuing to new AssetDataRegistry class as part of ongoing refactoring.
	 */
	public static function register_assets() {
		$asset_api = Package::container()->get( AssetApi::class );

		self::register_style( 'wc-block-editor', plugins_url( $asset_api->get_block_asset_build_path( 'editor', 'css' ), __DIR__ ), array( 'wp-edit-blocks' ) );
		self::register_style( 'wc-block-style', plugins_url( $asset_api->get_block_asset_build_path( 'style', 'css' ), __DIR__ ), array( 'wc-block-vendors-style' ) );

		wp_style_add_data( 'wc-block-editor', 'rtl', 'replace' );
		wp_style_add_data( 'wc-block-style', 'rtl', 'replace' );

		// Shared libraries and components across multiple blocks.
		$asset_api->register_script( 'wc-blocks-middleware', 'build/wc-blocks-middleware.js', [], false );
		$asset_api->register_script( 'wc-blocks-data-store', 'build/wc-blocks-data.js', [ 'wc-blocks-middleware' ], false );
		$asset_api->register_script( 'wc-blocks', $asset_api->get_block_asset_build_path( 'blocks' ), [], false );
		$asset_api->register_script( 'wc-vendors', $asset_api->get_block_asset_build_path( 'vendors' ), [], false );
		$asset_api->register_script( 'wc-blocks-registry', 'build/wc-blocks-registry.js', [], false );
		$asset_api->register_script( 'wc-shared-context', 'build/wc-shared-context.js', [], false );
		$asset_api->register_script( 'wc-shared-hocs', 'build/wc-shared-hocs.js', [], false );
		$asset_api->register_script( 'wc-price-format', 'build/price-format.js', [], false );

		if ( Package::feature()->is_experimental_build() ) {
			$asset_api->register_script( 'wc-blocks-google-analytics', 'build/wc-blocks-google-analytics.js', [ 'google-tag-manager' ] );
		}

		if ( Package::feature()->is_feature_plugin_build() ) {
			$asset_api->register_script( 'wc-blocks-checkout', 'build/blocks-checkout.js', [], false );
		}

		wp_add_inline_script(
			'wc-blocks-middleware',
			"
			var wcStoreApiNonce = '" . esc_js( wp_create_nonce( 'wc_store_api' ) ) . "';
			var wcStoreApiNonceTimestamp = '" . esc_js( time() ) . "';
			",
			'before'
		);
	}

	/**
	 * Get settings from the GA integration extension.
	 *
	 * @return array
	 */
	private static function get_google_analytics_settings() {
		return wp_parse_args(
			get_option( 'woocommerce_google_analytics_settings' ),
			[
				'ga_id'                     => '',
				'ga_event_tracking_enabled' => 'no',
			]
		);
	}

	/**
	 * Enqueue the Google Tag Manager script if prerequisites are met.
	 *
	 * @param AssetApi $asset_api Asset API class Instance.
	 */
	private static function maybe_enqueue_google_analytics( $asset_api ) {
		$settings = self::get_google_analytics_settings();

		if ( is_admin() || ! stristr( $settings['ga_id'], 'G-' ) || apply_filters( 'woocommerce_ga_disable_tracking', ! wc_string_to_bool( $settings['ga_event_tracking_enabled'] ) ) ) {
			return;
		}

		if ( ! wp_script_is( 'google-tag-manager', 'registered' ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_register_script( 'google-tag-manager', 'https://www.googletagmanager.com/gtag/js?id=' . $settings['ga_id'], [], null, false );
			wp_add_inline_script(
				'google-tag-manager',
				"
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', '" . esc_js( $settings['ga_id'] ) . "', { 'send_page_view': false });"
			);
		}

		wp_enqueue_script( 'wc-blocks-google-analytics' );
	}

	/**
	 * Register the vendors style file. We need to do it after the other files
	 * because we need to check if `wp-edit-post` has been enqueued.
	 */
	public static function enqueue_scripts() {
		$asset_api = Package::container()->get( AssetApi::class );

		// @todo Remove fix to load our stylesheets after editor CSS.
		// See #3068 and #3898 for the rationale of this fix. It should be no
		// longer necessary when the editor is loaded in an iframe (https://github.com/WordPress/gutenberg/issues/20797).
		if ( wp_style_is( 'wp-edit-post' ) ) {
			$block_style_dependencies = array( 'wp-edit-post' );
		} else {
			$block_style_dependencies = array();
		}
		self::register_style( 'wc-block-vendors-style', plugins_url( $asset_api->get_block_asset_build_path( 'vendors-style', 'css' ), __DIR__ ), $block_style_dependencies );

		if ( Package::feature()->is_experimental_build() ) {
			self::maybe_enqueue_google_analytics( $asset_api );
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
			'myaccount' => wc_get_page_id( 'myaccount' ),
			'shop'      => wc_get_page_id( 'shop' ),
			'cart'      => wc_get_page_id( 'cart' ),
			'checkout'  => wc_get_page_id( 'checkout' ),
			'privacy'   => wc_privacy_policy_page_id(),
			'terms'     => wc_terms_and_conditions_page_id(),
		];
		$checkout       = WC()->checkout();

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
				'shippingCostRequiresAddress'   => filter_var( get_option( 'woocommerce_shipping_cost_requires_address' ), FILTER_VALIDATE_BOOLEAN ),
				'wcBlocksAssetUrl'              => plugins_url( 'assets/', __DIR__ ),
				'wcBlocksBuildUrl'              => plugins_url( 'build/', __DIR__ ),
				'restApiRoutes'                 => [
					'/wc/store' => array_keys( Package::container()->get( RestApi::class )->get_routes_from_namespace( 'wc/store' ) ),
				],
				'homeUrl'                       => esc_url( home_url( '/' ) ),
				'storePages'                    => [
					'myaccount' => self::format_page_resource( $page_ids['myaccount'] ),
					'shop'      => self::format_page_resource( $page_ids['shop'] ),
					'cart'      => self::format_page_resource( $page_ids['cart'] ),
					'checkout'  => self::format_page_resource( $page_ids['checkout'] ),
					'privacy'   => self::format_page_resource( $page_ids['privacy'] ),
					'terms'     => self::format_page_resource( $page_ids['terms'] ),
				],
				'checkoutAllowsGuest'           => $checkout instanceof \WC_Checkout && false === filter_var(
					$checkout->is_registration_required(),
					FILTER_VALIDATE_BOOLEAN
				),
				'checkoutAllowsSignup'          => $checkout instanceof \WC_Checkout && filter_var(
					$checkout->is_registration_enabled(),
					FILTER_VALIDATE_BOOLEAN
				),
				'baseLocation'                  => wc_get_base_location(),
				'woocommerceBlocksPhase'        => Package::feature()->get_flag(),
				'hasDarkEditorStyleSupport'     => current_theme_supports( 'dark-editor-style' ),
				'loginUrl'                      => wp_login_url(),

				/*
				 * translators: If your word count is based on single characters (e.g. East Asian characters),
				 * enter 'characters_excluding_spaces' or 'characters_including_spaces'. Otherwise, enter 'words'.
				 * Do not translate into your own language.
				 */
				'wordCountType'                 => _x( 'words', 'Word count type. Do not translate!', 'woo-gutenberg-products-block' ),
				'hideOutOfStockItems'           => 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ),
			]
		);
	}

	/**
	 * Adds a redirect field to the login form so blocks can redirect users after login.
	 */
	public static function redirect_to_field() {
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( empty( $_GET['redirect_to'] ) ) {
			return;
		}
		echo '<input type="hidden" name="redirect" value="' . esc_attr( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification
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
		if ( ! is_a( $page, '\WP_Post' ) || 'publish' !== $page->post_status ) {
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
	 * @deprecated 4.5.0 Block types register the scripts themselves.
	 *
	 * @param string $script_name  Name of the script used to identify the file inside build folder.
	 * @param string $handle       Optional. Provided if the handle should be different than the script name. `wc-` prefix automatically added.
	 * @param array  $dependencies Optional. An array of registered script handles this script depends on. Default empty array.
	 */
	public static function register_block_script( $script_name, $handle = '', $dependencies = [] ) {
		_deprecated_function( 'register_block_script', '4.5.0' );
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

	/**
	 * Add async to script tags with defined handles.
	 *
	 * @param string $tag HTML for the script tag.
	 * @param string $handle Handle of script.
	 * @param string $src Src of script.
	 * @return string
	 */
	public static function async_script_loader_tags( $tag, $handle, $src ) {
		if ( ! in_array( $handle, array( 'google-tag-manager' ), true ) ) {
			return $tag;
		}
		// If script was output manually in wp_head, abort.
		if ( did_action( 'woocommerce_gtag_snippet' ) ) {
			return '';
		}
		return str_replace( '<script src', '<script async src', $tag );
	}
}

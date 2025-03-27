<?php
/**
 * Register the scripts, styles, and includes needed for pieces of the WooCommerce Admin experience.
 */

namespace Automattic\WooCommerce\Internal\Admin;

use Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore as OrdersDataStore;
use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Admin\PluginsHelper;
use Automattic\WooCommerce\Internal\Admin\ProductReviews\Reviews;
use Automattic\WooCommerce\Internal\Admin\ProductReviews\ReviewsCommentsOverrides;

/**
 * Loader Class.
 */
class Loader {
	/**
	 * Class instance.
	 *
	 * @var Loader instance
	 */
	protected static $instance = null;

	/**
	 * An array of classes to load from the includes folder.
	 *
	 * @var array
	 */
	protected static $classes = array();

	/**
	 * WordPress capability required to use analytics features.
	 *
	 * @var string
	 */
	protected static $required_capability = null;

	/**
	 * An array of dependencies that have been preloaded (to avoid duplicates).
	 *
	 * @var array
	 */
	protected $preloaded_dependencies = array(
		'script' => array(),
		'style'  => array(),
	);

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 * Hooks added here should be removed in `wc_admin_initialize` via the feature plugin.
	 */
	public function __construct() {
		Features::get_instance();
		WCAdminSharedSettings::get_instance();
		Translations::get_instance();
		WCAdminUser::get_instance();
		Settings::get_instance();
		SiteHealth::get_instance();
		SystemStatusReport::get_instance();

		wc_get_container()->get( Reviews::class );
		wc_get_container()->get( ReviewsCommentsOverrides::class );

		add_filter( 'admin_body_class', array( __CLASS__, 'add_admin_body_classes' ) );
		add_filter( 'admin_title', array( __CLASS__, 'update_admin_title' ) );
		add_action( 'in_admin_header', array( __CLASS__, 'embed_page_header' ) );
		add_action( 'admin_head', array( __CLASS__, 'remove_notices' ) );
		add_action( 'admin_head', array( __CLASS__, 'smart_app_banner' ) );
		add_action( 'admin_notices', array( __CLASS__, 'inject_before_notices' ), -9999 );
		add_action( 'admin_notices', array( __CLASS__, 'inject_after_notices' ), PHP_INT_MAX );

		// Added this hook to delete the field woocommerce_onboarding_homepage_post_id when deleting the homepage.
		add_action( 'trashed_post', array( __CLASS__, 'delete_homepage' ) );

		/*
		* Remove the emoji script as it always defaults to replacing emojis with Twemoji images.
		* Gutenberg has also disabled emojis. More on that here -> https://github.com/WordPress/gutenberg/pull/6151
		*/
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

		add_action( 'load-themes.php', array( __CLASS__, 'add_appearance_theme_view_tracks_event' ) );
	}

	/**
	 * Returns breadcrumbs for the current page.
	 */
	private static function get_embed_breadcrumbs() {
		return wc_admin_get_breadcrumbs();
	}

	/**
	 * Outputs breadcrumbs via PHP for the initial load of an embedded page.
	 *
	 * @param array $section Section to create breadcrumb from.
	 */
	private static function output_heading( $section ) {
		echo esc_html( $section );
	}

	/**
	 * Set up a div for the header embed to render into.
	 * The initial contents here are meant as a place loader for when the PHP page initially loads.
	 */
	public static function embed_page_header() {
		if ( ! PageController::is_admin_page() && ! PageController::is_embed_page() ) {
			return;
		}

		if ( ! PageController::is_embed_page() ) {
			return;
		}

		if ( PageController::is_modern_settings_page() ) {
			return;
		}

		$sections = self::get_embed_breadcrumbs();
		$sections = is_array( $sections ) ? $sections : array( $sections );

		$page_title      = '';
		$pages_with_tabs = array(
			'admin.php?page=wc-settings',
			'admin.php?page=wc-reports',
			'admin.php?page=wc-status',
		);

		if (
			count( $sections ) > 2 &&
			is_array( $sections[1] ) &&
			in_array( $sections[1][0], $pages_with_tabs, true )
		) {
			$page_title = $sections[1][1];
		} else {
			$page_title = end( $sections );
		}
		?>
		<div id="woocommerce-embedded-root" class="is-embed-loading">
			<div class="woocommerce-layout">
				<div class="woocommerce-layout__header is-embed-loading">
					<h1 class="woocommerce-layout__header-heading">
						<?php self::output_heading( $page_title ); ?>
					</h1>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds body classes to the main wp-admin wrapper, allowing us to better target elements in specific scenarios.
	 *
	 * @param string $admin_body_class Body class to add.
	 */
	public static function add_admin_body_classes( $admin_body_class = '' ) {
		if ( ! PageController::is_admin_or_embed_page() || PageController::is_modern_settings_page() ) {
			return $admin_body_class;
		}

		$classes   = explode( ' ', trim( $admin_body_class ) );
		$classes[] = 'woocommerce-admin-page';
		if ( PageController::is_embed_page() ) {
			$classes[] = 'woocommerce-embed-page';
		}

		// Add page ID as a class.
		$page_id = PageController::get_instance()->get_current_screen_id();
		if ( $page_id ) {
			$classes[] = $page_id;
		}

		/**
		 * Some routes or features like onboarding hide the wp-admin navigation and masterbar.
		 * Setting `woocommerce_admin_is_loading` to true allows us to premeptively hide these
		 * elements while the JS app loads.
		 * This class needs to be removed by those feature components (like <ProfileWizard />).
		 *
		 * @param bool $is_loading If WooCommerce Admin is loading a fullscreen view.
		 * @since 6.5.0
		 */
		$is_loading = apply_filters( 'woocommerce_admin_is_loading', false );

		if ( PageController::is_admin_page() && $is_loading ) {
			$classes[] = 'woocommerce-admin-is-loading';
		}

		$admin_body_class = implode( ' ', array_unique( $classes ) );
		return " $admin_body_class ";
	}

	/**
	 * Adds an iOS "Smart App Banner" for display on iOS Safari.
	 * See https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/PromotingAppswithAppBanners/PromotingAppswithAppBanners.html
	 */
	public static function smart_app_banner() {
		$exclude_paths = array(
			'/customize-store',
			'/setup-wizard',
			'/launch-your-store',
		);

		/* phpcs:ignore */
		$path = $_GET['path'] ?? '';

		if ( PageController::is_admin_or_embed_page() && ! in_array( $path, $exclude_paths, true ) ) {
			echo "
				<meta name='apple-itunes-app' content='app-id=1389130815'>
			";
		}
	}


	/**
	 * Removes notices that should not be displayed on WC Admin pages.
	 */
	public static function remove_notices() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		// Hello Dolly.
		if ( function_exists( 'hello_dolly' ) ) {
			remove_action( 'admin_notices', 'hello_dolly' );
		}
	}

	/**
	 * Runs before admin notices action and hides them.
	 */
	public static function inject_before_notices() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		// The JITMs won't be shown in the Onboarding Wizard.
		$is_onboarding   = isset( $_GET['path'] ) && '/setup-wizard' === wc_clean( wp_unslash( $_GET['path'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$maybe_hide_jitm = $is_onboarding ? '-hide' : '';

		echo '<div class="woocommerce-layout__jitm' . sanitize_html_class( $maybe_hide_jitm ) . '" id="jp-admin-notices"></div>';

		// Wrap the notices in a hidden div to prevent flickering before
		// they are moved elsewhere in the page by WordPress Core.
		echo '<div class="woocommerce-layout__notice-list-hide" id="wp__notice-list">';

		if ( PageController::is_admin_page() ) {
			// Capture all notices and hide them. WordPress Core looks for
			// `.wp-header-end` and appends notices after it if found.
			// https://github.com/WordPress/WordPress/blob/f6a37e7d39e2534d05b9e542045174498edfe536/wp-admin/js/common.js#L737 .
			echo '<div class="wp-header-end" id="woocommerce-layout__notice-catcher"></div>';
		}
	}

	/**
	 * Runs after admin notices and closes div.
	 */
	public static function inject_after_notices() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		// Close the hidden div used to prevent notices from flickering before
		// they are inserted elsewhere in the page.
		echo '</div>';
	}

	/**
	 * Edits Admin title based on section of wc-admin.
	 *
	 * @param string $admin_title Modifies admin title.
	 * @todo Can we do some URL rewriting so we can figure out which page they are on server side?
	 */
	public static function update_admin_title( $admin_title ) {
		if (
			! did_action( 'current_screen' ) ||
			! PageController::is_admin_page()
		) {
			return $admin_title;
		}

		$sections = self::get_embed_breadcrumbs();
		$pieces   = array();

		foreach ( $sections as $section ) {
			$pieces[] = is_array( $section ) ? $section[1] : $section;
		}

		$pieces = array_reverse( $pieces );
		$title  = implode( ' &lsaquo; ', $pieces );

		/* translators: %1$s: updated title, %2$s: blog info name */
		return sprintf( __( '%1$s &lsaquo; %2$s', 'woocommerce' ), $title, get_bloginfo( 'name' ) );
	}

	/**
	 * Set up a div for the app to render into.
	 */
	public static function page_wrapper() {
		?>
		<div class="wrap">
			<div id="root"></div>
		</div>
		<?php
	}

	/**
	 * Hooks extra necessary data into the component settings array already set in WooCommerce core.
	 *
	 * @param array $settings Array of component settings.
	 * @return array Array of component settings.
	 */
	public static function add_component_settings( $settings ) {
		if ( ! is_admin() ) {
			return $settings;
		}

		if ( ! function_exists( 'wc_blocks_container' ) ) {
			global $wp_locale;
			// inject data not available via older versions of wc_blocks/woo.
			$settings['orderStatuses'] = Settings::get_order_statuses( wc_get_order_statuses() );
			$settings['stockStatuses'] = Settings::get_order_statuses( wc_get_product_stock_status_options() );
			$settings['currency']      = Settings::get_currency_settings();
			$settings['locale']        = array(
				'siteLocale'    => isset( $settings['siteLocale'] )
					? $settings['siteLocale']
					: get_locale(),
				'userLocale'    => isset( $settings['l10n']['userLocale'] )
					? $settings['l10n']['userLocale']
					: get_user_locale(),
				'weekdaysShort' => isset( $settings['l10n']['weekdaysShort'] )
					? $settings['l10n']['weekdaysShort']
					: array_values( $wp_locale->weekday_abbrev ),
			);
		}

		/**
		 * The woocommerce_component_settings_preload_endpoints filter
		 *
		 * @since 6.5.0
		 */
		$preload_data_endpoints = apply_filters( 'woocommerce_component_settings_preload_endpoints', array() );

		$preload_data_endpoints['jetpackStatus'] = '/jetpack/v4/connection';
		if ( ! empty( $preload_data_endpoints ) ) {
			$preload_data = array_reduce(
				array_values( $preload_data_endpoints ),
				'rest_preload_api_request'
			);
		}

		/**
		 * The woocommerce_admin_preload_options filter
		 *
		 * @since 6.5.0
		 */
		$preload_options = apply_filters( 'woocommerce_admin_preload_options', array() );
		if ( ! empty( $preload_options ) ) {
			foreach ( $preload_options as $option ) {
				$settings['preloadOptions'][ $option ] = get_option( $option );
			}
		}

		/**
		 * The woocommerce_admin_preload_settings filter
		 *
		 * @since 6.5.0
		 */
		$preload_settings = apply_filters( 'woocommerce_admin_preload_settings', array() );
		if ( ! empty( $preload_settings ) ) {
			$setting_options = new \WC_REST_Setting_Options_V2_Controller();
			foreach ( $preload_settings as $group ) {
				$group_settings   = $setting_options->get_group_settings( $group );
				$preload_settings = array();
				foreach ( $group_settings as $option ) {
					if ( array_key_exists( 'id', $option ) && array_key_exists( 'value', $option ) ) {
						$preload_settings[ $option['id'] ] = $option['value'];
					}
				}
				$settings['preloadSettings'][ $group ] = $preload_settings;
			}
		}

		$user_controller = new \WP_REST_Users_Controller();
		$request         = new \WP_REST_Request();
		$request->set_query_params( array( 'context' => 'edit' ) );
		$user_response     = $user_controller->get_current_item( $request );
		$current_user_data = is_wp_error( $user_response ) ? (object) array() : $user_response->get_data();

		$settings['currentUserData']      = $current_user_data;
		$settings['reviewsEnabled']       = get_option( 'woocommerce_enable_reviews' );
		$settings['manageStock']          = get_option( 'woocommerce_manage_stock' );
		$settings['commentModeration']    = get_option( 'comment_moderation' );
		$settings['notifyLowStockAmount'] = get_option( 'woocommerce_notify_low_stock_amount' );
		// @todo On merge, once plugin images are added to core WooCommerce, `wcAdminAssetUrl` can be retired,
		// and `wcAssetUrl` can be used in its place throughout the codebase.
		$settings['wcAdminAssetUrl'] = WC_ADMIN_IMAGES_FOLDER_URL;
		$settings['wcVersion']       = WC_VERSION;
		$settings['siteUrl']         = site_url();
		$settings['shopUrl']         = get_permalink( wc_get_page_id( 'shop' ) );
		$settings['homeUrl']         = home_url();
		$settings['dateFormat']      = get_option( 'date_format' );
		$settings['timeZone']        = wc_timezone_string();
		$settings['plugins']         = array(
			'installedPlugins' => PluginsHelper::get_installed_plugin_slugs(),
			'activePlugins'    => Plugins::get_active_plugins(),
		);
		// Plugins that depend on changing the translation work on the server but not the client -
		// WooCommerce Branding is an example of this - so pass through the translation of
		// 'WooCommerce' to wcSettings.
		$settings['woocommerceTranslation'] = __( 'WooCommerce', 'woocommerce' );
		// We may have synced orders with a now-unregistered status.
		// E.g An extension that added statuses is now inactive or removed.
		$settings['unregisteredOrderStatuses'] = self::get_unregistered_order_statuses();
		// The separator used for attributes found in Variation titles.
		/* phpcs:ignore */
		$settings['variationTitleAttributesSeparator'] = apply_filters( 'woocommerce_product_variation_title_attributes_separator', ' - ', new \WC_Product() );

		if ( ! empty( $preload_data_endpoints ) ) {
			$settings['dataEndpoints'] = isset( $settings['dataEndpoints'] )
				? $settings['dataEndpoints']
				: array();
			foreach ( $preload_data_endpoints as $key => $endpoint ) {
				// Handle error case: rest_do_request() doesn't guarantee success.
				if ( empty( $preload_data[ $endpoint ] ) ) {
					$settings['dataEndpoints'][ $key ] = array();
				} else {
					$settings['dataEndpoints'][ $key ] = $preload_data[ $endpoint ]['body'];
				}
			}
		}
		$settings = self::get_custom_settings( $settings );
		if ( PageController::is_embed_page() ) {
			$settings['embedBreadcrumbs'] = self::get_embed_breadcrumbs();
		}

		$settings['allowMarketplaceSuggestions']      = WC_Marketplace_Suggestions::allow_suggestions();
		$settings['connectNonce']                     = wp_create_nonce( 'connect' );
		$settings['wcpay_welcome_page_connect_nonce'] = wp_create_nonce( 'wcpay-connect' );

		return $settings;
	}

	/**
	 * Format order statuses by removing a leading 'wc-' if present.
	 *
	 * @param array $statuses Order statuses.
	 * @return array formatted statuses.
	 *
	 * @deprecated migrate to \Automattic\WooCommerce\Internal\Admin\Settings instead.
	 */
	public static function get_order_statuses( $statuses ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.9.0', '\Automattic\WooCommerce\Internal\Admin\Settings::get_order_statuses' );

		return Settings::get_order_statuses( $statuses );
	}

	/**
	 * Get all order statuses present in analytics tables that aren't registered.
	 *
	 * @return array Unregistered order statuses.
	 *
	 * @deprecated migrate to \Automattic\WooCommerce\Internal\Admin\Settings instead.
	 */
	public static function get_unregistered_order_statuses() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.9.0' );

		$registered_statuses   = wc_get_order_statuses();
		$all_synced_statuses   = OrdersDataStore::get_all_statuses();
		$unregistered_statuses = array_diff( $all_synced_statuses, array_keys( $registered_statuses ) );
		$formatted_status_keys = Settings::get_order_statuses( array_fill_keys( $unregistered_statuses, '' ) );
		$formatted_statuses    = array_keys( $formatted_status_keys );

		return array_combine( $formatted_statuses, $formatted_statuses );
	}

	/**
	 * Register the admin settings for use in the WC REST API
	 *
	 * @param array $groups Array of setting groups.
	 * @return array
	 *
	 * @deprecated migrate to \Automattic\WooCommerce\Internal\Admin\Settings instead.
	 */
	public static function add_settings_group( $groups ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.9.0', '\Automattic\WooCommerce\Internal\Admin\Settings::add_settings_group' );

		return Settings::get_instance()->add_settings_group( $groups );
	}

	/**
	 * Add WC Admin specific settings
	 *
	 * @param array $settings Array of settings in wc admin group.
	 * @return array
	 *
	 * @deprecated migrate to \Automattic\WooCommerce\Internal\Admin\Settings instead.
	 */
	public static function add_settings( $settings ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.9.0', '\Automattic\WooCommerce\Internal\Admin\Settings::add_settings' );

		return Settings::get_instance()->add_settings( $settings );
	}

	/**
	 * Gets custom settings used for WC Admin.
	 *
	 * @param array $settings Array of settings to merge into.
	 * @return array
	 *
	 * @deprecated migrate to \Automattic\WooCommerce\Internal\Admin\Settings instead.
	 */
	public static function get_custom_settings( $settings ) {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.9.0' );

		$wc_rest_settings_options_controller = new \WC_REST_Setting_Options_Controller();
		$wc_admin_group_settings             = $wc_rest_settings_options_controller->get_group_settings( 'wc_admin' );
		$settings['wcAdminSettings']         = array();

		foreach ( $wc_admin_group_settings as $setting ) {
			if ( ! empty( $setting['id'] ) ) {
				$settings['wcAdminSettings'][ $setting['id'] ] = $setting['value'];
			}
		}
		return $settings;
	}

	/**
	 * Return an object defining the currency options for the site's current currency
	 *
	 * @return  array  Settings for the current currency {
	 *     Array of settings.
	 *
	 *     @type string $code       Currency code.
	 *     @type string $precision  Number of decimals.
	 *     @type string $symbol     Symbol for currency.
	 * }
	 *
	 * @deprecated migrate to \Automattic\WooCommerce\Internal\Admin\Settings instead.
	 */
	public static function get_currency_settings() {
		wc_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '9.9.0', '\Automattic\WooCommerce\Internal\Admin\Settings::get_currency_settings' );

		return Settings::get_currency_settings();
	}

	/**
	 * Delete woocommerce_onboarding_homepage_post_id field when the homepage is deleted
	 *
	 * @param int $post_id The deleted post id.
	 */
	public static function delete_homepage( $post_id ) {
		if ( 'page' !== get_post_type( $post_id ) ) {
			return;
		}
		$homepage_id = intval( get_option( 'woocommerce_onboarding_homepage_post_id', false ) );
		if ( $homepage_id === $post_id ) {
			delete_option( 'woocommerce_onboarding_homepage_post_id' );
		}
	}

	/**
	 * Adds the appearance_theme_view Tracks event.
	 */
	public static function add_appearance_theme_view_tracks_event() {
		wc_admin_record_tracks_event( 'appearance_theme_view', array() );
	}
}

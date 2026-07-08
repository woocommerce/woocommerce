<?php
/**
 * Features loader for features developed in WooCommerce Admin.
 */

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Internal\Admin\Analytics;
use Automattic\WooCommerce\Internal\Admin\Loader;
use Automattic\WooCommerce\Internal\Admin\RemoteInboxNotifications;
use Automattic\WooCommerce\Internal\Admin\WCAdminAssets;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Features Class.
 */
class Features {
	/**
	 * Class instance.
	 *
	 * @var Loader instance
	 */
	protected static $instance = null;

	/**
	 * Version metadata for WC Admin feature flags kept for backward compatibility.
	 *
	 * Keep this dictionary in sync with RETIRED_FEATURE_FLAGS in
	 * plugins/woocommerce/client/admin/client/utils/features/retired-feature-flags.ts.
	 *
	 * @var array<string, array{deprecated_since: string, removed_in: ?string}>
	 */
	private static $retired_feature_compatibility_versions = array(
		'activity-panels'                      => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'analytics'                            => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'analytics-scheduled-import'           => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'experimental-iapi-mini-cart'          => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'coupons'                              => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'core-profiler'                        => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'customize-store'                      => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'customer-effort-score-tracks'         => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'import-products-task'                 => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'experimental-fashion-sample-products' => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'shipping-smart-defaults'              => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'shipping-setting-tour'                => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'homescreen'                           => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'marketing'                            => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'mobile-app-banner'                    => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'onboarding'                           => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'onboarding-tasks'                     => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'pattern-toolkit-full-composability'   => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'payment-gateway-suggestions'          => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'product-custom-fields'                => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'printful'                             => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'remote-inbox-notifications'           => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'remote-free-extensions'               => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'shipping-label-banner'                => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'subscriptions'                        => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'transient-notices'                    => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'wc-pay-promotion'                     => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'wc-pay-welcome-page'                  => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'woo-mobile-welcome'                   => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
		'launch-your-store'                    => array(
			'deprecated_since' => '11.1.0',
			'removed_in'       => null,
		),
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
	 */
	public function __construct() {

		$this->register_internal_class_aliases();

		if ( ! self::should_load_features() ) {
			return;
		}

		// Load feature before WooCommerce update hooks.
		add_action( 'init', array( __CLASS__, 'load_features' ), 4 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ), 15 );
		add_filter( 'admin_body_class', array( __CLASS__, 'add_admin_body_classes' ) );
	}

	/**
	 * Gets a build configured array of enabled WooCommerce Admin features/sections, but does not respect optionally disabled features.
	 *
	 * @return array Enabled Woocommerce Admin features/sections.
	 */
	public static function get_features() {
		return apply_filters( 'woocommerce_admin_features', array() );
	}

	/**
	 * Gets the optional feature options as an associative array that can be toggled on or off.
	 *
	 * @deprecated 11.1.0 Use FeaturesUtil::feature_is_enabled() to check if a feature is enabled.
	 *
	 * @return array
	 */
	public static function get_optional_feature_options() {
		wc_deprecated_function( __METHOD__, '11.1.0', 'FeaturesUtil::feature_is_enabled()' );

		return array(
			'analytics'                  => Analytics::TOGGLE_OPTION_NAME,
			'remote-inbox-notifications' => RemoteInboxNotifications::TOGGLE_OPTION_NAME,
		);
	}

	/**
	 * Returns if a specific wc-admin feature exists in the current environment.
	 *
	 * @param  string $feature Feature slug.
	 * @return bool Returns true if the feature exists.
	 */
	public static function exists( $feature ) {
		$is_legacy_compatibility_feature = self::is_legacy_compatibility_feature( $feature );

		if ( $is_legacy_compatibility_feature ) {
			self::warn_legacy_feature_compatibility_usage( __METHOD__, $feature );
		}

		$features = $is_legacy_compatibility_feature
			? self::get_features_with_legacy_compatibility_defaults()
			: self::get_features();
		return in_array( $feature, $features, true );
	}

	/**
	 * Get the feature class as a string.
	 *
	 * @param string $feature Feature name.
	 * @return string|null
	 */
	public static function get_feature_class( $feature ) {
		$feature       = str_replace( '-', '', ucwords( strtolower( $feature ), '-' ) );
		$feature_class = 'Automattic\\WooCommerce\\Admin\\Features\\' . $feature;

		$should_autoload_class = self::should_load_features();

		if ( class_exists( $feature_class, $should_autoload_class ) ) {
			return $feature_class;
		}

		// Handle features contained in subdirectory.
		if ( class_exists( $feature_class . '\\Init', $should_autoload_class ) ) {
			return $feature_class . '\\Init';
		}

		return null;
	}

	/**
	 * Class loader for enabled WooCommerce Admin features/sections.
	 */
	public static function load_features() {
		if ( ! self::should_load_features() ) {
			return;
		}

		$always_loaded_feature_classes = array(
			\Automattic\WooCommerce\Internal\Admin\ActivityPanels::class,
			\Automattic\WooCommerce\Internal\Admin\Analytics::class,
			\Automattic\WooCommerce\Internal\Admin\Coupons::class,
			\Automattic\WooCommerce\Internal\Admin\CustomerEffortScoreTracks::class,
			\Automattic\WooCommerce\Internal\Admin\Homescreen::class,
			\Automattic\WooCommerce\Internal\Admin\Marketing::class,
			\Automattic\WooCommerce\Internal\Admin\MobileAppBanner::class,
			\Automattic\WooCommerce\Admin\Features\OnboardingTasks\Init::class,
			\Automattic\WooCommerce\Internal\Admin\RemoteInboxNotifications::class,
			\Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\Init::class,
			\Automattic\WooCommerce\Internal\Admin\ShippingLabelBanner::class,
			\Automattic\WooCommerce\Admin\Features\PaymentGatewaySuggestions\Init::class,
			\Automattic\WooCommerce\Admin\Features\TransientNotices::class,
			\Automattic\WooCommerce\Internal\Admin\WCPayPromotion\Init::class,
			\Automattic\WooCommerce\Internal\Admin\WcPayWelcomePage::class,
			\Automattic\WooCommerce\Admin\Features\LaunchYourStore::class,
		);

		foreach ( $always_loaded_feature_classes as $feature_class ) {
			new $feature_class();
		}

		$features = self::get_features();
		foreach ( $features as $feature ) {
			$feature_class = self::get_feature_class( $feature );

			if ( ! $feature_class ) {
				continue;
			}

			foreach ( $always_loaded_feature_classes as $loaded_feature_class ) {
				if ( is_a( $feature_class, $loaded_feature_class, true ) ) {
					// Skip the outer features loop because this feature was already loaded.
					continue 2;
				}
			}

			new $feature_class();
		}

		if ( FeaturesUtil::feature_is_enabled( 'blueprint' ) ) {
			new \Automattic\WooCommerce\Admin\Features\Blueprint\Init();
		}

		if ( FeaturesUtil::feature_is_enabled( 'order-detail-redesign' ) ) {
			new \Automattic\WooCommerce\Internal\Features\OrderDetailRedesign\Init();
		}
	}

	/**
	 * Gets a build configured array of enabled WooCommerce Admin respecting optionally disabled features.
	 *
	 * @return array Enabled Woocommerce Admin features/sections.
	 */
	public static function get_available_features() {
		$features                     = self::get_features_with_legacy_compatibility_defaults();
		$optional_feature_keys        = array( 'analytics', 'remote-inbox-notifications' );
		$legacy_compatibility_values  = self::get_legacy_feature_compatibility_values();
		$unavailable_features         = array();
		$available_compatibility_keys = array_keys( array_filter( $legacy_compatibility_values ) );

		$features = array_values( array_unique( array_merge( $features, $available_compatibility_keys ) ) );

		/**
		 * Filter allowing WooCommerce Admin optional features to be disabled.
		 *
		 * @param bool $disabled False.
		 */
		if ( apply_filters( 'woocommerce_admin_disabled', false ) ) {
			return array_values( array_diff( $features, $optional_feature_keys ) );
		}

		if (
			in_array( 'analytics', $features, true ) &&
			! FeaturesUtil::feature_is_enabled( 'analytics' )
		) {
			$unavailable_features[] = 'analytics';
		}

		if (
			in_array( 'remote-inbox-notifications', $features, true ) &&
			'yes' !== get_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME, 'yes' )
		) {
			$unavailable_features[] = 'remote-inbox-notifications';
		}

		return array_values( array_diff( $features, $unavailable_features ) );
	}

	/**
	 * Check if a feature is enabled.
	 *
	 * @param string $feature Feature slug.
	 * @return bool
	 */
	public static function is_enabled( $feature ) {
		if ( self::is_legacy_compatibility_feature( $feature ) ) {
			self::warn_legacy_feature_compatibility_usage( __METHOD__, $feature );
		}

		$available_features = self::get_available_features();
		return in_array( $feature, $available_features, true );
	}

	/**
	 * Enable a toggleable optional feature.
	 *
	 * @deprecated 11.1.0 Use FeaturesUtil::feature_is_enabled() to check if a feature is enabled.
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	public static function enable( $feature ) {
		wc_deprecated_function( __METHOD__, '11.1.0', 'FeaturesUtil::feature_is_enabled()' );

		if ( 'analytics' === $feature ) {
			update_option( Analytics::TOGGLE_OPTION_NAME, 'yes' );
			return true;
		}

		if ( 'remote-inbox-notifications' === $feature ) {
			update_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME, 'yes' );
			return true;
		}

		return false;
	}

	/**
	 * Disable a toggleable optional feature.
	 *
	 * @deprecated 11.1.0 Use FeaturesUtil::feature_is_enabled() to check if a feature is enabled.
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	public static function disable( $feature ) {
		wc_deprecated_function( __METHOD__, '11.1.0', 'FeaturesUtil::feature_is_enabled()' );

		if ( 'analytics' === $feature ) {
			update_option( Analytics::TOGGLE_OPTION_NAME, 'no' );
			return true;
		}

		if ( 'remote-inbox-notifications' === $feature ) {
			update_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME, 'no' );
			return true;
		}

		return false;
	}

	/**
	 * Adds the Features section to the advanced tab of WooCommerce Settings
	 *
	 * @deprecated 7.0 The WooCommerce Admin features are now handled by the WooCommerce features engine (see the FeaturesController class).
	 *
	 * @param array $sections Sections.
	 * @return array
	 */
	public static function add_features_section( $sections ) {
		return $sections;
	}

	/**
	 * Adds the Features settings.
	 *
	 * @deprecated 7.0 The WooCommerce Admin features are now handled by the WooCommerce features engine (see the FeaturesController class).
	 *
	 * @param array  $settings Settings.
	 * @param string $current_section Current section slug.
	 * @return array
	 */
	public static function add_features_settings( $settings, $current_section ) {
		return $settings;
	}

	/**
	 * Loads the required scripts on the correct pages.
	 */
	public static function load_scripts() {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return;
		}

		$available_features = self::get_available_features();
		$enabled_features   = array();
		foreach ( self::get_features() as $key ) {
			$enabled_features[ $key ] = in_array( $key, $available_features, true );
		}

		$enabled_features = array_merge( $enabled_features, self::get_legacy_feature_compatibility_values() );

		wp_add_inline_script( WC_ADMIN_APP, 'window.wcAdminFeatures = ' . wp_json_encode( $enabled_features, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ), 'before' );
	}


	/**
	 * Adds body classes to the main wp-admin wrapper, allowing us to better target elements in specific scenarios.
	 *
	 * @param string $admin_body_class Body class to add.
	 */
	public static function add_admin_body_classes( $admin_body_class = '' ) {
		if ( ! PageController::is_admin_or_embed_page() ) {
			return $admin_body_class;
		}

		$classes = explode( ' ', trim( $admin_body_class ) );

		foreach ( self::get_available_features() as $feature_key ) {
			$classes[] = sanitize_html_class( 'woocommerce-feature-enabled-' . $feature_key );
		}

		$admin_body_class = implode( ' ', array_unique( $classes ) );
		return " $admin_body_class ";
	}

	/**
	 * Gets legacy feature flag compatibility values.
	 *
	 * This method is intended for passive compatibility paths, such as script globals
	 * and filtering shared settings, where emitting deprecation notices would warn on
	 * every admin page load.
	 *
	 * @since 11.1.0
	 * @return array<string, bool>
	 */
	public static function get_legacy_feature_compatibility_values() {
		$compatibility_values = array_merge(
			array_fill_keys( array_keys( self::$retired_feature_compatibility_versions ), true ),
			array(
				'analytics'                  => FeaturesUtil::feature_is_enabled( 'analytics' ),
				'remote-inbox-notifications' => 'yes' === get_option( RemoteInboxNotifications::TOGGLE_OPTION_NAME, 'yes' ),
			)
		);

		return array_intersect_key(
			$compatibility_values,
			array_flip( self::get_features_with_legacy_compatibility_defaults() )
		);
	}

	/**
	 * Gets default legacy feature flag compatibility values before public filtering.
	 *
	 * @return array<string, bool>
	 */
	private static function get_legacy_feature_compatibility_defaults() {
		return array_merge(
			array_fill_keys( array_keys( self::$retired_feature_compatibility_versions ), true ),
			array(
				'analytics'                  => true,
				'remote-inbox-notifications' => true,
			)
		);
	}

	/**
	 * Gets WooCommerce Admin features with legacy compatibility defaults before public filtering.
	 *
	 * @return array Enabled Woocommerce Admin features/sections.
	 */
	private static function get_features_with_legacy_compatibility_defaults() {
		/**
		 * Filter allowing WooCommerce Admin features to be changed after legacy compatibility defaults are seeded.
		 *
		 * @since 11.1.0
		 *
		 * @param array $features Array of feature slugs.
		 */
		return apply_filters( 'woocommerce_admin_features', array_keys( self::get_legacy_feature_compatibility_defaults() ) );
	}

	/**
	 * Checks if a feature slug is supported only by the legacy compatibility shim.
	 *
	 * @param string $feature Feature slug.
	 * @return bool
	 */
	private static function is_legacy_compatibility_feature( $feature ) {
		return array_key_exists( $feature, self::get_legacy_feature_compatibility_defaults() );
	}

	/**
	 * Gets version metadata for a legacy feature flag shim.
	 *
	 * @param string $feature Feature slug.
	 * @return array{deprecated_since: string, removed_in: ?string}|null
	 */
	private static function get_legacy_feature_compatibility_versions( $feature ) {
		return self::$retired_feature_compatibility_versions[ $feature ] ?? null;
	}

	/**
	 * Gets the WooCommerce version where a legacy feature flag shim was deprecated.
	 *
	 * @param string $feature Feature slug.
	 * @return string|null
	 */
	private static function get_legacy_feature_compatibility_deprecation_version( $feature ) {
		$versions = self::get_legacy_feature_compatibility_versions( $feature );
		return $versions['deprecated_since'] ?? null;
	}

	/**
	 * Gets the WooCommerce version where a legacy feature flag shim will be removed.
	 *
	 * @param string $feature Feature slug.
	 * @return string|null
	 */
	private static function get_legacy_feature_compatibility_removal_version( $feature ) {
		$versions = self::get_legacy_feature_compatibility_versions( $feature );
		return $versions['removed_in'] ?? null;
	}

	/**
	 * Emits a deprecation notice for a direct legacy feature flag shim lookup.
	 *
	 * @param string $method  Method name.
	 * @param string $feature Feature slug.
	 */
	private static function warn_legacy_feature_compatibility_usage( $method, $feature ): void {
		$deprecation_version = self::get_legacy_feature_compatibility_deprecation_version( $feature );
		$removal_version     = self::get_legacy_feature_compatibility_removal_version( $feature );

		if ( ! $deprecation_version ) {
			return;
		}

		wc_deprecated_function(
			sprintf( "%s( '%s' )", $method, $feature ),
			$deprecation_version,
			sprintf(
				'direct feature behavior checks. The %1$s WC Admin feature flag shim will be removed in %2$s.',
				$feature,
				$removal_version ? 'WooCommerce ' . $removal_version : 'a future version of WooCommerce'
			)
		);
	}

	/**
	 * Alias internal features classes to make them backward compatible.
	 * We've moved our feature classes to src-internal as part of merging this
	 * repository with WooCommerce Core to form a monorepo.
	 * See https://wp.me/p90Yrv-2HY for details.
	 */
	private function register_internal_class_aliases() {
		$aliases = array(
			// new class => original class (this will be aliased).
			'Automattic\WooCommerce\Internal\Admin\WCPayPromotion\Init' => 'Automattic\WooCommerce\Admin\Features\WcPayPromotion\Init',
			'Automattic\WooCommerce\Internal\Admin\RemoteFreeExtensions\Init' => 'Automattic\WooCommerce\Admin\Features\RemoteFreeExtensions\Init',
			'Automattic\WooCommerce\Internal\Admin\ActivityPanels' => 'Automattic\WooCommerce\Admin\Features\ActivityPanels',
			'Automattic\WooCommerce\Internal\Admin\Analytics' => 'Automattic\WooCommerce\Admin\Features\Analytics',
			'Automattic\WooCommerce\Internal\Admin\Coupons' => 'Automattic\WooCommerce\Admin\Features\Coupons',
			'Automattic\WooCommerce\Internal\Admin\CouponsMovedTrait' => 'Automattic\WooCommerce\Admin\Features\CouponsMovedTrait',
			'Automattic\WooCommerce\Internal\Admin\CustomerEffortScoreTracks' => 'Automattic\WooCommerce\Admin\Features\CustomerEffortScoreTracks',
			'Automattic\WooCommerce\Internal\Admin\Homescreen' => 'Automattic\WooCommerce\Admin\Features\Homescreen',
			'Automattic\WooCommerce\Internal\Admin\Marketing' => 'Automattic\WooCommerce\Admin\Features\Marketing',
			'Automattic\WooCommerce\Internal\Admin\MobileAppBanner' => 'Automattic\WooCommerce\Admin\Features\MobileAppBanner',
			'Automattic\WooCommerce\Internal\Admin\RemoteInboxNotifications' => 'Automattic\WooCommerce\Admin\Features\RemoteInboxNotifications',
			'Automattic\WooCommerce\Internal\Admin\ShippingLabelBanner' => 'Automattic\WooCommerce\Admin\Features\ShippingLabelBanner',
			'Automattic\WooCommerce\Internal\Admin\ShippingLabelBannerDisplayRules' => 'Automattic\WooCommerce\Admin\Features\ShippingLabelBannerDisplayRules',
			'Automattic\WooCommerce\Internal\Admin\WcPayWelcomePage' => 'Automattic\WooCommerce\Admin\Features\WcPayWelcomePage',
		);
		foreach ( $aliases as $new_class => $orig_class ) {
			class_alias( $new_class, $orig_class );
		}
	}

	/**
	 * Check if we're in an admin context where features should be loaded.
	 *
	 * @return boolean
	 */
	private static function should_load_features() {
		$should_load = (
			is_admin() ||
			wp_doing_ajax() ||
			wp_doing_cron() ||
			( defined( 'WP_CLI' ) && WP_CLI ) ||
			( WC()->is_rest_api_request() && ! WC()->is_store_api_request() ) ||
			// Allow features to be loaded in frontend for admin users. This is needed for the use case such as the coming soon footer banner.
			current_user_can( 'manage_woocommerce' )
		);

		/**
		 * Filter to determine if admin features should be loaded.
		 *
		 * @since 9.6.0
		 * @param boolean $should_load Whether admin features should be loaded. It defaults to true when the current request is in an admin context.
		 */
		return apply_filters( 'woocommerce_admin_should_load_features', $should_load );
	}
}

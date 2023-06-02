<?php
/**
 * Features loader for features developed in WooCommerce Admin.
 */

namespace Automattic\WooCommerce\Admin\Features;

use Automattic\WooCommerce\Admin\Loader;

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
		// Load feature before WooCommerce update hooks.
		add_action( 'init', array( __CLASS__, 'load_features' ), 4 );
		add_filter( 'woocommerce_get_sections_advanced', array( __CLASS__, 'add_features_section' ) );
		add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'add_features_settings' ), 10, 2 );
		add_filter( 'woocommerce_get_settings_advanced', array( __CLASS__, 'maybe_load_beta_features_modal' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ), 15 );
		add_filter( 'admin_body_class', array( __CLASS__, 'add_admin_body_classes' ) );
		add_filter( 'update_option_woocommerce_allow_tracking', array( __CLASS__, 'maybe_disable_features' ), 10, 2 );
	}

	/**
	 * Gets a build configured array of enabled WooCommerce Admin features/sections.
	 *
	 * @return array Enabled Woocommerce Admin features/sections.
	 */
	public static function get_features() {
		return apply_filters( 'woocommerce_admin_features', array() );
	}

	/**
	 * Gets the beta feature options as an associative array that can be toggled on or off.
	 *
	 * @return array
	 */
	public static function get_beta_feature_options() {
		$features = [];

		$navigation_class = self::get_feature_class( 'navigation' );
		if ( $navigation_class ) {
			$features['navigation'] = $navigation_class::TOGGLE_OPTION_NAME;
		}

		return $features;
	}

	/**
	 * Returns if a specific wc-admin feature exists in the current environment.
	 *
	 * @param  string $feature Feature slug.
	 * @return bool Returns true if the feature exists.
	 */
	public static function exists( $feature ) {
		$features = self::get_features();
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

		if ( class_exists( $feature_class ) ) {
			return $feature_class;
		}

		// Handle features contained in subdirectory.
		if ( class_exists( $feature_class . '\\Init' ) ) {
			return $feature_class . '\\Init';
		}

		return null;
	}

	/**
	 * Class loader for enabled WooCommerce Admin features/sections.
	 */
	public static function load_features() {
		$features = self::get_features();
		foreach ( $features as $feature ) {
			$feature_class = self::get_feature_class( $feature );

			if ( $feature_class ) {
				new $feature_class();
			}
		}
	}

	/**
	 * Check if a feature is enabled.  Defaults to true for all features unless they are in beta.
	 *
	 * @param string $feature Feature slug.
	 * @return bool
	 */
	public static function is_enabled( $feature ) {
		if ( ! self::exists( $feature ) ) {
			return false;
		}

		$features = self::get_beta_feature_options();

		if ( isset( $features[ $feature ] ) ) {
			return 'yes' === get_option( $features[ $feature ], 'no' );
		}

		return true;
	}

	/**
	 * Disable a toggleable beta feature.
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	public static function disable( $feature ) {
		$features = self::get_beta_feature_options();

		if ( isset( $features[ $feature ] ) ) {
			update_option( $features[ $feature ], 'no' );
			return true;
		}

		return false;
	}

	/**
	 * Disable features when opting out of tracking.
	 *
	 * @param string $old_value Old value.
	 * @param string $value New value.
	 */
	public static function maybe_disable_features( $old_value, $value ) {
		if ( 'yes' === $value ) {
			return;
		}

		foreach ( self::get_features() as $feature ) {
			self::disable( $feature );
		}
	}

	/**
	 * Adds the Features section to the advanced tab of WooCommerce Settings
	 *
	 * @param array $sections Sections.
	 * @return array
	 */
	public static function add_features_section( $sections ) {
		$features = apply_filters(
			'woocommerce_settings_features',
			array()
		);

		if ( empty( $features ) ) {
			return $sections;
		}

		$sections['features'] = __( 'Features', 'woocommerce' );
		return $sections;
	}

	/**
	 * Adds the Features settings.
	 *
	 * @param array  $settings Settings.
	 * @param string $current_section Current section slug.
	 * @return array
	 */
	public static function add_features_settings( $settings, $current_section ) {
		if ( 'features' !== $current_section ) {
			return $settings;
		}

		$features = apply_filters(
			'woocommerce_settings_features',
			array()
		);

		if ( empty( $features ) ) {
			return $settings;
		}

		return array_merge(
			array(
				array(
					'title' => __( 'Features', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => __( 'Start using new features that are being progressively rolled out to improve the store management experience.', 'woocommerce' ),
					'id'    => 'features_options',
				),
			),
			$features,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'features_options',
				),
			)
		);
	}

	/**
	 * Conditionally loads the beta features tracking modal.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public static function maybe_load_beta_features_modal( $settings ) {
		$tracking_enabled = get_option( 'woocommerce_allow_tracking', 'no' );

		if ( 'yes' === $tracking_enabled ) {
			return $settings;
		}

		$rtl = is_rtl() ? '.rtl' : '';

		wp_enqueue_style(
			'wc-admin-beta-features-tracking-modal',
			Loader::get_url( "beta-features-tracking-modal/style{$rtl}", 'css' ),
			array( 'wp-components' ),
			Loader::get_file_version( 'css' )
		);

		wp_enqueue_script(
			'wc-admin-beta-features-tracking-modal',
			Loader::get_url( 'wp-admin-scripts/beta-features-tracking-modal', 'js' ),
			array( 'wp-i18n', 'wp-element', WC_ADMIN_APP ),
			Loader::get_file_version( 'js' ),
			true
		);

		return $settings;
	}

	/**
	 * Loads the required scripts on the correct pages.
	 */
	public static function load_scripts() {
		if ( ! Loader::is_admin_or_embed_page() ) {
			return;
		}

		if ( ! Loader::user_can_analytics() ) {
			return;
		}

		$features         = self::get_features();
		$enabled_features = array();
		foreach ( $features as $key ) {
			$enabled_features[ $key ] = self::is_enabled( $key );
		}
		wp_add_inline_script( WC_ADMIN_APP, 'window.wcAdminFeatures = ' . wp_json_encode( $enabled_features ), 'before' );
	}


	/**
	 * Adds body classes to the main wp-admin wrapper, allowing us to better target elements in specific scenarios.
	 *
	 * @param string $admin_body_class Body class to add.
	 */
	public static function add_admin_body_classes( $admin_body_class = '' ) {
		if ( ! Loader::is_admin_or_embed_page() ) {
			return $admin_body_class;
		}

		$classes = explode( ' ', trim( $admin_body_class ) );

		$features = self::get_features();
		foreach ( $features as $feature_key ) {
			$classes[] = sanitize_html_class( 'woocommerce-feature-enabled-' . $feature_key );
		}

		$admin_body_class = implode( ' ', array_unique( $classes ) );
		return " $admin_body_class ";
	}
}

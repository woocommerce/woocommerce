<?php
/**
 * Beta Tester Plugin Admin Features class.
 *
 * @package WC_Beta_Tester
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Beta_Tester Admin Features Class.
 */
class WC_Beta_Tester_Admin_Features {
	/**
	 * Constructor.
	 */
	public function __construct() {
		/**
		 * We need to hook into both filters to ensure that the feature flags are correctly set.
		 *
		 * The `woocommerce_admin_get_feature_config` filter is used to modify the feature config which holds the default values for the feature flags. We need this to ensure beta tester can fetch the available feature flags. See get_features() in ../api/features/features.php and replace_supported_features() in Automattic\WooCommerce\Internal\Admin\FeaturePlugin.
		 *
		 * The `woocommerce_admin_features` filter is used to modify the features directly. This allows us to test code behind feature flags even if they are run before the plugins_loaded hook is fired. For example, test code behind a feature flag in WooCommerce's install routines.
		 */
		add_filter( 'woocommerce_admin_get_feature_config', array( $this, 'modify_feature_config' ) );
		add_filter( 'woocommerce_admin_features', array( $this, 'modify_features' ) );
	}

	/**
	 * Modify the features.
	 *
	 * @param array $features Features.
	 * @return array
	 */
	public function modify_features( $features ) {
		$custom_feature_values = get_option( 'wc_admin_helper_feature_values', array() );

		$disabled_features = array_keys( array_diff( $custom_feature_values, array( true ) ) );
		$enabled_features  = array_keys( array_filter( $custom_feature_values ) );

		$features = array_merge( $features, $enabled_features );
		$features = array_diff( $features, $disabled_features );

		return $features;
	}

	/**
	 * Modify the feature config.
	 *
	 * @param array $feature_config Feature config.
	 * @return array
	 */
	public function modify_feature_config( $feature_config ) {
		$custom_feature_values = get_option( 'wc_admin_helper_feature_values', array() );
		foreach ( $custom_feature_values as $feature => $value ) {
			if ( isset( $feature_config[ $feature ] ) ) {
				$feature_config[ $feature ] = $value;
			}
		}
		return $feature_config;
	}
}

return new WC_Beta_Tester_Admin_Features();

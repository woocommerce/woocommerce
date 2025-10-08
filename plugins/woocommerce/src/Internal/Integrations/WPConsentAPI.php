<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Integrations;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Traits\ScriptDebug;
use WP_CONSENT_API;

/**
 * Class WPConsentAPI
 *
 * @since 8.5.0
 */
class WPConsentAPI {

	use ScriptDebug;


	/**
	 * Identifier of the consent category used for order attribution.
	 *
	 * @var string
	 */
	public static $consent_category = 'marketing';

	/**
	 * Register the consent API.
	 *
	 * @return void
	 */
	public function register() {
		add_action(
			'init',
			function() {
				$this->on_init();
			},
			20 // After OrderAttributionController.
		);
	}

	/**
	 * Register our hooks on init.
	 *
	 * @return void
	 */
	protected function on_init() {
		// Include integration to WP Consent Level API if available.
		if ( ! $this->is_wp_consent_api_active() ) {
			return;
		}

		$plugin = plugin_basename( WC_PLUGIN_FILE );
		add_filter( "wp_consent_api_registered_{$plugin}", '__return_true' );
		add_action(
			'wp_enqueue_scripts',
			function() {
				$this->enqueue_consent_api_scripts();
			}
		);

		/**
		 * Modify the "allowTracking" flag consent if the user has consented to marketing.
		 *
		 * Wp-consent-api will initialize the modules on "init" with priority 9,
		 * So this code needs to be run after that.
		 */
		add_filter(
			'wc_order_attribution_allow_tracking',
			function() {
				return function_exists( 'wp_has_consent' ) && wp_has_consent( self::$consent_category );
			}
		);
	}

	/**
	 * Check if WP Cookie Consent API is active
	 *
	 * @return bool
	 */
	protected function is_wp_consent_api_active() {
		return class_exists( WP_CONSENT_API::class );
	}

	/**
	 * Enqueue JS for integration with WP Consent Level API
	 *
	 * @return void
	 */
	private function enqueue_consent_api_scripts() {
		wp_enqueue_script(
			'wp-consent-api-integration',
			plugins_url(
				"assets/js/frontend/wp-consent-api-integration{$this->get_script_suffix()}.js",
				WC_PLUGIN_FILE
			),
			array( 'wp-consent-api', 'wc-order-attribution' ),
			Constants::get_constant( 'WC_VERSION' ),
			true
		);

		// Add data for the script above. `wp_enqueue_script` API does not allow data attributes,
		// so we need a separate script tag and pollute the global scope.
		wp_add_inline_script(
			'wp-consent-api-integration',
			sprintf(
				'window.wc_order_attribution.params.consentCategory = %s;',
				wp_json_encode( self::$consent_category, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES )
			),
			'before'
		);
	}
}

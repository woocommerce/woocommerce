<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\WCCom;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\Traits\ScriptDebug;

/**
 * Class TrackingController
 *
 * @since x.x.x
 */
class TrackingController implements RegisterHooksInterface {

	use ScriptDebug;

	/**
	 * @var FeaturesController
	 */
	private $features_controller;

	/**
	 * WCCOMTracking init.
	 *
	 * @param FeaturesController $features_controller Features controller.
	 */
	final public function init( FeaturesController $features_controller ) {
		$this->features_controller = $features_controller;
	}

	/**
	 * Register the WCCOM integration.
	 *
	 * @return void
	 */
	public function register() {
		// Bail if the feature is not enabled.
		if ( ! $this->features_controller->feature_is_enabled( 'order_source_attribution' ) ) {
			return;
		}

		// Bail if this is not a WCCOM site.
		if ( ! $this->is_wccom_cookie_terms_available() ) {
			return;
		}

		add_filter(
			'wc_order_source_attribution_allow_tracking',
			function() {
				return $this->is_wccom_tracking_allowed();
			}
		);

		add_action(
			'wp_enqueue_scripts',
			function() {
				$this->enqueue_scripts();
			}
		);
	}

	/**
	 * Check if WCCom_Cookie_Terms is available.
	 *
	 * @return bool
	 */
	protected function is_wccom_cookie_terms_available() {
		return class_exists( WCCom_Cookie_Terms::class );
	}

	/**
	 * Check if WCCOM tracking is allowed.
	 *
	 * @return bool
	 */
	protected function is_wccom_tracking_allowed() {
		return WCCom_Cookie_Terms::instance()->can_track_user( 'analytics' );
	}

	/**
	 * Enqueue JS for integration with WCCOM Consent Management API
	 *
	 * @return void
	 */
	private function enqueue_scripts() {
		wp_enqueue_script(
			'wccom-integration-js',
			plugins_url( "assets/js/frontend/wccom-integration{$this->get_script_suffix()}.js", WC_PLUGIN_FILE ),
			array( 'woocommerce-order-source-attribution-js' ),
			WC_VERSION,
			true
		);
	}
}

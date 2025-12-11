<?php
/**
 * FraudProtectionController class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\Features\FeaturesController;

defined( 'ABSPATH' ) || exit;

/**
 * Main controller for fraud protection features.
 *
 * This class orchestrates all fraud protection components and ensures
 * zero-impact when the feature flag is disabled.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class FraudProtectionController {

	/**
	 * Features controller instance.
	 *
	 * @var FeaturesController
	 */
	private $features_controller;

	/**
	 * Constructor. Sets up the controller.
	 */
	public function __construct() {
		$container = wc_get_container();
		$this->features_controller = $container->get( FeaturesController::class );

		// Defer feature check until init action to avoid triggering translation loading
		// before WooCommerce's textdomain is loaded.
		// See https://github.com/woocommerce/woocommerce/pull/61424.
		add_action( 'init', array( $this, 'maybe_init_hooks' ), 0 );
	}

	/**
	 * Initialize fraud protection hooks if the feature is enabled.
	 *
	 * This is called on the init action to defer the feature check until after
	 * WooCommerce's textdomain is loaded, avoiding the "translation loaded too early" notice.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function maybe_init_hooks(): void {
		if ( $this->is_fraud_protection_enabled() ) {
			$this->init();
		}
	}

	/**
	 * Initialize the controller.
	 * Called when feature flag is enabled.
	 *
	 * This method will be used to register hooks and initialize components
	 * in future implementations.
	 *
	 * @return void
	 */
	private function init(): void {
		// Future implementation: Register hooks and initialize components here.
		// For now, this is a placeholder for the infrastructure.
	}

	/**
	 * Check if fraud protection feature is enabled.
	 *
	 * This method can be used by other fraud protection classes to check
	 * the feature flag status.
	 *
	 * @return bool True if enabled.
	 */
	public function is_fraud_protection_enabled(): bool {
		return $this->features_controller->feature_is_enabled( 'fraud_protection' );
	}

	/**
	 * Log helper method for consistent logging across all fraud protection components.
	 *
	 * This static method ensures all fraud protection logs are written with
	 * the same 'woo-fraud-protection' source for easy filtering in WooCommerce logs.
	 *
	 * @param string $level   Log level (emergency, alert, critical, error, warning, notice, info, debug).
	 * @param string $message Log message.
	 * @param array  $context Optional context data.
	 *
	 * @return void
	 */
	public static function log( string $level, string $message, array $context = array() ): void {
		wc_get_logger()->log(
			$level,
			$message,
			array_merge( $context, array( 'source' => 'woo-fraud-protection' ) )
		);
	}
}

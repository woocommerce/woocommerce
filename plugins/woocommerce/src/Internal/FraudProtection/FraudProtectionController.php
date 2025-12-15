<?php
/**
 * FraudProtectionController class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

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
class FraudProtectionController implements RegisterHooksInterface {

	/**
	 * Features controller instance.
	 *
	 * @var FeaturesController
	 */
	private FeaturesController $features_controller;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );
	}

	/**
	 * Initialize the instance, runs when the instance is created by the dependency injection container.
	 *
	 * @internal
	 * @param FeaturesController $features_controller The instance of FeaturesController to use.
	 */
	final public function init( FeaturesController $features_controller ): void {
		$this->features_controller = $features_controller;
	}

	/**
	 * Hook into WordPress on init.
	 *
	 * @internal
	 */
	public function on_init(): void {
		// Bail if the feature is not enabled.
		if ( ! $this->feature_is_enabled() ) {
			return;
		}

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
	public function feature_is_enabled(): bool {
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

<?php
/**
 * FraudProtectionController class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\Jetpack\JetpackConnection;
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
	 * Blocked session notice instance.
	 *
	 * @var BlockedSessionNotice
	 */
	private BlockedSessionNotice $blocked_session_notice;

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'on_init' ) );
		add_action( 'admin_notices', array( $this, 'on_admin_notices' ) );
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'maybe_register_jetpack_connection' ), 10, 2 );
	}

	/**
	 * Initialize the instance, runs when the instance is created by the dependency injection container.
	 *
	 * @internal
	 *
	 * @param FeaturesController   $features_controller      The instance of FeaturesController to use.
	 * @param BlockedSessionNotice $blocked_session_notice   The instance of BlockedSessionNotice to use.
	 */
	final public function init(
		FeaturesController $features_controller,
		BlockedSessionNotice $blocked_session_notice
	): void {
		$this->features_controller    = $features_controller;
		$this->blocked_session_notice = $blocked_session_notice;
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

		$this->blocked_session_notice->register();
	}

	/**
	 * Display admin notice when Jetpack connection is not available.
	 *
	 * @internal
	 */
	public function on_admin_notices(): void {
		// Only show if feature is enabled.
		if ( ! $this->feature_is_enabled() || JetpackConnection::get_manager()->is_connected() ) {
			return;
		}

		// Only show on WooCommerce settings page.
		$screen = get_current_screen();

		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: Getting Started with Jetpack documentation URL */
					wp_kses_post( __( 'Your site failed to connect to Jetpack automatically. Fraud protection will fail open and allow all sessions until your site is connected to Jetpack. <a href="%s">How to connect to Jetpack</a>', 'woocommerce' ) ),
					esc_url( 'https://jetpack.com/support/getting-started-with-jetpack/' )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Maybe register Jetpack connection when fraud protection is enabled.
	 *
	 * Attempts to automatically register the site with Jetpack when the fraud protection
	 * feature is enabled and the site is not already connected.
	 *
	 * @since 10.5.0
	 *
	 * @internal
	 *
	 * @param string $feature_id The feature ID being toggled.
	 * @param bool   $is_enabled Whether the feature is being enabled or disabled.
	 */
	public function maybe_register_jetpack_connection( string $feature_id, bool $is_enabled ): void {
		if ( 'fraud_protection' !== $feature_id || ! $is_enabled ) {
			return;
		}

		$manager = JetpackConnection::get_manager();

		if ( $manager->is_connected() ) {
			return;
		}

		$result = $manager->try_registration();

		if ( is_wp_error( $result ) ) {
			$this->log( 'error', 'Failed to register Jetpack connection: ' . $result->get_error_message() );
			return;
		}

		$this->log( 'info', 'Jetpack connection registered successfully' );
	}

	/**
	 * Check if fraud protection feature is enabled.
	 *
	 * This method can be used by other fraud protection classes to check
	 * the feature flag status. Returns false (fail-open) if init hasn't run yet.
	 *
	 * @return bool True if enabled, false if not enabled or init hasn't run yet.
	 */
	public function feature_is_enabled(): bool {
		// Fail-open: don't block if init hasn't run yet to avoid FeaturesController translation notices.
		if ( ! did_action( 'init' ) ) {
			return false;
		}
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

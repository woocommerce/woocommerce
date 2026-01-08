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
	 * Jetpack connection manager instance.
	 *
	 * @var JetpackConnectionManager
	 */
	private JetpackConnectionManager $connection_manager;

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
	}

	/**
	 * Initialize the instance, runs when the instance is created by the dependency injection container.
	 *
	 * @internal
	 *
	 * @param FeaturesController       $features_controller      The instance of FeaturesController to use.
	 * @param JetpackConnectionManager $connection_manager       The instance of JetpackConnectionManager to use.
	 * @param BlockedSessionNotice     $blocked_session_notice   The instance of BlockedSessionNotice to use.
	 */
	final public function init(
		FeaturesController $features_controller,
		JetpackConnectionManager $connection_manager,
		BlockedSessionNotice $blocked_session_notice
	): void {
		$this->features_controller    = $features_controller;
		$this->connection_manager     = $connection_manager;
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
		if ( ! $this->feature_is_enabled() ) {
			return;
		}

		// Only show on WooCommerce settings page.
		$screen = get_current_screen();
		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		$connection_status = $this->connection_manager->get_connection_status();
		if ( $connection_status['connected'] ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' );

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Fraud protection warning:', 'woocommerce' ); ?></strong>
				<?php echo esc_html( $connection_status['error'] ); ?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: Settings page URL */
					wp_kses_post( __( 'Fraud protection will fail open and allow all sessions until connected. <a href="%s">Connect to Jetpack</a>', 'woocommerce' ) ),
					esc_url( $settings_url )
				);
				?>
			</p>
		</div>
		<?php
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

<?php
/**
 * BlackboxIntegration class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Handles Blackbox integration for fraud protection.
 *
 * This class manages the client-side Blackbox SDK integration and processes
 * session IDs from various checkout flows (Blocks, Shortcode, Add Payment Method).
 *
 * @since 10.6.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class BlackboxIntegration {

	/**
	 * Session clearance manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private SessionClearanceManager $session_manager;

	/**
	 * Fraud protection dispatcher instance.
	 *
	 * @var FraudProtectionDispatcher
	 */
	private FraudProtectionDispatcher $dispatcher;

	/**
	 * Initialize with dependencies.
	 *
	 * Note: FraudProtectionController is not injected to avoid circular dependency.
	 * It's retrieved from the container when needed via feature_is_enabled().
	 *
	 * @internal
	 *
	 * @param SessionClearanceManager   $session_manager The session clearance manager instance.
	 * @param FraudProtectionDispatcher $dispatcher      The fraud protection dispatcher instance.
	 */
	final public function init(
		SessionClearanceManager $session_manager,
		FraudProtectionDispatcher $dispatcher
	): void {
		$this->session_manager = $session_manager;
		$this->dispatcher      = $dispatcher;
	}

	/**
	 * Register hooks for Blackbox integration.
	 *
	 * Called from FraudProtectionController::on_init() which already checks
	 * if the feature is enabled.
	 *
	 * @return void
	 */
	public function register(): void {
		// Register Blocks callback for cart/extensions endpoint.
		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'woocommerce/fraud-protection',
				'callback'  => array( $this, 'handle_blocks_session_id' ),
			)
		);

		// AJAX endpoint for add-payment-method verification.
		add_action( 'wc_ajax_fraud_protection_verify', array( $this, 'ajax_verify_session' ) );

		// Enqueue scripts on checkout and add-payment-method pages.
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
		add_action( 'woocommerce_blocks_checkout_enqueue_data', array( $this, 'enqueue_blocks_data' ) );

		// Reset session clearance for blocks checkout BEFORE cart data is hydrated.
		// This ensures payment methods are hidden until verification completes.
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', array( $this->session_manager, 'reset_expired_session_clearance' ) );
	}

	/**
	 * Handle session_id from Blocks checkout via cart/extensions.
	 *
	 * @param array $data Data from applyExtensionCartUpdate.
	 * @return void
	 */
	public function handle_blocks_session_id( array $data ): void {
		$session_id = sanitize_text_field( $data['blackbox_session_id'] ?? '' );
		$this->process_blackbox_session( $session_id );
	}

	/**
	 * Handle session_id from shortcode checkout.
	 * Called from WC_AJAX::update_order_review().
	 *
	 * @param string $session_id The Blackbox session ID from POST.
	 * @return void
	 */
	public function handle_shortcode_session_id( string $session_id ): void {
		$this->process_blackbox_session( sanitize_text_field( $session_id ) );
	}

	/**
	 * AJAX endpoint for add-payment-method verification.
	 * Called via wc_ajax_fraud_protection_verify.
	 *
	 * @return void
	 */
	public function ajax_verify_session(): void {
		check_ajax_referer( 'fraud-protection-verify', 'security' );

		$session_id = isset( $_POST['blackbox_session_id'] )
			? sanitize_text_field( wp_unslash( $_POST['blackbox_session_id'] ) )
			: '';

		// Process and verify.
		$this->process_blackbox_session( $session_id );

		// Return result.
		if ( $this->session_manager->is_session_blocked() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to proceed at this time. Please try again later.', 'woocommerce' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Verification complete.', 'woocommerce' ),
			)
		);
	}

	/**
	 * Process Blackbox session - called from all handlers.
	 * Always calls verify, even with empty session_id (fail-open, let server decide).
	 *
	 * @param string $session_id The Blackbox session ID (may be empty).
	 * @return void
	 */
	public function process_blackbox_session( string $session_id ): void {
		// Skip if already processed this exact session.
		if ( ! empty( $session_id ) && $this->session_manager->get_blackbox_session_id() === $session_id ) {
			return;
		}

		FraudProtectionController::log(
			'info',
			sprintf(
				'Processing Blackbox session: %s | WC Session: %s',
				$session_id ? $session_id : '(empty - fail-open)',
				$this->session_manager->get_session_id()
			)
		);

		// Store the blackbox session ID in the WC session.
		// SessionDataCollector will include it in the 'session' data when collecting event data.
		if ( ! empty( $session_id ) ) {
			$this->session_manager->set_blackbox_session_id( $session_id );
		}

		// Dispatch checkout event to WPCOM - let server-side decide.
		// The blackbox_session_id is included in session data via SessionDataCollector.
		$this->dispatcher->dispatch_event( 'checkout' );
	}

	/**
	 * Enqueue scripts on shortcode checkout and add-payment-method pages.
	 *
	 * Skips checkout pages that use the Blocks checkout - those are handled
	 * by enqueue_blocks_data() via the woocommerce_blocks_checkout_enqueue_data hook.
	 *
	 * @return void
	 */
	public function maybe_enqueue_scripts(): void {
		// Skip if this is a Blocks checkout page - handled via woocommerce_blocks_enqueue_checkout_block_scripts_before.
		if ( is_checkout() && has_block( 'woocommerce/checkout' ) ) {
			return;
		}

		if ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'add-payment-method' ) ) {
			// Reset clearance to hide payment methods until verification completes.
			// Skips reset if recently verified (prevents infinite loop on add-payment-method reload).
			$this->session_manager->reset_expired_session_clearance();

			$checkout_type = is_wc_endpoint_url( 'add-payment-method' ) ? 'add-payment-method' : 'shortcode';
			$this->enqueue_blackbox_scripts( $checkout_type );
		}
	}

	/**
	 * Enqueue scripts and data for Blocks checkout.
	 *
	 * @return void
	 */
	public function enqueue_blocks_data(): void {
		$this->enqueue_blackbox_scripts( 'blocks' );
	}

	/**
	 * Enqueue Blackbox-related scripts.
	 *
	 * @param string $checkout_type The checkout type: 'blocks', 'shortcode', or 'add-payment-method'.
	 * @return void
	 */
	private function enqueue_blackbox_scripts( string $checkout_type ): void {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Enqueue mock Blackbox SDK.
		wp_enqueue_script(
			'wc-fraud-protection-blackbox-mock',
			plugins_url( 'assets/js/frontend/fraud-protection/blackbox-mock' . $suffix . '.js', WC_PLUGIN_FILE ),
			array(),
			WC_VERSION,
			array( 'in_footer' => true )
		);

		// Enqueue WooCommerce fraud checkout script.
		$deps = 'blocks' === $checkout_type
			? array( 'wc-fraud-protection-blackbox-mock', 'wp-data', 'wp-api-fetch' )
			: array( 'wc-fraud-protection-blackbox-mock', 'jquery' );

		wp_enqueue_script(
			'wc-fraud-protection-checkout',
			plugins_url( 'assets/js/frontend/fraud-protection/checkout' . $suffix . '.js', WC_PLUGIN_FILE ),
			$deps,
			WC_VERSION,
			array( 'in_footer' => true )
		);

		wp_localize_script(
			'wc-fraud-protection-checkout',
			'wcFraudProtection',
			array(
				'checkoutType'      => $checkout_type,
				'namespace'         => 'woocommerce/fraud-protection',
				'timeoutMs'         => 5000,
				'ajaxUrl'           => \WC_AJAX::get_endpoint( 'fraud_protection_verify' ),
				'nonce'             => wp_create_nonce( 'fraud-protection-verify' ),
				'isSessionVerified' => ! $this->session_manager->is_session_pending(), // true = ALLOWED or BLOCKED.
			)
		);
	}
}

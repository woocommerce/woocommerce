<?php
/**
 * FraudProtectionController class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Main controller for fraud protection features.
 *
 * This class sets up hooks on instantiation to implement fraud protection friction points.
 *
 * @since 10.4.0
 */
class FraudProtectionController {

	/**
	 * Session clearance manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private $session_manager;

	/**
	 * Features controller instance.
	 *
	 * @var FeaturesController
	 */
	private $features_controller;

	/**
	 * Constructor. Sets up hooks on instantiation.
	 */
	public function __construct() {
		// Get dependencies from container.
		$container = wc_get_container();
		$this->session_manager     = $container->get( SessionClearanceManager::class );
		$this->features_controller = $container->get( FeaturesController::class );

		// Register email classes and preview handlers (always, even when feature is disabled).
		add_filter( 'woocommerce_email_classes', array( $this, 'handle_register_email_classes' ), 10, 1 );
		add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'handle_prepare_otp_email_for_preview' ), 10, 1 );

		// Only initialize if fraud protection is enabled.
		if ( $this->is_fraud_protection_enabled() ) {
			$this->init_hooks();
		}
	}

	/**
	 * Initialize hooks for fraud protection.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		$apply_to = $this->get_apply_to_setting();

		// Ensure cart (and session) is loaded before fraud protection checks.
		// Cart initialization handles session setup for both traditional and Store API flows.
		add_action( 'woocommerce_load_cart_from_session', array( $this, 'handle_ensure_cart_loaded' ), 5, 0 );

		// Checkout friction: hide payment methods.
		if ( 'checkout' === $apply_to || 'both' === $apply_to ) {
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'handle_checkout_payment_methods' ), 10, 1 );
			add_action( 'woocommerce_before_checkout_form', array( $this, 'handle_check_blocked_session_redirect' ), 1, 0 );
		}

		// Cart friction: block add to cart.
		if ( 'cart' === $apply_to || 'both' === $apply_to ) {
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'handle_add_to_cart_validation' ), 10, 3 );
			add_action( 'woocommerce_before_cart', array( $this, 'handle_check_blocked_session_redirect' ), 1, 0 );
		}

		// Enqueue frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'handle_enqueue_frontend_assets' ), 10, 0 );

		// Block API requests for blocked sessions.
		add_filter( 'rest_pre_dispatch', array( $this, 'handle_rest_api_blocked_session' ), 10, 3 );
	}

	/**
	 * Ensure cart and session are loaded for fraud protection.
	 *
	 * This hook fires after cart is loaded from session, ensuring both cart and session
	 * are available. Works for both traditional (cookie) and Store API (token) flows.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_ensure_cart_loaded(): void {
		// This hook is called after cart is loaded from session.
		// By this point, WC()->cart and WC()->session are both available.
		// No additional initialization needed - we're just using this as a timing anchor.
	}

	/**
	 * Check if fraud protection feature is enabled.
	 *
	 * @return bool True if enabled.
	 */
	private function is_fraud_protection_enabled(): bool {
		return $this->features_controller->feature_is_enabled( 'fraud_protection' );
	}

	/**
	 * Get the "apply to" setting value.
	 *
	 * @return string One of: cart, checkout, both.
	 */
	private function get_apply_to_setting(): string {
		$apply_to = get_option( 'woocommerce_fraud_protection_apply_to', 'both' );

		// Validate the value.
		if ( ! in_array( $apply_to, array( 'cart', 'checkout', 'both' ), true ) ) {
			return 'both';
		}

		return $apply_to;
	}

	/**
	 * Get asset URL.
	 *
	 * @param string $path Asset path relative to plugin root.
	 * @return string Asset URL.
	 */
	private function get_asset_url( string $path ): string {
		/**
		 * Filter the asset URL.
		 *
		 * @since 10.4.0
		 *
		 * @param string $url  The asset URL.
		 * @param string $path The asset path.
		 */
		return apply_filters( 'woocommerce_get_asset_url', plugins_url( $path, WC_PLUGIN_FILE ), $path );
	}

	/**
	 * Handle checkout payment methods filtering.
	 *
	 * Hides all payment methods if session is not cleared.
	 *
	 * @internal
	 *
	 * @param array $available_gateways Available payment gateways.
	 * @return array Filtered payment gateways.
	 */
	public function handle_checkout_payment_methods( $available_gateways ) {
		// If session is not cleared, hide all payment methods.
		if ( ! $this->session_manager->is_session_cleared() ) {
			return array();
		}

		return $available_gateways;
	}

	/**
	 * Handle add to cart validation.
	 *
	 * Blocks add to cart if session is not cleared.
	 *
	 * @internal
	 *
	 * @param bool $passed Validation status.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity Quantity.
	 * @return bool Validation status.
	 */
	public function handle_add_to_cart_validation( $passed, $product_id, $quantity ) {
		// If session is not cleared, block add to cart.
		if ( ! $this->session_manager->is_session_cleared() ) {
			// Don't show error notice - we'll handle this via modal.
			return false;
		}

		return $passed;
	}

	/**
	 * Enqueue frontend assets for fraud protection modal.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_enqueue_frontend_assets(): void {
		// Only load on checkout, cart, and product pages.
		if ( ! is_checkout() && ! is_cart() && ! is_product() && ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
			return;
		}

		$suffix  = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';
		$version = Constants::get_constant( 'WC_VERSION' );

		// Register and enqueue JavaScript.
		$script_url = $this->get_asset_url( 'assets/js/frontend/fraud-protection-clearance-modal' . $suffix . '.js' );
		wp_enqueue_script(
			'wc-fraud-protection-modal',
			$script_url,
			array( 'jquery' ),
			$version,
			true
		);

		// Register and enqueue CSS.
		$style_url = $this->get_asset_url( 'assets/css/fraud-protection-clearance-modal' . $suffix . '.css' );
		wp_enqueue_style(
			'wc-fraud-protection-modal',
			$style_url,
			array(),
			$version
		);

		// Pass data to JavaScript.
		wp_localize_script(
			'wc-fraud-protection-modal',
			'wcFraudProtection',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'restUrl'       => rest_url( 'wc/v3/fraud-protection/clearance' ),
				'nonce'         => wp_create_nonce( 'wc-fraud-protection' ),
				'restNonce'     => wp_create_nonce( 'wp_rest' ),
				'sessionStatus' => $this->session_manager->get_session_status(),
				'isCheckout'    => is_checkout(),
				'isProduct'     => is_product() || is_shop() || is_product_category() || is_product_tag(),
				'shopUrl'       => get_permalink( wc_get_page_id( 'shop' ) ),
				'applyTo'       => $this->get_apply_to_setting(),
			)
		);
	}

	/**
	 * Block REST API requests for blocked sessions.
	 *
	 * @internal
	 *
	 * @param mixed            $result  Response to replace the requested version with.
	 * @param \WP_REST_Server  $server  Server instance.
	 * @param \WP_REST_Request $request Request used to generate the response.
	 * @return mixed Response.
	 */
	public function handle_rest_api_blocked_session( $result, $server, $request ) {
		// Only check WooCommerce endpoints.
		$route = $request->get_route();
		if ( strpos( $route, '/wc/' ) === false && strpos( $route, '/wc-' ) === false ) {
			return $result;
		}

		// Allow the clearance endpoint itself.
		if ( strpos( $route, '/fraud-protection/clearance' ) !== false ) {
			return $result;
		}

		// Check if session is blocked.
		if ( $this->session_manager->is_session_blocked() ) {
			return new \WP_Error(
				'fraud_protection_session_blocked',
				__( 'Your session has been blocked due to security concerns.', 'woocommerce' ),
				array( 'status' => 401 )
			);
		}

		return $result;
	}

	/**
	 * Check for blocked session and redirect to shop page.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_check_blocked_session_redirect(): void {
		if ( $this->session_manager->is_session_blocked() ) {
			$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
			wp_safe_redirect( $shop_url );
			exit;
		}
	}

	/**
	 * Register fraud protection email classes with WooCommerce.
	 *
	 * @internal
	 *
	 * @param array $email_classes Existing email classes.
	 * @return array Modified email classes array.
	 */
	public function handle_register_email_classes( $email_classes ) {
		// Include the email class file.
		require_once WC()->plugin_path() . '/includes/emails/class-wc-email-fraud-protection-otp.php';

		// Add our email class to the list.
		$email_classes['WC_Email_Fraud_Protection_Otp'] = new \WC_Email_Fraud_Protection_Otp();

		return $email_classes;
	}

	/**
	 * Prepare OTP email for preview with dummy data.
	 *
	 * @internal
	 *
	 * @param \WC_Email $email Email object being prepared for preview.
	 * @return \WC_Email Modified email object.
	 */
	public function handle_prepare_otp_email_for_preview( $email ) {
		// Only modify our OTP email class.
		if ( ! $email instanceof \WC_Email_Fraud_Protection_Otp ) {
			return $email;
		}

		// Set dummy data for preview.
		$email->otp_code           = '123456';
		$email->expiration_minutes = 60;
		$email->challenge_id       = 'preview_challenge_id_12345';
		$email->user_email         = 'customer@example.com';

		return $email;
	}
}

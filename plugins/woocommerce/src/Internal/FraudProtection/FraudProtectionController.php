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

		// Register Store API routes.
		add_action( 'rest_api_init', array( $this, 'handle_register_store_api_routes' ), 10, 0 );

		// Register email classes and preview handlers (always, even when feature is disabled).
		add_filter( 'woocommerce_email_classes', array( $this, 'handle_register_email_classes' ), 10, 1 );
		add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'handle_prepare_otp_email_for_preview' ), 10, 1 );

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

		// Track cart events (add, update, remove, restore) for fraud signals.
		if ( 'cart' === $apply_to || 'both' === $apply_to ) {
			add_action( 'woocommerce_add_to_cart', array( $this, 'handle_track_cart_item_added' ), 10, 6 );
			add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'handle_track_cart_item_updated' ), 10, 4 );
			add_action( 'woocommerce_remove_cart_item', array( $this, 'handle_track_cart_item_removed' ), 10, 2 );
			add_action( 'woocommerce_restore_cart_item', array( $this, 'handle_track_cart_item_restored' ), 10, 2 );
			add_action( 'woocommerce_before_cart', array( $this, 'handle_check_blocked_session_redirect' ), 1, 0 );
		}

		// Enqueue frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'handle_enqueue_frontend_assets' ), 10, 0 );

		// Block API requests for blocked sessions.
		add_filter( 'rest_pre_dispatch', array( $this, 'handle_rest_api_blocked_session' ), 10, 3 );
	}

	/**
	 * Register Store API routes for fraud protection challenge endpoints.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_register_store_api_routes(): void {
		$container = wc_get_container();

		// Get dependencies.
		$api_client        = $container->get( FraudProtectionServiceApiClient::class );
		$challenge_manager = $container->get( FraudProtectionChallengeManager::class );
		$session_manager   = $container->get( SessionClearanceManager::class );

		// Get schema controller and create schema instance.
		$schema_controller = $container->get( \Automattic\WooCommerce\StoreApi\SchemaController::class );
		$extend_schema     = $container->get( \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class );
		$schema            = new \Automattic\WooCommerce\StoreApi\Schemas\V1\FraudProtectionOtpSchema( $extend_schema, $schema_controller );

		// Create route instances.
		$request_route = new \Automattic\WooCommerce\StoreApi\Routes\V1\FraudProtectionChallengeRequest(
			$schema_controller,
			$schema,
			$api_client,
			$challenge_manager,
			$session_manager
		);

		$verify_route = new \Automattic\WooCommerce\StoreApi\Routes\V1\FraudProtectionChallengeVerify(
			$schema_controller,
			$schema,
			$api_client,
			$challenge_manager,
			$session_manager
		);

		$retry_route = new \Automattic\WooCommerce\StoreApi\Routes\V1\FraudProtectionChallengeRetry(
			$schema_controller,
			$schema,
			$challenge_manager
		);

		// Register routes with WordPress REST API under Store API namespace.
		$namespace = 'wc/store/v1';

		register_rest_route(
			$namespace,
			$request_route->get_path(),
			$request_route->get_args()
		);

		register_rest_route(
			$namespace,
			$verify_route->get_path(),
			$verify_route->get_args()
		);

		register_rest_route(
			$namespace,
			$retry_route->get_path(),
			$retry_route->get_args()
		);
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

	public function update_session( $event_name, $session_data ) {
		$container = wc_get_container();
		$api_client = $container->get( FraudProtectionServiceApiClient::class );
		$decision = $api_client->track_session_event( $event_name, $session_data );
		switch ( $decision ) {
			case 'allow':
				$this->session_manager->allow_session();
				break;
			case 'block':
				$this->session_manager->block_session();
				break;
			case 'challenge':
				$this->session_manager->challenge_session();
				break;
			default: // ! Important: Unknown decision, just allow the session
				$this->session_manager->allow_session();
				break;
		};
	}

	/**
	 * Track cart item added event.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id Product ID.
	 * @param int    $quantity Quantity.
	 * @param int    $variation_id Variation ID.
	 * @param array  $variation Variation data.
	 * @param array  $cart_item_data Cart item data.
	 * @return void
	 */
	public function handle_track_cart_item_added( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		$session_data = $this->build_cart_event_session_data( 'item_added', $product_id, $quantity, $variation_id );
		$this->update_session( 'cart_item_added', $session_data );
	}

	/**
	 * Track cart item quantity updated event.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity New quantity.
	 * @param int    $old_quantity Old quantity.
	 * @param object $cart Cart object.
	 * @return void
	 */
	public function handle_track_cart_item_updated( $cart_item_key, $quantity, $old_quantity, $cart ) {
		// TODO: some debouncing logic is needed because it's called twice for a single update event
		$cart_item = isset( $cart->cart_contents[ $cart_item_key ] ) ? $cart->cart_contents[ $cart_item_key ] : null;

		if ( ! $cart_item ) {
			return;
		}

		$product_id   = isset( $cart_item['product_id'] ) ? $cart_item['product_id'] : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;

		$session_data = $this->build_cart_event_session_data( 'item_updated', $product_id, $quantity, $variation_id );
		$session_data['old_quantity'] = $old_quantity;
		$this->update_session( 'cart_item_updated', $session_data );
	}

	/**
	 * Track cart item removed event.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param object $cart Cart object.
	 * @return void
	 */
	public function handle_track_cart_item_removed( $cart_item_key, $cart ) {
		$cart_item = isset( $cart->removed_cart_contents[ $cart_item_key ] ) ? $cart->removed_cart_contents[ $cart_item_key ] : null;

		if ( ! $cart_item ) {
			return;
		}

		$product_id   = isset( $cart_item['product_id'] ) ? $cart_item['product_id'] : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;
		$quantity     = isset( $cart_item['quantity'] ) ? $cart_item['quantity'] : 0;

		$session_data = $this->build_cart_event_session_data( 'item_removed', $product_id, $quantity, $variation_id );
		$this->update_session( 'cart_item_removed', $session_data );
	}

	/**
	 * Track cart item restored event.
	 *
	 * @internal
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param object $cart Cart object.
	 * @return void
	 */
	public function handle_track_cart_item_restored( $cart_item_key, $cart ) {
		$cart_item = isset( $cart->cart_contents[ $cart_item_key ] ) ? $cart->cart_contents[ $cart_item_key ] : null;

		if ( ! $cart_item ) {
			return;
		}

		$product_id   = isset( $cart_item['product_id'] ) ? $cart_item['product_id'] : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0;
		$quantity     = isset( $cart_item['quantity'] ) ? $cart_item['quantity'] : 0;

		$session_data = $this->build_cart_event_session_data( 'item_restored', $product_id, $quantity, $variation_id );
		$this->update_session( 'cart_item_restored', $session_data );
	}

	/**
	 * Build session data for cart events.
	 *
	 * @param string $action Action type (item_added, item_updated, item_removed, item_restored).
	 * @param int    $product_id Product ID.
	 * @param int    $quantity Quantity.
	 * @param int    $variation_id Variation ID.
	 * @return array Session data.
	 */
	private function build_cart_event_session_data( $action, $product_id, $quantity, $variation_id ) {
		$session_key = $this->get_session_key();

		return [
			'session_key'  => $session_key,
			'action'       => $action,
			'product_id'   => $product_id,
			'quantity'     => $quantity,
			'variation_id' => $variation_id,
			'cart_total'   => WC()->cart ? WC()->cart->get_cart_contents_count() : null, // TODO: fix totals are not updated yet
			'email'        => $this->get_user_email(),
		];
	}

	/**
	 * Get session key for current session.
	 *
	 * @return string Session key.
	 */
	private function get_session_key() {
		if ( isset( WC()->session ) && WC()->session instanceof \WC_Session ) {
			$customer_id = WC()->session->get_customer_id();
			if ( $customer_id ) {
				return $customer_id;
			}
		}

		if ( function_exists( 'wp_get_session_token' ) ) {
			$token = wp_get_session_token();
			if ( $token ) {
				return 'guest-' . $token;
			}
		}

		return 'no-session';
	}

	/**
	 * Get user email if available.
	 *
	 * @return string|null User email or null.
	 */
	private function get_user_email() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			return $user->user_email;
		}

		if ( isset( WC()->session ) && WC()->session instanceof \WC_Session ) {
			$customer_data = WC()->session->get( 'customer' );
			if ( is_array( $customer_data ) && ! empty( $customer_data['email'] ) ) {
				return $customer_data['email'];
			}
		}

		return null;
	}

	/**
	 * Enqueue frontend assets for fraud protection modal.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_enqueue_frontend_assets(): void {
		// Only load on cart and checkout pages.
		if ( ! is_checkout() && ! is_cart() && ! is_product() ) {
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

		// Get user email if available.
		$user_email = '';
		if ( is_user_logged_in() ) {
			$user       = wp_get_current_user();
			$user_email = $user->user_email;
		} elseif ( isset( WC()->session ) && WC()->session instanceof \WC_Session ) {
			$customer_data = WC()->session->get( 'customer' );
			if ( is_array( $customer_data ) && ! empty( $customer_data['email'] ) ) {
				$user_email = $customer_data['email'];
			}
		}

		// Pass data to JavaScript.
		wp_localize_script(
			'wc-fraud-protection-modal',
			'wcFraudProtection',
			array(
				'restUrl'       => rest_url( 'wc/store/v1/fraud-protection/challenge' ),
				'storeNonce'    => wp_create_nonce( 'wc_store_api' ),
				'sessionStatus' => $this->session_manager->get_session_status(),
				'userEmail'     => $user_email,
				'shopUrl'       => get_permalink( wc_get_page_id( 'shop' ) ),
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
		// Only check WooCommerce and Store API endpoints.
		$route = $request->get_route();
		if ( strpos( $route, '/wc/' ) === false && strpos( $route, '/wc-' ) === false ) {
			return $result;
		}

		// Allow the Store API challenge endpoints themselves.
		if ( strpos( $route, '/store/v1/fraud-protection/challenge' ) !== false ) {
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

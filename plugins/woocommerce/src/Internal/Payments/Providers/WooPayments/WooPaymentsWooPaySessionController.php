<?php
/**
 * WooPaymentsWooPaySessionController class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Connection\Rest_Authentication;
use Automattic\WooCommerce\Internal\Payments\NativePaymentsRuntimeArbiter;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Native WooPay session REST and AJAX callbacks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsWooPaySessionController implements RegisterHooksInterface {

	private const NAMESPACE = 'payments/woopay';

	private const CLASSIC_WOOPAY_SCRIPT_HANDLE = 'wc-woopayments-woopay';

	private const CLASSIC_WOOPAY_STYLE_HANDLE = 'wc-woopayments-woopay';

	/**
	 * Runtime owner arbiter.
	 *
	 * @var NativePaymentsRuntimeArbiter
	 */
	private NativePaymentsRuntimeArbiter $arbiter;

	/**
	 * WooPay session service.
	 *
	 * @var WooPaymentsWooPaySessionService
	 */
	private WooPaymentsWooPaySessionService $session_service;

	/**
	 * Whether WooPay express checkout buttons have already rendered in this request.
	 *
	 * @var bool
	 */
	private bool $has_rendered_express_checkout_buttons = false;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param NativePaymentsRuntimeArbiter    $arbiter         Runtime owner arbiter.
	 * @param WooPaymentsWooPaySessionService $session_service WooPay session service.
	 */
	final public function init( NativePaymentsRuntimeArbiter $arbiter, WooPaymentsWooPaySessionService $session_service ): void {
		$this->arbiter         = $arbiter;
		$this->session_service = $session_service;
	}

	/**
	 * Register WooPay REST and AJAX hooks.
	 */
	public function register() {
		if ( ! $this->arbiter->should_native_register() || ! $this->session_service->is_woopay_enabled() ) {
			return;
		}

		if ( false === has_action( 'rest_api_init', array( $this, 'register_routes' ) ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		foreach ( $this->get_ajax_hooks() as $hook => $callback ) {
			if ( false === has_action( $hook, $callback ) ) {
				add_action( $hook, $callback );
			}
		}

		foreach ( $this->get_frontend_hooks() as $hook => $callback ) {
			if ( false === has_action( $hook, $callback ) ) {
				add_action( $hook, $callback );
			}
		}

		if ( false === has_filter( 'wcpay_metadata_from_order', array( $this, 'maybe_add_woopay_user_metadata' ) ) ) {
			add_filter( 'wcpay_metadata_from_order', array( $this, 'maybe_add_woopay_user_metadata' ), 10, 2 );
		}
	}

	/**
	 * Register WooPay session routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/session',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_session' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Check WooPay route permissions.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return bool|WP_Error
	 */
	public function check_permission( WP_REST_Request $request ) {
		if ( 'WooPay' !== $request->get_header( 'user_agent' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$signed = class_exists( Rest_Authentication::class )
			? Rest_Authentication::is_signed_with_blog_token()
			: false;

		/**
		 * Filters whether a WooPay session request is signed with the connected blog token.
		 *
		 * @param bool            $signed  Whether the request is signed.
		 * @param WP_REST_Request $request REST request.
		 *
		 * @since 11.0.0
		 */
		if ( ! (bool) apply_filters( 'wcpay_woopay_is_signed_with_blog_token', $signed, $request ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get WooPay session data.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_session( WP_REST_Request $request ) {
		try {
			$email = $request->get_param( 'email' );

			return new WP_REST_Response(
				$this->session_service->get_session_data( is_scalar( $email ) ? sanitize_email( (string) $email ) : null, $request ),
				200
			);
		} catch ( Throwable $exception ) {
			return new WP_Error( 'wcpay_server_error', __( 'Unable to get WooPay session data.', 'woocommerce' ), array( 'status' => 400 ) );
		}
	}

	/**
	 * Handle WooPay init AJAX.
	 */
	public function handle_init_woopay(): void {
		if ( ! $this->is_ajax_nonce_valid( 'wcpay_init_woopay_nonce' ) ) {
			wp_send_json( array( 'result' => 'failure' ), 403 );
		}

		wp_send_json( $this->get_init_woopay_response( wp_unslash( $_POST ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handle encrypted WooPay session AJAX.
	 */
	public function handle_get_woopay_session(): void {
		if ( ! $this->is_ajax_nonce_valid( 'woopay_session_nonce' ) ) {
			wp_send_json( array( 'result' => 'failure' ), 403 );
		}

		wp_send_json( $this->get_encrypted_session_response( wp_unslash( $_POST ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handle WooPay phone-number session AJAX.
	 */
	public function handle_set_woopay_phone_number(): void {
		if ( ! $this->is_ajax_nonce_valid( 'woopay_session_nonce' ) ) {
			wp_send_json( array( 'result' => 'failure' ), 403 );
		}

		wp_send_json( $this->get_phone_session_response( wp_unslash( $_POST ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handle WooPay request-signature AJAX.
	 */
	public function handle_get_woopay_signature(): void {
		if ( ! $this->is_ajax_nonce_valid( 'woopay_signature_nonce' ) ) {
			wp_send_json_error( array( 'result' => 'failure' ), 403 );
		}

		wp_send_json_success( $this->get_signature_response( wp_unslash( $_POST ) ), 200 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handle encrypted minimum WooPay session AJAX.
	 */
	public function handle_get_woopay_minimum_session_data(): void {
		if ( ! $this->is_ajax_nonce_valid( 'woopay_session_nonce' ) ) {
			wp_send_json( array( 'result' => 'failure' ), 403 );
		}

		wp_send_json( $this->get_minimum_session_response( wp_unslash( $_POST ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Handle admin WooPay appearance persistence AJAX.
	 */
	public function handle_set_admin_woopay_appearance(): void {
		if ( ! $this->is_ajax_nonce_valid( 'wcpay_admin_woopay_appearance_nonce' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'result' => 'failure' ), 403 );
		}

		$request = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $this->is_valid_appearance_request( $request ) ) {
			wp_send_json_error( array( 'result' => 'failure' ), 400 );
		}

		$this->get_admin_appearance_response( $request );

		wp_send_json_success();
	}

	/**
	 * Handle shopper WooPay appearance persistence AJAX.
	 */
	public function handle_set_shopper_woopay_appearance(): void {
		if ( ! $this->is_ajax_nonce_valid( 'woopay_session_nonce' ) ) {
			wp_send_json_error( array( 'result' => 'failure' ), 403 );
		}

		$request = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $this->is_valid_appearance_request( $request ) ) {
			wp_send_json_error( array( 'result' => 'failure' ), 400 );
		}

		wp_send_json_success( $this->get_shopper_appearance_response( $request ) );
	}

	/**
	 * Enqueue Core-owned WooPay frontend assets on supported shopper surfaces.
	 */
	public function enqueue_frontend_assets(): void {
		if ( ! $this->is_supported_frontend_surface() ) {
			return;
		}

		$context = $this->get_current_button_context();
		if (
			! $this->session_service->should_show_woopay_button( $context ) &&
			! $this->session_service->should_load_woopay_save_user_assets( $context )
		) {
			return;
		}

		$this->register_classic_woopay_assets();
		wp_localize_script( self::CLASSIC_WOOPAY_SCRIPT_HANDLE, 'wcpay_core_woopay_config', $this->get_classic_woopay_config( $context ) );
		wp_enqueue_style( self::CLASSIC_WOOPAY_STYLE_HANDLE );
		wp_enqueue_script( self::CLASSIC_WOOPAY_SCRIPT_HANDLE );
	}

	/**
	 * Display the WooPay express checkout placeholder.
	 */
	public function display_express_checkout_buttons(): void {
		if ( $this->has_rendered_express_checkout_buttons || ! $this->is_supported_frontend_surface() ) {
			return;
		}

		$context = $this->get_current_button_context();
		if ( ! $this->session_service->should_show_woopay_button( $context ) ) {
			return;
		}

		$config   = $this->session_service->get_woopay_frontend_config( $context );
		$settings = is_array( $config['woopayButton'] ?? null ) ? $config['woopayButton'] : array();
		$type     = isset( $settings['type'] ) && is_scalar( $settings['type'] ) ? (string) $settings['type'] : 'default';
		$theme    = isset( $settings['theme'] ) && is_scalar( $settings['theme'] ) ? (string) $settings['theme'] : 'dark';
		$height   = isset( $settings['height'] ) && is_scalar( $settings['height'] ) ? (string) $settings['height'] : '48';
		$radius   = isset( $settings['radius'] ) && is_scalar( $settings['radius'] ) ? (string) $settings['radius'] : '4';

		$this->has_rendered_express_checkout_buttons = true;

		echo '<div class="wcpay-express-checkout-wrapper">';
		echo '<div id="wcpay-woopay-button" data-product_page="' . esc_attr( 'product' === $context ? '1' : '0' ) . '">';
		echo '<div class="woopay-express-button is-placeholder" aria-label="' . esc_attr__( 'WooPay', 'woocommerce' ) . '" data-type="' . esc_attr( $type ) . '" data-theme="' . esc_attr( $theme ) . '" data-size="' . esc_attr( (string) ( $settings['size'] ?? 'default' ) ) . '" style="height: ' . esc_attr( $height ) . 'px; border-radius: ' . esc_attr( $radius ) . 'px"></div>';
		echo '</div>';
		echo '<p id="wcpay-express-checkout-button-separator">&mdash; ' . esc_html__( 'OR', 'woocommerce' ) . ' &mdash;</p>';
		echo '</div>';
	}

	/**
	 * Handle WooPay product add-to-cart AJAX.
	 */
	public function handle_add_to_cart(): void {
		check_ajax_referer( 'wcpay-add-to-cart', 'security' );

		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		if ( function_exists( 'WC' ) && WC() ) {
			WC()->shipping()->reset_shipping();
		}

		$request    = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product_id = isset( $request['product_id'] ) ? absint( $request['product_id'] ) : 0;
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json(
				array(
					'error' => array(
						'code'    => 'invalid_product_id',
						'message' => __( 'Invalid product ID.', 'woocommerce' ),
					),
				),
				404
			);
		}

		$quantity = isset( $request['quantity'] ) ? (int) wc_stock_amount( $request['quantity'] ) : 1;
		$quantity = max( 1, $quantity );

		/**
		 * Filters whether WooCommerce should add the WooPay product to the cart.
		 *
		 * @param bool $passed     Whether validation passed.
		 * @param int  $product_id Product ID.
		 * @param int  $quantity   Quantity.
		 *
		 * @since 11.0.0
		 */
		if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity ) ) {
			wp_send_json(
				array(
					'error'  => true,
					'submit' => true,
				),
				400
			);
		}

		if ( function_exists( 'WC' ) && WC() && WC()->cart ) {
			WC()->cart->empty_cart();
			$variation_id = isset( $request['variation_id'] ) ? absint( $request['variation_id'] ) : 0;
			$attributes   = $this->get_add_to_cart_attributes( $request );
			$added        = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes );
			if ( ! $added ) {
				wp_send_json(
					array(
						'error' => array(
							'code'    => 'add_to_cart_failed',
							'message' => __( 'Unable to add this product to the cart.', 'woocommerce' ),
						),
					),
					400
				);
			}

			WC()->cart->calculate_totals();
		}

		wp_send_json(
			array(
				'result' => 'success',
				'cart'   => array(
					'items_count' => function_exists( 'WC' ) && WC() && WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
					'total'       => function_exists( 'WC' ) && WC() && WC()->cart ? WC()->cart->get_total( '' ) : '',
				),
			)
		);
	}

	/**
	 * Get product variation attributes from a WooPay add-to-cart request.
	 *
	 * @param array<string,mixed> $request Unsigned add-to-cart request data.
	 * @return array<string,mixed>
	 */
	private function get_add_to_cart_attributes( array $request ): array {
		$attributes = array();
		if ( isset( $request['attributes'] ) && is_array( $request['attributes'] ) ) {
			$clean_attributes = wc_clean( $request['attributes'] );
			$attributes       = is_array( $clean_attributes ) ? $clean_attributes : array();
		}

		foreach ( $request as $key => $value ) {
			if ( 0 !== strpos( (string) $key, 'attribute_' ) || is_array( $value ) ) {
				continue;
			}

			$attributes[ sanitize_key( $key ) ] = wc_clean( $value );
		}

		return $attributes;
	}

	/**
	 * Handle WooPay frontend error notices.
	 */
	public function handle_show_error_notice(): void {
		$is_nonce_valid = check_ajax_referer( 'woopay_button_nonce', false, false );

		if ( ! $is_nonce_valid ) {
			wp_send_json_error(
				__( 'You aren’t authorized to do that.', 'woocommerce' ),
				403
			);
		}

		$request = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$message = isset( $request['message'] ) && is_scalar( $request['message'] )
			? sanitize_text_field( (string) $request['message'] )
			: __( 'There was a problem processing the payment.', 'woocommerce' );

		wc_add_notice( $message, 'error' );
		wp_send_json_success(
			array(
				'notice' => wc_print_notices( true ),
			)
		);
	}

	/**
	 * Clear WooPay session data when WooCommerce completes payment.
	 */
	public function handle_woocommerce_payment_complete(): void {
		$this->session_service->clear_woopay_session_data();
	}

	/**
	 * Add WooPay save-user session data to order metadata.
	 *
	 * @param array<string,mixed> $metadata Metadata.
	 * @param \WC_Order           $order    Order object.
	 * @return array<string,mixed>
	 */
	public function maybe_add_woopay_user_metadata( array $metadata, \WC_Order $order ): array {
		return $this->session_service->maybe_add_woopay_user_metadata( $metadata, $order );
	}

	/**
	 * Build the WooPay init response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,mixed>
	 */
	public function get_init_woopay_response( array $request ): array {
		return $this->session_service->init_woopay_session( $request );
	}

	/**
	 * Build the encrypted WooPay session response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,mixed>
	 */
	public function get_encrypted_session_response( array $request ): array {
		return $this->session_service->get_encrypted_session_data( $request );
	}

	/**
	 * Build the phone-session response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,string>
	 */
	public function get_phone_session_response( array $request ): array {
		if ( ! empty( $request['empty'] ) ) {
			$this->session_service->clear_woopay_session_data();
		} else {
			$this->session_service->set_woopay_phone_session_data( $request );
		}

		return array( 'result' => 'success' );
	}

	/**
	 * Build the WooPay signature response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,string>
	 */
	public function get_signature_response( array $request ): array {
		unset( $request );

		return array( 'signature' => $this->session_service->get_woopay_request_signature() );
	}

	/**
	 * Build the encrypted minimum session response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,mixed>
	 */
	public function get_minimum_session_response( array $request ): array {
		unset( $request );

		return $this->session_service->get_encrypted_minimum_session_data();
	}

	/**
	 * Build the admin appearance response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,string>
	 */
	public function get_admin_appearance_response( array $request ): array {
		$payload = $this->get_appearance_payload( $request );

		$this->session_service->save_woopay_appearance( $payload['appearance'], $payload['font_rules'] );

		return array( 'result' => 'success' );
	}

	/**
	 * Build the shopper appearance response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,bool>
	 */
	public function get_shopper_appearance_response( array $request ): array {
		$payload = $this->get_appearance_payload( $request );

		return array(
			'stored' => $this->session_service->maybe_save_woopay_appearance( $payload['appearance'], $payload['font_rules'] ),
		);
	}

	/**
	 * Build the appearance response.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array<string,string>
	 */
	public function get_appearance_response( array $request ): array {
		return $this->get_admin_appearance_response( $request );
	}

	/**
	 * Check an AJAX nonce without dying.
	 *
	 * @param string $action Nonce action.
	 * @return bool
	 */
	private function is_ajax_nonce_valid( string $action ): bool {
		return (bool) check_ajax_referer( $action, false, false );
	}

	/**
	 * Check whether an appearance request carries a valid WooPay appearance payload.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return bool
	 */
	private function is_valid_appearance_request( array $request ): bool {
		return isset( $request['appearance'] ) &&
			is_array( $request['appearance'] ) &&
			$this->session_service->validate_appearance_schema( $request['appearance'] );
	}

	/**
	 * Get appearance payload data from a request.
	 *
	 * @param array<string,mixed> $request Request data.
	 * @return array{appearance:array<string,mixed>,font_rules:array<int,array<string,string>>}
	 */
	private function get_appearance_payload( array $request ): array {
		$appearance = isset( $request['appearance'] ) && is_array( $request['appearance'] )
			? $request['appearance']
			: array();

		$font_rules = array();
		if ( isset( $request['font_rules'] ) ) {
			$raw_font_rules = $request['font_rules'];
			if ( is_string( $raw_font_rules ) ) {
				$decoded        = json_decode( $raw_font_rules, true );
				$raw_font_rules = is_array( $decoded ) ? $decoded : array();
			}

			if ( is_array( $raw_font_rules ) ) {
				$font_rules = $this->session_service->sanitize_woopay_font_rules( $raw_font_rules );
			}
		}

		return array(
			'appearance' => $appearance,
			'font_rules' => $font_rules,
		);
	}

	/**
	 * Get WooPay AJAX hooks and callbacks.
	 *
	 * @return array<string,callable>
	 */
	private function get_ajax_hooks(): array {
		return array(
			'wc_ajax_wcpay_init_woopay'                   => array( $this, 'handle_init_woopay' ),
			'wc_ajax_wcpay_get_woopay_session'            => array( $this, 'handle_get_woopay_session' ),
			'wc_ajax_wcpay_set_woopay_phone_number'       => array( $this, 'handle_set_woopay_phone_number' ),
			'wc_ajax_wcpay_get_woopay_signature'          => array( $this, 'handle_get_woopay_signature' ),
			'wc_ajax_wcpay_get_woopay_minimum_session_data' => array( $this, 'handle_get_woopay_minimum_session_data' ),
			'wp_ajax_wcpay_admin_set_woopay_appearance'   => array( $this, 'handle_set_admin_woopay_appearance' ),
			'wc_ajax_wcpay_shopper_set_woopay_appearance' => array( $this, 'handle_set_shopper_woopay_appearance' ),
			'wc_ajax_wcpay_add_to_cart'                   => array( $this, 'handle_add_to_cart' ),
			'wp_ajax_woopay_express_checkout_button_show_error_notice' => array( $this, 'handle_show_error_notice' ),
			'wp_ajax_nopriv_woopay_express_checkout_button_show_error_notice' => array( $this, 'handle_show_error_notice' ),
		);
	}

	/**
	 * Get WooPay frontend hooks and callbacks.
	 *
	 * @return array<string,callable>
	 */
	private function get_frontend_hooks(): array {
		return array(
			'wp_enqueue_scripts'                           => array( $this, 'enqueue_frontend_assets' ),
			'woocommerce_checkout_before_customer_details' => array( $this, 'display_express_checkout_buttons' ),
			'woocommerce_proceed_to_checkout'              => array( $this, 'display_express_checkout_buttons' ),
			'woocommerce_after_add_to_cart_form'           => array( $this, 'display_express_checkout_buttons' ),
			'woocommerce_pay_order_before_payment'         => array( $this, 'display_express_checkout_buttons' ),
			'woocommerce_payment_complete'                 => array( $this, 'handle_woocommerce_payment_complete' ),
		);
	}

	/**
	 * Register classic WooPay assets when WooCommerce has not registered them yet.
	 */
	private function register_classic_woopay_assets(): void {
		if ( ! wp_script_is( self::CLASSIC_WOOPAY_SCRIPT_HANDLE, 'registered' ) ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_script(
				self::CLASSIC_WOOPAY_SCRIPT_HANDLE,
				WC()->plugin_url() . '/assets/js/frontend/woopayments-woopay' . $suffix . '.js',
				array( 'jquery' ),
				WC_VERSION,
				true
			);
		}

		if ( ! wp_style_is( self::CLASSIC_WOOPAY_STYLE_HANDLE, 'registered' ) ) {
			wp_register_style(
				self::CLASSIC_WOOPAY_STYLE_HANDLE,
				WC()->plugin_url() . '/assets/css/woopayments-woopay.css',
				array(),
				WC_VERSION
			);
			wp_style_add_data( self::CLASSIC_WOOPAY_STYLE_HANDLE, 'rtl', 'replace' );
		}
	}

	/**
	 * Get localized classic WooPay config.
	 *
	 * @param string $context WooPay button context.
	 * @return array<string,mixed>
	 */
	private function get_classic_woopay_config( string $context ): array {
		return array_merge(
			array(
				'wcAjaxUrl'                => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'confirmationErrorMessage' => __( 'There was a problem processing the payment. Please try again.', 'woocommerce' ),
			),
			$this->session_service->get_woopay_frontend_config( $context ),
			$this->session_service->get_save_user_checkout_data()
		);
	}

	/**
	 * Tell whether the current request is a supported shopper frontend surface.
	 *
	 * @return bool
	 */
	private function is_supported_frontend_surface(): bool {
		if ( $this->is_block_cart_or_checkout_surface() ) {
			return false;
		}

		return ( function_exists( 'is_checkout' ) && is_checkout() ) ||
			( function_exists( 'is_cart' ) && is_cart() ) ||
			( function_exists( 'is_product' ) && is_product() );
	}

	/**
	 * Tell whether the current request renders a Blocks cart or checkout page.
	 *
	 * @return bool
	 */
	private function is_block_cart_or_checkout_surface(): bool {
		$post_id = function_exists( 'get_queried_object_id' ) ? get_queried_object_id() : 0;
		$post    = $post_id ? get_post( $post_id ) : null;

		if ( ! $post instanceof \WP_Post ) {
			$post = get_queried_object();
		}

		if ( ! $post instanceof \WP_Post ) {
			$post = get_post();
		}

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		return has_block( 'woocommerce/cart', $post ) || has_block( 'woocommerce/checkout', $post );
	}

	/**
	 * Get the current WooPay button context.
	 *
	 * @return string
	 */
	private function get_current_button_context(): string {
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'product';
		}

		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}

		return 'checkout';
	}
}

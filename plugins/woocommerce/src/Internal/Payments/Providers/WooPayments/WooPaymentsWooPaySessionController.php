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
		);
	}
}

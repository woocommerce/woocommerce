<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionServiceApiClient;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionChallengeManager;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

/**
 * FraudProtectionOtpVerify class.
 *
 * Handles OTP verification endpoint.
 *
 * @since 10.4.0
 */
class FraudProtectionOtpVerify extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'fraud-protection-otp-verify';

	/**
	 * API client instance.
	 *
	 * @var FraudProtectionServiceApiClient
	 */
	private $api_client;

	/**
	 * Challenge manager instance.
	 *
	 * @var FraudProtectionChallengeManager
	 */
	private $challenge_manager;

	/**
	 * Session manager instance.
	 *
	 * @var SessionClearanceManager
	 */
	private $session_manager;

	/**
	 * Constructor.
	 *
	 * @param \Automattic\WooCommerce\StoreApi\SchemaController $schema_controller Schema Controller instance.
	 * @param \Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema $schema Schema class for this route.
	 * @param FraudProtectionServiceApiClient $api_client API client instance.
	 * @param FraudProtectionChallengeManager $challenge_manager Challenge manager instance.
	 * @param SessionClearanceManager $session_manager Session manager instance.
	 */
	public function __construct( $schema_controller, $schema, $api_client, $challenge_manager, $session_manager ) {
		parent::__construct( $schema_controller, $schema );
		$this->api_client        = $api_client;
		$this->challenge_manager = $challenge_manager;
		$this->session_manager   = $session_manager;
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/fraud-protection/otp/verify';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_response' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'challenge_id' => [
						'description' => __( 'Unique identifier for the OTP challenge.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
					'otp_code'     => [
						'description'       => __( 'Six-digit OTP code.', 'woocommerce' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => function( $param ) {
							return preg_match( '/^\d{6}$/', $param );
						},
					],
				],
			],
		];
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$challenge_id = $request['challenge_id'];
		$otp_code     = $request['otp_code'];

		// Verify OTP.
		$result = $this->challenge_manager->verify_otp( $challenge_id, $otp_code );

		// Handle verification errors.
		if ( is_wp_error( $result ) ) {
			$error_code = $result->get_error_code();
			$error_msg  = $result->get_error_message();

			// Map error codes to HTTP status codes.
			$status_map = [
				'challenge_not_found' => 404,
				'otp_expired'         => 400,
				'max_attempts'        => 429,
				'otp_invalid'         => 400,
			];

			$http_status = isset( $status_map[ $error_code ] ) ? $status_map[ $error_code ] : 400;

			throw new RouteException( $error_code, $error_msg, $http_status );
		}

		// Get challenge data for email difference tracking.
		$challenge = $this->challenge_manager->get_challenge( $challenge_id );
		if ( ! $challenge ) {
			throw new RouteException(
				'challenge_not_found',
				__( 'Challenge not found.', 'woocommerce' ),
				404
			);
		}

		// Track verification with WPCOM API.
		$session_key     = $this->get_session_key();
		$original_email  = $this->get_original_email();
		$challenge_email = $challenge['email'];

		$session_data = [
			'session_key'         => $session_key,
			'email'               => $original_email ? $original_email : $challenge_email,
			'challenge_email'     => $challenge_email,
			'challenge_id'        => $challenge_id,
			'verification_status' => 'success',
		];

		// Log email difference if detected.
		if ( $original_email && $original_email !== $challenge_email ) {
			$this->log_info(
				'Email difference detected during OTP verification',
				[
					'original_email'  => $original_email,
					'challenge_email' => $challenge_email,
					'challenge_id'    => $challenge_id,
				]
			);
		}

		$decision = $this->api_client->track_session_event( 'challenge_verified', $session_data );

		// Handle API decision.
		if ( 'allow' === $decision ) {
			$this->session_manager->allow_session();
			$this->challenge_manager->delete_challenge( $challenge_id );

			return rest_ensure_response( [
				'success'        => true,
				'session_status' => 'allowed',
				'message'        => __( 'Verification successful.', 'woocommerce' ),
			] );
		}

		if ( 'block' === $decision ) {
			$this->session_manager->block_session();
			$this->challenge_manager->delete_challenge( $challenge_id );

			throw new RouteException(
				'fraud_protection_session_blocked',
				__( 'Your session has been blocked due to security concerns.', 'woocommerce' ),
				403
			);
		}

		// Challenge decision - should not happen after successful verification, but handle gracefully.
		throw new RouteException(
			'unexpected_api_response',
			__( 'Unexpected response from verification service.', 'woocommerce' ),
			500
		);
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
	 * Get original email from session (if available).
	 *
	 * @return string|null Original email or null.
	 */
	private function get_original_email() {
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
	 * Log an info message.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context.
	 */
	private function log_info( $message, $context = [] ) {
		$logger = wc_get_logger();
		$logger->info( $message, array_merge( [ 'source' => 'woo-fraud-protection-otp' ], $context ) );
	}
}

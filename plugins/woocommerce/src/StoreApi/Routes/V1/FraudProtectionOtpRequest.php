<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionServiceApiClient;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionChallengeManager;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;

/**
 * FraudProtectionOtpRequest class.
 *
 * Handles OTP challenge request endpoint.
 *
 * @since 10.4.0
 */
class FraudProtectionOtpRequest extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'fraud-protection-otp-request';

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
		return '/fraud-protection/otp/request';
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
					'email' => [
						'description'       => __( 'Email address for OTP challenge.', 'woocommerce' ),
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_email( $param );
						},
						'sanitize_callback' => 'sanitize_email',
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
		$email = $request['email'];

		// Get session key for API call.
		$session_key  = $this->get_session_key();
		$session_data = [
			'session_key' => $session_key,
			'email'       => $email,
		];

		// Call WPCOM API to get decision.
		$decision = $this->api_client->track_session_event( 'challenge_requested', $session_data );

		// Handle decision outcomes.
		if ( 'allow' === $decision ) {
			$this->session_manager->allow_session();
			return rest_ensure_response( [
				'success'        => true,
				'session_status' => 'allowed',
				'message'        => __( 'Session verified successfully.', 'woocommerce' ),
			] );
		}

		if ( 'block' === $decision ) {
			$this->session_manager->block_session();
			throw new RouteException(
				'fraud_protection_session_blocked',
				__( 'Your session has been blocked due to security concerns.', 'woocommerce' ),
				403
			);
		}

		// Decision is "challenge" - generate and send OTP.
		$challenge = $this->challenge_manager->create_challenge( $session_key, $email );

		// Send OTP email.
		$email_sent = $this->send_otp_email( $email, $challenge );

		// If email fails, allow session with logging (fail-open pattern).
		if ( ! $email_sent ) {
			$this->session_manager->allow_session();
			$this->log_error( 'Failed to send OTP email, allowing session (fail-open)' );
			return rest_ensure_response( [
				'success'        => true,
				'session_status' => 'allowed',
				'message'        => __( 'Session verified successfully.', 'woocommerce' ),
			] );
		}

		// Calculate time until expiration.
		$expires_in = $challenge['expires_at'] - time();

		return rest_ensure_response( [
			'success'            => true,
			'challenge_id'       => $challenge['challenge_id'],
			'expires_in'         => max( 0, $expires_in ),
			'attempts_remaining' => 3 - $challenge['attempts'],
			'message'            => __( 'Verification code sent to your email.', 'woocommerce' ),
		] );
	}

	/**
	 * Send OTP email.
	 *
	 * @param string $email Email address.
	 * @param array  $challenge Challenge data.
	 * @return bool Whether email was sent successfully.
	 */
	private function send_otp_email( $email, $challenge ) {
		try {
			$mailer       = WC()->mailer();
			$emails       = $mailer->get_emails();
			$otp_email    = isset( $emails['WC_Email_Fraud_Protection_Otp'] ) ? $emails['WC_Email_Fraud_Protection_Otp'] : null;

			if ( ! $otp_email ) {
				$this->log_error( 'OTP email class not found' );
				return false;
			}

			$otp_email->trigger(
				$email,
				$challenge['otp_code'],
				$challenge['challenge_id'],
				60 // expiration minutes
			);

			$this->log_info( 'OTP email sent successfully', [ 'challenge_id' => $challenge['challenge_id'] ] );
			return true;
		} catch ( \Exception $e ) {
			$this->log_error( 'Exception sending OTP email: ' . $e->getMessage() );
			return false;
		}
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

		// Fallback for guests without session.
		if ( function_exists( 'wp_get_session_token' ) ) {
			$token = wp_get_session_token();
			if ( $token ) {
				return 'guest-' . $token;
			}
		}

		return 'no-session';
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

	/**
	 * Log an error message.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context.
	 */
	private function log_error( $message, $context = [] ) {
		$logger = wc_get_logger();
		$logger->error( $message, array_merge( [ 'source' => 'woo-fraud-protection-otp' ], $context ) );
	}
}

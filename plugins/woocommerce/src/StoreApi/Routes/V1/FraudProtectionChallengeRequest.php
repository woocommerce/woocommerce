<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionServiceApiClient;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionChallengeManager;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

/**
 * FraudProtectionChallengeRequest class.
 *
 * Handles fraud protection challenge request endpoint.
 * Generic endpoint that can be used by any challenge method (OTP, SMS, etc).
 *
 * @since 10.4.0
 */
class FraudProtectionChallengeRequest extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'fraud-protection-challenge-request';

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
	 * Session data collector instance.
	 *
	 * @var SessionDataCollector
	 */
	private $data_collector;

	/**
	 * Constructor.
	 *
	 * @param \Automattic\WooCommerce\StoreApi\SchemaController          $schema_controller Schema Controller instance.
	 * @param \Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema $schema            Schema class for this route.
	 * @param FraudProtectionServiceApiClient                            $api_client        API client instance.
	 * @param FraudProtectionChallengeManager                            $challenge_manager Challenge manager instance.
	 * @param SessionClearanceManager                                    $session_manager   Session manager instance.
	 * @param SessionDataCollector                                       $data_collector    Session data collector instance.
	 */
	public function __construct( $schema_controller, $schema, $api_client, $challenge_manager, $session_manager, $data_collector ) {
		parent::__construct( $schema_controller, $schema );
		$this->api_client        = $api_client;
		$this->challenge_manager = $challenge_manager;
		$this->session_manager   = $session_manager;
		$this->data_collector    = $data_collector;
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/fraud-protection/challenge/request';
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
						'description'       => __( 'Email address for challenge delivery.', 'woocommerce' ),
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

		// Collect full session data using SessionDataCollector.
		$session_data          = $this->data_collector->collect();
		$session_data['email'] = $email; // Override with form input.

		// Call WPCOM API to get verdict.
		$verdict = $this->api_client->track_session_event( 'challenge_requested', $session_data );

		// There is no need to trigger a challenge if the verdict is already blocked.
		if ( FraudProtectionServiceApiClient::DECISION_BLOCK === $verdict ) {
			$this->session_manager->block_session();
			throw new RouteException(
				'fraud_protection_session_blocked',
				__( 'Your session has been blocked due to security concerns.', 'woocommerce' ),
				403
			);
		}

		// Verdict is "challenge" - generate and send OTP.
		$challenge = $this->challenge_manager->create_challenge( $session_data['session_id'] ?? 'no-session', $email );

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
	 * Log an info message.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context.
	 */
	private function log_info( $message, $context = [] ) {
		$logger = wc_get_logger();
		$logger->info( $message, array_merge( [ 'source' => 'woo-fraud-protection' ], $context ) );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Message to log.
	 * @param array  $context Additional context.
	 */
	private function log_error( $message, $context = [] ) {
		$logger = wc_get_logger();
		$logger->error( $message, array_merge( [ 'source' => 'woo-fraud-protection' ], $context ) );
	}
}

<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionServiceApiClient;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionChallengeManager;
use Automattic\WooCommerce\Internal\FraudProtection\SessionClearanceManager;
use Automattic\WooCommerce\Internal\FraudProtection\SessionDataCollector;

/**
 * FraudProtectionChallengeVerify class.
 *
 * Handles fraud protection challenge verification endpoint.
 * Generic endpoint that can be used by any challenge method (OTP, SMS, etc).
 *
 * @since 10.4.0
 */
class FraudProtectionChallengeVerify extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'fraud-protection-challenge-verify';

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
		return '/fraud-protection/challenge/verify';
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
						'description' => __( 'Unique identifier for the challenge.', 'woocommerce' ),
						'type'        => 'string',
						'required'    => true,
					],
					'otp_code'     => [
						'description'       => __( 'Verification code (six-digit OTP for email challenges).', 'woocommerce' ),
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
	 * On successful OTP verification, tracks `challenge_succeeded` event.
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

		
		// Collect full session data using SessionDataCollector.
		$session_data    = $this->data_collector->collect();
		$challenge       = $result['challenge'];
		$original_email  = $session_data['email'];
		$challenge_email = $challenge['email'];

		// Override email with challenge email for API call.
		$session_data['email'] = $challenge_email;

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

		// Track successful verification with WPCOM API.
		$verdict = $this->api_client->track_session_event( 'challenge_verified', $session_data );

		// Handle API verdict.
		if ( FraudProtectionServiceApiClient::DECISION_ALLOW === $verdict ) {
			$this->session_manager->allow_session();
			$this->challenge_manager->delete_challenge( $challenge_id );

			return rest_ensure_response( [
				'success'        => true,
				'session_status' => 'allowed',
				'message'        => __( 'Verification successful.', 'woocommerce' ),
			] );
		}

		if ( FraudProtectionServiceApiClient::DECISION_BLOCK === $verdict ) {
			$this->session_manager->block_session();
			$this->challenge_manager->delete_challenge( $challenge_id );

			throw new RouteException(
				'fraud_protection_session_blocked',
				__( 'Your session has been blocked due to security concerns.', 'woocommerce' ),
				403
			);
		}

		// Challenge verdict - should not happen after successful verification, but handle gracefully.
		throw new RouteException(
			'unexpected_api_response',
			__( 'Unexpected response from verification service.', 'woocommerce' ),
			500
		);
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

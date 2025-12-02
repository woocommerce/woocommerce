<?php
namespace Automattic\WooCommerce\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\Internal\FraudProtection\FraudProtectionChallengeManager;

/**
 * FraudProtectionOtpResend class.
 *
 * Handles OTP resend endpoint.
 *
 * @since 10.4.0
 */
class FraudProtectionOtpResend extends AbstractRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'fraud-protection-otp-resend';

	/**
	 * Challenge manager instance.
	 *
	 * @var FraudProtectionChallengeManager
	 */
	private $challenge_manager;

	/**
	 * Constructor.
	 *
	 * @param \Automattic\WooCommerce\StoreApi\SchemaController $schema_controller Schema Controller instance.
	 * @param \Automattic\WooCommerce\StoreApi\Schemas\V1\AbstractSchema $schema Schema class for this route.
	 * @param FraudProtectionChallengeManager $challenge_manager Challenge manager instance.
	 */
	public function __construct( $schema_controller, $schema, $challenge_manager ) {
		parent::__construct( $schema_controller, $schema );
		$this->challenge_manager = $challenge_manager;
	}

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/fraud-protection/otp/resend';
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

		// Get existing challenge.
		$challenge = $this->challenge_manager->get_challenge( $challenge_id );

		if ( ! $challenge ) {
			throw new RouteException(
				'challenge_not_found',
				__( 'Challenge not found or expired.', 'woocommerce' ),
				404
			);
		}

		// Check if challenge is expired.
		if ( $this->challenge_manager->is_challenge_expired( $challenge_id ) ) {
			$this->challenge_manager->delete_challenge( $challenge_id );
			throw new RouteException(
				'otp_expired',
				__( 'This verification code has expired. Please request a new one.', 'woocommerce' ),
				400
			);
		}

		// Check max attempts.
		if ( $challenge['attempts'] >= 3 ) {
			$this->challenge_manager->delete_challenge( $challenge_id );
			throw new RouteException(
				'max_attempts',
				__( 'Maximum verification attempts exceeded. Please start over.', 'woocommerce' ),
				429
			);
		}

		// Increment attempts counter.
		$incremented = $this->challenge_manager->increment_attempts( $challenge_id );

		if ( ! $incremented ) {
			throw new RouteException(
				'challenge_not_found',
				__( 'Challenge not found or expired.', 'woocommerce' ),
				404
			);
		}

		// Send OTP email with existing code.
		$email_sent = $this->send_otp_email( $challenge['email'], $challenge );

		if ( ! $email_sent ) {
			$this->log_error( 'Failed to resend OTP email', [ 'challenge_id' => $challenge_id ] );
			throw new RouteException(
				'email_send_failed',
				__( 'Failed to send verification code. Please try again.', 'woocommerce' ),
				500
			);
		}

		// Get updated challenge to reflect new attempts count.
		$updated_challenge = $this->challenge_manager->get_challenge( $challenge_id );
		$attempts_remaining = 3 - $updated_challenge['attempts'];

		$this->log_info( 'OTP resent successfully', [ 'challenge_id' => $challenge_id, 'attempts' => $updated_challenge['attempts'] ] );

		return rest_ensure_response( [
			'success'            => true,
			'attempts_remaining' => $attempts_remaining,
			'cooldown_seconds'   => 30,
			'message'            => __( 'Verification code resent to your email.', 'woocommerce' ),
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
			$mailer    = WC()->mailer();
			$emails    = $mailer->get_emails();
			$otp_email = isset( $emails['WC_Email_Fraud_Protection_Otp'] ) ? $emails['WC_Email_Fraud_Protection_Otp'] : null;

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

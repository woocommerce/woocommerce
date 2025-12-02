<?php
/**
 * Class WC_Email_Fraud_Protection_Otp file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Fraud_Protection_Otp', false ) ) {

	/**
	 * Fraud Protection OTP Email.
	 *
	 * An email sent to customers containing a one-time password for fraud protection verification.
	 *
	 * @class       WC_Email_Fraud_Protection_Otp
	 * @version     10.4.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Fraud_Protection_Otp extends WC_Email {

		/**
		 * OTP code.
		 *
		 * @var string
		 */
		public $otp_code;

		/**
		 * Challenge ID.
		 *
		 * @var string
		 */
		public $challenge_id;

		/**
		 * User email address.
		 *
		 * @var string
		 */
		public $user_email;

		/**
		 * Expiration time in minutes.
		 *
		 * @var int
		 */
		public $expiration_minutes;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'fraud_protection_otp';
			$this->customer_email = true;
			$this->title          = __( 'Fraud Protection OTP', 'woocommerce' );
			$this->email_group    = 'fraud_protection';
			$this->description    = __( 'Send a one-time password to customers for fraud protection verification', 'woocommerce' );
			$this->template_html  = 'emails/fraud-protection-otp.php';
			$this->template_plain = 'emails/plain/fraud-protection-otp.php';
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @since 10.4.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Verification code for {site_title}', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since 10.4.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Verify your email address', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param string $email              Email address to send to.
		 * @param string $otp_code           6-digit OTP code.
		 * @param string $challenge_id       Challenge identifier.
		 * @param int    $expiration_minutes Expiration time in minutes (default 60).
		 * @return bool True if email sent successfully, false otherwise.
		 */
		public function trigger( $email, $otp_code, $challenge_id, $expiration_minutes = 60 ) {
			$this->setup_locale();

			// Set email recipient and data.
			$this->recipient          = $email;
			$this->user_email         = $email;
			$this->otp_code           = $otp_code;
			$this->challenge_id       = $challenge_id;
			$this->expiration_minutes = $expiration_minutes;

			// Log email attempt.
			$logger = wc_get_logger();
			$logger->info(
				sprintf(
					'Attempting to send OTP email | Challenge: %s | Email: %s',
					$challenge_id,
					$email
				),
				array( 'source' => 'woo-fraud-protection-otp' )
			);

			$result = false;
			if ( $this->is_enabled() && $this->get_recipient() ) {
				try {
					$result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

					if ( $result ) {
						$logger->info(
							sprintf(
								'OTP email sent successfully | Challenge: %s | Email: %s',
								$challenge_id,
								$email
							),
							array( 'source' => 'woo-fraud-protection-otp' )
						);
					} else {
						$logger->error(
							sprintf(
								'Failed to send OTP email | Challenge: %s | Email: %s',
								$challenge_id,
								$email
							),
							array( 'source' => 'woo-fraud-protection-otp' )
						);
					}
				} catch ( Exception $e ) {
					// Log the exception but don't throw it - fail gracefully.
					$logger->error(
						sprintf(
							'Exception while sending OTP email | Challenge: %s | Email: %s | Error: %s',
							$challenge_id,
							$email,
							$e->getMessage()
						),
						array( 'source' => 'woo-fraud-protection-otp' )
					);
					$result = false;
				}
			}

			$this->restore_locale();

			return $result;
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'email_heading'       => $this->get_heading(),
					'additional_content'  => $this->get_additional_content(),
					'otp_code'            => $this->otp_code,
					'expiration_minutes'  => $this->expiration_minutes,
					'challenge_id'        => $this->challenge_id,
					'user_email'          => $this->user_email,
					'blogname'            => $this->get_blogname(),
					'sent_to_admin'       => false,
					'plain_text'          => false,
					'email'               => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'email_heading'       => $this->get_heading(),
					'additional_content'  => $this->get_additional_content(),
					'otp_code'            => $this->otp_code,
					'expiration_minutes'  => $this->expiration_minutes,
					'challenge_id'        => $this->challenge_id,
					'user_email'          => $this->user_email,
					'blogname'            => $this->get_blogname(),
					'sent_to_admin'       => false,
					'plain_text'          => true,
					'email'               => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 10.4.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'If you did not request this code, please ignore this email.', 'woocommerce' );
		}
	}
}

return new WC_Email_Fraud_Protection_Otp();

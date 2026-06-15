<?php
/**
 * Class WC_Email_Customer_Verify_Email file.
 *
 * @package WooCommerce\Emails
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email_Customer_Verify_Email', false ) ) :

	/**
	 * Customer Verify Email.
	 *
	 * An email sent to the customer with a link to confirm they own their account email address.
	 *
	 * @since 11.0.0
	 */
	class WC_Email_Customer_Verify_Email extends WC_Email {

		/**
		 * Verification link.
		 *
		 * @var string
		 */
		public $verify_url;

		/**
		 * User display name.
		 *
		 * @var string
		 */
		public $user_display_name;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_verify_email';
			$this->customer_email = true;
			$this->title          = __( 'Verify email address', 'woocommerce' );
			$this->description    = __( 'Sent to customers with a link to confirm they own their account email address.', 'woocommerce' );
			$this->template_html  = 'emails/customer-verify-email.php';
			$this->template_plain = 'emails/plain/customer-verify-email.php';
			$this->email_group    = 'accounts';

			// Trigger.
			add_action( 'woocommerce_customer_verify_email_notification', array( $this, 'trigger' ), 10, 2 );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @since 11.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Verify your email address for {site_title}', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since 11.0.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Confirm your email address', 'woocommerce' );
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int    $user_id    The user ID to send the email to.
		 * @param string $verify_url The verification link.
		 */
		public function trigger( $user_id, $verify_url = '' ): void {
			$this->setup_locale();

			if ( $user_id && $verify_url ) {
				$this->object            = new WP_User( $user_id );
				$this->verify_url        = $verify_url;
				$this->recipient         = stripslashes( $this->object->user_email );
				$customer                = new WC_Customer( $user_id );
				$first_name              = ! empty( $customer->get_billing_first_name() ) ? $customer->get_billing_first_name() : $this->object->first_name;
				$this->user_display_name = ! empty( $first_name ) ? $first_name : $this->object->user_login;
			}

			$this->send_notification();

			$this->restore_locale();
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
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'user_display_name'  => $this->user_display_name,
					'verify_url'         => $this->verify_url,
					'blogname'           => $this->get_blogname(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
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
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'user_display_name'  => $this->user_display_name,
					'verify_url'         => $this->verify_url,
					'blogname'           => $this->get_blogname(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 11.0.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for reading.', 'woocommerce' );
		}
	}

endif;

return new WC_Email_Customer_Verify_Email();

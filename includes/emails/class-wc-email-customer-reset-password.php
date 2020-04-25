<?php
/**
 * Class WC_Email_Customer_Reset_Password file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_Reset_Password', false ) ) :

	/**
	 * Customer Reset Password.
	 *
	 * An email sent to the customer when they reset their password.
	 *
	 * @class       WC_Email_Customer_Reset_Password
	 * @version     3.5.0
	 * @package     WooCommerce/Classes/Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_Reset_Password extends WC_Email {
		/**
		 * User object.
		 *
		 * @var \WP_User
		 */
		public $object;

		/**
		 * User ID.
		 *
		 * @var integer
		 */
		public $user_id;

		/**
		 * User login name.
		 *
		 * @var string
		 */
		public $user_login;

		/**
		 * User email.
		 *
		 * @var string
		 */
		public $user_email;

		/**
		 * Reset key.
		 *
		 * @var string
		 */
		public $reset_key;

		/**
		 * Initialize email id.
		 */
		protected function init_id() {
			$this->id = 'customer_reset_password';
		}

		/**
		 * Initialize title.
		 */
		protected function init_title() {
			$this->title = __( 'Reset password', 'woocommerce' );
		}

		/**
		 * Initialize description.
		 */
		protected function init_description() {
			$this->description = __( 'Customer "reset password" emails are sent when customers reset their passwords.', 'woocommerce' );
		}

		/**
		 * Initialize template html.
		 */
		protected function init_template_html() {
			$this->template_html = 'emails/customer-reset-password.php';
		}

		/**
		 * Initialize template plain.
		 */
		protected function init_template_plain() {
			$this->template_plain = 'emails/plain/customer-reset-password.php';
		}

		/**
		 * True when the email notification is sent to customers.
		 *
		 * @var bool
		 */
		protected function init_customer_email() {
			$this->customer_email = true;
		}

		/**
		 * Instance specific hooks
		 */
		protected function hooks() {
			add_action( 'woocommerce_reset_password_notification', array( $this, 'trigger' ), 10, 2 );
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Password Reset Request for {site_title}', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Password Reset Request', 'woocommerce' );
		}

		/**
		 * Trigger.
		 *
		 * @param string $user_login User login.
		 * @param string $reset_key Password reset key.
		 */
		public function trigger( $user_login = '', $reset_key = '' ) {
			$this->setup_locale();

			if ( $user_login && $reset_key ) {
				$this->object     = get_user_by( 'login', $user_login );
				$this->user_id    = $this->object->ID;
				$this->user_login = $user_login;
				$this->reset_key  = $reset_key;
				$this->user_email = stripslashes( $this->object->user_email );
				$this->recipient  = $this->user_email;
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get arguments for get_content_html method.
		 *
		 * @return array
		 */
		public function get_content_html_args() {
			return array(
				'email_heading'      => $this->get_heading(),
				'user_id'            => $this->user_id,
				'user_login'         => $this->user_login,
				'reset_key'          => $this->reset_key,
				'blogname'           => $this->get_blogname(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			);
		}

		/**
		 * Get arguments for get_content_plain method.
		 *
		 * @return array
		 */
		public function get_content_plain_args() {
			return array(
				'email_heading'      => $this->get_heading(),
				'user_id'            => $this->user_id,
				'user_login'         => $this->user_login,
				'reset_key'          => $this->reset_key,
				'blogname'           => $this->get_blogname(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for reading.', 'woocommerce' );
		}
	}

endif;

return new WC_Email_Customer_Reset_Password();

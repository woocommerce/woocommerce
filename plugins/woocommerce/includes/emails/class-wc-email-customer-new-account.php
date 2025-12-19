<?php
/**
 * Class WC_Email_Customer_New_Account file.
 *
 * @package WooCommerce\Emails
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Customer_New_Account', false ) ) {

	/**
	 * Customer New Account.
	 *
	 * An email sent to the customer when they create an account.
	 *
	 * @class       WC_Email_Customer_New_Account
	 * @version     3.5.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Customer_New_Account extends WC_Email {

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
		 * User password.
		 *
		 * @var string
		 */
		public $user_pass;

		/**
		 * Is the password generated?
		 *
		 * @var bool
		 */
		public $password_generated;

		/**
		 * Magic link to set initial password.
		 *
		 * @var string
		 */
		public $set_password_url;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'customer_new_account';
			$this->customer_email = true;
			$this->title          = __( 'New account', 'woocommerce' );
			$this->email_group    = 'accounts';
			$this->description    = __( 'Send an email to customers notifying them that they have created an account', 'woocommerce' );
			$this->template_html  = 'emails/customer-new-account.php';
			$this->template_plain = 'emails/plain/customer-new-account.php';
			parent::__construct();

			// Must be after parent's constructor which sets `block_email_editor_enabled` property.
			if ( $this->block_email_editor_enabled ) {
				$this->title       = __( 'Account created', 'woocommerce' );
				$this->description = __( 'Notifies customers when their account has been created.', 'woocommerce' );
			}
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Your {site_title} account has been created!', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Welcome to {site_title}', 'woocommerce' );
		}

		/**
		 * Trigger.
		 *
		 * @param int    $user_id User ID.
		 * @param string $user_pass User password.
		 * @param bool   $password_generated Whether the password was generated automatically or not.
		 */
		public function trigger( $user_id, $user_pass = '', $password_generated = false ) {
			$this->setup_locale();

			if ( $user_id ) {
				$this->object             = new WP_User( $user_id );
				$this->set_password_url   = $this->generate_set_password_url();
				$this->user_login         = stripslashes( $this->object->user_login );
				$this->user_email         = stripslashes( $this->object->user_email );
				$this->recipient          = $this->user_email;
				$this->user_pass          = $user_pass;
				$this->password_generated = $password_generated;
			}

			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

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
					'user_login'         => $this->user_login,
					'blogname'           => $this->get_blogname(),
					'set_password_url'   => $this->set_password_url,
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
					'password_generated' => $this->password_generated,
					'user_pass'          => $this->user_pass, // Password is no longer used in the template, but we're keeping it here for backwards compatibility with custom templates.
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
					'user_login'         => $this->user_login,
					'blogname'           => $this->get_blogname(),
					'set_password_url'   => $this->set_password_url,
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
					'password_generated' => $this->password_generated,
					'user_pass'          => $this->user_pass, // Password is no longer used in the template, but we're keeping it here for backwards compatibility with custom templates.
				)
			);
		}

		/**
		 * Get block editor email template content.
		 *
		 * @return string
		 */
		public function get_block_editor_email_template_content() {
			return wc_get_template_html(
				$this->template_block_content,
				array(
					'user_login'         => $this->user_login,
					'set_password_url'   => $this->set_password_url,
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
					'password_generated' => $this->password_generated,
					'user_pass'          => $this->user_pass, // Password is no longer used in the template, but we're keeping it here for backwards compatibility with custom templates.
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'We look forward to seeing you soon.', 'woocommerce' );
		}

		/**
		 * Generate set password URL link for a new user.
		 *
		 * @since 6.0.0
		 * @return string
		 */
		protected function generate_set_password_url() {
			// Generate a magic link so user can set initial password.
			$key = get_password_reset_key( $this->object );

			if ( is_wp_error( $key ) ) {
				// Something went wrong while getting the key for new password URL, send customer to the generic password reset.
				return wc_get_account_endpoint_url( 'lost-password' );
			}

			return sprintf( '%s?action=newaccount&key=%s&login=%s', wc_get_account_endpoint_url( 'lost-password' ), $key, rawurlencode( $this->object->user_login ) );
		}
	}
}

return new WC_Email_Customer_New_Account();

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Email_Customer_New_Account' ) ) :

/**
 * Customer New Account.
 *
 * An email sent to the customer when they create an account.
 *
 * @class       WC_Email_Customer_New_Account
 * @version     2.3.0
 * @package     WooCommerce/Classes/Emails
 * @author      WooThemes
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
	 * Constructor.
	 */
	public function __construct() {

		$this->id             = 'customer_new_account';
		$this->customer_email = true;
		$this->title          = __( 'New account', 'woocommerce' );
		$this->description    = __( 'Customer "new account" emails are sent to the customer when a customer signs up via checkout or account pages.', 'woocommerce' );

		$this->template_html  = 'emails/customer-new-account.php';
		$this->template_plain = 'emails/plain/customer-new-account.php';

		$this->subject        = __( 'Your account on {site_title}', 'woocommerce');
		$this->heading        = __( 'Welcome to {site_title}', 'woocommerce');

		// Call parent constructor
		parent::__construct();
	}

	/**
	 * Trigger.
	 *
	 * @param int $user_id
	 * @param string $user_pass
	 * @param bool $password_generated
	 */
	public function trigger( $user_id, $user_pass = '', $password_generated = false ) {

		if ( $user_id ) {
			$this->object             = new WP_User( $user_id );

			$this->user_pass          = $user_pass;
			$this->user_login         = stripslashes( $this->object->user_login );
			$this->user_email         = stripslashes( $this->object->user_email );
			$this->recipient          = $this->user_email;
			$this->password_generated = $password_generated;
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'email_heading'      => $this->get_heading(),
			'user_login'         => $this->user_login,
			'user_pass'          => $this->user_pass,
			'blogname'           => $this->get_blogname(),
			'password_generated' => $this->password_generated,
			'sent_to_admin'      => false,
			'plain_text'         => false,
			'email'				 => $this
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'email_heading'      => $this->get_heading(),
			'user_login'         => $this->user_login,
			'user_pass'          => $this->user_pass,
			'blogname'           => $this->get_blogname(),
			'password_generated' => $this->password_generated,
			'sent_to_admin'      => false,
			'plain_text'         => true,
			'email'			     => $this
		) );
	}
}

endif;

return new WC_Email_Customer_New_Account();

<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Customer New Account
 *
 * An email sent to the customer when they create an account.
 *
 * @class 		WC_Email_Customer_New_Account
 * @version		2.0.0
 * @package		WooCommerce/Classes/Emails
 * @author 		WooThemes
 * @extends 	WC_Email
 */
class WC_Email_Customer_New_Account extends WC_Email {

	var $user_login;
	var $user_email;
	var $user_pass;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		$this->id 				= 'customer_new_account';
		$this->title 			= __( 'New account', 'woocommerce' );
		$this->description		= __( 'Customer new account emails are sent when a customer signs up via the checkout or My Account page.', 'woocommerce' );

		$this->template_html 	= 'emails/customer-new-account.php';
		$this->template_plain 	= 'emails/plain/customer-new-account.php';

		$this->subject 			= __( 'Your account on {blogname}', 'woocommerce');
		$this->heading      	= __( 'Welcome to {blogname}', 'woocommerce');

		// Call parent constuctor
		parent::__construct();
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $user_id, $user_pass = '' ) {
		global $woocommerce;

		if ( $user_id ) {
			$this->object 		= new WP_User( $user_id );

			$this->user_pass	= $user_pass;
			$this->user_login 	= stripslashes( $this->object->user_login );
			$this->user_email 	= stripslashes( $this->object->user_email );
			$this->recipient	= $this->user_email;
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() )
			return;

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		woocommerce_get_template( $this->template_html, array(
			'email_heading' => $this->get_heading(),
			'user_login' 	=> $this->user_login,
			'user_pass'		=> $this->user_pass,
			'blogname'		=> $this->get_blogname()
		) );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		woocommerce_get_template( $this->template_plain, array(
			'email_heading' => $this->get_heading(),
			'user_login' 	=> $this->user_login,
			'user_pass'		=> $this->user_pass,
			'blogname'		=> $this->get_blogname()
		) );
		return ob_get_clean();
	}
}
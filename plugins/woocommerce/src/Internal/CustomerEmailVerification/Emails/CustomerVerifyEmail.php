<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\CustomerEmailVerification\Emails;

use WC_Customer;
use WC_Email;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Customer email verification email.
 *
 * Sent to a customer with a link to confirm they own their account email address.
 *
 * @since 11.0.0
 */
class CustomerVerifyEmail extends WC_Email {

	/**
	 * One-time verification URL included in the email.
	 *
	 * @var string
	 */
	public $verify_url;

	/**
	 * Display name used to greet the customer.
	 *
	 * @var string
	 */
	public $user_display_name;

	/**
	 * Customer email address. Populated by the email preview ({@see EmailPreview}) for the editor.
	 *
	 * @var string
	 */
	public $user_email;

	/**
	 * Customer login. Populated by the email preview ({@see EmailPreview}) for the editor.
	 *
	 * @var string
	 */
	public $user_login;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'customer_verify_email';
		$this->customer_email = true;
		$this->title          = __( 'Confirm email address', 'woocommerce' );
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
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Confirm your email address for {site_title}', 'woocommerce' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Confirm your email address', 'woocommerce' );
	}

	/**
	 * Default content to show below the main email content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Thanks for reading.', 'woocommerce' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int    $user_id    The user ID to send the email to.
	 * @param string $verify_url The one-time verification URL.
	 * @return void
	 */
	public function trigger( $user_id, $verify_url = '' ) {
		$this->setup_locale();

		if ( $user_id && $verify_url ) {
			$this->object            = new WP_User( $user_id );
			$this->verify_url        = $verify_url;
			$this->recipient         = wp_unslash( $this->object->user_email );
			$customer                = new WC_Customer( $user_id );
			$first_name              = ! empty( $customer->get_billing_first_name() ) ? $customer->get_billing_first_name() : $this->object->first_name;
			$this->user_display_name = ! empty( $first_name ) ? $first_name : $this->object->user_login;

			$this->send_notification();
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
				'user_display_name'  => $this->user_display_name,
				'user_email'         => $this->object instanceof WP_User ? $this->object->user_email : '',
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
				'user_email'         => $this->object instanceof WP_User ? $this->object->user_email : '',
				'verify_url'         => $this->verify_url,
				'blogname'           => $this->get_blogname(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			)
		);
	}

	/**
	 * Get the content rendered into the block email editor's WooCommerce content placeholder.
	 *
	 * @return string
	 */
	public function get_block_editor_email_template_content() {
		return wc_get_template_html(
			$this->template_block_content,
			array(
				'user_display_name' => $this->user_display_name,
				'user_email'        => $this->object instanceof WP_User ? $this->object->user_email : '',
				'verify_url'        => $this->verify_url,
				'sent_to_admin'     => false,
				'plain_text'        => false,
				'email'             => $this,
			)
		);
	}
}

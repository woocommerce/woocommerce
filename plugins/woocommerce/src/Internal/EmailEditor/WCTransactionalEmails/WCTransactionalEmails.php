<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

/**
 * Class WCTransactionalEmails
 *
 * Handles the initialization and management of WooCommerce transactional emails.
 *
 * @package Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails
 */
class WCTransactionalEmails {

	/**
	 * Array of core transactional email types.
	 *
	 * @var array
	 */
	public static $core_transactional_emails = array(
		'cancelled_order',
		'customer_completed_order',
		'customer_failed_order',
		'customer_invoice',
		'customer_new_account',
		'customer_note',
		'customer_on_hold_order',
		'customer_processing_order',
		'customer_refunded_order',
		'customer_reset_password',
		'failed_order',
		'new_order',
	);

	/**
	 * Email template generator instance.
	 *
	 * @var WCTransactionalEmailPostsGenerator
	 */
	private $email_template_generator;

	/**
	 * Constructor.
	 *
	 * Initializes the WCTransactionalEmailPostsGenerator by setting up the template generator.
	 */
	public function __construct() {
		$this->email_template_generator = new WCTransactionalEmailPostsGenerator();
	}

	/**
	 * Initialize the class.
	 *
	 * @internal
	 */
	final public function init() {
		$this->init_email_templates();
	}

	/**
	 * Initialize email templates based on the current page context.
	 */
	public function init_email_templates() {
		if ( ! isset( $_GET['page'], $_GET['tab'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$is_wc_email_settings_page = 'wc-settings' === $_GET['page'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& 'email' === $_GET['tab']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $is_wc_email_settings_page ) {
			$this->email_template_generator->init();
		}
	}
}

<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\EmailEditor\WCTransactionalEmails;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

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
		'customer_cancelled_order',
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
		add_action( 'current_screen', array( $this, 'init_email_templates' ), 50 );
	}

	/**
	 * Get the Core WooCommerce transactional emails for the block editor.
	 *
	 * @return array
	 */
	public static function get_transactional_emails() {
		$emails = self::$core_transactional_emails;

		if ( FeaturesUtil::feature_is_enabled( 'point_of_sale' ) ) {
			$emails[] = 'customer_pos_completed_order';
			$emails[] = 'customer_pos_refunded_order';
		}

		/**
		 * Filter the transactional emails for the block editor.
		 *
		 * @param array $transactional_emails The transactional emails.
		 * @return array
		 * @since 9.9.0
		 */
		return apply_filters( 'woocommerce_transactional_emails_for_block_editor', $emails );
	}

	/**
	 * Initialize email templates on WooCommerce admin pages.
	 */
	public function init_email_templates() {
		if ( ! function_exists( 'wc_get_screen_ids' ) ) {
			return;
		}

		$screen = get_current_screen();

		$wc_screen_ids = array_merge(
			wc_get_screen_ids(),
			array(
				'woocommerce_page_wc-admin',
				'edit-woo_email',
			)
		);

		if ( ! $screen || ! in_array( $screen->id, $wc_screen_ids, true ) ) {
			return;
		}

		// run only on WooCommerce admin pages.
		$this->email_template_generator->initialize();
	}
}

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
		'admin_payment_gateway_enabled',
		'cancelled_order',
		'customer_cancelled_order',
		'customer_completed_order',
		'customer_failed_order',
		'customer_invoice',
		'customer_new_account',
		'customer_note',
		'customer_on_hold_order',
		'customer_pos_completed_order',
		'customer_pos_refunded_order',
		'customer_processing_order',
		'customer_refunded_order',
		'customer_partially_refunded_order',
		'customer_reset_password',
		'customer_review_request',
		'customer_verify_email',
		'failed_order',
		'new_order',
	);

	/**
	 * Initialize the class.
	 *
	 * Email posts are no longer generated on initialization. They are created
	 * lazily (as drafts) when a user opens the email editor for a specific
	 * email type, following the WordPress Site Editor pattern where file
	 * templates are the source of truth until the user edits and saves.
	 *
	 * @internal
	 */
	final public function init() {
	}

	/**
	 * Get the core transactional emails.
	 *
	 * @return array
	 */
	public static function get_core_transactional_emails() {
		$emails = self::$core_transactional_emails;

		if ( FeaturesUtil::feature_is_enabled( 'fulfillments' ) ) {
			$fulfillment_emails = array(
				'customer_fulfillment_created',
				'customer_fulfillment_updated',
				'customer_fulfillment_deleted',
			);
			$emails             = array_merge( $emails, $fulfillment_emails );
		}

		return $emails;
	}

	/**
	 * Get the Core WooCommerce transactional emails for the block editor.
	 *
	 * @return array
	 */
	public static function get_transactional_emails() {
		$emails = self::get_core_transactional_emails();

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
	 *
	 * @deprecated 11.1.0 Email posts are no longer generated on admin page loads; they are created lazily when the user opens the editor. No-op, will be removed in a future version.
	 * @return void
	 */
	public function init_email_templates() {
		wc_deprecated_function( __METHOD__, '11.1.0' );
	}
}

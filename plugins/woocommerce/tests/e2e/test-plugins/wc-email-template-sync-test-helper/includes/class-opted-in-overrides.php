<?php
/**
 * Opted-in and transactional-emails list filters for the WC Email Template Sync test helper plugin.
 *
 * @package WC_Email_Template_Sync_Test_Helper
 */

declare( strict_types=1 );

namespace WC_Email_Template_Sync_Test_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Filter wrappers that augment the sync registry's opted-in list and the block-editor
 * transactional-emails list with fixture-controlled values. Dormant when the driving
 * options are empty.
 */
class Opted_In_Overrides {

	public const OPTED_IN_OPTION      = 'wc_test_opted_in_emails_override';
	public const TRANSACTIONAL_OPTION = 'wc_test_transactional_emails_override';

	/**
	 * Register both filters.
	 */
	public function register(): void {
		add_filter(
			'woocommerce_email_template_sync_opted_in_emails',
			array( $this, 'merge_opted_in' ),
			100
		);
		add_filter(
			'woocommerce_transactional_emails_for_block_editor',
			array( $this, 'merge_transactional' ),
			100
		);
	}

	/**
	 * Non-destructively merge the test-controlled opted-in list into the production return value.
	 *
	 * @param mixed $opted_in The upstream filter value (expected: array keyed by email id).
	 * @return array The merged map.
	 */
	public function merge_opted_in( $opted_in ): array {
		$opted_in = is_array( $opted_in ) ? $opted_in : array();
		$override = get_option( self::OPTED_IN_OPTION, array() );
		if ( ! is_array( $override ) || empty( $override ) ) {
			return $opted_in;
		}
		return array_merge( $opted_in, $override );
	}

	/**
	 * Non-destructively merge the test-controlled "registered for block editor" list with
	 * the production return value, deduping by email id.
	 *
	 * @param mixed $emails The upstream filter value (expected: array of email ids).
	 * @return array The merged list with stable numeric keys.
	 */
	public function merge_transactional( $emails ): array {
		$emails   = is_array( $emails ) ? $emails : array();
		$override = get_option( self::TRANSACTIONAL_OPTION, array() );
		if ( ! is_array( $override ) || empty( $override ) ) {
			return $emails;
		}
		return array_values( array_unique( array_merge( $emails, $override ) ) );
	}
}

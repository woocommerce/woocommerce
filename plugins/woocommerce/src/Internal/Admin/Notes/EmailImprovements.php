<?php
/**
 * Adds a note when the email improvements feature is enabled for existing stores
 * or when the feature is not enabled to try the new templates.
 *
 * @since 9.9.0
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Notes;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Notes\Note;
use Automattic\WooCommerce\Admin\Notes\NoteTraits;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * EmailImprovements
 */
class EmailImprovements {
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-email-improvements';

	/**
	 * Get the note.
	 *
	 * @return Note|void
	 */
	public static function get_note() {
		$is_feature_enabled             = FeaturesUtil::feature_is_enabled( 'email_improvements' );
		$is_enabled_for_existing_stores = 'yes' === get_option( 'woocommerce_email_improvements_existing_store_enabled' );

		if ( $is_feature_enabled && $is_enabled_for_existing_stores ) {
			return self::get_email_improvements_enabled_note();
		}

		if ( ! $is_feature_enabled ) {
			return self::get_try_email_improvements_note();
		}
	}

	/**
	 * Get the note for when the email improvements feature is enabled for existing stores.
	 *
	 * @return Note
	 */
	private static function get_email_improvements_enabled_note() {
		$note = new Note();
		$note->set_title( __( 'Your emails have a new look!', 'woocommerce' ) );
		$note->set_content( __( 'We’re excited to introduce our refreshed email templates designed to enhance your customers shopping experience. Preview and customize your emails in Settings.', 'woocommerce' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'manage-emails',
			__( 'Manage emails', 'woocommerce' ),
			'?page=wc-settings&tab=email'
		);
		return $note;
	}

	/**
	 * Get the note for when the email improvements feature is disabled.
	 *
	 * @return Note
	 */
	private static function get_try_email_improvements_note() {
		$note = new Note();
		$note->set_title( __( 'Introducing new email templates for your store!', 'woocommerce' ) );
		$note->set_content( __( 'We’re excited to introduce our refreshed email templates designed to enhance your customers shopping experience. Preview and customize your emails in Settings.', 'woocommerce' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'try-new-templates',
			__( 'Try new templates', 'woocommerce' ),
			'?page=wc-settings&tab=email&try-new-templates'
		);
		return $note;
	}
}

<?php
/**
 * WooCommerce Admin (Dashboard) Giving feedback notes provider
 *
 * Adds notes to the merchant's inbox about giving feedback.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Giving_Feedback_Notes
 */
class WC_Admin_Notes_Giving_Feedback_Notes {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-store-notice-giving-feedback-2';

	/**
	 * Get the note.
	 */
	protected static function get_note() {
		// We need to show Admin Giving feeback notification after 8 days of install.
		$eight_days_in_seconds = 8 * DAY_IN_SECONDS;
		if ( ! self::wc_admin_active_for( $eight_days_in_seconds ) ) {
			return;
		}

		// Otherwise, create our new note.
		$note = new WC_Admin_Note();
		$note->set_title( __( 'Give feedback', 'woocommerce' ) );
		$note->set_content( __( 'Now that you’ve chosen us as a partner, our goal is to make sure we\'re providing the right tools to meet your needs. We\'re looking forward to having your feedback on the store setup experience so we can improve it in the future.', 'woocommerce' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'share-feedback',
			__( 'Share feedback', 'woocommerce' ),
			'https://automattic.survey.fm/new-onboarding-survey'
		);
		return $note;
	}
}

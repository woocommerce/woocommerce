<?php
/**
 * WooCommerce Admin Edit products on the move note.
 *
 * Adds a note to download the mobile app.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Edit_Products_On_The_Move
 */
class WC_Admin_Notes_Edit_Products_On_The_Move {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-edit-products-on-the-move';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		// Only add this note if this store is at least a year old.
		$year_in_seconds = 365 * DAY_IN_SECONDS;
		if ( ! self::wc_admin_active_for( $year_in_seconds ) ) {
			return;
		}

		// Check that the previous mobile app notes have not been actioned.
		if ( WC_Admin_Notes_Mobile_App::has_note_been_actioned() ) {
			return;
		}

		if ( WC_Admin_Notes_Real_Time_Order_Alerts::has_note_been_actioned() ) {
			return;
		}

		if ( WC_Admin_Notes_Performance_On_Mobile::has_note_been_actioned() ) {
			return;
		}

		$note = new WC_Admin_Note();

		$note->set_title( __( 'Edit products on the move', 'woocommerce' ) );
		$note->set_content( __( 'Edit and create new products from your mobile devices with the Woo app', 'woocommerce' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce' ),
			'https://woocommerce.com/mobile/?utm_source=inbox'
		);

		return $note;
	}
}

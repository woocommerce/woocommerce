<?php
/**
 * WooCommerce Admin Performance on mobile note.
 *
 * Adds a note to download the mobile app, performance on mobile.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Performance_On_Mobile
 */
class WC_Admin_Notes_Performance_On_Mobile {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-performance-on-mobile';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		// Only add this note if this store is at least 9 months old.
		$nine_months_in_seconds = MONTH_IN_SECONDS * 9;
		if ( ! self::wc_admin_active_for( $nine_months_in_seconds ) ) {
			return;
		}

		// Check that the previous mobile app notes have not been actioned.
		if ( WC_Admin_Notes_Mobile_App::has_note_been_actioned() ) {
			return;
		}

		if ( WC_Admin_Notes_Real_Time_Order_Alerts::has_note_been_actioned() ) {
			return;
		}

		$note = new WC_Admin_Note();

		$note->set_title( __( 'Track your store performance on mobile', 'woocommerce-admin' ) );
		$note->set_content( __( 'Monitor your sales and high performing products with the Woo app.', 'woocommerce-admin' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'learn-more',
			__( 'Learn more', 'woocommerce-admin' ),
			'https://woocommerce.com/mobile/?utm_source=inbox'
		);

		return $note;
	}
}

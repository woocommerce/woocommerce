<?php
/**
 * WooCommerce Admin Real Time Order Alerts Note.
 *
 * Adds a note to download the mobile app to monitor store activity.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Real_Time_Order_Alerts
 */
class WC_Admin_Notes_Real_Time_Order_Alerts {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-real-time-order-alerts';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		// Only add this note if the store is 3 months old.
		$ninety_days_in_seconds = 90 * DAY_IN_SECONDS;
		if ( ! self::wc_admin_active_for( $ninety_days_in_seconds ) ) {
			return;
		}

		// Check that the previous mobile app note was not actioned.
		$data_store = \WC_Data_Store::load( 'admin-note' );
		$note_ids   = $data_store->get_notes_with_name( WC_Admin_Notes_Mobile_App::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			$note = WC_Admin_Notes::get_note( $note_ids[0] );
			if ( WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED === $note->get_status() ) {
				return;
			}
		}

		$content = __( 'Get notifications about store activity, including new orders and product reviews directly on your mobile devices with the Woo app.', 'woocommerce' );

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Get real-time order alerts anywhere', 'woocommerce' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'learn-more', __( 'Learn more', 'woocommerce' ), 'https://woocommerce.com/mobile/?utm_source=inbox' );
		return $note;
	}
}

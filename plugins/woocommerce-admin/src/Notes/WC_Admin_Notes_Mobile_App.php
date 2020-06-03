<?php
/**
 * WooCommerce Admin Mobile App Note Provider.
 *
 * Adds a note to the merchant's inbox showing the benefits of the mobile app.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Mobile_App
 */
class WC_Admin_Notes_Mobile_App {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-mobile-app';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		// We want to show the mobile app note after day 2.
		$two_days_in_seconds = 2 * DAY_IN_SECONDS;
		if ( ! self::wc_admin_active_for( $two_days_in_seconds ) ) {
			return;
		}

		$content = __( 'Install the WooCommerce mobile app to manage orders, receive sales notifications, and view key metrics — wherever you are.', 'woocommerce-admin' );

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Install Woo mobile app', 'woocommerce-admin' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'learn-more', __( 'Learn more', 'woocommerce-admin' ), 'https://woocommerce.com/mobile/' );
		return $note;
	}
}

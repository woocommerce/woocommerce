<?php
/**
 * WooCommerce Admin Mobile App Note Provider.
 *
 * Adds a note to the merchant's inbox showing the benefits of the mobile app.
 *
 * @package WooCommerce Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Mobile_App
 */
class WC_Admin_Notes_Mobile_App {

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-mobile-app';

	/**
	 * Possibly add mobile app note.
	 */
	public static function possibly_add_mobile_app_note() {

		$wc_admin_installed = get_option( 'wc_admin_install_timestamp', false );
		if ( false === $wc_admin_installed ) {
			$wc_admin_installed = time();
			update_option( 'wc_admin_install_timestamp', $wc_admin_installed );
		}

		$current_time        = time();
		$two_days_in_seconds = 172800;

		// We want to show the mobile app note after day 2.
		if ( $current_time - $wc_admin_installed < $two_days_in_seconds ) {
			return;
		}

		$data_store = WC_Data_Store::load( 'admin-note' );

		// We already have this note? Then exit, we're done.
		$note_ids = $data_store->get_notes_with_name( self::NOTE_NAME );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		$content = __( 'Install the WooCommerce mobile app to manage orders, receive sales notifications, and view key metrics — wherever you are.', 'woocommerce-admin' );

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Install Woo mobile app', 'woocommerce-admin' ) );
		$note->set_content( $content );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'phone' );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action( 'learn-more', __( 'Learn more', 'woocommerce-admin' ), 'https://woocommerce.com/mobile/' );
		$note->save();
	}
}

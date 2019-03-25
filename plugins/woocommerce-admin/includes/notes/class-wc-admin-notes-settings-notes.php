<?php
/**
 * WooCommerce Admin (Dashboard) Settings Moved Note Provider.
 *
 * Adds notes to the merchant's inbox concerning settings that have moved.
 *
 * @package WooCommerce Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Settings_Notes
 */
class WC_Admin_Notes_Settings_Notes {
	/**
	 * Add notes for each setting that has moved.
	 */
	public static function add_notes_for_settings_that_have_moved() {
		self::possibly_add_notice_setting_moved_note();
	}

	/**
	 * Possibly add a notice setting moved note.
	 */
	protected static function possibly_add_notice_setting_moved_note() {
		$name = 'wc-admin-store-notice-setting-moved';

		$data_store = WC_Data_Store::load( 'admin-note' );

		// We already have this note? Then exit, we're done.
		$note_ids = $data_store->get_notes_with_name( $name );
		if ( ! empty( $note_ids ) ) {
			return;
		}

		// Otherwise, create our new note.
		$note = new WC_Admin_Note();
		$note->set_title( __( 'Looking for the Store Notice setting?', 'woocommerce-admin' ) );
		$note->set_content( __( 'It can now be found in the Customizer.', 'woocommerce-admin' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'info' );
		$note->set_name( $name );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'open-customizer',
			__( 'Open Customizer', 'woocommerce-admin' ),
			'customize.php'
		);
		$note->save();
	}
}

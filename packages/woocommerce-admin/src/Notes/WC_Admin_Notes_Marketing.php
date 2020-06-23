<?php
/**
 * WooCommerce Admin Marketing Note Provider.
 *
 * Adds notes to the merchant's inbox concerning the marketing dashboard.
 *
 * @package WooCommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Marketing
 */
class WC_Admin_Notes_Marketing {

	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME_INTRO = 'wc-admin-marketing-intro';

	/**
	 * Maybe add a note introducing the marketing hub.
	 */
	public static function possibly_add_note_intro() {

		$data_store = \WC_Data_Store::load( 'admin-note' );

		// See if we've already created this kind of note so we don't do it again.
		$note_ids = $data_store->get_notes_with_name( self::NOTE_NAME_INTRO );

		if ( ! empty( $note_ids ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Connect with your audience', 'woocommerce' ) );
		$note->set_content( __( 'Grow your customer base and increase your sales with marketing tools built for WooCommerce.', 'woocommerce' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_icon( 'speaker' );
		$note->set_name( self::NOTE_NAME_INTRO );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'open-marketing-hub',
			__( 'Open marketing hub', 'woocommerce' ),
			admin_url( 'admin.php?page=wc-admin&path=/marketing' ),
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED
		);

		$note->save();
	}
}

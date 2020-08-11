<?php
/**
 * WooCommerce Admin Marketing Note Provider.
 *
 * Adds notes to the merchant's inbox concerning the marketing dashboard.
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
	const NOTE_NAME = 'wc-admin-marketing-intro';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		$note = new WC_Admin_Note();
		$note->set_title( __( 'Connect with your audience', 'woocommerce-admin' ) );
		$note->set_content( __( 'Grow your customer base and increase your sales with marketing tools built for WooCommerce.', 'woocommerce-admin' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'open-marketing-hub',
			__( 'Open marketing hub', 'woocommerce-admin' ),
			admin_url( 'admin.php?page=wc-admin&path=/marketing' ),
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED
		);
		return $note;
	}
}

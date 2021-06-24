<?php
/**
 * WooCommerce Admin Marketing Note Provider.
 *
 * Adds notes to the merchant's inbox concerning the marketing dashboard.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Marketing
 */
class Marketing {

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
	 *
	 * @return Note
	 */
	public static function get_note() {
		// Don't show the note unless the store has been active at least 5 days.
		if ( ! self::is_wc_admin_active_in_date_range( 'week-1-4', 5 * DAY_IN_SECONDS ) ) {
			return;
		}

		$note = new Note();
		$note->set_title( __( 'Connect with your audience', 'woocommerce-admin' ) );
		$note->set_content( __( 'Grow your customer base and increase your sales with marketing tools built for WooCommerce.', 'woocommerce-admin' ) );
		$note->set_type( Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'open-marketing-hub',
			__( 'Open marketing hub', 'woocommerce-admin' ),
			admin_url( 'admin.php?page=wc-admin&path=/marketing' ),
			Note::E_WC_ADMIN_NOTE_ACTIONED
		);
		return $note;
	}
}

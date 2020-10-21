<?php
/**
 * WooCommerce Admin: Insight - First sale
 *
 * Adds a note to give insight about the first sale.
 *
 * @package WooCommerce\Admin
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Insight_First_Sale.
 */
class WC_Admin_Notes_Insight_First_Sale {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-insight-first-sale';

	/**
	 * Get the note.
	 */
	public static function get_note() {
		// We want to show the note after eight days.
		if ( ! self::wc_admin_active_for( 8 * DAY_IN_SECONDS ) ) {
			return;
		}

		$note = new WC_Admin_Note();
		$note->set_title( __( 'Did you know?', 'woocommerce' ) );
		$note->set_content( __( 'A WooCommerce powered store needs on average 31 days to get the first sale. You\'re on the right track! Do you find this type of insight useful?', 'woocommerce' ) );
		$note->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_SURVEY );
		$note->set_name( self::NOTE_NAME );
		$note->set_content_data( (object) array() );
		$note->set_source( 'woocommerce-admin' );

		// Note that there is no corresponding function called in response to
		// this. Apart from setting the note to actioned a tracks event is
		// sent in NoteActions.
		$note->add_action(
			'affirm-insight-first-sale',
			__( 'Yes', 'woocommerce' ),
			false,
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED,
			false,
			__( 'Thanks for your feedback', 'woocommerce' )
		);
		$note->add_action(
			'deny-insight-first-sale',
			__( 'No', 'woocommerce' ),
			false,
			WC_Admin_Note::E_WC_ADMIN_NOTE_ACTIONED,
			false,
			__( 'Thanks for your feedback', 'woocommerce' )
		);

		return $note;
	}
}

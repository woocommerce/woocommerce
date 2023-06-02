<?php
/**
 * WooCommerce Admin (Dashboard) choosing a theme note
 *
 * Adds notes to the merchant's inbox about choosing a theme.
 */

namespace Automattic\WooCommerce\Admin\Notes;

defined( 'ABSPATH' ) || exit;

/**
 * Giving_Feedback_Notes
 */
class ChoosingTheme {
	/**
	 * Note traits.
	 */
	use NoteTraits;

	/**
	 * Name of the note for use in the database.
	 */
	const NOTE_NAME = 'wc-admin-choosing-a-theme';

	/**
	 * Get the note.
	 *
	 * @return Note
	 */
	protected static function get_note() {
		// We need to show choosing a theme notification after 1 day of install.
		if ( ! self::wc_admin_active_for( DAY_IN_SECONDS ) ) {
			return;
		}

		// Otherwise, create our new note.
		$note = new Note();
		$note->set_title( __( 'Choosing a theme?', 'woocommerce' ) );
		$note->set_content( __( 'Check out the themes that are compatible with WooCommerce and choose one aligned with your brand and business needs.', 'woocommerce' ) );
		$note->set_content_data( (object) array() );
		$note->set_type( Note::E_WC_ADMIN_NOTE_MARKETING );
		$note->set_name( self::NOTE_NAME );
		$note->set_source( 'woocommerce-admin' );
		$note->add_action(
			'visit-the-theme-marketplace',
			__( 'Visit the theme marketplace', 'woocommerce' ),
			'https://woocommerce.com/product-category/themes/?utm_source=inbox'
		);
		return $note;
	}
}
